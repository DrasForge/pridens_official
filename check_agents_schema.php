<?php
require_once 'admin_API/db.php';

try {
    $stmt = $pdo->query("DESCRIBE agents");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
