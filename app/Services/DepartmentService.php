<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use InvalidArgumentException;
use RuntimeException;

class DepartmentService
{
    public function __construct(
        private readonly Department $departmentModel
    ) {}

    public function list(): array
    {
        return $this->departmentModel->getAllActive();
    }

    public function listNames(): array
    {
        return $this->departmentModel->getActiveNames();
    }

    public function getById(int $id): ?array
    {
        return $this->departmentModel->getById($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $name = $this->validateName($data);

        if ($this->departmentModel->findByNormalizedName($name) !== null) {
            throw new RuntimeException('Department name already exists', 409);
        }

        $id = $this->departmentModel->create([
            'name' => $name,
            'description' => $data['description'] ?? null,
            'status_id' => $data['status_id'] ?? 1,
            'created_by' => $userId ?: null,
        ]);

        $department = $this->departmentModel->getById($id);
        if (!$department) {
            throw new RuntimeException('Department created but could not be loaded', 500);
        }

        return $department;
    }

    public function update(int $id, array $data, ?int $userId = null): array
    {
        $department = $this->departmentModel->getById($id);
        if (!$department) {
            throw new RuntimeException('Department not found', 404);
        }

        $name = $this->validateName($data);
        $duplicate = $this->departmentModel->findByNormalizedName($name, $id);
        if ($duplicate !== null) {
            throw new RuntimeException('Department name already exists', 409);
        }

        $updated = $this->departmentModel->update($id, [
            'name' => $name,
            'updated_by' => $userId ?: null,
        ]);

        if (!$updated) {
            throw new RuntimeException('Failed to update department', 500);
        }

        $fresh = $this->departmentModel->getById($id);
        if (!$fresh) {
            throw new RuntimeException('Department updated but could not be loaded', 500);
        }

        return $fresh;
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        $department = $this->departmentModel->getById($id);
        if (!$department) {
            throw new RuntimeException('Department not found', 404);
        }

        $deleted = $this->departmentModel->delete($id, $userId ?: null);
        if (!$deleted) {
            throw new RuntimeException('Failed to delete department', 500);
        }

        return true;
    }

    private function validateName(array $data): string
    {
        if (!array_key_exists('name', $data)) {
            throw new InvalidArgumentException('Department name is required', 400);
        }

        if (!is_string($data['name'])) {
            throw new InvalidArgumentException('Department name must be a string', 400);
        }

        $name = trim($data['name']);
        if ($name === '') {
            throw new InvalidArgumentException('Department name cannot be empty', 400);
        }

        $length = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        if ($length > 100) {
            throw new InvalidArgumentException('Department name must not exceed 100 characters', 400);
        }

        return $name;
    }
}
