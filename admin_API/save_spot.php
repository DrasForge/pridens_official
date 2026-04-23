<?php
// admin_API/save_spot.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_POST['merchant_id'] ?? 0);
$spotId = intval($_POST['spot_id'] ?? 0); // 0 means new

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($spotId === 0) {
        // Check limit for new spots only - limited to exactly 1
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM merchant_spots WHERE merchant_id = ?");
        $stmt->execute([$merchantId]);
        if ($stmt->fetchColumn() >= 1) {
            $pdo->rollBack();
            echo json_encode(['status'=>'error', 'message'=>'Only one spot is allowed per merchant.']);
            exit;
        }
    }

    // Fetch Merchant fallback data for Location & Hours
    $merDataStmt = $pdo->prepare("SELECT opening_hours, address_barangay, latitude, longitude FROM merchants WHERE id = ?");
    $merDataStmt->execute([$merchantId]);
    $merData = $merDataStmt->fetch();

    $spotName = $_POST['spot_name'] ?? '';
    $spotTagline = substr($_POST['spot_tagline'] ?? '', 0, 50);
    $spotDesc = $_POST['spot_description'] ?? '';
    $barangay = $merData['address_barangay'] ?? '';
    $latitude = floatval($merData['latitude'] ?? 0);
    $longitude = floatval($merData['longitude'] ?? 0);
    $operatingHours = $merData['opening_hours'] ?? '{}';
    $amenities = $_POST['amenities'] ?? '[]';

    $uploadDir = '../uploads/spots/' . $merchantId . '/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    // Profile & Cover
    $profilePath = $_POST['existing_profile'] ?? '';
    $coverPath = $_POST['existing_cover'] ?? '';

    if (isset($_FILES['spot_profile']) && $_FILES['spot_profile']['error'] == 0) {
        $name = time() . '_p_' . $_FILES['spot_profile']['name'];
        move_uploaded_file($_FILES['spot_profile']['tmp_name'], $uploadDir . $name);
        $profilePath = 'uploads/spots/' . $merchantId . '/' . $name;
    } elseif ($spotId === 0) {
        // Find merchant logo as default if new
        $merStmt = $pdo->prepare("SELECT store_logo FROM merchants WHERE id = ?");
        $merStmt->execute([$merchantId]);
        $profilePath = $merStmt->fetchColumn() ?: '';
    }

    if (isset($_FILES['spot_cover']) && $_FILES['spot_cover']['error'] == 0) {
        $name = time() . '_c_' . $_FILES['spot_cover']['name'];
        move_uploaded_file($_FILES['spot_cover']['tmp_name'], $uploadDir . $name);
        $coverPath = 'uploads/spots/' . $merchantId . '/' . $name;
    }

    if ($spotId === 0) {
        $insStmt = $pdo->prepare("INSERT INTO merchant_spots 
            (merchant_id, spot_name, spot_tagline, spot_description, spot_profile, spot_cover, operating_hours, amenities, barangay, latitude, longitude)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insStmt->execute([$merchantId, $spotName, $spotTagline, $spotDesc, $profilePath, $coverPath, $operatingHours, $amenities, $barangay, $latitude, $longitude]);
        $spotId = $pdo->lastInsertId();
    } else {
        $upStmt = $pdo->prepare("UPDATE merchant_spots SET 
            spot_name=?, spot_tagline=?, spot_description=?, spot_profile=?, spot_cover=?, operating_hours=?, amenities=?, barangay=?, latitude=?, longitude=?
            WHERE id=? AND merchant_id=?");
        $upStmt->execute([$spotName, $spotTagline, $spotDesc, $profilePath, $coverPath, $operatingHours, $amenities, $barangay, $latitude, $longitude, $spotId, $merchantId]);
    }

    // Media Logic (handling slots)
    $sections = ['interior', 'exterior'];
    foreach ($sections as $sec) {
        // Pictures (6)
        for ($i=0; $i<6; $i++) {
            $existingPath = $_POST["existing_{$sec}_pic_{$i}"] ?? '';
            $caption = $_POST["{$sec}_pic_caption_{$i}"] ?? '';
            $finalPath = $existingPath;

            if (isset($_FILES["{$sec}_pic_{$i}"]) && $_FILES["{$sec}_pic_{$i}"]['error'] == 0) {
                $f = $_FILES["{$sec}_pic_{$i}"];
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $filename = time() . "_{$sec}_pic_{$i}_.{$ext}";
                move_uploaded_file($f['tmp_name'], $uploadDir . $filename);
                $finalPath = 'uploads/spots/' . $merchantId . '/' . $filename;
            }

            if ($finalPath) {
                $check = $pdo->prepare("SELECT id FROM merchant_spot_media WHERE spot_id=? AND section=? AND media_type=? AND slot_index=?");
                $check->execute([$spotId, $sec, 'image', $i]);
                $mediaRowId = $check->fetchColumn();

                if ($mediaRowId) {
                    $upMedia = $pdo->prepare("UPDATE merchant_spot_media SET file_path=?, caption=? WHERE id=?");
                    $upMedia->execute([$finalPath, $caption, $mediaRowId]);
                } else {
                    $insMedia = $pdo->prepare("INSERT INTO merchant_spot_media (spot_id, section, media_type, slot_index, file_path, caption) VALUES (?, ?, ?, ?, ?, ?)");
                    $insMedia->execute([$spotId, $sec, 'image', $i, $finalPath, $caption]);
                }
            }
        }
        // Videos (2)
        for ($i=0; $i<2; $i++) {
            $existingPath = $_POST["existing_{$sec}_vid_{$i}"] ?? '';
            $caption = $_POST["{$sec}_vid_caption_{$i}"] ?? '';
            $finalPath = $existingPath;

            if (isset($_FILES["{$sec}_vid_{$i}"]) && $_FILES["{$sec}_vid_{$i}"]['error'] == 0) {
                $f = $_FILES["{$sec}_vid_{$i}"];
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $filename = time() . "_{$sec}_vid_{$i}_.{$ext}";
                move_uploaded_file($f['tmp_name'], $uploadDir . $filename);
                $finalPath = 'uploads/spots/' . $merchantId . '/' . $filename;
            }

            if ($finalPath) {
                $check = $pdo->prepare("SELECT id FROM merchant_spot_media WHERE spot_id=? AND section=? AND media_type=? AND slot_index=?");
                $check->execute([$spotId, $sec, 'video', $i]);
                $mediaRowId = $check->fetchColumn();

                if ($mediaRowId) {
                    $upMedia = $pdo->prepare("UPDATE merchant_spot_media SET file_path=?, caption=? WHERE id=?");
                    $upMedia->execute([$finalPath, $caption, $mediaRowId]);
                } else {
                    $insMedia = $pdo->prepare("INSERT INTO merchant_spot_media (spot_id, section, media_type, slot_index, file_path, caption) VALUES (?, ?, ?, ?, ?, ?)");
                    $insMedia->execute([$spotId, $sec, 'video', $i, $finalPath, $caption]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Spot updated successfully', 'spot_id' => $spotId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
