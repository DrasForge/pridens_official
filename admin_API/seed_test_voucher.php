<?php
require_once 'db.php';
// Seed a dummy voucher for testing
$accountId = 'PRD-TEST-USER'; // Assuming a test user exists or just use a valid ID
try {
    // try to get any subscriber
    $sub = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetchColumn();
    if($sub) {
        $code = 'PV-' . strtoupper(substr(md5(time()), 0, 8));
        $pdo->prepare("INSERT IGNORE INTO p_vouchers (voucher_code, account_id, amount) VALUES (?, ?, ?)")
            ->execute([$code, $sub, 100.00]);
        echo "✓ Created test voucher: $code (Value: 100) for user $sub\n";
    }
} catch (PDOException $e) { echo $e->getMessage(); }
?>
