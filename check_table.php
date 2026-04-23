<?php
require_once 'admin_API/db.php';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'plan_billing_brackets'");
    $table_exists = $stmt->fetch();
    if ($table_exists) {
        $stmt = $pdo->query("DESCRIBE plan_billing_brackets");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "Table does not exist";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
