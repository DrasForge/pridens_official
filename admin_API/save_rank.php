<?php
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['rank_name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    if (isset($data['id']) && !empty($data['id'])) {
        // Update
        $sql = "UPDATE ranks SET rank_name = ?, level = ?, is_auto = ?, is_entry = ?, has_clusters = ?, has_incentives = ?, rules_count = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['rank_name'],
            $data['level'],
            $data['is_auto'] ? 1 : 0,
            $data['is_entry'] ? 1 : 0,
            $data['has_clusters'] ? 1 : 0,
            $data['has_incentives'] ? 1 : 0,
            $data['rules_count'] ?? 0,
            $data['id']
        ]);
    } else {
        // Insert
        $sql = "INSERT INTO ranks (rank_name, level, is_auto, is_entry, has_clusters, has_incentives, rules_count) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['rank_name'],
            $data['level'],
            $data['is_auto'] ? 1 : 0,
            $data['is_entry'] ? 1 : 0,
            $data['has_clusters'] ? 1 : 0,
            $data['has_incentives'] ? 1 : 0,
            $data['rules_count'] ?? 0
        ]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
