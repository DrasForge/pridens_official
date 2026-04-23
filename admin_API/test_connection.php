<?php
// admin_API/test_connection.php
try {
    $host = '127.0.0.1';
    $db   = 'pridens_official';
    $user = 'root';
    $pass = '';
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "SUCCESS: Connected to $db\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:\n" . implode("\n", $tables);
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage();
}
?>
