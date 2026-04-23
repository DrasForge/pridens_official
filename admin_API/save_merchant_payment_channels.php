<?php
// admin_API/save_merchant_payment_channels.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_POST['merchant_id'] ?? 0);
$type = $_POST['type'] ?? ''; // 'Bank' or 'E-Wallet'
$providerName = $_POST['provider_name'] ?? '';
$accountName = $_POST['account_name'] ?? '';
$accountNumber = $_POST['account_number'] ?? '';
$isEnabled = intval($_POST['is_enabled'] ?? 1);
$channelId = intval($_POST['channel_id'] ?? 0); // 0 for new, >0 for update

if (!$merchantId || !$type || !$providerName) {
    echo json_encode(['status'=>'error', 'message'=>'Missing required fields']);
    exit;
}

try {
    $qrCodePath = $_POST['existing_qr_code_path'] ?? null;

    // Handle QR Code Upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/qrcodes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $extension = pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_qr_' . $merchantId . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $targetFile)) {
            $qrCodePath = $targetFile;
        }
    }

    if ($channelId > 0) {
        // Update
        $stmt = $pdo->prepare("UPDATE merchant_payment_channels SET 
                               provider_name = ?, account_name = ?, account_number = ?, 
                               qr_code_path = ?, is_enabled = ?, updated_at = NOW() 
                               WHERE id = ? AND merchant_id = ?");
        $stmt->execute([$providerName, $accountName, $accountNumber, $qrCodePath, $isEnabled, $channelId, $merchantId]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO merchant_payment_channels 
                               (merchant_id, type, provider_name, account_name, account_number, qr_code_path, is_enabled) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$merchantId, $type, $providerName, $accountName, $accountNumber, $qrCodePath, $isEnabled]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Payment channel saved successfully']);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
