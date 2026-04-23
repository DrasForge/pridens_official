<?php
// admin_API/get_pending_promotions.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    $sql = "
        SELECT 
            p.id as promotion_id,
            a.agent_id,
            a.first_name,
            a.last_name,
            a.created_at as agent_since,
            r_curr.rank_name as current_rank,
            r_targ.rank_name as target_rank,
            p.created_at as requested_at
        FROM agent_promotions p
        JOIN agents a ON p.agent_id = a.agent_id
        JOIN ranks r_curr ON p.current_rank_id = r_curr.id
        JOIN ranks r_targ ON p.target_rank_id = r_targ.id
        WHERE p.status = 'Pending'
        ORDER BY p.created_at ASC
    ";
    
    $promotions = $pdo->query($sql)->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $promotions]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
