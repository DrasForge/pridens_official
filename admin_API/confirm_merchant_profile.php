<?php
// admin_API/confirm_merchant_profile.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized']);
    exit;
}

$mId = intval($_POST['merchant_id'] ?? 0);
if (!$mId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE merchants SET profile_last_updated = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$mId]);
    
    echo json_encode(['status'=>'success', 'message'=>'Profile confirmed']);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
