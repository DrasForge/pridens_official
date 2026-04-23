<?php
// admin_API/generate_dummy_orders.php
require_once 'db.php';
try {
    $mId = 1; 
    $sId = 'PR-0000001';
    
    // 1. Setup exact product environment from example
    $pdo->exec("DELETE FROM merchant_products WHERE merchant_id = $mId");
    $items = [
        ['name' => 'Battery', 'price' => 100.00, 'is_exclusive' => 0],
        ['name' => 'LED Light', 'price' => 100.00, 'is_exclusive' => 1],
        ['name' => 'Copper Wire', 'price' => 20.00, 'is_exclusive' => 0]
    ];
    foreach ($items as $item) {
        $stmt = $pdo->prepare("INSERT INTO merchant_products (merchant_id, name, price, base_price, seller_sku, is_exclusive, status, is_available) VALUES (?, ?, ?, ?, ?, ?, 'Active', 1)");
        $stmt->execute([$mId, $item['name'], $item['price'], $item['price'], 'SKU-'.time().rand(10,99), $item['is_exclusive']]);
    }

    // 2. Clear old test orders
    $pdo->exec("DELETE FROM merchant_orders WHERE merchant_id = $mId");

    // 3. Setup logic variables
    $immediateDiscountRate = 0.10; // 10% off SRP for exclusive items
    $pointsRate = 0.008; // 0.8% back to subscriber
    $profitRate = 0.007; // 0.7% to Pridens profit

    // 4. Fetch the products we just made
    $stmt = $pdo->prepare("SELECT id, name, price, is_exclusive FROM merchant_products WHERE merchant_id = ?");
    $stmt->execute([$mId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orderScenarios = [
        ['sn' => 'ORD-JOHN-001', 'status' => 'Payment Sent', 'method' => 'Bank', 'shipping' => 0],
        ['sn' => 'ORD-TEST-002', 'status' => 'Unpaid', 'method' => 'COD', 'shipping' => 45],
        ['sn' => 'ORD-TEST-003', 'status' => 'To Ship', 'method' => 'E-Wallet', 'shipping' => 45]
    ];

    echo "Generating Precise Rule-Based orders (10% Disc, 0.8% Pts, 0.7% Profit)...\n";

    foreach ($orderScenarios as $scene) {
        $orderSrpTotal = 0;
        $orderExclusiveDiscount = 0;
        $shippingFee = $scene['shipping'];

        $checkoutItems = [];
        foreach ($products as $p) {
            // Replicate John's exact quantities for the first one
            $qty = ($scene['sn'] === 'ORD-JOHN-001') ? ($p['name'] === 'LED Light' ? 2 : 1) : rand(1, 2);
            
            $itemSrpSubtotal = $p['price'] * $qty;
            $itemDiscount = 0;
            
            if ($p['is_exclusive']) {
                $itemDiscount = $itemSrpSubtotal * $immediateDiscountRate;
            }

            $orderSrpTotal += $itemSrpSubtotal;
            $orderExclusiveDiscount += $itemDiscount;

            $checkoutItems[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'srp' => $p['price'],
                'qty' => $qty,
                'exclusive_discount' => $itemDiscount,
                'price_after_exclusive' => $p['price'] - ($itemDiscount / $qty)
            ];
        }

        $grandTotal = ($orderSrpTotal - $orderExclusiveDiscount) + $shippingFee;
        $subPoints = $grandTotal * $pointsRate;
        $priProfit = $grandTotal * $profitRate;

        // Insert order
        $stmt = $pdo->prepare("INSERT INTO merchant_orders 
            (order_sn, merchant_id, subscriber_id, total_amount, srp_total, exclusive_discount_amount, voucher_discount_amount, total_discount_deducted, points_earned, pridens_profit_amount, shipping_fee, status, payment_method, payment_proof_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $proof = ($scene['status'] === 'Payment Sent') ? 'uploads/qrcodes/dummy_receipt.jpg' : null;
        $stmt->execute([
            $scene['sn'], $mId, $sId, $grandTotal, $orderSrpTotal, 
            $orderExclusiveDiscount, 0, $orderExclusiveDiscount, 
            $subPoints, $priProfit, $shippingFee, $scene['status'], $scene['method'], $proof
        ]);
        $orderId = $pdo->lastInsertId();

        // Insert items
        foreach ($checkoutItems as $oi) {
            $istmt = $pdo->prepare("INSERT INTO merchant_order_items (order_id, product_id, name_snapshot, price, srp_snapshot, item_exclusive_discount, quantity, subtotal) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $istmt->execute([
                $orderId, 
                $oi['id'], 
                $oi['name'], 
                $oi['price_after_exclusive'], 
                $oi['srp'], 
                $oi['exclusive_discount'], 
                $oi['qty'], 
                $oi['price_after_exclusive'] * $oi['qty']
            ]);
        }
        echo "✓ OK: {$scene['sn']}. SRP: ₱$orderSrpTotal | Ded: ₱$orderExclusiveDiscount | Final: ₱$grandTotal | Pts: $subPoints\n";
    }

    echo "Done! Perfectly matched dummy data generated.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
