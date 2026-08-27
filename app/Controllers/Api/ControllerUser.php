<?php

namespace App\Controllers\Api;

use App\Models\User;
use App\Services\UserService;
use App\Helpers\Response;
use App\Helpers\PasswordPolicy;

class ControllerUser
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function show()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 18;
            $filters = isset($_GET['filters']) ? $_GET['filters'] : [];
            $sorts = isset($_GET['sorts']) ? $_GET['sorts'] : [];

            $result = $this->userService->getAllUsers($page, $per_page, $filters, $sorts);

            Response::paginated(
                $result['data'],
                $result['pagination']['total'] ?? 0,
                $page,
                $per_page,
                'Users fetched successfully'
            );

        } catch (\Exception $e) {
            error_log("ControllerUser show error: " . $e->getMessage());
            Response::serverError('Error fetching users', ['exception' => $e->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $rawInput = file_get_contents('php://input');
            $jsonInput = json_decode($rawInput, true);
            $input = is_array($jsonInput) ? $jsonInput : $_POST;

            if (empty($input) && !empty($rawInput)) {
                parse_str($rawInput, $parsed);
                if (is_array($parsed)) {
                    $input = $parsed;
                }
            }

            $full_name = $input['full_name'] ?? '';
            $username = $input['username'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $role_id = $input['role_id'] ?? '';
            $status_id = isset($input['status_id']) ? (int)$input['status_id'] : 1;

            $full_name = trim($full_name);
            $username = trim($username);
            $email = trim($email);
            $password = trim($password);
            $role_id = trim($role_id);

            $errors = [];

            if (empty($full_name)) {
                $errors['full_name'] = 'Full name is required';
            }

            if (empty($username)) {
                $errors['username'] = 'Username is required';
            }

            if (empty($email)) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }

            // if (empty($password)) {
            //     $errors['password'] = 'Password is required';
            // } elseif (strlen($password) < 6) {
            //     $errors['password'] = 'Password must be at least 6 characters';
            // }


            if (empty($password)) {
                $errors['password'] = 'Password is required';
            } else {
                $passwordError = PasswordPolicy::validate($password);
                if ($passwordError !== null) {
                    $errors['password'] = $passwordError;
                }
            }

            if (empty($role_id)) {
                $errors['role_id'] = 'Role is required';
            }

            if (!empty($errors)) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
                return;
            }

            $created_by = $_SESSION['user_id'] ?? null;

            $data = [
                'full_name' => $full_name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role_id' => (int)$role_id,
                'status_id' => $status_id,
                'created_by' => $created_by
            ];

            $user = $this->userService->createUser($data);

            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'User created successfully', 'data' => $user]);

        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getUserById(int $id)
    {
        try {
            if ($id <= 0) {
                Response::validationError(['id' => 'Invalid user ID']);
            }

            $user = $this->userService->getUserById($id);

            if (!$user) {
                Response::notFound('User not found');
            }

            Response::success($user, 'User fetched successfully');

        } catch (\Exception $e) {
            error_log("ControllerUser getUserById error: " . $e->getMessage());
            Response::serverError('Error fetching user', ['exception' => $e->getMessage()]);
        }
    }

    public function update()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $id = isset($input['id']) ? (int)$input['id'] : 0;

            if ($id <= 0) {
                Response::validationError(['id' => 'Invalid user ID']);
            }

            // $full_name  = isset($input['full_name'])  ? trim($input['full_name'])  : '';
            // $email      = isset($input['email'])       ? trim($input['email'])      : '';
            // $role_id    = isset($input['role_id'])     ? trim($input['role_id'])    : '';
            // $status_id  = isset($input['status_id'])   ? (int)$input['status_id']  : null;

            // $updated_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

            // $data = [
            //     'full_name'  => $full_name,
            //     'email'      => $email,
            //     'role_id'    => $role_id,
            //     'status_id'  => $status_id,
            //     'updated_by' => $updated_by
            // ];

            $full_name  = isset($input['full_name'])  ? trim($input['full_name'])  : '';
            $email      = isset($input['email'])       ? trim($input['email'])      : '';
            $role_id    = isset($input['role_id'])     ? trim($input['role_id'])    : '';
            $status_id  = isset($input['status_id'])   ? (int)$input['status_id']  : null;
            $password   = isset($input['password'])    ? trim($input['password'])   : '';

            $updated_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

            $data = [
                'full_name'  => $full_name,
                'email'      => $email,
                'role_id'    => $role_id,
                'status_id'  => $status_id,
                'updated_by' => $updated_by
            ];

            // Only touch the password if the caller actually sent one — leaves it
            // unchanged otherwise, matching Models\User::update()'s existing behavior.
            if ($password !== '') {
                $passwordError = PasswordPolicy::validate($password);
                if ($passwordError !== null) {
                    Response::validationError(['password' => $passwordError]);
                    return;
                }
                $data['password'] = $password;
            }

            $user = $this->userService->updateUser($id, $data);

            Response::success($user, 'User updated successfully');

        } catch (\Exception $e) {
            error_log("ControllerUser update error: " . $e->getMessage());
            Response::error($e->getMessage(), 400);
        }
    }

    public function delete()
    {
        try {
            // Get raw input
            $rawInput = file_get_contents("php://input");

            // Try JSON first, fallback to form-urlencoded
            $data = json_decode($rawInput, true);
            if (empty($data)) {
                parse_str($rawInput, $data);
            }

            // Fallback to $_POST
            if (empty($data)) {
                $data = $_POST;
            }

            // Validate id exists before casting
            if (empty($data['id'])) {
                Response::validationError(['id' => 'User ID is required']);
                return;
            }

            $id = (int)$data['id'];

            if ($id <= 0) {
                Response::validationError(['id' => 'Invalid user ID']);
                return;
            }

            $deleted_by = $_SESSION['user_id'] ?? null;

            $this->userService->deleteUser($id, $deleted_by);

            Response::success([], 'User deleted successfully');

        } catch (\Exception $e) {
            error_log("ControllerUser delete error: " . $e->getMessage());
            Response::error($e->getMessage(), 400);
        }
    }
}
