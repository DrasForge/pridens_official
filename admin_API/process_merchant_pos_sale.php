<?php
// admin_API/process_merchant_pos_sale.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$merchantId = intval($input['merchant_id'] ?? 0);
$subscriberId = $input['subscriber_id'] ?? null;
$cart = $input['cart'] ?? [];
$paymentMethod = $input['payment_method'] ?? 'Cash';
$totalAmount = floatval($input['total_amount'] ?? 0);

if (!$merchantId || empty($cart)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid transaction data']);
    exit;
}

try {
    $pdo->beginTransaction();

    $posTxId = 'POS-' . time() . '-' . rand(1000, 9999);
    $now = date('Y-m-d H:i:s');

    foreach ($cart as $item) {
        $type = $item['item_type'];
        $itemId = intval($item['id']);
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $exclusiveDiscount = floatval($item['exclusive_discount_amount'] ?? 0);
        $voucherDiscount = floatval($item['voucher_discount_amount'] ?? 0);
        $itemTotal = ($price * $qty) - $exclusiveDiscount - $voucherDiscount;
        
        // Calculate Fees (1.5% Total) based on discounted total or base total?
        // User said "except for the points", usually fees are on the net sale.
        $pointsEarned = $itemTotal * 0.008;
        $pridensProfit = $itemTotal * 0.007;

        $orderSn = strtoupper($type[0]) . date('ymd') . rand(1000, 9999);

        if ($type === 'Product') {
            $stmt = $pdo->prepare("
                INSERT INTO merchant_orders (order_sn, merchant_id, subscriber_id, total_amount, exclusive_discount_amount, voucher_discount_amount, points_earned, pridens_profit_amount, status, source, pos_transaction_id, payment_method, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Completed', 'POS', ?, 'Paid Already', ?)
            ");
            $stmt->execute([$orderSn, $merchantId, $subscriberId, $itemTotal, $exclusiveDiscount, $voucherDiscount, $pointsEarned, $pridensProfit, $posTxId, $now]);
            
            // Deduct Stock
            $pdo->prepare("UPDATE merchant_products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $itemId]);
        } 
        else if ($type === 'Food') {
            $stmt = $pdo->prepare("
                INSERT INTO merchant_food_orders (order_sn, merchant_id, subscriber_id, total_amount, exclusive_discount, voucher_discount, points_received, system_fee, status, source, pos_transaction_id, payment_method, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Delivered', 'POS', ?, ?, ?)
            ");
            $stmt->execute([$orderSn, $merchantId, $subscriberId, $itemTotal, $exclusiveDiscount, $voucherDiscount, $pointsEarned, $pridensProfit, $posTxId, $paymentMethod, $now]);
        } 
        else if ($type === 'Service') {
            $stmt = $pdo->prepare("
                INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, voucher_discount, points_earned, pridens_profit, status, source, pos_transaction_id, payment_method, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', 'POS', ?, ?, ?)
            ");
            $stmt->execute([$orderSn, $merchantId, $itemId, $subscriberId, $itemTotal, $exclusiveDiscount, $voucherDiscount, $pointsEarned, $pridensProfit, $posTxId, $paymentMethod, $now]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'transaction_id' => $posTxId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
