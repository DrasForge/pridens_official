<?php
// admin_API/update_merchant_status.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$id     = intval($input['merchant_id'] ?? 0);
$status = $input['status'] ?? '';
$reason = trim($input['rejection_reason'] ?? '');

if (!$id || !in_array($status, ['Active','Suspended','Rejected','Pending'])) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']); exit;
}

try {
    if ($status === 'Active') {
        $pdo->prepare("UPDATE merchants SET status='Active', verified_by=?, verified_at=NOW(), rejection_reason=NULL WHERE id=?")
            ->execute([$_SESSION['admin_id'], $id]);
    } elseif ($status === 'Rejected') {
        $pdo->prepare("UPDATE merchants SET status='Rejected', rejection_reason=? WHERE id=?")
            ->execute([$reason, $id]);
    } else {
        $pdo->prepare("UPDATE merchants SET status=? WHERE id=?")->execute([$status, $id]);
    }
    echo json_encode(['status'=>'success','message'=>"Merchant status updated to $status."]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
