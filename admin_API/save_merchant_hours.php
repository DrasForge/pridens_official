<?php
// admin_API/save_merchant_hours.php
header('Content-Type: application/json');
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$merchantId = intval($data['merchant_id'] ?? 0);
$schedule = $data['schedule'] ?? [];

if (!$merchantId || empty($schedule)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($schedule as $row) {
        $stmt = $pdo->prepare("INSERT INTO merchant_operating_hours (merchant_id, day_of_week, is_open, open_time, close_time) 
                               VALUES (?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE 
                               is_open = VALUES(is_open), 
                               open_time = VALUES(open_time), 
                               close_time = VALUES(close_time)");
        $stmt->execute([
            $merchantId, 
            $row['day'], 
            $row['is_open'], 
            $row['open_time'], 
            $row['close_time']
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Schedule saved successfully']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
