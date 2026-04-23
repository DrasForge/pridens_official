<?php
// admin_API/get_service_detail.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$serviceId = intval($_GET['service_id'] ?? 0);
if (!$serviceId) {
    echo json_encode(['status'=>'error', 'message'=>'Service ID missing']);
    exit;
}

try {
    // 1. Core Service Info
    $stmt = $pdo->prepare("SELECT * FROM merchant_services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        echo json_encode(['status'=>'error', 'message'=>'Service not found']);
        exit;
    }

    // 2. Inclusions
    $incStmt = $pdo->prepare("SELECT content FROM merchant_service_inclusions WHERE service_id = ?");
    $incStmt->execute([$serviceId]);
    $service['inclusions'] = $incStmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Media Hub
    $mediaStmt = $pdo->prepare("SELECT * FROM merchant_service_media WHERE service_id = ? ORDER BY section, slot_index");
    $mediaStmt->execute([$serviceId]);
    $media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

    $groupedMedia = ['samples' => [], 'process' => [], 'requirements' => []];
    foreach ($media as $m) {
        $groupedMedia[$m['section']][] = $m;
    }
    $service['media_hub'] = $groupedMedia;

    echo json_encode(['status' => 'success', 'data' => $service]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
