<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $parameters = [];
    private string $method;
    private string $route;
    private string $controllerNamespace = 'App\\Controllers\\Api\\';

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->route  = $this->cleanUri();
    }

    // -----------------------------------------------------------------------
    // URI helpers
    // -----------------------------------------------------------------------

    private function cleanUri(): string
    {
        $uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = rtrim((string) ($_ENV['APP_BASE_PATH'] ?? ''), '/');

        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = explode('?', $uri)[0];
        return rtrim($uri, '/') ?: '/';
    }

    // -----------------------------------------------------------------------
    // Route registration
    // -----------------------------------------------------------------------

    public function get(string $path, string $handler): self    { return $this->register('GET',    $path, $handler); }
    public function post(string $path, string $handler): self   { return $this->register('POST',   $path, $handler); }
    public function put(string $path, string $handler): self    { return $this->register('PUT',    $path, $handler); }
    public function delete(string $path, string $handler): self { return $this->register('DELETE', $path, $handler); }
    public function patch(string $path, string $handler): self  { return $this->register('PATCH',  $path, $handler); }

    public function any(string $path, string $handler): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->register($method, $path, $handler);
        }
        return $this;
    }

    private function register(string $method, string $path, string $handler): self
    {
        $path = rtrim($path, '/') ?: '/';
        [$controller, $action] = explode('@', $handler, 2);

        $this->routes[$method][$path] = [
            'controller' => $controller,
            'action'     => $action,
        ];

        return $this;
    }

    public function resource(string $name, string $controller): self
    {
        $path = "/$name";
        $this->get($path,              "$controller@index");
        $this->get("$path/create",     "$controller@create");
        $this->post($path,             "$controller@store");
        $this->get("$path/{id}",       "$controller@show");
        $this->get("$path/{id}/edit",  "$controller@edit");
        $this->put("$path/{id}",       "$controller@update");
        $this->delete("$path/{id}",    "$controller@destroy");
        return $this;
    }

    // -----------------------------------------------------------------------
    // Dispatch
    // -----------------------------------------------------------------------

    public function dispatch(): void
    {
        if (isset($this->routes[$this->method][$this->route])) {
            $this->executeRoute($this->routes[$this->method][$this->route]);
            exit;
        }

        foreach ($this->routes[$this->method] ?? [] as $pattern => $handler) {
            if ($this->matchPattern($pattern, $this->route, $this->parameters)) {
                $this->executeRoute($handler);
                exit;
            }
        }

        $this->notFound();
    }

    private function matchPattern(string $pattern, string $route, array &$parameters): bool
    {
        $regex = preg_quote($pattern, '#');
        $regex = preg_replace('#\\\{(\w+)\\\}#', '([^/]+)', $regex);
        $regex = "#^$regex$#";

        if (!preg_match($regex, $route, $matches)) {
            return false;
        }

        preg_match_all('#\{(\w+)\}#', $pattern, $paramNames);

        for ($i = 0; $i < count($paramNames[1]); $i++) {
            $parameters[$paramNames[1][$i]] = $matches[$i + 1];
        }

        return true;
    }

    private function executeRoute(array $handler): void
    {
        try {
            $this->authorizeRoute($handler);

            $controllerClass = $this->controllerNamespace . $handler['controller'];
            $actionMethod    = $handler['action'];

            if (!class_exists($controllerClass)) {
                throw new \RuntimeException("Controller not found: $controllerClass");
            }

            // Use Container for autowiring
            $container = new \App\Core\Container();

            // Register event subscribers before resolving the controller
            $dispatcher = $container->get(\Symfony\Component\EventDispatcher\EventDispatcher::class);
            $dispatcher->addSubscriber($container->get(\App\EventSubscriber\DashboardCacheSubscriber::class));
            $dispatcher->addSubscriber($container->get(\App\EventSubscriber\AttendanceLeaveSubscriber::class));

            $controller = $container->get($controllerClass);

            if (!method_exists($controller, $actionMethod)) {
                throw new \RuntimeException("Action not found: {$handler['controller']}@$actionMethod");
            }

            // Pass the Request object if the action expects it
            $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
            $params = array_values($this->parameters);

            // Handle method arguments (like UUID) + Request
            $response = $this->invokeAction($controller, $actionMethod, $request, $params);

            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                $response->send();
            }
        } catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            error_log("Route authorization error: " . $e->getMessage());
            $this->sendJson(['success' => false, 'message' => 'Forbidden. You do not have permission for this action.'], 403);
        } catch (\Exception $e) {
            error_log("Route execution error: " . $e->getMessage());
            $this->sendJson(['success' => false, 'message' => 'Error processing request: ' . $e->getMessage()], 500);
        }
    }

    private function invokeAction(object $controller, string $method, \Symfony\Component\HttpFoundation\Request $request, array $pathParams): mixed
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && $type->getName() === \Symfony\Component\HttpFoundation\Request::class) {
                $arguments[] = $request;
            } elseif (!empty($pathParams)) {
                $arguments[] = array_shift($pathParams);
            } else {
                $arguments[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }
        }

        return $reflection->invokeArgs($controller, $arguments);
    }

    // -----------------------------------------------------------------------
    // Authorization
    // -----------------------------------------------------------------------

    private function authorizeRoute(array $handler): void
    {
        if (!$this->isApiRoute() || $this->isPublicRoute()) {
            return;
        }

        if (!$this->isLoggedIn()) {
            error_log("DEBUG: Auth failed for route: " . $this->route);
            error_log("DEBUG: Auth Header: " . $this->getAuthorizationHeader());
            
            // Log ALL headers to see if it's arriving under a different name
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            error_log("DEBUG: All Headers: " . json_encode($headers));

            // Debug SESSION
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            error_log("DEBUG: Session State: " . json_encode($_SESSION));

            $this->sendJson(['success' => false, 'message' => 'Unauthorized. Please log in first.'], 401);
        }

        $this->ensureRequiredAuthType();

        $requiredPermissions = $this->resolveRequiredPermissions($handler);
        if (empty($requiredPermissions)) {
            return;
        }

        if (!$this->userHasAnyPermission($requiredPermissions)) {
            // FIX: never reveal which permissions are required to a client
            $this->sendJson(['success' => false, 'message' => 'Forbidden. You do not have permission for this action.'], 403);
        }
    }

    private function isPublicRoute(): bool
    {
        $publicRoutes = [
            '/api/auth/login',
            '/api/auth/admin/login',
            '/api/auth/employee/login',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/attendance/qr',
            '/api/attendance/checkin',
        ];

        return in_array($this->route, $publicRoutes, true);
    }

    private function isApiRoute(): bool
    {
        return strpos($this->route, '/api/') === 0;
    }

    // -----------------------------------------------------------------------
    // Authentication — session + Bearer token
    // -----------------------------------------------------------------------

    private function isLoggedIn(): bool
    {
        // Session-based (web UI)
        if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
            if (!empty($_SESSION['user_id'])     && ($_SESSION['auth_type'] ?? '') === 'user')     return true;
            if (!empty($_SESSION['employee_id']) && ($_SESSION['auth_type'] ?? '') === 'employee') return true;
        }

        // Bearer token (mobile / API)
        $auth = $this->getAuthorizationHeader();

        if (str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));
            if ($token !== '') {
                error_log("DEBUG: Validating token: " . substr($token, 0, 10) . "...");
                return $this->validateToken($token);
            }
        }

        return false;
    }

    private function validateToken(string $token): bool
    {
        try {
            $authModel = new \App\Models\Auth();
            $tokenRow  = $authModel->findAccessToken($token);

            if (!$tokenRow) {
                error_log("DEBUG: Token not found in database or expired.");
                return false;
            }

            // FIX 1: enforce token expiry
            if (!empty($tokenRow['expires_at']) && strtotime($tokenRow['expires_at']) < time()) {
                $authModel->revokeAccessToken((int) $tokenRow['id']);
                error_log("DEBUG: Token has expired.");
                return false;
            }

            // FIX 2: check token has not been manually revoked
            if (!empty($tokenRow['revoked']) && (int) $tokenRow['revoked'] === 1) {
                error_log("DEBUG: Token has been revoked.");
                return false;
            }

            $authType   = (string) ($tokenRow['tokenable_type'] ?? '');
            $identityId = (int)    ($tokenRow['tokenable_id']   ?? 0);

            if ($authType === 'user') {
                $user = $authModel->getAdminById($identityId);
                if (!$user) return false;

                $_SESSION['user_id']      = (int) $user['id'];
                $_SESSION['employee_id']  = null;
                $_SESSION['uuid']         = $user['uuid'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['full_name']    = $user['full_name'];
                $_SESSION['email']        = $user['email'];
                $_SESSION['role']         = $user['role_name'] ?? null;
                $_SESSION['role_id']      = $user['role_id']   ?? null;
                $_SESSION['auth_type']    = 'user';
                // FIX 3: store only the token DB row ID — never the raw token
                $_SESSION['access_token_id'] = (int) $tokenRow['id'];
                $_SESSION['login']        = true;

                $authModel->touchAccessToken((int) $tokenRow['id']);
                return true;
            }

            if ($authType === 'employee') {
                $employee = $authModel->getEmployeeById($identityId);
                if (!$employee) return false;

                // FIX 4: reject inactive / suspended employees
                if (isset($employee['status']) && $employee['status'] !== 'active') {
                    return false;
                }

                $_SESSION['user_id']      = null;
                $_SESSION['employee_id']  = (int) $employee['id'];
                $_SESSION['uuid']         = $employee['uuid'];
                $_SESSION['username']     = $employee['username'];
                $_SESSION['full_name']    = $employee['full_name'];
                $_SESSION['email']        = $employee['email'];
                $_SESSION['role']         = null;
                $_SESSION['role_id']      = null;
                $_SESSION['auth_type']    = 'employee';
                // FIX 3: store only the token DB row ID — never the raw token
                $_SESSION['access_token_id'] = (int) $tokenRow['id'];
                $_SESSION['login']        = true;

                $authModel->touchAccessToken((int) $tokenRow['id']);
                return true;
            }
        } catch (\Exception $e) {
            error_log('Token validation error: ' . $e->getMessage());
        }

        return false;
    }

    // -----------------------------------------------------------------------
    // Auth-type enforcement
    // -----------------------------------------------------------------------

    private function ensureRequiredAuthType(): void
    {
        $requiredAuthType = $this->resolveRequiredAuthType();
        if ($requiredAuthType === null) return;

        $currentAuthType = $_SESSION['auth_type'] ?? null;
        if ($currentAuthType === $requiredAuthType) return;

        if ($this->route === '/api/attendance/scan') {
            if ($requiredAuthType === 'employee' && $this->hasValidEmployeeAuth()) {
                return;
            }

            if ($requiredAuthType === 'employee' && $currentAuthType === 'user') {
                // Let the scan endpoint continue to permissions; the controller
                // still resolves the employee identity and will fail safely if
                // the employee is inactive or missing.
                return;
            }
        }

        // If the request includes a valid bearer token for the required account
        // type, let it replace any stale session auth type.
        $auth = $this->getAuthorizationHeader();
        if (is_string($auth) && str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));
            if ($token !== '' && $this->validateToken($token) && ($_SESSION['auth_type'] ?? null) === $requiredAuthType) {
                return;
            }
        }

        // Backward compatibility: admin web sessions created before the auth_type split
        if ($requiredAuthType === 'user' && $currentAuthType === null && !empty($_SESSION['user_id'])) {
            return;
        }

        // FIX: do not reveal the required_auth_type to the client
        $this->sendJson(['success' => false, 'message' => 'Forbidden. Invalid account type for this route.'], 403);
    }

    private function hasValidEmployeeAuth(): bool
    {
        if (!empty($_SESSION['employee_id']) && ($_SESSION['auth_type'] ?? '') === 'employee') {
            return true;
        }

        $auth = $this->getAuthorizationHeader();
        if (!is_string($auth) || !str_starts_with($auth, 'Bearer ')) {
            return false;
        }

        $token = trim(substr($auth, 7));
        if ($token === '') {
            return false;
        }

        if (!$this->validateToken($token)) {
            return false;
        }

        return ($_SESSION['auth_type'] ?? '') === 'employee' && !empty($_SESSION['employee_id']);
    }

    private function getAuthorizationHeader(): string
    {
        // 1. Try standard getallheaders()
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (is_array($headers)) {
            $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if (is_string($auth) && $auth !== '') {
                return $auth;
            }
        }

        // 2. Try Apache-specific or CGI fallback
        if (isset($_SERVER['Authorization'])) {
            return $_SERVER['Authorization'];
        }
        
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // 3. Try standard HTTP_AUTHORIZATION
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'HTTP_X_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return '';
    }

    // -----------------------------------------------------------------------
    // Permission checks
    // -----------------------------------------------------------------------

    private function userHasAnyPermission(array $permissionSlugs): bool
    {
        // FIX 5: attendance scan — verify employee is active before bypassing permissions
        if ($this->route === '/api/attendance/scan'
            && ($_SESSION['auth_type'] ?? '') === 'employee'
            && !empty($_SESSION['employee_id'])
        ) {
            try {
                $authModel = new \App\Models\Auth();
                $employee  = $authModel->getEmployeeById((int) $_SESSION['employee_id']);
                // Employee lookups in this codebase expose `status_id`, not a string `status`.
                // Treat status_id = 1 as the active employee state so scan requests are allowed.
                return $employee && (int) ($employee['status_id'] ?? 0) === 1;
            } catch (\Exception $e) {
                error_log('Attendance scan auth error: ' . $e->getMessage());
                return false;
            }
        }

        $normalized = [];
        foreach ($permissionSlugs as $slug) {
            if (!is_string($slug)) continue;
            $slug = strtolower(trim($slug));
            if ($slug !== '') $normalized[] = $slug;
        }

        $normalized = array_values(array_unique($normalized));
        if (empty($normalized)) return true;

        if (function_exists('hasAnyPermissionSlugs')) {
            return \hasAnyPermissionSlugs($normalized);
        }

        if ($this->isAdminSession()) return true;

        $permissions = $_SESSION['permissions'] ?? [];
        if (!is_array($permissions)) return false;

        $permissions = array_map(static fn ($s) => strtolower((string) $s), $permissions);

        foreach ($normalized as $slug) {
            if (in_array($slug, $permissions, true)) return true;
        }

        return false;
    }

    private function isAdminSession(): bool
    {
        $roleName = strtolower((string) ($_SESSION['role'] ?? $_SESSION['role_name'] ?? ''));
        $roleId   = (int) ($_SESSION['role_id'] ?? 0);
        return $roleName === 'admin' || $roleId === 1;
    }

    private function resolveRequiredPermissions(array $handler): array
    {
        $controller = $handler['controller'] ?? '';
        $action     = $handler['action']     ?? '';

        if ($controller === 'ControllerEmployee'
            && in_array($action, ['show', 'update', 'calendarEvents'], true)
            && ($_SESSION['auth_type'] ?? '') === 'employee'
        ) {
            return [];
        }

        return match ($controller) {
            'ControllerDashboard'  => ['dashboard.view'],
            'ControllerAttendance' => $this->permissionsForAttendanceAction($action),
            'ControllerAttendanceLocation' => $this->permissionsForAttendanceLocationAction($action),
            'ControllerEmployee' => $this->permissionsForEmployeeAction($action),
            'ControllerDepartment' => $this->permissionsForDepartmentAction($action),
            'ControllerLeave'      => $this->permissionsForLeaveAction($action),
            'ControllerCalendar'   => $this->permissionsForCalendarAction($action),
            'ControllerReport'     => $this->permissionsForReportAction($action),
            'ControllerUser'       => $this->permissionsForUserAction($action),
            'ControllerRole'       => $this->permissionsForRoleAction($action),
            'ControllerPermission' => $this->permissionsForPermissionAction($action),
            'ControllerPayroll'    => $this->permissionsForPayrollAction($action),
            default                => [],
        };
    }

    private function resolveRequiredAuthType(): ?string
    {
        if (!$this->isApiRoute() || $this->isPublicRoute()) return null;

        return match (true) {
            $this->route === '/api/auth/logout'                                => null,
            $this->route === '/api/attendance/scan'                            => 'employee',
            $this->route === '/api/attendance/checkin'                        => null,
            $this->route === '/api/auth/employee/me'                           => 'employee',
            $this->route === '/api/leave/create'                               => 'employee',
            in_array($this->route, [
                '/api/leave/request',
                '/api/leave/employee/create',
                '/api/employee/leave/create',
                '/api/employee/leave/request',
                '/api/auth/employee/leave/create',
                '/api/leaves',
            ], true)                                                            => 'employee',
            $this->route === '/api/attendance/history'                         => 'employee',
            $this->route === '/api/leave/history'                              => 'employee',
            $this->route === '/api/employee/calendar-events'                  => 'employee',
            // ADD THIS LINE:
            $this->route === '/api/attendance/locations'                       => null,
            $this->route === '/api/attendance/locations/current'               => null,
            preg_match('#^/api/employees/\d+$#', $this->route) === 1          => null,
            $this->route === '/api/leave/list'                                 => null,
            $this->route === '/api/payroll/generate'                           => 'user',
            $this->route === '/api/payroll/approve'                            => 'user',
            $this->route === '/api/payroll/summary'                            => 'user',
            $this->route === '/api/payroll/config/{id}'                        => 'user',
            default                                                            => 'user',
        };
    }

    // -----------------------------------------------------------------------
    // Per-controller permission maps
    // -----------------------------------------------------------------------

    private function permissionsForPayrollAction(string $action): array
    {
        return match ($action) {
            'summary', 'getConfig'           => ['payroll.view'],
            'generate', 'approve', 'updateConfig' => ['payroll.manage'],
            default                          => ['payroll.view'],
        };
    }

    private function permissionsForAttendanceAction(string $action): array
    {
        return match ($action) {
            'show', 'today'                  => ['attendance.view'],
            'checkIn', 'checkOut', 'scan'    => ['attendance.update', 'attendance.create'],
            'update'                         => ['attendance.update'],
            'history'                        => [],
            default                          => ['attendance.view'],
        };
    }

    private function permissionsForAttendanceLocationAction(string $action): array
    {
        return match ($action) {
            'index', 'show'               => ['attendance.view', 'attendance.manage_location'],
            'store', 'update', 'destroy'  => ['attendance.manage_location'],
            default                       => ['attendance.manage_location'],
        };
    }
    
    private function permissionsForEmployeeAction(string $action): array
    {
        return match ($action) {
            'index', 'show'        => ['employee.view',   'employees.view'],
            'store'                => ['employee.create', 'employees.create'],
            'update'               => ['employee.update', 'employees.update'],
            'delete', 'destroy'    => ['employee.delete', 'employees.delete'],
            default                => ['employee.view',   'employees.view'],
        };
    }

    private function permissionsForDepartmentAction(string $action): array
    {
        return match ($action) {
            'index', 'show' => ['department.view', 'departments.view', 'roles.manage'],
            'store'        => ['department.create', 'departments.manage', 'roles.manage'],
            'update'       => ['department.update', 'departments.manage', 'roles.manage'],
            'destroy'      => ['department.delete', 'departments.manage', 'roles.manage'],
            default        => ['department.view', 'departments.view', 'roles.manage'],
        };
    }

    private function permissionsForLeaveAction(string $action): array
    {
        return match ($action) {
            'index'            => ['leave.view'],
            'create'           => ['leave.create', 'leave.view'],
            'approve'          => ['leave.approve', 'leave.update', 'leave.view'],
            'reject'           => ['leave.reject', 'leave.update', 'leave.view'],
            'reopen'           => ['leave.reopen', 'leave.update', 'leave.view'],
            'cancelApproval'   => ['leave.cancel_approval', 'leave.approve', 'leave.update', 'leave.view'],
            'history'          => [],
            default            => ['leave.view'],
        };
    }

    private function permissionsForCalendarAction(string $action): array
    {
        return match ($action) {
            'index', 'show', 'filters'                     => ['calendar.view', 'calendar.manage'],
            'store', 'update', 'destroy', 'approveLeave',
            'rejectLeave'                                 => ['calendar.manage'],
            default                                        => ['calendar.view', 'calendar.manage'],
        };
    }

    private function permissionsForReportAction(string $action): array
    {
        return match ($action) {
            'dailyList'    => ['report.view_daily',   'report.view'],
            'summary'      => ['report.view_summary', 'report.view'],
            'detailedList' => ['report.view_detail',  'report.view'],
            'topEmployees' => ['report.view_top',     'report.view'],
            default        => ['report.view'],
        };
    }

    private function permissionsForUserAction(string $action): array
    {
        return match ($action) {
            'show', 'getUserById' => ['user.view',   'users.view'],
            'create'              => ['user.create', 'users.create'],
            'update'              => ['user.update', 'users.update'],
            'delete'              => ['user.delete', 'users.delete'],
            default               => ['user.view',   'users.view'],
        };
    }

    private function permissionsForRoleAction(string $action): array
    {
        return match ($action) {
            'index', 'show', 'stats', 'search',
            'permissions', 'permissionsGrouped',
            'rolePermissions'                        => ['role.view',   'roles.view'],
            'store'                                  => ['role.create', 'roles.manage'],
            'update', 'accept', 'updateStatus',
            'updateRolePermissions'                  => ['role.update', 'roles.manage'],
            'destroy'                                => ['role.delete', 'roles.manage'],
            default                                  => ['role.view',   'roles.view'],
        };
    }

    private function permissionsForPermissionAction(string $action): array
    {
        return match ($action) {
            'index', 'show', 'getByCategory', 'getById',
            'getCategories', 'getPermissionsByRole',
            'checkUserPermission'                        => ['permission.view',   'permissions.view'],
            'create'                                     => ['permission.add',    'permission.create', 'permissions.manage'],
            'update'                                     => ['permission.update', 'permissions.manage'],
            'delete'                                     => ['permission.delete', 'permissions.manage'],
            'assignToRole', 'removeFromRole',
            'assignMultipleToRole'                       => ['permission.update', 'permissions.manage'],
            default                                      => ['permission.view',   'permissions.view'],
        };
    }

    // -----------------------------------------------------------------------
    // Response helpers
    // -----------------------------------------------------------------------

    private function notFound(): void
    {
        // FIX 6: never expose the requested route or method to the client
        $this->sendJson(['success' => false, 'message' => 'Not found'], 404);
    }

    private function sendJson(array $data, int $statusCode = 200): void
    {
        // FIX 7: add security headers on every response
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------------
    // Debug helpers (remove or gate behind APP_DEBUG in production)
    // -----------------------------------------------------------------------

    public function getRoutes(): array { return $this->routes; }

    public function printRoutes(): void
    {
        if (($_ENV['APP_DEBUG'] ?? 'false') !== 'true') return;

        echo "\n====== Registered Routes ======\n";
        foreach ($this->routes as $method => $routes) {
            echo "\n$method:\n";
            foreach ($routes as $path => $handler) {
                echo "  $path -> {$handler['controller']}@{$handler['action']}\n";
            }
        }
        echo "\n";
    }
}
