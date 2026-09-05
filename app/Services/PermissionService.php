<?php

namespace App\Services;

use App\Core\Database;
use App\Support\Uuid;
use Exception;
use PDO;

class PermissionService
{
    protected $db;
    protected $table = 'tbl_permissions';
    protected $rolePermissionTable = 'tbl_role_permissions';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all permissions with optional filters
     */
    public function getAllPermissions($filters = [])
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
            $params = [];

            if (!empty($filters['module'])) {
                $query .= " AND module = :module";
                $params['module'] = $filters['module'];
            }

            if (!empty($filters['search'])) {
                $search = "%" . $filters['search'] . "%";
                // Use distinct placeholders because PDO/MySQL can reject the same
                // named parameter being reused multiple times in one prepared statement.
                $query .= " AND (module LIKE :search_module OR action LIKE :search_action OR description LIKE :search_description)";
                $params['search_module']      = $search;
                $params['search_action']      = $search;
                $params['search_description'] = $search;
            }

            if (isset($filters['status_id'])) {
                $query .= " AND status_id = :status_id";
                $params['status_id'] = (int) $filters['status_id'];
            }

            $query .= " ORDER BY module ASC, action ASC";

            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching permissions: " . $e->getMessage());
        }
    }

    /**
     * Get permission by ID
     */
    public function getPermissionById($id)
    {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL"
            );
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching permission: " . $e->getMessage());
        }
    }

    /**
     * Get permission by module + action (unique key)
     */
    public function getPermissionByModuleAction($module, $action)
    {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT * FROM {$this->table} WHERE module = :module AND action = :action AND deleted_at IS NULL"
            );
            $stmt->execute(['module' => $module, 'action' => $action]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching permission: " . $e->getMessage());
        }
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissionsByCategory()
    {
        try {
            $stmt = $this->db->getConnection()->query(
                "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY module ASC, action ASC"
            );
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($permissions as $permission) {
                $mod = $permission['module'];
                if (!isset($grouped[$mod])) {
                    $grouped[$mod] = [];
                }
                $grouped[$mod][] = $permission;
            }

            return $grouped;
        } catch (Exception $e) {
            throw new Exception("Error grouping permissions: " . $e->getMessage());
        }
    }

    /**
     * Get permissions for a specific role
     */
    public function getPermissionsByRole($roleId)
    {
        try {
            $query = "SELECT p.* FROM {$this->table} p
                      INNER JOIN {$this->rolePermissionTable} rp ON p.id = rp.permission_id
                      WHERE rp.role_id = :role_id AND p.deleted_at IS NULL
                      ORDER BY p.module ASC, p.action ASC";

            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute(['role_id' => $roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching role permissions: " . $e->getMessage());
        }
    }

    /**
     * Get distinct modules
     */
    public function getCategories()
    {
        try {
            $stmt = $this->db->getConnection()->query(
                "SELECT DISTINCT module FROM {$this->table} WHERE deleted_at IS NULL ORDER BY module ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            throw new Exception("Error fetching modules: " . $e->getMessage());
        }
    }

    /**
     * Create a new permission
     */
    public function createPermission($data)
    {
        try {
            if (empty($data['module']) || empty($data['action'])) {
                throw new Exception("Module and action are required");
            }

            $module = trim($data['module']);
            $action = trim($data['action']);

            if ($this->getPermissionByModuleAction($module, $action)) {
                throw new Exception("Permission '{$module}.{$action}' already exists");
            }

            $description = isset($data['description']) ? trim($data['description']) : null;
            $status_id   = isset($data['status_id'])   ? (int) $data['status_id']  : 1;
            $uuid        = $data['uuid'] ?? Uuid::v4();

            $stmt = $this->db->getConnection()->prepare(
                "INSERT INTO {$this->table} (uuid, module, action, description, status_id, created_at)
                 VALUES (:uuid, :module, :action, :description, :status_id, NOW())"
            );
            $result = $stmt->execute([
                'uuid'        => $uuid,
                'module'      => $module,
                'action'      => $action,
                'description' => $description,
                'status_id'   => $status_id,
            ]);

            return $result ? $this->db->getConnection()->lastInsertId() : false;
        } catch (Exception $e) {
            throw new Exception("Error creating permission: " . $e->getMessage());
        }
    }

    /**
     * Update a permission
     */
    public function updatePermission($id, $data)
    {
        try {
            if (!$this->getPermissionById($id)) {
                throw new Exception("Permission not found");
            }

            $updateFields = [];
            $params = ['id' => $id];

            if (isset($data['module'])) {
                $updateFields[] = 'module = :module';
                $params['module'] = trim($data['module']);
            }

            if (isset($data['action'])) {
                // Check uniqueness if both module+action are being updated
                $module = $params['module'] ?? null;
                $action = trim($data['action']);
                if ($module) {
                    $stmt = $this->db->getConnection()->prepare(
                        "SELECT id FROM {$this->table} WHERE module = :module AND action = :action AND id != :id AND deleted_at IS NULL"
                    );
                    $stmt->execute(['module' => $module, 'action' => $action, 'id' => $id]);
                    if ($stmt->fetch()) {
                        throw new Exception("Permission '{$module}.{$action}' already exists");
                    }
                }
                $updateFields[] = 'action = :action';
                $params['action'] = $action;
            }

            if (isset($data['description'])) {
                $updateFields[] = 'description = :description';
                $params['description'] = !empty($data['description']) ? trim($data['description']) : null;
            }

            if (isset($data['status_id'])) {
                $updateFields[] = 'status_id = :status_id';
                $params['status_id'] = (int) $data['status_id'];
            }

            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }

            $updateFields[] = 'updated_at = NOW()';

            $stmt = $this->db->getConnection()->prepare(
                "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = :id AND deleted_at IS NULL"
            );
            return $stmt->execute($params);
        } catch (Exception $e) {
            throw new Exception("Error updating permission: " . $e->getMessage());
        }
    }

    /**
     * Soft-delete a permission
     */
    public function deletePermission($id)
    {
        try {
            if (!$this->getPermissionById($id)) {
                throw new Exception("Permission not found");
            }

            // Check if assigned to any role
            $stmt = $this->db->getConnection()->prepare(
                "SELECT COUNT(*) as count FROM {$this->rolePermissionTable} WHERE permission_id = :id"
            );
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception("Cannot delete permission that is assigned to roles");
            }

            // Soft delete
            $stmt = $this->db->getConnection()->prepare(
                "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id"
            );
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            throw new Exception("Error deleting permission: " . $e->getMessage());
        }
    }

    /**
     * Assign a single permission to a role
     */
    public function assignPermissionToRole($roleId, $permissionId)
    {
        try {
            if (!$this->getPermissionById($permissionId)) {
                throw new Exception("Permission not found");
            }

            $stmt = $this->db->getConnection()->prepare(
                "SELECT id FROM {$this->rolePermissionTable}
                 WHERE role_id = :role_id AND permission_id = :permission_id"
            );
            $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
            if ($stmt->fetch()) {
                throw new Exception("Permission already assigned to this role");
            }

            $stmt = $this->db->getConnection()->prepare(
                "INSERT INTO {$this->rolePermissionTable} (role_id, permission_id)
                 VALUES (:role_id, :permission_id)"
            );
            return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        } catch (Exception $e) {
            throw new Exception("Error assigning permission: " . $e->getMessage());
        }
    }

    /**
     * Remove a single permission from a role
     */
    public function removePermissionFromRole($roleId, $permissionId)
    {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "DELETE FROM {$this->rolePermissionTable}
                 WHERE role_id = :role_id AND permission_id = :permission_id"
            );
            return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        } catch (Exception $e) {
            throw new Exception("Error removing permission: " . $e->getMessage());
        }
    }

    /**
     * Replace all permissions for a role (bulk assign)
     */
    public function assignMultiplePermissionsToRole($roleId, $permissionIds)
    {
        try {
            $pdo = $this->db->getConnection();

            $stmt = $pdo->prepare(
                "DELETE FROM {$this->rolePermissionTable} WHERE role_id = :role_id"
            );
            $stmt->execute(['role_id' => $roleId]);

            if (!empty($permissionIds)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO {$this->rolePermissionTable} (role_id, permission_id)
                     VALUES (:role_id, :permission_id)"
                );
                foreach ($permissionIds as $permissionId) {
                    if (!$stmt->execute(['role_id' => $roleId, 'permission_id' => (int) $permissionId])) {
                        throw new Exception("Failed to assign permission ID: " . $permissionId);
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error assigning multiple permissions: " . $e->getMessage());
        }
    }

    /**
     * Get total permission count
     */
    public function getPermissionCount()
    {
        try {
            $stmt = $this->db->getConnection()->query(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL"
            );
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            throw new Exception("Error counting permissions: " . $e->getMessage());
        }
    }

    /**
     * Check if a user has a specific permission
     */
    public function userHasPermission($userId, $permissionSlug)
    {
        try {
            // slug format: module.action  e.g. "users.view"
            $parts  = explode('.', $permissionSlug, 2);
            $module = $parts[0]  ?? null;
            $action = $parts[1]  ?? null;

            if (!$module || !$action) {
                return false;
            }

            $query = "SELECT COUNT(DISTINCT p.id) as count FROM {$this->table} p
                      INNER JOIN {$this->rolePermissionTable} rp ON p.id = rp.permission_id
                      INNER JOIN tbl_user_roles ur ON ur.role_id = rp.role_id
                      INNER JOIN tbl_users u ON u.id = ur.user_id
                      WHERE u.id = :user_id AND u.deleted_at IS NULL
                        AND p.module = :module AND p.action = :action
                        AND p.deleted_at IS NULL";

            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute(['user_id' => $userId, 'module' => $module, 'action' => $action]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            throw new Exception("Error checking permission: " . $e->getMessage());
        }
    }

    /**
     * Validate permission data before save
     */
    public function validatePermission($data)
    {
        $errors = [];

        if (empty($data['module'])) {
            $errors['module'] = 'Module is required';
        } elseif (strlen($data['module']) > 50) {
            $errors['module'] = 'Module must not exceed 50 characters';
        }

        if (empty($data['action'])) {
            $errors['action'] = 'Action is required';
        } elseif (strlen($data['action']) > 50) {
            $errors['action'] = 'Action must not exceed 50 characters';
        }

        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors['description'] = 'Description must not exceed 500 characters';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

}
