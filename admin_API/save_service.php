<?php
// admin_API/save_service.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_POST['merchant_id'] ?? 0);
$serviceId = intval($_POST['service_id'] ?? 0);

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $pdo->beginTransaction();

    $name = $_POST['service_name'] ?? '';
    $tagline = substr($_POST['tagline'] ?? '', 0, 50);
    $desc = $_POST['description'] ?? '';
    $catId = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 60);
    $status = $_POST['status'] ?? 'active';
    $serviceMethod = $_POST['service_method'] ?? 'In-Store';
    $paymentMethods = $_POST['payment_methods'] ?? 'Online';
    $isExclusive = isset($_POST['is_exclusive']) ? 1 : 0;
    $exclusivePercentage = floatval($_POST['exclusive_percentage'] ?? 0);

    $uploadDir = '../uploads/services/' . $merchantId . '/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    // Cover Photo
    $coverPath = $_POST['existing_cover'] ?? '';
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
        $fname = time() . '_cv_' . $_FILES['cover_photo']['name'];
        move_uploaded_file($_FILES['cover_photo']['tmp_name'], $uploadDir . $fname);
        $coverPath = 'uploads/services/' . $merchantId . '/' . $fname;
    }

    if ($serviceId === 0) {
        $sql = "INSERT INTO merchant_services (merchant_id, service_name, tagline, description, category_id, price, duration_minutes, cover_photo, status, service_method, payment_methods, is_exclusive, exclusive_percentage) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$merchantId, $name, $tagline, $desc, $catId ?: null, $price, $duration, $coverPath, $status, $serviceMethod, $paymentMethods, $isExclusive, $exclusivePercentage]);
        $serviceId = $pdo->lastInsertId();
    } else {
        $sql = "UPDATE merchant_services SET service_name=?, tagline=?, description=?, category_id=?, price=?, duration_minutes=?, cover_photo=?, status=?, service_method=?, payment_methods=?, is_exclusive=?, exclusive_percentage=? WHERE id=? AND merchant_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $tagline, $desc, $catId ?: null, $price, $duration, $coverPath, $status, $serviceMethod, $paymentMethods, $isExclusive, $exclusivePercentage, $serviceId, $merchantId]);
    }

    // Handle Inclusions (Dynamic list)
    $inclusions = json_decode($_POST['inclusions'] ?? '[]', true);
    $pdo->prepare("DELETE FROM merchant_service_inclusions WHERE service_id = ?")->execute([$serviceId]);
    if (is_array($inclusions)) {
        $insStmt = $pdo->prepare("INSERT INTO merchant_service_inclusions (service_id, content) VALUES (?, ?)");
        foreach ($inclusions as $item) {
            if (trim($item)) $insStmt->execute([$serviceId, trim($item)]);
        }
    }

    // Handle Media Hub (Slots based)
    // We expect sections like 'samples' (idx 0-5), 'process' (idx 0-2)
    $mediaSections = [
        'samples' => 6,
        'process' => 3,
        'requirements' => 3
    ];

    foreach ($mediaSections as $sec => $limit) {
        for ($i=0; $i<$limit; $i++) {
            $fKey = "media_{$sec}_{$i}";
            $existing = $_POST["existing_media_{$sec}_{$i}"] ?? '';
            $caption = $_POST["caption_{$sec}_{$i}"] ?? '';
            $path = $existing;

            if (isset($_FILES[$fKey]) && $_FILES[$fKey]['error'] == 0) {
                $fname = time() . "_{$sec}_{$i}_" . $_FILES[$fKey]['name'];
                move_uploaded_file($_FILES[$fKey]['tmp_name'], $uploadDir . $fname);
                $path = 'uploads/services/' . $merchantId . '/' . $fname;
            }

            if ($path) {
                $check = $pdo->prepare("SELECT id FROM merchant_service_media WHERE service_id=? AND section=? AND slot_index=?");
                $check->execute([$serviceId, $sec, $i]);
                $rowId = $check->fetchColumn();

                if ($rowId) {
                    $pdo->prepare("UPDATE merchant_service_media SET file_path=?, caption=? WHERE id=?")->execute([$path, $caption, $rowId]);
                } else {
                    $pdo->prepare("INSERT INTO merchant_service_media (service_id, section, media_type, slot_index, file_path, caption) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$serviceId, $sec, 'image', $i, $path, $caption]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Service saved successfully', 'service_id' => $serviceId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
