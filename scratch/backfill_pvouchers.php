<?php
require_once 'admin_API/db.php';

try {
    echo "Backfilling missing P-voucher rewards...\n";

    // Find Active subscribers who don't have an 'Initial Approval Reward'
    $subs = $pdo->query("
        SELECT s.account_id, s.plan_id, s.joined_date, sp.monthly_pvoucher
        FROM subscribers s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        LEFT JOIN subscriber_pvoucher_ledger l ON s.account_id = l.account_id AND l.description = 'Initial Approval Reward'
        WHERE s.subscription_status = 'Active' AND l.id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subs as $sub) {
        $amount = $sub['monthly_pvoucher'];
        if ($amount > 0) {
            $pdo->prepare("INSERT INTO subscriber_pvoucher_ledger (account_id, amount, type, description, created_at) VALUES (?, ?, 'Credit', 'Initial Approval Reward', ?)")
                ->execute([$sub['account_id'], $amount, $sub['joined_date'] . ' 09:00:00']);
            echo "Rewarded Subscriber {$sub['account_id']} with ₱$amount\n";
        }
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
