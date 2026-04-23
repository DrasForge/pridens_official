<?php
$host = '127.0.0.1';
$db   = 'pridens_official';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "Connected successfully to $db\n";
    $stmt = $pdo->query("SHOW TABLES");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
