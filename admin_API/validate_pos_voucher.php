<?php
// admin_API/validate_pos_voucher.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$merchantId = intval($input['merchant_id'] ?? 0);
$voucherCode = trim($input['voucher_code'] ?? '');
$cartAmount = floatval($input['cart_amount'] ?? 0);

if (!$merchantId || !$voucherCode) {
    echo json_encode(['status' => 'error', 'message' => 'Missing code or merchant ID']);
    exit;
}

try {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM merchant_vouchers 
                           WHERE merchant_id = ? AND voucher_code = ? 
                           AND start_time <= ? AND end_time >= ?");
    $stmt->execute([$merchantId, $voucherCode, $now, $now]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired voucher code']);
        exit;
    }

    if ($cartAmount < $voucher['min_spend']) {
        echo json_encode(['status' => 'error', 'message' => 'Minimum spend of ₱' . number_format($voucher['min_spend'], 2) . ' not met']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'voucher' => [
            'id' => $voucher['id'],
            'name' => $voucher['name'],
            'reward_type' => $voucher['reward_type'],
            'reward_value' => floatval($voucher['reward_value']),
            'item_category' => $voucher['item_category'],
            'target_type' => $voucher['target_type']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
