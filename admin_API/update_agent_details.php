<?php
// admin_API/update_agent_details.php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['agent_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit();
}

try {
    // Check if rank is changing to reset rank_promoted_at
    $stmtRank = $pdo->prepare("SELECT rank_id FROM agents WHERE agent_id = ?");
    $stmtRank->execute([$input['agent_id']]);
    $currentRank = $stmtRank->fetchColumn();

    $rankPromotedSql = "";
    if ($currentRank != $input['rank_id']) {
        $rankPromotedSql = ", rank_promoted_at = CURRENT_TIMESTAMP";
    }

    $stmt = $pdo->prepare("
        UPDATE agents SET 
            first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            birthdate = ?, 
            gender = ?, 
            marital_status = ?, 
            occupation = ?, 
            address = ?, 
            barangay = ?, 
            city = ?, 
            province = ?, 
            rank_id = ?, 
            status = ?,
            agent_position = ?
            $rankPromotedSql
        WHERE agent_id = ?
    ");

    $stmt->execute([
        $input['first_name'],
        $input['middle_name'] ?? null,
        $input['last_name'],
        $input['email'],
        $input['phone'] ?? null,
        $input['birthdate'] ?? null,
        $input['gender'] ?? null,
        $input['marital_status'] ?? null,
        $input['occupation'] ?? null,
        $input['address'] ?? null,
        $input['barangay'] ?? null,
        $input['city'] ?? null,
        $input['province'] ?? null,
        $input['rank_id'] ?? null,
        $input['status'] ?? 'Active',
        $input['agent_position'] ?? 'Sales Agent',
        $input['agent_id']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Agent updated successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
