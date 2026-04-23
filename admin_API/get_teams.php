<?php
// admin_API/get_teams.php
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
            t.*, 
            CONCAT(a.first_name, ' ', a.last_name) AS leader_name,
            r.rank_name AS leader_rank,
            tp.team_name AS parent_team_name,
            (SELECT COUNT(*) FROM agents WHERE team_id = t.id) AS member_count
        FROM teams t
        JOIN agents a ON t.leader_id = a.agent_id
        JOIN ranks r ON a.rank_id = r.id
        LEFT JOIN teams tp ON t.parent_team_id = tp.id
        ORDER BY t.created_at DESC
    ";
    
    $teams = $pdo->query($sql)->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $teams]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
