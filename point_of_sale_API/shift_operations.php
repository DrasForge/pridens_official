<?php
// point_of_sale_API/shift_operations.php
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$action = $_GET['action'] ?? '';

try {
    if ($action === 'check') {
        // Check if admin has open shift
        $stmt = $pdo->prepare("SELECT * FROM `pos_shifts` WHERE `admin_id` = ? AND `status` = 'Open' LIMIT 1");
        $stmt->execute([$admin_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shift) {
            echo json_encode(['success' => true, 'has_shift' => true, 'shift' => $shift]);
        } else {
            echo json_encode(['success' => true, 'has_shift' => false]);
        }
    } 
    elseif ($action === 'open') {
        // Open a new shift
        $stmt = $pdo->prepare("SELECT id FROM `pos_shifts` WHERE `admin_id` = ? AND `status` = 'Open' LIMIT 1");
        $stmt->execute([$admin_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You already have an open shift.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $opening_balance = isset($input['opening_balance']) ? floatval($input['opening_balance']) : 0.00;

        // Get max Z counter
        $stmtZ = $pdo->query("SELECT MAX(shift_z_counter) as max_z FROM `pos_shifts`");
        $z_res = $stmtZ->fetch(PDO::FETCH_ASSOC);
        $next_z = ($z_res['max_z'] ? (int)$z_res['max_z'] : 0) + 1;

        $stmt = $pdo->prepare("INSERT INTO `pos_shifts` (`admin_id`, `shift_z_counter`, `opening_balance`) VALUES (?, ?, ?)");
        $stmt->execute([$admin_id, $next_z, $opening_balance]);

        // Audit Log
        $logStmt = $pdo->prepare("INSERT INTO `pos_audit_logs` (`admin_id`, `action_type`, `description`) VALUES (?, 'OPEN_SHIFT', CONCAT('Opened shift with balance: ', ?))");
        $logStmt->execute([$admin_id, $opening_balance]);

        echo json_encode(['success' => true, 'message' => 'Shift opened successfully.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
