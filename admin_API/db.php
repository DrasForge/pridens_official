<?php
// admin_API/db.php
$host = 'localhost';
$db   = 'pridens_official'; // Ensure this matches your phpMyAdmin database name
$user = 'root';       // Default XAMPP/Laragon user
$pass = '';           // Default XAMPP/Laragon password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Return a JSON error if DB connection fails, rather than throwing a fatal error visible to end user
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}
?>
