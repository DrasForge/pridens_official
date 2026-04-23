<?php
// admin_API/csrf.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production to specific domain
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Only output JSON if this file is called directly
if (basename($_SERVER['PHP_SELF']) == 'csrf.php') {
    echo json_encode([
        'status' => 'success',
        'csrf_token' => $_SESSION['csrf_token']
    ]);
}
?>
