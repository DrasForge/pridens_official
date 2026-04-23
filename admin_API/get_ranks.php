<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM ranks ORDER BY level ASC");
    $ranks = $stmt->fetchAll();
    echo json_encode(['success' => true, 'ranks' => $ranks]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
