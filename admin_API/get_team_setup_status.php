<?php
// admin_API/get_team_setup_status.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    // Select agents who have a rank where is_team_leader = 1
    // BUT they are NOT a leader of any team in the 'teams' table.
    $sql = "
        SELECT 
            a.agent_id, 
            a.first_name, 
            a.last_name, 
            r.rank_name,
            a.created_at
        FROM agents a
        JOIN ranks r ON a.rank_id = r.id
        WHERE r.is_team_leader = 1
        AND a.agent_id NOT IN (SELECT leader_id FROM teams)
        ORDER BY a.created_at DESC
    ";
    
    $pending = $pdo->query($sql)->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $pending]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
