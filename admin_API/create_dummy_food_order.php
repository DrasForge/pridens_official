<?php
// admin_API/create_dummy_food_order.php
require_once 'db.php';

try {
    $pdo->beginTransaction();

    // 1. Get a merchant
    $merchant = $pdo->query("SELECT id FROM merchants LIMIT 1")->fetch();
    if (!$merchant) throw new Exception("No merchants found");
    $mId = $merchant['id'];

    // 2. Get a subscriber
    $subscriber = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetch();
    if (!$subscriber) throw new Exception("No subscribers found");
    $sId = $subscriber['account_id'];

    // 3. Create a Food Category if not exists
    $pdo->prepare("INSERT INTO merchant_food_categories (merchant_id, name, description) VALUES (?, 'Best Sellers', 'Most ordered items')")->execute([$mId]);
    $catId = $pdo->lastInsertId();

    // 4. Create a Food Item
    $pdo->prepare("INSERT INTO merchant_foods (merchant_id, food_category_id, name, description, base_price, prep_time_mins, spicy_level, is_best_seller) 
                   VALUES (?, ?, 'Classic Double Cheeseburger', 'Two juicy patties with cheddar cheese', 180.00, 15, 0, 1)")->execute([$mId, $catId]);
    $foodId = $pdo->lastInsertId();

    // 5. Create Modifier Group & Option
    $pdo->prepare("INSERT INTO merchant_food_modifier_groups (food_id, name, min_selection, max_selection, is_required) VALUES (?, 'Add-ons', 0, 3, 0)")->execute([$foodId]);
    $groupId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO merchant_food_modifier_options (group_id, name, extra_price) VALUES (?, 'Extra Bacon', 45.00)")->execute([$groupId]);
    $pdo->prepare("INSERT INTO merchant_food_modifier_options (group_id, name, extra_price) VALUES (?, 'Extra Cheese', 20.00)")->execute([$groupId]);

    // 6. Create a Food Order
    $pdo->prepare("INSERT INTO merchant_food_orders (merchant_id, subscriber_id, status, total_amount, prep_notes) 
                   VALUES (?, ?, 'Pending', 245.00, 'Please make it extra well-done')")->execute([$mId, $sId]);
    $orderId = $pdo->lastInsertId();

    // 7. Add Item to Order
    $modifiers = [
        ['group' => 'Add-ons', 'option' => 'Extra Bacon', 'price' => 45.00],
        ['group' => 'Add-ons', 'option' => 'Extra Cheese', 'price' => 20.00]
    ];
    $pdo->prepare("INSERT INTO merchant_food_order_items (order_id, food_id, quantity, price, modifiers_json) VALUES (?, ?, 1, 180.00, ?)")->execute([$orderId, $foodId, json_encode($modifiers)]);

    $pdo->commit();
    echo "Successfully created dummy Food Order #$orderId for Merchant #$mId.\n";
    echo "You can now check the Kitchen Orders section to verify.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
