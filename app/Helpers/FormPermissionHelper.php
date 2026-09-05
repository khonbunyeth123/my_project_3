<?php
namespace App\Helpers;
use App\Core\Database;

class FormPermissionHelper
{
    public static function can($module, $action): bool
    {
        $userId = self::getUserId();
        if (!$userId) return false;

        // FIX: If you are Admin, skip the DB check and allow access
        if (self::isAdmin()) {
            return true;
        }

        $db = Database::getInstance()->getConnection();

        $query = "
            SELECT COUNT(DISTINCT p.id) as count
            FROM tbl_user_roles ur
            JOIN tbl_role_permissions rp ON rp.role_id = ur.role_id
            JOIN tbl_permissions p ON rp.permission_id = p.id
            JOIN tbl_users u ON u.id = ur.user_id
            WHERE u.id = ?
            AND u.deleted_at IS NULL
            AND p.module = ?
            AND p.action = ?
            AND p.status_id = 1
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$userId, $module, $action]);
        $result = $stmt->fetch();
        
        return isset($result['count']) && $result['count'] > 0;
    }

    public static function hasRole($roles): bool
    {
        $userId = self::getUserId();
        if (!$userId) return false;

        $roles = is_array($roles) ? $roles : [$roles];
        $db = Database::getInstance()->getConnection();

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $query = "
            SELECT COUNT(DISTINCT r.id) as count
            FROM tbl_users u
            JOIN tbl_user_roles ur ON ur.user_id = u.id
            JOIN tbl_roles r ON ur.role_id = r.id
            WHERE u.id = ? AND r.name IN ($placeholders)
            AND u.deleted_at IS NULL
        ";
        
        $params = array_merge([$userId], $roles);
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return isset($result['count']) && $result['count'] > 0;
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('Admin');
    }

    public static function isManager(): bool
    {
        return self::hasRole('Manager');
    }

    public static function isEmployee(): bool
    {
        return self::hasRole('Employee');
    }

    public static function disabled($module, $action): string
    {
        return !self::can($module, $action) ? 'disabled' : '';
    }

    public static function disabledClass($module, $action): string
    {
        return !self::can($module, $action) ? 'bg-gray-100 opacity-50 cursor-not-allowed' : '';
    }

    private static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}
