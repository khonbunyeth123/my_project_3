<?php
session_start();

// Load .env file
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database connection
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';

// Response helper
function jsonResponse($success, $message, $data = [], $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'dpl' => $success // For compatibility with your frontend
    ]);
    exit;
}

try {
    // Get POST data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;

    // Validate input
    if (empty($email) || empty($password)) {
        jsonResponse(false, 'Email and password are required', [], 400);
    }

    // Get database connection
    $db = \App\Core\Database::getInstance();

    // Query user by email (resolve role through tbl_user_roles)
    $query = "SELECT u.id, u.uuid, u.username, u.full_name, u.email, u.password, ur.role_id, u.status_id, r.name AS role_name
              FROM tbl_users u
              LEFT JOIN (
                  SELECT user_id, MIN(role_id) AS role_id
                  FROM tbl_user_roles
                  GROUP BY user_id
              ) ur ON ur.user_id = u.id
              LEFT JOIN tbl_roles r ON r.id = ur.role_id
              WHERE u.email = ?
                AND u.deleted_at IS NULL
              LIMIT 1";
    $result = $db->query($query, [$email]);

    if (empty($result)) {
        jsonResponse(false, 'Invalid email or password', [], 401);
    }

    $user = $result[0];

    // Check if user is active
    if ((int)$user['status_id'] !== 1) {
        jsonResponse(false, 'Your account has been disabled', [], 403);
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        jsonResponse(false, 'Invalid email or password', [], 401);
    }

    // Prevent session fixation
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['login'] = true;
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['uuid'] = $user['uuid'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = (int)$user['role_id'];
    $_SESSION['role'] = $user['role_name'] ?? '';
    $_SESSION['auth_type'] = 'user';
    $_SESSION['employee_id'] = null;

    // Load permission slugs into session cache (module.action)
    $permQuery = "SELECT DISTINCT CONCAT(p.module, '.', p.action) AS permission_slug
                  FROM tbl_user_roles ur
                  INNER JOIN tbl_role_permissions rp ON rp.role_id = ur.role_id
                  INNER JOIN tbl_permissions p ON p.id = rp.permission_id
                  WHERE ur.user_id = ?
                    AND p.status_id = 1
                    AND p.deleted_at IS NULL";
    $permRows = $db->query($permQuery, [(int)$user['id']]);

    $permissions = array_map(
        static fn(array $row): string => strtolower((string)$row['permission_slug']),
        $permRows
    );
    $_SESSION['permissions'] = array_values(array_unique($permissions));

    // Update last login
    $updateQuery = "UPDATE tbl_users SET login_session = ?, updated_at = NOW() WHERE id = ?";
    $db->query($updateQuery, [session_id(), $user['id']]);

    // Handle "Remember Me"
    if ($remember) {
        // Set cookie for 30 days
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    jsonResponse(true, 'Login successful', [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role_name'] ?? null,
    ], 200);

} catch (\Exception $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred during login', [], 500);
}
?>
 Mm.