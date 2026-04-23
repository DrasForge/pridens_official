<?php
// admin_API/upload_insurance_doc.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['document']) || empty($_POST['account_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$accountId = $_POST['account_id'];
$file = $_FILES['document'];

// Check if subscriber is active
$stmtCheck = $pdo->prepare("SELECT subscription_status FROM subscribers WHERE account_id = ?");
$stmtCheck->execute([$accountId]);
$subStatus = $stmtCheck->fetchColumn();

if ($subStatus !== 'Active') {
    echo json_encode(['status' => 'error', 'message' => 'Subscriber account must be officially approved (Active) before uploading documents.']);
    exit();
}

// Check file type
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Only PDF, JPG, and PNG are allowed']);
    exit();
}

$uploadDir = 'uploads/insurance_docs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = $accountId . '_insurance_' . time() . '.' . $extension;
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    try {
        $pdo->prepare("UPDATE subscribers SET insurance_document_path = ?, insurance_ready_for_approval = 1 WHERE account_id = ?")
            ->execute([$targetPath, $accountId]);
        
        echo json_encode(['status' => 'success', 'message' => 'Document uploaded successfully', 'path' => $targetPath]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
}
?>
