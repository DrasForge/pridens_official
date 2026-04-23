<?php
require_once 'db.php';

try {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Plan ID required']);
        exit;
    }
    
    $plan_id = $_GET['id'];
    
    // 1. Fetch Main Plan with SKU strings
    $sql = "SELECT sp.*, 
            p1.sku AS onboarding_sku, 
            p2.sku AS monthly_sku, 
            p3.sku AS post_term_sku_ref
            FROM subscription_plans sp
            LEFT JOIN pos_products p1 ON sp.onboarding_product_id = p1.id
            LEFT JOIN pos_products p2 ON sp.monthly_product_id = p2.id
            LEFT JOIN pos_products p3 ON sp.post_term_product_id = p3.id
            WHERE sp.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found']);
        exit;
    }
    
    // 2. Fetch Position Residuals
    $stmt = $pdo->prepare("SELECT rank_name as rank, amount FROM plan_position_residuals WHERE plan_id = ?");
    $stmt->execute([$plan_id]);
    $plan['position_residuals'] = $stmt->fetchAll();
    
    // 3. Fetch Insurance Benefits
    $stmt = $pdo->prepare("SELECT benefit_name as name, amount, requires_contestability as contestability FROM plan_insurance_benefits WHERE plan_id = ?");
    $stmt->execute([$plan_id]);
    $plan['insurance_benefits'] = $stmt->fetchAll();
    
    // 4. Fetch Claim Requirements
    $stmt = $pdo->prepare("SELECT document_name FROM plan_claim_requirements WHERE plan_id = ?");
    $stmt->execute([$plan_id]);
    $plan['claim_requirements'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 5. Fetch Billing Brackets
    $stmt = $pdo->prepare("SELECT approval_from_day as `from`, approval_to_day as `to`, bill_on_day as bill_on FROM plan_billing_brackets WHERE plan_id = ?");
    $stmt->execute([$plan_id]);
    $plan['billing_brackets'] = $stmt->fetchAll();
    
    // Parse JSON fields
    if (isset($plan['card_theme'])) {
        $plan['card_theme'] = json_decode($plan['card_theme'], true);
    }
    
    echo json_encode(['success' => true, 'plan' => $plan]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
