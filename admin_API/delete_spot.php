<?php
// admin_API/delete_spot.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

// Only handle POST safely
$data = json_decode(file_get_contents('php://input'), true);
$spotId = intval($data['spot_id'] ?? 0);
$merchantId = intval($data['merchant_id'] ?? 0);

if (!$spotId || !$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Missing parameters']);
    exit;
}

try {
    // Basic auth check
    $stmt = $pdo->prepare("SELECT id FROM merchant_spots WHERE id = ? AND merchant_id = ?");
    $stmt->execute([$spotId, $merchantId]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['status'=>'error', 'message'=>'Spot not found or access denied']);
        exit;
    }

    // Since merchant_spot_media is ON DELETE CASCADE, deleting spot drops media DB records.
    // However, we should try to wipe physical files so we don't leak space.
    $mediaStmt = $pdo->prepare("SELECT file_path FROM merchant_spot_media WHERE spot_id = ?");
    $mediaStmt->execute([$spotId]);
    while ($row = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['file_path']) && file_exists('../' . $row['file_path'])) {
            unlink('../' . $row['file_path']);
        }
    }

    $delStmt = $pdo->prepare("DELETE FROM merchant_spots WHERE id = ?");
    $delStmt->execute([$spotId]);

    echo json_encode(['status'=>'success', 'message'=>'Spot deleted']);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
