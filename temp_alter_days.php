<?php
require_once 'admin_API/db.php';
try {
    $pdo->exec("ALTER TABLE subscription_plans CHANGE contestability_period_months contestability_period_days INT DEFAULT 0");
    echo "Column renamed to days successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
