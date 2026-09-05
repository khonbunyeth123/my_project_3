<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Models\User;

/**
 * A base controller that provides Symfony-like helpers for the custom framework.
 */
abstract class BaseController
{
    /**
     * Returns a JsonResponse.
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Gets the current logged in user.
     */
    protected function getUser(): ?object
    {
        if (isset($_SESSION['user_id'])) {
            return (object)[
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'roles' => [$_SESSION['role'] ?? 'ROLE_USER']
            ];
        }
        
        if (isset($_SESSION['employee_id'])) {
            return (object)[
                'id' => $_SESSION['employee_id'],
                'username' => $_SESSION['username'] ?? '',
                'roles' => ['ROLE_EMPLOYEE']
            ];
        }

        return null;
    }

    /**
     * Mimics denyAccessUnlessGranted.
     */
    protected function denyAccessUnlessGranted(string $attribute, mixed $subject = null): void
    {
        require_once __DIR__ . '/../../Helpers/PermissionHelper.php';

        $permissions = match ($attribute) {
            'LEAVE_STORE'   => ['leave.create', 'leave.view'],
            'LEAVE_APPROVE' => ['leave.approve', 'leave.update', 'leave.view'],
            'LEAVE_REJECT'  => ['leave.reject', 'leave.update', 'leave.view'],
            'LEAVE_DESTROY' => ['leave.delete', 'leave.update', 'leave.view'],
            default         => [],
        };

        if ($permissions !== [] && !hasAnyPermissionSlugs($permissions)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                'You do not have permission to perform this action.'
            );
        }
    }
}
