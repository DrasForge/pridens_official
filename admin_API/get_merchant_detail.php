<?php
// admin_API/get_merchant_detail.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID required']);
    exit;
}

try {
    // Basic Details
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE id = ?");
    $stmt->execute([$id]);
    $merchant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$merchant) {
        echo json_encode(['status'=>'error', 'message'=>'Merchant not found']);
        exit;
    }

    // Owner Details
    $stmtO = $pdo->prepare("SELECT * FROM merchant_owners WHERE merchant_id = ?");
    $stmtO->execute([$id]);
    $merchant['owner'] = $stmtO->fetch(PDO::FETCH_ASSOC);

    // Login Details
    $stmtU = $pdo->prepare("SELECT email, created_at, last_login FROM merchant_users WHERE merchant_id = ?");
    $stmtU->execute([$id]);
    $merchant['user'] = $stmtU->fetch(PDO::FETCH_ASSOC);

    // Documents
    $stmtD = $pdo->prepare("SELECT doc_type, file_path, uploaded_at FROM merchant_documents WHERE merchant_id = ?");
    $stmtD->execute([$id]);
    $merchant['documents'] = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$merchant]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
