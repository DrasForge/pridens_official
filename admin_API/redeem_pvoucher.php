<?php
// admin_API/redeem_pvoucher.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

// This API is used by the Merchant Scanner to redeem a platform P-Voucher
$mId = intval($_POST['merchant_id'] ?? 0);
$voucherCode = trim($_POST['voucher_code'] ?? '');

if (!$mId || !$voucherCode) {
    echo json_encode(['status'=>'error', 'message'=>'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Validate Voucher
    $stmt = $pdo->prepare("SELECT * FROM p_vouchers WHERE voucher_code = ? AND status = 'Active' FOR UPDATE");
    $stmt->execute([$voucherCode]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        throw new Exception("Voucher invalid, expired, or already redeemed.");
    }

    // 2. Fetch Merchant Handling Fee
    $stmtM = $pdo->prepare("SELECT pvoucher_handling_fee FROM merchants WHERE id = ?");
    $stmtM->execute([$mId]);
    $handlingFee = $stmtM->fetchColumn() ?: 0.00;

    // 3. Mark Voucher as Redeemed
    $updateV = $pdo->prepare("UPDATE p_vouchers SET status = 'Redeemed', redeemed_at = NOW(), redeemed_by = ? WHERE id = ?");
    $updateV->execute([$mId, $voucher['id']]);

    // 4. Record into Merchant Ledger (The Payout Queue)
    $totalPayout = $voucher['amount'] + $handlingFee;
    $insL = $pdo->prepare("
        INSERT INTO merchant_pvoucher_ledgers (merchant_id, voucher_id, amount, handling_fee, total_payout, payout_status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $insL->execute([$mId, $voucher['id'], $voucher['amount'], $handlingFee, $totalPayout]);
    $ledgerId = $pdo->lastInsertId();

    // 5. Automated Paymongo Disbursement Stub
    // In a real scenario, this would call Paymongo Payouts API
    $disbursementSuccess = triggerPaymongoDisbursement($ledgerId, $totalPayout);
    
    if($disbursementSuccess) {
        $pdo->prepare("UPDATE merchant_pvoucher_ledgers SET payout_status = 'Paid', paid_at = NOW(), paymongo_id = ? WHERE id = ?")
            ->execute(['SIMULATED_REMITTANCE_' . time(), $ledgerId]);
    }

    $pdo->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => 'P-Voucher successfully redeemed!',
        'data' => [
            'voucher_amount' => $voucher['amount'],
            'handling_fee' => $handlingFee,
            'total_disbursed' => $totalPayout,
            'payout_status' => $disbursementSuccess ? 'Paid' : 'Pending'
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}

/**
 * Simularted Paymongo Disbursement Logic
 */
function triggerPaymongoDisbursement($ledgerId, $amount) {
    // Logic for Paymongo Payouts would go here.
    // For now, we return true to simulate a successful automation as requested.
    return true; 
}
?>
