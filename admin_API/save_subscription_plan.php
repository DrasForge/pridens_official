<?php
require_once 'db.php';

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // 1. Insert Main Plan
    $sql = "INSERT INTO subscription_plans (
        plan_name, has_insurance, insurance_coverage, payment_terms, subscription_term_months, 
        insurance_term_months, contestability_period_days, quota_weight, requires_beneficiaries,
        onboarding_product_id, monthly_product_id, post_term_enabled, 
        post_term_product_id, post_term_payment_cycle, post_term_sku, post_term_fee,
        otc_l1, otc_l2, otc_l3, otc_l4, otc_l5,
        monthly_pvoucher, monthly_merchant_handling, monthly_points_rewards, monthly_collection_fee,
        residual_l1, residual_l2, residual_l3, residual_l4, climbs_insurance_premium,
        card_theme
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";
    
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
        $data['otc_l1'] ?? 0,
        $data['otc_l2'] ?? 0,
        $data['otc_l3'] ?? 0,
        $data['otc_l4'] ?? 0,
        $data['otc_l5'] ?? 0,
        $data['monthly_pvoucher'] ?? 0,
        $data['monthly_merchant_handling'] ?? 0,
        $data['monthly_points_rewards'] ?? 0,
        $data['monthly_collection_fee'] ?? 0,
        $data['residual_l1'] ?? 0,
        $data['residual_l2'] ?? 0,
        $data['residual_l3'] ?? 0,
        $data['residual_l4'] ?? 0,
        $data['climbs_insurance_premium'] ?? 0,
        isset($data['card_theme']) ? json_encode($data['card_theme']) : null
    ]);
    
    $plan_id = $pdo->lastInsertId();
    
    // 2. Insert Position-Based Residuals
    if (!empty($data['position_residuals'])) {
        $stmtRes = $pdo->prepare("INSERT INTO plan_position_residuals (plan_id, rank_name, amount) VALUES (?, ?, ?)");
        foreach ($data['position_residuals'] as $res) {
            if (!empty($res['rank'])) {
                $stmtRes->execute([$plan_id, $res['rank'], $res['amount']]);
            }
        }
    }
    
    // 3. Insert Insurance Benefits
    if (!empty($data['insurance_benefits'])) {
        $stmtBen = $pdo->prepare("INSERT INTO plan_insurance_benefits (plan_id, benefit_name, amount, requires_contestability) VALUES (?, ?, ?, ?)");
        foreach ($data['insurance_benefits'] as $ben) {
            $stmtBen->execute([$plan_id, $ben['name'], $ben['amount'], $ben['contestability'] ? 1 : 0]);
        }
    }
    
    // 4. Insert Claim Requirements
    if (!empty($data['claim_requirements'])) {
        $stmtReq = $pdo->prepare("INSERT INTO plan_claim_requirements (plan_id, document_name) VALUES (?, ?)");
        foreach ($data['claim_requirements'] as $req) {
            if (!empty($req)) {
                $stmtReq->execute([$plan_id, $req]);
            }
        }
    }
    
    // 5. Insert Billing Brackets
    if (!empty($data['billing_brackets'])) {
        $stmtBra = $pdo->prepare("INSERT INTO plan_billing_brackets (plan_id, approval_from_day, approval_to_day, bill_on_day) VALUES (?, ?, ?, ?)");
        foreach ($data['billing_brackets'] as $bra) {
            $stmtBra->execute([$plan_id, $bra['from'], $bra['to'], $bra['bill_on']]);
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Subscription Plan saved successfully']);

} catch (\PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
