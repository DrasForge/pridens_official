<?php
// admin_API/get_team_eligibility.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    // 1. Eligible Leaders: Ranks with is_team_leader=1, AND agent doesn't already lead a team
    $eligibleLeaders = $pdo->query("
        SELECT a.agent_id, a.first_name, a.last_name, r.rank_name
        FROM agents a
        JOIN ranks r ON a.rank_id = r.id
        WHERE r.is_team_leader = 1
        AND a.agent_id NOT IN (SELECT leader_id FROM teams)
        ORDER BY a.last_name ASC
    ")->fetchAll();

    // 2. Potential Parent Teams
    $parentTeams = $pdo->query("SELECT id, team_name FROM teams ORDER BY team_name ASC")->fetchAll();

    echo json_encode([
        'status' => 'success',
        'leaders' => $eligibleLeaders,
        'parent_teams' => $parentTeams
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
