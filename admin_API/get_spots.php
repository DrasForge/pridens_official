<?php
// admin_API/get_spots.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    // 1. Get total spots logic for limits validation
    $stmt = $pdo->prepare("SELECT id, spot_name, spot_tagline, spot_cover, barangay, spot_profile, created_at, IFNULL(spot_cover, '') as image_path FROM merchant_spots WHERE merchant_id = ? ORDER BY created_at DESC");
    $stmt->execute([$merchantId]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Let's populate media counts to show on the dashboard card
    foreach ($spots as &$spot) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM merchant_spot_media WHERE spot_id = ?");
        $countStmt->execute([$spot['id']]);
        $spot['media_count'] = $countStmt->fetchColumn();
    }

    echo json_encode([
        'status' => 'success', 
        'data' => $spots,
        'count' => count($spots),
        'max_spots' => 1
    ]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
