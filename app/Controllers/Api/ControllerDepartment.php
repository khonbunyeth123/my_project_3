<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\DepartmentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ControllerDepartment extends BaseController
{
    public function __construct(
        private readonly DepartmentService $service
    ) {}

    public function index(): JsonResponse
    {
        try {
            $departments = $this->service->list();

            return $this->json([
                'success' => true,
                'message' => 'Success',
                'data' => $departments,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'Error loading departments');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $department = $this->service->getById($id);
            if (!$department) {
                return $this->json([
                    'success' => false,
                    'message' => 'Department not found',
                ], 404);
            }

            return $this->json([
                'success' => true,
                'message' => 'Success',
                'data' => $department,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'Error loading department');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $payload = $this->getInputData($request);
            $userId = (int) ($_SESSION['user_id'] ?? 0);

            $department = $this->service->create($payload, $userId > 0 ? $userId : null);

            return $this->json([
                'success' => true,
                'message' => 'Department created successfully',
                'data' => $department,
            ], 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'Error creating department');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $payload = $this->getInputData($request);
            $userId = (int) ($_SESSION['user_id'] ?? 0);

            $department = $this->service->update($id, $payload, $userId > 0 ? $userId : null);

            return $this->json([
                'success' => true,
                'message' => 'Department updated successfully',
                'data' => $department,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'Error updating department');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $this->service->delete($id, $userId > 0 ? $userId : null);

            return $this->json([
                'success' => true,
                'message' => 'Department deleted successfully',
                'data' => null,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'Error deleting department');
        }
    }

    private function getInputData(Request $request): array
    {
        $content = trim((string) $request->getContent());
        $contentType = strtolower((string) $request->headers->get('Content-Type', ''));

        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            if (str_contains($contentType, 'application/json')) {
                throw new \InvalidArgumentException('Invalid JSON payload', 400);
            }
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        return [];
    }

    private function errorResponse(\Throwable $e, string $fallbackMessage): JsonResponse
    {
        $code = (int) $e->getCode();
        if (!in_array($code, [400, 404, 409], true)) {
            $code = 500;
        }

        error_log('Department controller error: ' . $e->getMessage());

        return $this->json([
            'success' => false,
            'message' => $code === 500 ? $fallbackMessage : $e->getMessage(),
        ], $code);
    }
}
