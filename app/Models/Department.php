<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Support\Uuid;
use PDO;

class Department
{
    private PDO $db;
    private string $table = 'tbl_departments';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllActive(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name
            FROM {$this->table}
            WHERE deleted_at IS NULL
              AND status_id = 1
            ORDER BY id DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveNames(): array
    {
        return array_values(array_map(
            static fn (array $department): string => (string) ($department['name'] ?? ''),
            $this->getAllActive()
        ));
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name
            FROM {$this->table}
            WHERE id = :id
              AND deleted_at IS NULL
              AND status_id = 1
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
        ];
    }

    public function findByNormalizedName(string $name, ?int $excludeId = null): ?array
    {
        $sql = "
            SELECT id, name, deleted_at
            FROM {$this->table}
            WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
        ";

        $params = ['name' => trim($name)];

        if ($excludeId !== null) {
            $sql .= " AND id <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $uuid = Uuid::v4();
        $now  = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
                (uuid, name, description, status_id, created_at, created_by)
            VALUES
                (:uuid, :name, :description, :status_id, :created_at, :created_by)
        ");

        $stmt->execute([
            'uuid' => $uuid,
            'name' => (string) $data['name'],
            'description' => $data['description'] ?? null,
            'status_id' => (int) ($data['status_id'] ?? 1),
            'created_at' => $now,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = ['name = :name'];
        $params = [
            'id' => $id,
            'name' => (string) $data['name'],
            'updated_by' => $data['updated_by'] ?? null,
        ];

        if (array_key_exists('description', $data)) {
            $fields[] = 'description = :description';
            $params['description'] = $data['description'];
        }

        if (array_key_exists('status_id', $data)) {
            $fields[] = 'status_id = :status_id';
            $params['status_id'] = (int) $data['status_id'];
        }

        $fields[] = 'updated_at = NOW()';
        $fields[] = 'updated_by = :updated_by';

        $sql = "
            UPDATE {$this->table}
            SET " . implode(', ', $fields) . "
            WHERE id = :id
              AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id, ?int $deletedBy = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET deleted_at = NOW(),
                deleted_by = :deleted_by
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        return $stmt->execute([
            'deleted_by' => $deletedBy,
            'id' => $id,
        ]);
    }
}
