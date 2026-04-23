<?php
// admin_API/save_team.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    $id = $_POST['id'] ?? null;
    $team_name = $_POST['team_name'] ?? '';
    $team_goal = $_POST['team_goal'] ?? '';
    $leader_id = $_POST['leader_id'] ?? '';
    $parent_team_id = !empty($_POST['parent_team_id']) ? $_POST['parent_team_id'] : null;

    if (empty($team_name) || empty($leader_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Team name and leader are required.']);
        exit();
    }

    // Handle Logo Upload
    $logoPath = $_POST['existing_logo'] ?? null;
    if (!empty($_FILES['team_logo']['name'])) {
        $uploadDir = '../admin_folder/uploads/team_logos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = pathinfo($_FILES['team_logo']['name'], PATHINFO_EXTENSION);
        $fileName = 'team_' . time() . '_' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['team_logo']['tmp_name'], $targetPath)) {
            $logoPath = 'uploads/team_logos/' . $fileName;
        }
    }

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, team_goal = ?, team_logo = ?, leader_id = ?, parent_team_id = ? WHERE id = ?");
        $stmt->execute([$team_name, $team_goal, $logoPath, $leader_id, $parent_team_id, $id]);
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_goal, team_logo, leader_id, parent_team_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$team_name, $team_goal, $logoPath, $leader_id, $parent_team_id]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Team saved successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
