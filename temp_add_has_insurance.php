<?php
require_once 'admin_API/db.php';
try {
    $pdo->exec("ALTER TABLE subscription_plans ADD has_insurance BOOLEAN DEFAULT TRUE AFTER plan_name");
    echo "has_insurance column added successfully.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
