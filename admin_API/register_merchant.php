<?php
// admin_API/register_merchant.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

$adminId = $_SESSION['admin_id'];

// ── Collect POST fields
$repFirst    = trim($_POST['rep_first_name'] ?? '');
$repLast     = trim($_POST['rep_last_name'] ?? '');
$repMiddle   = trim($_POST['rep_middle_name'] ?? '');
$repContact  = trim($_POST['representative_contact'] ?? '');
$repEmail    = trim($_POST['email'] ?? '');
$repPassword = $_POST['password'] ?? '';
$repIdType   = trim($_POST['rep_id_type'] ?? '');

$bizName     = trim($_POST['business_name'] ?? '');
$bizType     = trim($_POST['business_type'] ?? '');
$bizCat      = trim($_POST['business_category'] ?? '');
$bizTin      = trim($_POST['business_tin'] ?? '');
$bankName    = trim($_POST['bank_name'] ?? '');
$bankAccName = trim($_POST['bank_account_name'] ?? '');
$bankAccNum  = trim($_POST['bank_account_number'] ?? '');

$province    = trim($_POST['province'] ?? '');
$city        = trim($_POST['city_municipality'] ?? '');
$barangay    = trim($_POST['barangay'] ?? '');
$location    = trim($_POST['location'] ?? '');
$latitude    = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude   = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$bizEmail    = trim($_POST['business_email'] ?? '');
$bizContact  = trim($_POST['business_contact'] ?? '');
$openHours   = trim($_POST['opening_hours'] ?? '');

$storeName   = $bizName; // Can be customized later
$storeSlug   = strtolower(preg_replace('/[^a-z0-9]+/', '-', $storeName)) . '-' . time();

if (empty($repFirst) || empty($repLast) || empty($repEmail) || empty($repPassword) || empty($bizName)) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']); exit;
}

// Check email uniqueness
$chk = $pdo->prepare("SELECT id FROM merchant_users WHERE email = ?");
$chk->execute([$repEmail]);
if ($chk->fetchColumn()) {
    echo json_encode(['status' => 'error', 'message' => 'That email is already registered as a merchant account.']); exit;
}

try {
    $pdo->beginTransaction();

    // 1. Generate merchant code
    $lastCode = $pdo->query("SELECT merchant_code FROM merchants ORDER BY id DESC LIMIT 1")->fetchColumn();
    $nextNum  = $lastCode ? intval(substr($lastCode, 3)) + 1 : 1;
    $merchantCode = 'MR-' . str_pad($nextNum, 7, '0', STR_PAD_LEFT);

    // 2. Insert merchant
    $stmtM = $pdo->prepare("
        INSERT INTO merchants (
            merchant_code, business_type, business_name, store_name, store_slug,
            store_category, business_tin, business_email, business_contact,
            opening_hours, address_street, address_barangay, address_city,
            address_province, latitude, longitude,
            bank_name, bank_account_name, bank_account_number, status
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending')
    ");
    $stmtM->execute([
        $merchantCode, $bizType, $bizName, $storeName, $storeSlug,
        $bizCat, $bizTin, $bizEmail, $bizContact,
        $openHours, $location, $barangay, $city,
        $province, $latitude, $longitude,
        $bankName, $bankAccName, $bankAccNum
    ]);
    $merchantId = $pdo->lastInsertId();

    // 3. Insert owner
    $stmtO = $pdo->prepare("
        INSERT INTO merchant_owners (merchant_id, first_name, last_name, middle_name, contact_number, email, id_type)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmtO->execute([$merchantId, $repFirst, $repLast, $repMiddle, $repContact, $repEmail, $repIdType]);

    // 4. Save merchant login (password hashed)
    $pwdHash = password_hash($repPassword, PASSWORD_DEFAULT);
    $stmtU = $pdo->prepare("INSERT INTO merchant_users (merchant_id, email, password_hash) VALUES (?,?,?)");
    $stmtU->execute([$merchantId, $repEmail, $pwdHash]);

    // 5. Handle file uploads
    $uploadDir = __DIR__ . '/uploads/merchants/' . $merchantId . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    function saveUpload($fileKey, $dir, $prefix) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
        $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        $fname = $prefix . '_' . time() . '.' . $ext;
        $dest  = $dir . $fname;
        move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest);
        return 'uploads/merchants/' . basename(dirname($dest)) . '/' . $fname;
    }

    // ID photos
    $idFront = saveUpload('rep_id_front', $uploadDir, 'id_front');
    $idBack  = saveUpload('rep_id_back',  $uploadDir, 'id_back');
    if ($idFront || $idBack) {
        $pdo->prepare("UPDATE merchant_owners SET id_front_path=?, id_back_path=? WHERE merchant_id=?")
            ->execute([$idFront, $idBack, $merchantId]);
    }

    // Business documents
    $docTypes = ['dti_cert' => 'DTI/SEC Certificate', 'bir_cert' => 'BIR Certificate', 'business_permit' => "Mayor's Business Permit"];
    foreach ($docTypes as $field => $label) {
        $path = saveUpload($field, $uploadDir, $field);
        if ($path) {
            $pdo->prepare("INSERT INTO merchant_documents (merchant_id, doc_type, file_path) VALUES (?,?,?)")
                ->execute([$merchantId, $label, $path]);
        }
    }

    // Store logo & banner
    $logo   = saveUpload('store_logo',   $uploadDir, 'logo');
    $banner = saveUpload('store_banner', $uploadDir, 'banner');
    if ($logo || $banner) {
        $pdo->prepare("UPDATE merchants SET store_logo=?, store_banner=? WHERE id=?")->execute([$logo, $banner, $merchantId]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Merchant registered successfully!', 'merchant_code' => $merchantCode]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
