<?php
// admin_API/delete_service.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$serviceId = intval($data['service_id'] ?? 0);
$merchantId = intval($data['merchant_id'] ?? 0);

if (!$serviceId || !$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Missing parameters']);
    exit;
}

try {
    // Auth check
    $stmt = $pdo->prepare("SELECT id FROM merchant_services WHERE id = ? AND merchant_id = ?");
    $stmt->execute([$serviceId, $merchantId]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['status'=>'error', 'message'=>'Access denied']);
        exit;
    }

    // Wipe physical files
    $media = $pdo->prepare("SELECT file_path FROM merchant_service_media WHERE service_id = ?");
    $media->execute([$serviceId]);
    while ($m = $media->fetch(PDO::FETCH_ASSOC)) {
        if (file_exists('../' . $m['file_path'])) unlink('../' . $m['file_path']);
    }
    
    // DB records (Cascade handled by DB)
    $pdo->prepare("DELETE FROM merchant_services WHERE id = ?")->execute([$serviceId]);

    echo json_encode(['status' => 'success', 'message' => 'Service deleted']);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
