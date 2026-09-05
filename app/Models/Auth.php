<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Auth
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // -----------------------------------------------------------------------
    // User / Employee lookup (login)
    // -----------------------------------------------------------------------

    public function findAdminByIdentifier(string $identifier): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.uuid,
                u.username,
                u.password,
                u.full_name,
                u.email,
                ur.role_id,
                u.status_id,
                r.name AS role_name
            FROM tbl_users u
            LEFT JOIN (
                SELECT user_id, MIN(role_id) AS role_id
                FROM tbl_user_roles
                GROUP BY user_id
            ) ur ON ur.user_id = u.id
            LEFT JOIN tbl_roles r ON ur.role_id = r.id
            WHERE (u.username = :username_identifier OR u.email = :email_identifier)
              AND u.status_id = 1
              AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            ':username_identifier' => $identifier,
            ':email_identifier'    => $identifier,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findEmployeeByIdentifier(string $identifier): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.uuid,
                e.username,
                e.password,
                e.full_name,
                e.email,
                e.status_id
            FROM tbl_employees e
            WHERE (e.username = :username_identifier OR e.email = :email_identifier)
              AND e.deleted_at IS NULL
              AND e.status_id = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':username_identifier' => $identifier,
            ':email_identifier'    => $identifier,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------------
    // Access tokens
    // -----------------------------------------------------------------------

    /**
     * Store a new access token.
     * Only the SHA-256 hash of the raw token is persisted — the raw token
     * is never written to the database.
     */
    public function createAccessToken(
        string $tokenableType,
        int    $tokenableId,
        string $token,
        ?string $expiresAt = null
    ): int {
        // FIX: hash before storing — raw token never touches the DB
        $tokenHash = hash('sha256', $token);

        $stmt = $this->db->prepare("
            INSERT INTO tbl_access_tokens
                (token, tokenable_type, tokenable_id, expires_at, created_at)
            VALUES
                (:token, :tokenable_type, :tokenable_id, :expires_at, NOW())
        ");

        $stmt->execute([
            ':token'          => $tokenHash,
            ':tokenable_type' => $tokenableType,
            ':tokenable_id'   => $tokenableId,
            ':expires_at'     => $expiresAt,
        ]);

        // FIX: return the new row ID so callers can store it in session instead of the raw token
        return (int) $this->db->lastInsertId();
    }

    /**
     * Look up a token row by the raw bearer token.
     *
     * FIX 1: compare ONLY the SHA-256 hash — the plain token is never sent
     *         to the database, eliminating the dual-match vulnerability.
     * FIX 2: SELECT explicit columns instead of SELECT * so the token hash
     *         itself is not returned to PHP and cannot leak into session/logs.
     */
    public function findAccessToken(string $token): ?array
    {
        // Hash on the PHP side; only the hash hits the DB
        $tokenHash = hash('sha256', $token);

        $stmt = $this->db->prepare("
            SELECT
                id,
                tokenable_type,
                tokenable_id,
                expires_at,
                last_used_at,
                revoked_at,
                created_at
            FROM tbl_access_tokens
            WHERE token      = :token_hash
              AND revoked_at IS NULL
              AND (expires_at IS NULL OR expires_at > NOW())
            LIMIT 1
        ");
        $stmt->bindValue(':token_hash', $tokenHash);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update the last-used timestamp for an active token.
     */
    public function touchAccessToken(int $tokenId): void
    {
        $stmt = $this->db->prepare("
            UPDATE tbl_access_tokens
            SET    last_used_at = NOW()
            WHERE  id = :id
        ");
        $stmt->execute([':id' => $tokenId]);
    }

    /**
     * Revoke a token by its database row ID.
     *
     * FIX 3: accept the row ID (integer) instead of the raw token string.
     *         The Router already stores only the token ID in session, so this
     *         matches. Revoking by ID is also safer — no token value crosses
     *         the wire again after initial authentication.
     */
    public function revokeAccessToken(int $tokenId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tbl_access_tokens
            SET    revoked_at = NOW()
            WHERE  id         = :id
              AND  revoked_at IS NULL
        ");
        return $stmt->execute([':id' => $tokenId]);
    }

    /**
     * Revoke ALL active tokens for a given owner (used on logout).
     * Prefer this over revoking a single token to prevent session fixation.
     */
    public function revokeAllTokensFor(string $tokenableType, int $tokenableId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tbl_access_tokens
            SET    revoked_at = NOW()
            WHERE  tokenable_type = :type
              AND  tokenable_id   = :id
              AND  revoked_at     IS NULL
        ");
        return $stmt->execute([
            ':type' => $tokenableType,
            ':id'   => $tokenableId,
        ]);
    }

    // -----------------------------------------------------------------------
    // Password resets
    // -----------------------------------------------------------------------

    /**
     * Store a new password reset token.
     * Only the SHA-256 hash of the token is persisted.
     */
    public function createPasswordReset(string $email, string $token): void
    {
        $tokenHash = hash('sha256', $token);
        
        // Remove any existing tokens for this email
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
        $stmt->execute([':email' => $email]);

        // Insert the new token hash
        $stmt = $this->db->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (:email, :token, NOW())");
        $stmt->execute([
            ':email' => $email,
            ':token' => $tokenHash
        ]);
    }

    /**
     * Look up a password reset record by email and token.
     */
    public function findPasswordReset(string $email, string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->db->prepare("SELECT created_at FROM password_resets WHERE email = :email AND token = :token LIMIT 1");
        $stmt->execute([
            ':email' => $email,
            ':token' => $tokenHash
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Delete all password reset tokens for a given email.
     */
    public function deletePasswordReset(string $email): void
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
        $stmt->execute([':email' => $email]);
    }

    /**
     * Update the password for a user/employee by email.
     * Updates both tables to ensure consistency.
     */
    public function updatePasswordByEmail(string $email, string $hashedPassword): void
    {
        // Update tbl_users
        $stmt = $this->db->prepare("UPDATE tbl_users SET password = :password, updated_at = NOW() WHERE email = :email");
        $stmt->execute([':password' => $hashedPassword, ':email' => $email]);

        // Update tbl_employees
        $stmt = $this->db->prepare("UPDATE tbl_employees SET password = :password, updated_at = NOW() WHERE email = :email");
        $stmt->execute([':password' => $hashedPassword, ':email' => $email]);
    }

    // -----------------------------------------------------------------------
    // Identity lookup (used after token validation)
    // -----------------------------------------------------------------------

    public function getAdminById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.uuid,
                u.username,
                u.full_name,
                u.email,
                ur.role_id,
                u.status_id,
                r.name AS role_name
            FROM tbl_users u
            LEFT JOIN (
                SELECT user_id, MIN(role_id) AS role_id
                FROM tbl_user_roles
                GROUP BY user_id
            ) ur ON ur.user_id = u.id
            LEFT JOIN tbl_roles r ON r.id = ur.role_id
            WHERE u.id        = :id
              AND u.status_id = 1
              AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getEmployeeById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.uuid,
                e.username,
                e.full_name,
                e.email,
                e.status_id
            FROM tbl_employees e
            WHERE e.id        = :id
              AND e.status_id = 1
              AND e.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
