<?php

namespace App\Controllers\Api;

use App\Services\RoleService;
use App\Helpers\Response;
use Exception;

class ControllerRole
{
    private $roleService;
    private $response;

    public function __construct()
    {
        $this->roleService = new RoleService();
        $this->response = new Response();
    }

    public function index()
    {
        try {
            $filters = [];

            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }

            $search = $_GET['search'] ?? '';
            $roles = $this->roleService->getAllRoles($filters);

            if (!empty($search)) {
                $roles = $this->roleService->searchRoles($search, $filters);
            }

            $roles = array_map(function ($role) {
                return $this->formatRoleForResponse($role);
            }, $roles);

            return $this->response->success([
                'data' => $roles,
                'count' => count($roles),
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            error_log("ControllerRole Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function show($id = null)
    {
        try {
            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $role = $this->roleService->getRoleWithPermissions($id);

            return $this->response->success([
                'data' => $role
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function store()
    {
        try {
            $this->checkPermission('roles.manage');

            $input = $this->getJsonInput();
            error_log("ROLE STORE INPUT: " . json_encode($input));
            $role = $this->roleService->createRole([
                'name' => $input['name'] ?? '',
                'slug' => $input['slug'] ?? '',
                'description' => $input['description'] ?? '',
                'permissions' => $input['permissions'] ?? []
            ]);

            return $this->response->success([
                'data' => $this->formatRoleForResponse($role),
                'message' => 'Role created successfully and is pending approval'
            ], 201);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function update($id = null)
    {
        try {
            $this->checkPermission('roles.manage');

            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $input = $this->getJsonInput();

            $result = $this->roleService->updateRole($id, $input);

            if (isset($input['permissions']) && (int) ($_SESSION['role_id'] ?? 0) === (int) $id && function_exists('loadSessionPermissions')) {
                loadSessionPermissions();
            }

            return $this->response->success($result);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function destroy($id = null)
    {
        try {
            $this->checkPermission('roles.manage');

            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $result = $this->roleService->deleteRole($id);

            return $this->response->success($result);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function accept($id = null)
    {
        try {
            $this->checkPermission('roles.manage');

            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $result = $this->roleService->acceptRole($id);

            return $this->response->success($result);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function stats()
    {
        try {
            $stats = $this->roleService->getRoleStats();

            return $this->response->success([
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function permissions()
    {
        try {
            $permissions = $this->roleService->getAllPermissions();

            return $this->response->success([
                'data' => $permissions,
                'count' => count($permissions)
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function permissionsGrouped()
    {
        try {
            $permissions = $this->roleService->getPermissionsForDisplay();

            return $this->response->success([
                'data' => $permissions
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function rolePermissions($id = null)
    {
        try {
            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $role = $this->roleService->getRoleWithPermissions($id);

            return $this->response->success([
                'data' => [
                    'role_id' => $role['id'],
                    'role_name' => $role['name'],
                    'permissions' => $role['permissions'],
                    'permission_objects' => $role['permission_objects']
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateRolePermissions($id = null)
    {
        try {
            $this->checkPermission('roles.manage');

            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $input = $this->getJsonInput();
            $permissions = $input['permissions'] ?? [];

            if (!is_array($permissions)) {
                return $this->response->error('Permissions must be an array', 400);
            }

            $result = $this->roleService->updateRole($id, [
                'permissions' => $permissions
            ]);

            if ((int) ($_SESSION['role_id'] ?? 0) === (int) $id && function_exists('loadSessionPermissions')) {
                loadSessionPermissions();
            }

            return $this->response->success(array_merge($result, [
                'role_id' => $id,
                'permissions' => $permissions
            ]));
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function search()
    {
        try {
            $query = $_GET['q'] ?? '';
            $status = $_GET['status'] ?? '';

            if (empty($query)) {
                return $this->response->error('Search query is required', 400);
            }

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }

            $roles = $this->roleService->searchRoles($query, $filters);
            $roles = array_map(function ($role) {
                return $this->formatRoleForResponse($role);
            }, $roles);

            return $this->response->success([
                'data' => $roles,
                'count' => count($roles),
                'query' => $query
            ]);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateStatus($id = null)
    {
        try {
            $this->checkPermission('roles.manage');

            if (!$id) {
                $id = $_GET['id'] ?? null;
            }

            if (!$id) {
                return $this->response->error('Role ID is required', 400);
            }

            $input = $this->getJsonInput();
            $status = $input['status'] ?? null;

            if (!$status) {
                return $this->response->error('Status is required', 400);
            }

            $result = $this->roleService->updateRole($id, [
                'status' => $status
            ]);

            return $this->response->success($result);
        } catch (Exception $e) {
            return $this->response->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function getJsonInput()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }

    private function checkPermission($permission)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isLoggedIn = isset($_SESSION['login']) && $_SESSION['login'] === true;
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$isLoggedIn || $userId <= 0) {
            throw new Exception('Unauthorized', 401);
        }

        $roleName = strtolower((string) ($_SESSION['role'] ?? $_SESSION['role_name'] ?? ''));
        if ($roleName === 'admin' || (int) ($_SESSION['role_id'] ?? 0) === 1) {
            return;
        }

        $aliases = [(string) $permission];
        if ($permission === 'roles.manage') {
            $aliases = ['roles.manage', 'role.create', 'role.update', 'role.delete'];
        } elseif ($permission === 'roles.view') {
            $aliases = ['roles.view', 'role.view'];
        }

        foreach ($aliases as $slug) {
            if ($this->roleService->userHasPermission($userId, $slug)) {
                return;
            }
        }

        throw new Exception('Forbidden', 403);
    }

    private function formatRoleForResponse($role)
    {
        return [
            'id'               => $role['id'],
            'uuid'             => $role['uuid'],
            'name'             => $role['name'],
            'slug'             => $role['slug'] ?? $this->generateSlug($role['name']),
            'description'      => $role['description'] ?? '',
            'status'           => $role['status'],
            'status_id'        => $role['status_id'],
            'user_count'       => $role['user_count'] ?? 0,
            'permission_count' => $role['permission_count'] ?? 0,
            'permissions'      => $role['permissions'] ?? [],
            'created_at'       => $role['created_at'],
            'updated_at'       => $role['updated_at'],
        ];
    }

    private function generateSlug($text)
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = preg_replace('/^-|-$/', '', $slug);
        return $slug;
    }
}
