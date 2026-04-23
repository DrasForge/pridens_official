<?php
// admin_API/process_promotion_approval.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['promotion_id']) || empty($input['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit();
}

try {
    $promoId = $input['promotion_id'];
    $action = $input['action']; // 'approve' or 'reject'
    $adminId = $_SESSION['admin_id'] ?? 1; // Fallback to 1 if not explicitly set but logged in
    
    // Fetch promotion details
    $stmt = $pdo->prepare("SELECT * FROM agent_promotions WHERE id = ? AND status = 'Pending'");
    $stmt->execute([$promoId]);
    $promo = $stmt->fetch();
    
    if (!$promo) {
        echo json_encode(['status' => 'error', 'message' => 'Promotion request not found or already processed.']);
        exit();
    }
    
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // Update Agent Rank and reset promoted_at
        $agentStmt = $pdo->prepare("UPDATE agents SET rank_id = ?, rank_promoted_at = CURRENT_TIMESTAMP WHERE agent_id = ?");
        $agentStmt->execute([$promo['target_rank_id'], $promo['agent_id']]);
        
        // Mark promotion as Approved
        $updStmt = $pdo->prepare("UPDATE agent_promotions SET status = 'Approved', reviewed_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updStmt->execute([$adminId, $promoId]);
        
        $msg = "Promotion approved successfully.";
    } else {
        // Mark promotion as Rejected
        $updStmt = $pdo->prepare("UPDATE agent_promotions SET status = 'Rejected', reviewed_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updStmt->execute([$adminId, $promoId]);
        $msg = "Promotion rejected.";
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => $msg]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
