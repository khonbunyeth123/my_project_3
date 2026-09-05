<?php

if (!function_exists('isAdminRole')) {
    function isAdminRole(): bool
    {
        $roleName = strtolower((string) ($_SESSION['role'] ?? $_SESSION['role_name'] ?? ''));
        $roleId = (int) ($_SESSION['role_id'] ?? 0);

        return $roleName === 'admin' || $roleId === 1;
    }
}

if (!function_exists('loadSessionPermissions')) {
    function loadSessionPermissions(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['permissions'] = [];
            return;
        }

        try {
            $pdo = \App\Core\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT DISTINCT CONCAT(p.module, '.', p.action) AS permission_slug
                 FROM tbl_user_roles ur
                 INNER JOIN tbl_role_permissions rp ON rp.role_id = ur.role_id
                 INNER JOIN tbl_permissions p ON p.id = rp.permission_id
                 INNER JOIN tbl_users u ON u.id = ur.user_id
                 WHERE u.id = :user_id
                   AND u.deleted_at IS NULL
                   AND p.status_id = 1
                   AND p.deleted_at IS NULL"
            );
            $stmt->execute(['user_id' => $userId]);

            $permissions = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            $permissions = array_map(static fn ($slug) => strtolower((string) $slug), $permissions);
            $permissions = array_values(array_unique($permissions));

            $_SESSION['permissions'] = $permissions;
        } catch (\Throwable $e) {
            error_log('Permission cache load failed: ' . $e->getMessage());
            $_SESSION['permissions'] = [];
        }
    }
}

if (!function_exists('hasPermissionSlug')) {
    function hasPermissionSlug(string $permissionSlug): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isLoggedIn = isset($_SESSION['login']) && $_SESSION['login'] === true;
        if (!$isLoggedIn) {
            return false;
        }

        if (isAdminRole()) {
            return true;
        }

        if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
            loadSessionPermissions();
        }

        $permissionSlug = strtolower(trim($permissionSlug));
        if ($permissionSlug === '') {
            return false;
        }

        $permissions = $_SESSION['permissions'] ?? [];
        if (!is_array($permissions)) {
            $permissions = [];
        }

        if (in_array($permissionSlug, $permissions, true)) {
            return true;
        }

        // If permissions were changed by checkbox during this session, refresh once this request.
        static $refreshedThisRequest = false;
        if (!$refreshedThisRequest) {
            $refreshedThisRequest = true;
            loadSessionPermissions();
            $permissions = $_SESSION['permissions'] ?? [];
            if (is_array($permissions) && in_array($permissionSlug, $permissions, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hasAnyPermissionSlugs')) {
    function hasAnyPermissionSlugs(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (!is_string($slug)) {
                continue;
            }

            if (hasPermissionSlug($slug)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Usage: hasPermission('dashboard', 'view')
     */
    function hasPermission(string $module, string $action = 'view'): bool
    {
        $module = strtolower(trim($module));
        $action = strtolower(trim($action));

        if ($module === '' || $action === '') {
            return false;
        }

        return hasPermissionSlug($module . '.' . $action);
    }
}
