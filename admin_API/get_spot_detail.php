<?php
// admin_API/get_spot_detail.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$spotId = intval($_GET['spot_id'] ?? 0);
if (!$spotId) {
    echo json_encode(['status'=>'error', 'message'=>'Spot ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM merchant_spots WHERE id = ?");
    $stmt->execute([$spotId]);
    $spot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$spot) {
        echo json_encode(['status'=>'error', 'message'=>'Spot not found']);
        exit;
    }

    // Get all media for this spot
    $mediaStmt = $pdo->prepare("SELECT * FROM merchant_spot_media WHERE spot_id = ? ORDER BY id ASC");
    $mediaStmt->execute([$spotId]);
    $media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group media by section and type for easier consumption in front-end
    $groupedMedia = [
        'interior' => ['image' => [], 'video' => []],
        'exterior' => ['image' => [], 'video' => []]
    ];

    foreach ($media as $m) {
        $groupedMedia[$m['section']][$m['media_type']][] = $m;
    }

    $spot['media_groups'] = $groupedMedia;

    echo json_encode(['status' => 'success', 'data' => $spot]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
