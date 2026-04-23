<?php
// admin_API/login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

// Verify CSRF Token
if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit();
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// Prevent empty submissions processing further against DB
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
    exit();
}

// Connect to Database
require_once 'db.php';

try {
    // Prepare statement to prevent SQL injection
    $stmt = $pdo->prepare('SELECT id, username, password_hash, first_name, last_name FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    // Verify Password against hash
    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_full_name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: $admin['username'];
        
        echo json_encode(['status' => 'success', 'message' => 'Login successful', 'redirect' => 'dashboard.html']);
    } else {
        // Delay response slightly to mitigate timing attacks on invalid users
        usleep(300000); 
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    // In production, do not expose $e->getMessage()
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred during authentication.']);
}
?>
