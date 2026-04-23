<?php
// point_of_sale_API/checkout.php
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Get Input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid cart data']);
    exit;
}

// Normalize inputs
$agent_referral_code = !empty(trim($input['agent_referral_code'] ?? '')) ? trim($input['agent_referral_code']) : null;
$subscriber_account_id = !empty(trim($input['subscriber_account_id'] ?? '')) ? trim($input['subscriber_account_id']) : null;

try {
    $pdo->beginTransaction();

    // 1. Verify open shift
    $stmt = $pdo->prepare("SELECT id FROM `pos_shifts` WHERE `admin_id` = ? AND `status` = 'Open' LIMIT 1");
    $stmt->execute([$admin_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$shift) {
        throw new Exception("No open shift found. Please open a shift first.");
    }
    $shift_id = $shift['id'];

    // 2. Calculate totals and CHECK STOCK
    $subtotal = 0;
    foreach ($input['cart'] as $item) {
        $stmtP = $pdo->prepare("SELECT name, price, stock_quantity, category FROM pos_products WHERE id = ?");
        $stmtP->execute([$item['id']]);
        $dbProduct = $stmtP->fetch(PDO::FETCH_ASSOC);
        if(!$dbProduct) throw new Exception("Product ID {$item['id']} not found.");
        
        // Stock Check: Only check for categories that are likely physical products
        // (Assuming Fees/Applications might not use stock, but let's check everything by default if it's in the table)
        if ($dbProduct['stock_quantity'] < intval($item['qty'])) {
            throw new Exception("Insufficient stock for '{$dbProduct['name']}'. Available: {$dbProduct['stock_quantity']}");
        }

        $subtotal += floatval($dbProduct['price']) * intval($item['qty']);
    }

    $discount_type = $input['discount_type'] ?? 'Regular';
    $discount_amount = 0;
    if ($discount_type === 'Senior' || $discount_type === 'PWD') {
        // Flat 20% discount against non-VAT item for simple BIR formula
        $discount_amount = $subtotal * 0.20;
    }
    $grand_total = $subtotal - $discount_amount;
    $tendered = floatval($input['tendered_amount']);
    if ($tendered < $grand_total) {
        throw new Exception("Tendered amount is less than total.");
    }
    $change = $tendered - $grand_total;

    // 3. Generate receipt number and accumulate grand total
    $stmtSys = $pdo->query("SELECT * FROM pos_system_settings LIMIT 1 FOR UPDATE");
    $settings = $stmtSys->fetch(PDO::FETCH_ASSOC);

    $next_receipt = intval($settings['last_receipt_number']) + 1;
    $receipt_string = str_pad($next_receipt, 6, "0", STR_PAD_LEFT);
    
    $new_grand_total = floatval($settings['accumulated_grand_total']) + $grand_total;
    
    $pdo->prepare("UPDATE pos_system_settings SET last_receipt_number = ?, accumulated_grand_total = ?")->execute([$next_receipt, $new_grand_total]);

    // 4. Insert transaction (now with subscriber_account_id)
    $stmtT = $pdo->prepare("INSERT INTO pos_transactions 
        (receipt_number, shift_id, customer_last_name, customer_first_name, customer_middle_name, agent_referral_code, subscriber_account_id, discount_type, subtotal, discount_amount, grand_total, payment_method, tendered_amount, change_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cash', ?, ?)");
    
    $stmtT->execute([
        $receipt_string, 
        $shift_id, 
        $input['customer_last_name'], 
        $input['customer_first_name'], 
        $input['customer_middle_name'], 
        $agent_referral_code, 
        $subscriber_account_id,
        $discount_type, 
        $subtotal, 
        $discount_amount, 
        $grand_total, 
        $tendered, 
        $change
    ]);
    
    $transaction_id = $pdo->lastInsertId();

    // 5. Insert transaction items & DEDUCT STOCK
    $hasSubscriptionItem = false;
    foreach ($input['cart'] as $item) {
        $stmtP = $pdo->prepare("SELECT name, price, base_price, category FROM pos_products WHERE id = ?");
        $stmtP->execute([$item['id']]);
        $prod = $stmtP->fetch(PDO::FETCH_ASSOC);
        
        $qty = intval($item['qty']);
        $itemSubtotal = floatval($prod['price']) * $qty;

        // Insert item record
        $stmtItems = $pdo->prepare("INSERT INTO pos_transaction_items (transaction_id, product_id, product_name_snapshot, price_snapshot, cost_price_snapshot, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtItems->execute([$transaction_id, $item['id'], $prod['name'], $prod['price'], $prod['base_price'], $qty, $itemSubtotal]);

        // Deduct Stock
        $pdo->prepare("UPDATE pos_products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $item['id']]);

        // Log Inventory Movement
        $logStmt = $pdo->prepare("INSERT INTO pos_inventory_logs (product_id, change_amount, action_type, admin_id, notes) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([$item['id'], -$qty, 'Sale', $admin_id, "Sale SI# {$receipt_string}"]);

        // Detect if cart includes a subscription monthly product
        if ($prod['category'] === 'Subscriptions') {
            $hasSubscriptionItem = true;
        }
    }


    // 6. Update Shift Totals
    $pdo->prepare("UPDATE pos_shifts SET total_sales = total_sales + ?, total_discounts = total_discounts + ? WHERE id = ?")->execute([$grand_total, $discount_amount, $shift_id]);

    // 7. MONTHLY COMMISSION DISTRIBUTION
    if ($hasSubscriptionItem && $subscriber_account_id) {
        // Fetch subscriber's plan, billing_day, and referral_code (direct agent)
        $stmtSub = $pdo->prepare("SELECT plan_id, billing_day, referral_code FROM subscribers WHERE account_id = ? LIMIT 1");
        $stmtSub->execute([$subscriber_account_id]);
        $subInfo = $stmtSub->fetch(PDO::FETCH_ASSOC);

        if ($subInfo && $subInfo['plan_id'] && $subInfo['referral_code']) {
            // Determine on-time: payment must be on or within 3 days after billing_day
            $todayDay = intval(date('j')); // day of month
            $billingDay = intval($subInfo['billing_day'] ?? 0);
            $isOnTime = false;
            if ($billingDay > 0) {
                $diff = $todayDay - $billingDay;
                // Handle month-end edge cases: if billing day is later in month, diff can be negative
                if ($diff >= 0 && $diff <= 3) {
                    $isOnTime = true;
                }
            }

            require_once '../admin_API/commission_engine.php';
            $engine = new CommissionEngine($pdo);
            $engine->distributeMonthly(
                $subscriber_account_id,
                $transaction_id,
                $subInfo['plan_id'],
                $isOnTime,
                $subInfo['referral_code']
            );
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'receipt_number' => $receipt_string, 'change' => $change]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
