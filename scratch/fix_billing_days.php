<?php
require_once 'admin_API/db.php';

try {
    echo "Fixing missing billing days...\n";
    
    // Get subscribers who have a plan but no billing day
    $subs = $pdo->query("SELECT s.account_id, s.joined_date, s.plan_id 
                         FROM subscribers s 
                         WHERE s.plan_id IS NOT NULL AND s.billing_day IS NULL")
                ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subs as $sub) {
        $dayOfMonth = (int)date('j', strtotime($sub['joined_date']));
        $planId = $sub['plan_id'];

        $stmt = $pdo->prepare("SELECT bill_on_day FROM plan_billing_brackets 
                               WHERE plan_id = ? AND ? >= approval_from_day AND ? <= approval_to_day 
                               LIMIT 1");
        $stmt->execute([$planId, $dayOfMonth, $dayOfMonth]);
        $billDay = $stmt->fetchColumn();

        if ($billDay) {
            $pdo->prepare("UPDATE subscribers SET billing_day = ? WHERE account_id = ?")
                ->execute([$billDay, $sub['account_id']]);
            echo "Updated Subscriber {$sub['account_id']} to Billing Day $billDay\n";
        } else {
            echo "No bracket found for Subscriber {$sub['account_id']} (Day: $dayOfMonth, Plan: $planId)\n";
        }
    }
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
