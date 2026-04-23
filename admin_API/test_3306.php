<?php
// admin_API/test_3306.php
try {
    $dsn = "mysql:host=127.0.0.1;port=3306;dbname=pridens_official;charset=utf8mb4";
    $pdo = new PDO($dsn, "root", "");
    echo "SUCCESS: Connected via 127.0.0.1:3306\n";
    $stmt = $pdo->query("SHOW TABLES");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "F1: " . $e->getMessage() . "\n";
    try {
        $dsn2 = "mysql:host=localhost;port=3306;dbname=pridens_official;charset=utf8mb4";
        $pdo2 = new PDO($dsn2, "root", "");
        echo "SUCCESS: Connected via localhost:3306\n";
    } catch (Exception $e2) {
        echo "F2: " . $e2->getMessage() . "\n";
    }
}
?>
