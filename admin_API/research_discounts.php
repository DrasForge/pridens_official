<?php
require_once 'db.php';
try {
    echo "--- Current Exclusive Offers ---\n";
    $stmt = $pdo->query("SELECT * FROM merchant_exclusive_offers");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n\n";

    echo "--- Subscription Plans ---\n";
    $stmt = $pdo->query("SELECT * FROM subscription_plans");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n\n";

    echo "--- Subscriber Plan Check ---\n";
    $stmt = $pdo->query("SELECT account_id, plan_id FROM subscribers LIMIT 3");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n\n";
} catch(Exception $e) { echo $e->getMessage(); }
