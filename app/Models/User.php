<?php

namespace App\Models;

use App\Core\Database;
use App\Support\Uuid;
use PDO;

class User
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // Get user by ID
    public function getById(int $id)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.uuid,
                u.username,
                u.full_name,
                u.email,
                ur.role_id,
                u.status_id,
                u.created_at,
                r.name AS role_name
            FROM tbl_users u
            LEFT JOIN (
                SELECT user_id, MIN(role_id) AS role_id
                FROM tbl_user_roles
                GROUP BY user_id
            ) ur ON ur.user_id = u.id
            LEFT JOIN tbl_roles r ON ur.role_id = r.id
            WHERE u.id = ? AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all users with optional filters, pagination
    public function getAll(int $offset = 0, int $limit = 18, array $filters = [], array $sorts = [])
    {
        $where = "WHERE u.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $search = "%{$filters['search']}%";
            $params = array_merge($params, [$search, $search, $search]);
        }

        $orderBy = "ORDER BY u.created_at DESC";
        if (!empty($sorts['property']) && in_array($sorts['property'], ['id','username','full_name','email','role_id','status_id','created_at'])) {
            $dir = strtoupper($sorts['direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
            $orderColumn = $sorts['property'] === 'role_id'
                ? 'COALESCE(ur.role_id, 0)'
                : "u.{$sorts['property']}";
            $orderBy = "ORDER BY {$orderColumn} $dir";
        }

        // Total count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM tbl_users u $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Data
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.uuid,
                u.username,
                u.full_name,
                u.email,
                ur.role_id,
                u.status_id,
                u.created_at,
                r.name AS role_name
            FROM tbl_users u
            LEFT JOIN (
                SELECT user_id, MIN(role_id) AS role_id
                FROM tbl_user_roles
                GROUP BY user_id
            ) ur ON ur.user_id = u.id
            LEFT JOIN tbl_roles r ON ur.role_id = r.id
            $where
            $orderBy
            LIMIT ?, ?
        ");
        
        $i = 1;
        foreach ($params as $val) {
            $stmt->bindValue($i++, $val);
        }
        $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['data' => $users, 'total' => $total];
    }

    // Create user
    public function create(array $data)
    {
        try {
            $uuid = Uuid::v4();

            error_log("User Model - Creating user with username: " . $data['username']);

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO tbl_users (uuid, username, full_name, email, password, status_id, created_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ");

            $result = $stmt->execute([
                $uuid,
                $data['username'],
                $data['full_name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['status_id'] ?? 1,
                $data['created_by'] ?? null
            ]);

            if (!$result) {
                error_log("User Model - Insert failed: " . json_encode($stmt->errorInfo()));
                throw new \Exception("Failed to insert user: " . $stmt->errorInfo()[2]);
            }

            $userId = (int) $this->pdo->lastInsertId();
            $this->syncUserRole($userId, (int) $data['role_id']);

            $this->pdo->commit();

            error_log("User Model - User created with ID: " . $userId);

            return $this->getById($userId);

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("User Model - Create exception: " . $e->getMessage());
            throw $e;
        }
    }

    // Update user
    public function update(int $id, array $data)
    {
        $updates = [];
        $params = [];

        foreach (['full_name', 'email', 'status_id'] as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $roleIdProvided = array_key_exists('role_id', $data) && $data['role_id'] !== null && $data['role_id'] !== '';

        if (empty($updates) && !$roleIdProvided) {
            return $this->getById($id);
        }

        $this->pdo->beginTransaction();

        try {
            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                if (isset($data['updated_by'])) {
                    $updates[] = "updated_by = ?";
                    $params[] = $data['updated_by'];
                }

                $params[] = $id;
                $sql = "UPDATE tbl_users SET " . implode(', ', $updates) . " WHERE id = ? AND deleted_at IS NULL";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            if ($roleIdProvided) {
                $this->syncUserRole($id, (int) $data['role_id']);
            }

            $this->pdo->commit();
            return $this->getById($id);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // Soft delete user
    public function delete(int $id, $deleted_by = null)
    {
        $sql = "UPDATE tbl_users SET deleted_at = NOW(), deleted_by = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$deleted_by, $id]);
    }

    // Get permissions for a user
    public function getPermissions(int $userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.module, p.action
            FROM tbl_user_roles ur
            JOIN tbl_role_permissions rp ON ur.role_id = rp.role_id
            JOIN tbl_permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.status_id = 1
              AND p.deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function syncUserRole(int $userId, int $roleId): void
    {
        if ($userId <= 0 || $roleId <= 0) {
            throw new \InvalidArgumentException('A valid user and role are required.');
        }

        $deleteStmt = $this->pdo->prepare('DELETE FROM tbl_user_roles WHERE user_id = ?');
        $deleteStmt->execute([$userId]);

        $insertStmt = $this->pdo->prepare(
            'INSERT INTO tbl_user_roles (user_id, role_id, created_at) VALUES (?, ?, NOW())'
        );
        $insertStmt->execute([$userId, $roleId]);
    }

}
