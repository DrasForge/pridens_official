<?php
// admin_API/save_merchant_profile.php
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
    $pdo->beginTransaction();

    $storeName = $_POST['store_name'] ?? '';
    $storeSlug = $_POST['store_slug'] ?? '';
    $storeDesc = $_POST['store_description'] ?? '';
    
    $email1 = $_POST['business_email'] ?? '';
    $email2 = $_POST['business_email_2'] ?? '';
    $email3 = $_POST['business_email_3'] ?? '';
    
    $contact1 = $_POST['business_contact'] ?? '';
    $contact2 = $_POST['business_contact_2'] ?? '';
    $contact3 = $_POST['business_contact_3'] ?? '';

    $lat = floatval($_POST['latitude'] ?? 0);
    $lng = floatval($_POST['longitude'] ?? 0);
    $hours = $_POST['opening_hours'] ?? '{}';

    $uploadDir = '../uploads/merchants/' . $mId . '/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    // Identity update
    $sql = "UPDATE merchants SET 
            store_name = ?, 
            store_slug = ?, 
            store_description = ?,
            business_email = ?,
            business_email_2 = ?,
            business_email_3 = ?,
            business_contact = ?,
            business_contact_2 = ?,
            business_contact_3 = ?,
            latitude = ?,
            longitude = ?,
            opening_hours = ?,
            profile_last_updated = CURRENT_TIMESTAMP
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $storeName, $storeSlug, $storeDesc,
        $email1, $email2, $email3,
        $contact1, $contact2, $contact3,
        $lat, $lng, $hours,
        $mId
    ]);

    // Media (Logo & Banner)
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === 0) {
        $ext = pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['store_logo']['tmp_name'], $uploadDir . $filename);
        $path = 'uploads/merchants/' . $mId . '/' . $filename;
        $pdo->prepare("UPDATE merchants SET store_logo = ? WHERE id = ?")->execute([$path, $mId]);
    }

    if (isset($_FILES['store_banner']) && $_FILES['store_banner']['error'] === 0) {
        $ext = pathinfo($_FILES['store_banner']['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['store_banner']['tmp_name'], $uploadDir . $filename);
        $path = 'uploads/merchants/' . $mId . '/' . $filename;
        $pdo->prepare("UPDATE merchants SET store_banner = ? WHERE id = ?")->execute([$path, $mId]);
    }

    // Documents
    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'doc_') === 0 && $file['error'] === 0) {
            $docType = str_replace('_', ' ', substr($key, 4));
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'doc_' . time() . '_' . str_replace(' ', '_', $docType) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $path = 'uploads/merchants/' . $mId . '/' . $filename;

            // Check if doc type exists to update, else insert
            $check = $pdo->prepare("SELECT id FROM merchant_documents WHERE merchant_id = ? AND doc_type = ?");
            $check->execute([$mId, $docType]);
            if ($row = $check->fetch()) {
                $pdo->prepare("UPDATE merchant_documents SET file_path = ?, uploaded_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$path, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO merchant_documents (merchant_id, doc_type, file_path) VALUES (?, ?, ?)")
                    ->execute([$mId, $docType, $path]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Profile synchronized']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
