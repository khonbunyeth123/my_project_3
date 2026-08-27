<?php

namespace App\Services;

use App\Models\Role;
use Exception;

class RoleService
{
    private $roleModel;
    private PermissionService $permissionService;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->permissionService = new PermissionService();
    }

    /**
     * Get all roles for display
     */
    public function getAllRoles($filters = [])
    {
        $roles = $this->roleModel->getAllRoles();

        // Convert status_id to status string
        $roles = array_map(function ($role) {
            $role['status'] = $this->roleModel->getStatusName($role['status_id']);
            return $role;
        }, $roles);

        // Apply filters if provided
        if (!empty($filters['status'])) {
            $roles = array_filter($roles, function ($role) use ($filters) {
                return $role['status'] === $filters['status'];
            });
        }

        return $roles;
    }

    /**
     * Get a role with all its permissions
     */
    public function getRoleWithPermissions($roleId)
    {
        $role = $this->roleModel->getRoleById($roleId);
        
        if (!$role) {
            throw new Exception('Role not found', 404);
        }

        $permissions = $this->roleModel->getRolePermissions($roleId);
        
        // Convert to slug format (module.action)
        $permissionSlugs = array_map(function ($p) {
            return $p['permission_slug'];
        }, $permissions);

        $role['status'] = $this->roleModel->getStatusName($role['status_id']);
        $role['permissions'] = $permissionSlugs;
        $role['permission_objects'] = $permissions;

        return $role;
    }

    /**
     * Create a new role with permissions
     */
        public function createRole($data)
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new Exception('Role name is required', 422);
        }

        // Check if role already exists
        if ($this->roleModel->roleExists($data['name'])) {
            throw new Exception('Role name already exists', 422);
        }

        // Validate slug format if provided
        if (!empty($data['slug'])) {
            if (!$this->isValidSlug($data['slug'])) {
                throw new Exception('Invalid slug format. Use lowercase letters, numbers, hyphens, and underscores only', 422);
            }
        } else {
            // Auto-generate slug from name
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Check for a soft-deleted role with the same slug — restore it instead of failing
        $deletedRole = $this->roleModel->findDeletedRoleBySlug($data['slug']);

        try {
            if ($deletedRole) {
                $roleId = $this->roleModel->restoreRole($deletedRole['id'], [
                    'name' => trim($data['name']),
                    'description' => trim($data['description'] ?? ''),
                    'status_id' => 2
                ]);
            } else {
                // Slug still active on a non-deleted role? Block with a clean error
                if ($this->roleModel->slugExists($data['slug'])) {
                    throw new Exception('Role slug already exists', 422);
                }

                $roleId = $this->roleModel->createRole([
                    'name' => trim($data['name']),
                    'slug' => trim($data['slug']),
                    'description' => trim($data['description'] ?? ''),
                    'status_id' => 2
                ]);
            }

            // Assign permissions if provided
            if (!empty($data['permissions']) && is_array($data['permissions'])) {
                $this->assignPermissionsToRole($roleId, $data['permissions']);
            }

            return $this->getRoleWithPermissions($roleId);
        } catch (Exception $e) {
            // Preserve the original status code (e.g. 422) instead of forcing 500
            $code = $e->getCode();
            $code = ($code >= 400 && $code < 500) ? $code : 500;
            throw new Exception($code === 500 ? ('Failed to create role: ' . $e->getMessage()) : $e->getMessage(), $code);
        }
    }

    /**
     * Update an existing role
     */
    public function updateRole($roleId, $data)
    {
        $role = $this->roleModel->getRoleById($roleId);
        
        if (!$role) {
            throw new Exception('Role not found', 404);
        }

        $updateData = [];

        // Validate and update name
        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (empty($name)) {
                throw new Exception('Role name cannot be empty', 422);
            }
            
            // Check if new name already exists (excluding current role)
            if ($this->roleModel->roleExists($name, $roleId)) {
                throw new Exception('Role name already exists', 422);
            }
            
            $updateData['name'] = $name;
        }

        // Update description
        if (isset($data['description'])) {
            $updateData['description'] = trim($data['description']);
        }

        // Update status if provided
        if (isset($data['status'])) {
            $statusId = $this->roleModel->getStatusId($data['status']);
            if ($statusId === null) {
                throw new Exception('Invalid status value', 422);
            }
            $updateData['status_id'] = $statusId;
        }

        try {
            $this->roleModel->updateRole($roleId, $updateData);

            // Sync permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->assignPermissionsToRole($roleId, $data['permissions']);
            }

            return [
                'message' => 'Role updated successfully'
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to update role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Soft delete a role
     */
    public function deleteRole($roleId)
    {
        $role = $this->roleModel->getRoleById($roleId);
        
        if (!$role) {
            throw new Exception('Role not found', 404);
        }

        // Prevent deleting roles that have users assigned
        if ($role['user_count'] > 0) {
            throw new Exception('Cannot delete role with assigned users. Please reassign users first.', 422);
        }

        try {
            $this->roleModel->deleteRole($roleId);
            return [
                'message' => 'Role deleted successfully'
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to delete role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Accept/approve a pending role
     */
    public function acceptRole($roleId)
    {
        $role = $this->roleModel->getRoleById($roleId);
        
        if (!$role) {
            throw new Exception('Role not found', 404);
        }

        if ($role['status_id'] !== 2) { // Not pending
            throw new Exception('Only pending roles can be accepted', 422);
        }

        try {
            $this->roleModel->updateRole($roleId, ['status_id' => 1]); // Set to active
            return [
                'message' => 'Role accepted and is now active'
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to accept role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign permissions to a role
     */
    private function assignPermissionsToRole($roleId, $permissionSlugs)
    {
        // Get all available permissions
        $allPermissions = $this->roleModel->getAllPermissions();
        $permissionMap = [];

        foreach ($allPermissions as $perm) {
            $permissionMap[$perm['permission_slug']] = $perm['id'];
        }

        // Convert slugs to IDs and validate
        $permissionIds = [];
        foreach ($permissionSlugs as $slug) {
            if (!isset($permissionMap[$slug])) {
                throw new Exception("Invalid permission: {$slug}", 422);
            }
            $permissionIds[] = $permissionMap[$slug];
        }

        // Sync permissions (replace all)
        $this->roleModel->syncPermissions($roleId, $permissionIds);
    }

    /**
     * Get all available permissions
     */
    public function getAllPermissions()
    {
        $permissions = $this->roleModel->getAllPermissions();
        
        return array_map(function ($p) {
            return [
                'id' => $p['id'],
                'value' => $p['permission_slug'],
                'label' => ucfirst($p['module']) . ' - ' . ucfirst($p['action']),
                'module' => $p['module'],
                'action' => $p['action']
            ];
        }, $permissions);
    }

    /**
     * Get roles by status
     */
    public function getRolesByStatus($status)
    {
        $statusId = $this->roleModel->getStatusId($status);
        
        if ($statusId === null) {
            throw new Exception('Invalid status', 422);
        }

        $roles = $this->roleModel->getAllRoles();
        
        return array_filter($roles, function ($role) use ($statusId) {
            return $role['status_id'] === $statusId;
        });
    }

    /**
     * Search roles
     */
    public function searchRoles($query, $filters = [])
    {
        $roles = $this->getAllRoles($filters);
        $query = strtolower(trim($query));

        if (empty($query)) {
            return $roles;
        }

        return array_filter($roles, function ($role) use ($query) {
            return strpos(strtolower($role['name']), $query) !== false ||
                   strpos(strtolower($role['description'] ?? ''), $query) !== false ||
                   strpos(strtolower($role['uuid']), $query) !== false;
        });
    }

    /**
     * Get role statistics
     */
    public function getRoleStats()
    {
        $roles = $this->roleModel->getAllRoles();

        $stats = [
            'total_roles' => count($roles),
            'active_roles' => 0,
            'pending_roles' => 0,
            'inactive_roles' => 0,
            'total_users' => 0
        ];

        foreach ($roles as $role) {
            if ($role['status_id'] === 1) {
                $stats['active_roles']++;
            } elseif ($role['status_id'] === 2) {
                $stats['pending_roles']++;
            } elseif ($role['status_id'] === 3) {
                $stats['inactive_roles']++;
            }
            
            $stats['total_users'] += $role['user_count'] ?? 0;
        }

        return $stats;
    }

    /**
     * Validate slug format
     */
    private function isValidSlug($slug)
    {
        return preg_match('/^[a-z0-9_-]+$/', $slug) === 1;
    }

    /**
     * Generate slug from text
     */
    private function generateSlug($text)
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = preg_replace('/^-|-$/', '', $slug);
        return $slug;
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission($userId, $permissionSlug)
    {
        return $this->permissionService->userHasPermission((int) $userId, (string) $permissionSlug);
    }

    /**
     * Get permissions for display in frontend
     */
    public function getPermissionsForDisplay()
    {
        $permissions = $this->getAllPermissions();
        
        // Group by module
        $grouped = [];
        foreach ($permissions as $perm) {
            if (!isset($grouped[$perm['module']])) {
                $grouped[$perm['module']] = [];
            }
            $grouped[$perm['module']][] = $perm;
        }

        return $grouped;
    }
}


