<?php
require_once 'db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data or Plan ID missing']);
        exit;
    }
    
    $plan_id = $data['id'];
    $pdo->beginTransaction();
    
    // 1. Update Main Plan
    $sql = "UPDATE subscription_plans SET 
        plan_name = ?, 
        has_insurance = ?,
        insurance_coverage = ?, 
        payment_terms = ?, 
        subscription_term_months = ?, 
        insurance_term_months = ?, 
        contestability_period_days = ?,
        quota_weight = ?, 
        requires_beneficiaries = ?,
        onboarding_product_id = ?, 
        monthly_product_id = ?, 
        post_term_enabled = ?,
        post_term_product_id = ?,
        post_term_payment_cycle = ?,
        post_term_sku = ?,
        post_term_fee = ?,
        otc_l1 = ?, otc_l2 = ?, otc_l3 = ?, otc_l4 = ?, otc_l5 = ?,
        monthly_pvoucher = ?, monthly_merchant_handling = ?, monthly_points_rewards = ?, monthly_collection_fee = ?,
        residual_l1 = ?, residual_l2 = ?, residual_l3 = ?, residual_l4 = ?, climbs_insurance_premium = ?,
        card_theme = ?
        WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['plan_name'],
        isset($data['has_insurance']) ? ($data['has_insurance'] ? 1 : 0) : 1,
        $data['insurance_coverage'],
        $data['payment_terms'],
        $data['subscription_term'],
        $data['insurance_term'],
        $data['contestability_period_days'],
        $data['quota_weight'],
        $data['requires_beneficiaries'] ? 1 : 0,
        $data['onboarding_product_id'],
        $data['monthly_product_id'],
        $data['post_term_enabled'] ? 1 : 0,
        $data['post_term_product_id'] ?: null,
        $data['post_term_payment_cycle'] ?: null,
        $data['post_term_sku'] ?? null,
        $data['post_term_fee'] ?? 0,
        $data['otc_l1'] ?? 0, $data['otc_l2'] ?? 0, $data['otc_l3'] ?? 0, $data['otc_l4'] ?? 0, $data['otc_l5'] ?? 0,
        $data['monthly_pvoucher'] ?? 0, $data['monthly_merchant_handling'] ?? 0, $data['monthly_points_rewards'] ?? 0, $data['monthly_collection_fee'] ?? 0,
        $data['residual_l1'] ?? 0, $data['residual_l2'] ?? 0, $data['residual_l3'] ?? 0, $data['residual_l4'] ?? 0, $data['climbs_insurance_premium'] ?? 0,
        isset($data['card_theme']) ? json_encode($data['card_theme']) : null,
        $plan_id
    ]);
    
    // 2. Clear and Re-insert Position-Based Residuals
    $pdo->prepare("DELETE FROM plan_position_residuals WHERE plan_id = ?")->execute([$plan_id]);
    if (!empty($data['position_residuals'])) {
        $stmtRes = $pdo->prepare("INSERT INTO plan_position_residuals (plan_id, rank_name, amount) VALUES (?, ?, ?)");
        foreach ($data['position_residuals'] as $res) {
            if (!empty($res['rank'])) {
                $stmtRes->execute([$plan_id, $res['rank'], $res['amount']]);
            }
        }
    }
    
    // 3. Clear and Re-insert Insurance Benefits
    $pdo->prepare("DELETE FROM plan_insurance_benefits WHERE plan_id = ?")->execute([$plan_id]);
    if (!empty($data['insurance_benefits'])) {
        $stmtBen = $pdo->prepare("INSERT INTO plan_insurance_benefits (plan_id, benefit_name, amount, requires_contestability) VALUES (?, ?, ?, ?)");
        foreach ($data['insurance_benefits'] as $ben) {
            $stmtBen->execute([$plan_id, $ben['name'], $ben['amount'], $ben['contestability'] ? 1 : 0]);
        }
    }
    
    // 4. Clear and Re-insert Claim Requirements
    $pdo->prepare("DELETE FROM plan_claim_requirements WHERE plan_id = ?")->execute([$plan_id]);
    if (!empty($data['claim_requirements'])) {
        $stmtReq = $pdo->prepare("INSERT INTO plan_claim_requirements (plan_id, document_name) VALUES (?, ?)");
        foreach ($data['claim_requirements'] as $req) {
            if (!empty($req)) {
                $stmtReq->execute([$plan_id, $req]);
            }
        }
    }
    
    // 5. Clear and Re-insert Billing Brackets
    $pdo->prepare("DELETE FROM plan_billing_brackets WHERE plan_id = ?")->execute([$plan_id]);
    if (!empty($data['billing_brackets'])) {
        $stmtBra = $pdo->prepare("INSERT INTO plan_billing_brackets (plan_id, approval_from_day, approval_to_day, bill_on_day) VALUES (?, ?, ?, ?)");
        foreach ($data['billing_brackets'] as $bra) {
            $stmtBra->execute([$plan_id, $bra['from'], $bra['to'], $bra['bill_on']]);
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Subscription Plan updated successfully']);

} catch (\PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
