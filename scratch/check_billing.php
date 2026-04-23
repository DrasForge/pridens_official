<?php
require_once 'admin_API/db.php';

try {
    echo "--- Billing Data Check ---\n";
    
    echo "Brackets Count: " . $pdo->query("SELECT COUNT(*) FROM plan_billing_brackets")->fetchColumn() . "\n";
    
    echo "\nSample Subscribers (first 10):\n";
    $stmt = $pdo->query("SELECT account_id, plan_id, billing_day, subscription_status FROM subscribers LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['account_id']} | Plan: " . ($row['plan_id'] ?? 'NULL') . " | Bill Day: " . ($row['billing_day'] ?? 'NULL') . " | Status: {$row['subscription_status']}\n";
    }

    echo "\nSummary:\n";
    echo "Total Subscribers: " . $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn() . "\n";
    echo "Subscribers with Plan: " . $pdo->query("SELECT COUNT(*) FROM subscribers WHERE plan_id IS NOT NULL")->fetchColumn() . "\n";
    echo "Subscribers with Billing Day: " . $pdo->query("SELECT COUNT(*) FROM subscribers WHERE billing_day IS NOT NULL")->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
