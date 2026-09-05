<?php

namespace App\Models;
use App\Core\Database;
use App\Support\Uuid;
use PDO;

class Role
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Get all roles with their permissions and user count
     */
    public function getAllRoles($includeDeleted = false)
    {
        $query = "SELECT 
                    r.id,
                    r.uuid,
                    r.name,
                    r.slug,
                    r.description,
                    r.status_id,
                    r.created_at,
                    r.created_by,
                    r.updated_at,
                    r.updated_by,
                    COUNT(DISTINCT u.id) as user_count,
                    COUNT(DISTINCT rp.permission_id) as permission_count
                FROM tbl_roles r
                LEFT JOIN tbl_user_roles ur
                    ON ur.role_id = r.id
                LEFT JOIN tbl_users u
                    ON u.id = ur.user_id AND u.deleted_at IS NULL
                LEFT JOIN tbl_role_permissions rp 
                    ON r.id = rp.role_id";

        if (!$includeDeleted) {
            $query .= " WHERE r.deleted_at IS NULL";
        }

        $query .= " GROUP BY r.id ORDER BY r.created_at DESC";

        $result = $this->pdo->query($query);
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get a single role by ID with permissions
     */
    public function getRoleById($id)
    {
        $query = "SELECT 
                    r.id,   
                    r.uuid,
                    r.name,
                    r.slug,
                    r.description,
                    r.status_id,
                    r.created_at,
                    r.created_by,
                    r.updated_at,
                    r.updated_by,
                    COUNT(DISTINCT u.id) as user_count
                FROM tbl_roles r
                LEFT JOIN tbl_user_roles ur ON ur.role_id = r.id
                LEFT JOIN tbl_users u ON u.id = ur.user_id AND u.deleted_at IS NULL
                WHERE r.id = ? AND r.deleted_at IS NULL
                GROUP BY r.id";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        $role = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$role) return null;

        // ✅ ADD THIS (IMPORTANT)
        $permissions = $this->getRolePermissions($id);

        // Convert to slug array (VERY IMPORTANT for frontend)
        $role['permissions'] = array_map(function ($p) {
            return $p['permission_slug'];
        }, $permissions);

        return $role;
    }

    /**
     * Get a role by UUID
     */
    public function getRoleByUuid($uuid)
    {
        $query = "SELECT 
                    r.id,
                    r.uuid,
                    r.name,
                    r.slug,
                    r.description,
                    r.status_id,
                    r.created_at,
                    r.created_by,
                    r.updated_at,
                    r.updated_by,
                    COUNT(DISTINCT u.id) as user_count
                  FROM tbl_roles r
                  LEFT JOIN tbl_user_roles ur ON ur.role_id = r.id
                  LEFT JOIN tbl_users u ON u.id = ur.user_id AND u.deleted_at IS NULL
                  WHERE r.uuid = ? AND r.deleted_at IS NULL
                  GROUP BY r.id";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$uuid]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($roleId)
    {
        $query = "SELECT 
                    p.id,
                    p.uuid,
                    p.module,
                    p.action,
                    p.description,
                    CONCAT(p.module, '.', p.action) as permission_slug
                  FROM tbl_permissions p
                  INNER JOIN tbl_role_permissions rp ON p.id = rp.permission_id
                  WHERE rp.role_id = ? AND p.status_id = 1 AND p.deleted_at IS NULL
                  ORDER BY p.module, p.action";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create a new role
     */
    public function createRole($data)
    {
        $uuid = Uuid::v4();
        $now = date('Y-m-d H:i:s'); 
        $createdBy = $_SESSION['user_id'] ?? null;

        $query = "INSERT INTO tbl_roles (uuid, name, slug, description, status_id, created_at, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            $uuid,
            $data['name'] ?? '',
            $data['slug'] ?? '',
            $data['description'] ?? null,
            $data['status_id'] ?? 2, // 2 = pending by default
            $now,
            $createdBy
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update a role
     */
    public function updateRole($id, $data)
    {
        $now = date('Y-m-d H:i:s');
        $updatedBy = $_SESSION['user_id'] ?? null;

        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $values[] = $data['description'];
        }
        if (isset($data['status_id'])) {
            $fields[] = "status_id = ?";
            $values[] = $data['status_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = ?";
        $values[] = $now;
        $fields[] = "updated_by = ?";
        $values[] = $updatedBy;
        $values[] = $id;

        $query = "UPDATE tbl_roles SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($values);
    }

    /**
     * Soft delete a role
     */
    public function deleteRole($id)
    {
        $now = date('Y-m-d H:i:s');
        $deletedBy = $_SESSION['user_id'] ?? null;

        $query = "UPDATE tbl_roles 
                  SET deleted_at = ?, deleted_by = ? 
                  WHERE id = ? AND deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$now, $deletedBy, $id]);
    }

    /**
     * Find a soft-deleted role matching this slug (if any)
     */
    public function findDeletedRoleBySlug($slug)
    {
        $query = "SELECT id, name, slug FROM tbl_roles WHERE slug = ? AND deleted_at IS NOT NULL LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if slug exists (including soft-deleted rows, since DB has a raw UNIQUE constraint)
     */
    public function slugExists($slug, $excludeId = null)
    {
        $query = "SELECT COUNT(*) as count FROM tbl_roles WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC)['count'] > 0;
    }

    /**
     * Restore a soft-deleted role and reset it to pending with new data
     */
    public function restoreRole($id, $data)
    {
        $now = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? null;

        $query = "UPDATE tbl_roles 
                  SET name = ?, description = ?, status_id = ?, 
                      deleted_at = NULL, deleted_by = NULL,
                      updated_at = ?, updated_by = ?
                  WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['status_id'] ?? 2,
            $now,
            $userId,
            $id
        ]);

        return $id;
    }

    /**
     * Check if role exists
     */
    public function roleExists($name, $excludeId = null)
    {
        $query = "SELECT COUNT(*) as count FROM tbl_roles WHERE name = ? AND deleted_at IS NULL";
        $params = [$name];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get role by name
     */
    public function getRoleByName($name)
    {
        $query = "SELECT * FROM tbl_roles WHERE name = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$name]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all permissions (for reference)
     */
    public function getAllPermissions()
    {
        $query = "SELECT 
                    id,
                    uuid,
                    module,
                    action,
                    description,
                    CONCAT(module, '.', action) as permission_slug
                  FROM tbl_permissions
                  WHERE status_id = 1 AND deleted_at IS NULL
                  ORDER BY module, action";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get permission by slug (e.g., 'users.create')
     */
    public function getPermissionBySlug($slug)
    {
        $parts = explode('.', $slug);
        if (count($parts) !== 2) {
            return null;
        }

        $query = "SELECT * FROM tbl_permissions 
                  WHERE module = ? AND action = ? AND status_id = 1 AND deleted_at IS NULL
                  LIMIT 1";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($parts);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Assign permission to role
     */
    public function assignPermission($roleId, $permissionId)
    {
        // Check if already assigned
        $checkQuery = "SELECT id FROM tbl_role_permissions WHERE role_id = ? AND permission_id = ?";
        $stmt = $this->pdo->prepare($checkQuery);
        $stmt->execute([$roleId, $permissionId]);
        
        if ($stmt->fetch()) {
            return true; // Already assigned
        }

        $query = "INSERT INTO tbl_role_permissions (role_id, permission_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Remove permission from role
     */
    public function removePermission($roleId, $permissionId)
    {
        $query = "DELETE FROM tbl_role_permissions WHERE role_id = ? AND permission_id = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Sync permissions for a role (replace all)
     */
    public function syncPermissions($roleId, $permissionIds = [])
    {
        // Delete existing permissions
        $deleteQuery = "DELETE FROM tbl_role_permissions WHERE role_id = ?";
        $deleteStmt = $this->pdo->prepare($deleteQuery);
        $deleteStmt->execute([$roleId]);

        // Insert new permissions
        if (!empty($permissionIds)) {
            $query = "INSERT INTO tbl_role_permissions (role_id, permission_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($query);
            
            foreach ($permissionIds as $permissionId) {
                $stmt->execute([$roleId, $permissionId]);
            }
        }

        return true;
    }

    /**
     * Get permissions count for a role
     */
    public function getPermissionCount($roleId)
    {
        $query = "SELECT COUNT(*) as count FROM tbl_role_permissions WHERE role_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$roleId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get status name by status_id
     */
    public function getStatusName($statusId)
    {
        $statuses = [
            1 => 'active',
            2 => 'pending',
            3 => 'inactive'
        ];
        return $statuses[$statusId] ?? 'unknown';
    }

    /**
     * Get status ID by name
     */
    public function getStatusId($statusName)
    {
        $statuses = [
            'active' => 1,
            'pending' => 2,
            'inactive' => 3
        ];
        return $statuses[strtolower($statusName)] ?? null;
    }
}
