<?php
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY id DESC");
    $plans = array_map(function($plan) {
        if (!empty($plan['card_theme'])) {
            $plan['card_theme'] = json_decode($plan['card_theme'], true);
        }
        return $plan;
    }, $stmt->fetchAll());
    
    echo json_encode(['success' => true, 'plans' => $plans]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
