<?php
// point_of_sale_API/shift_reports.php
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
    // Both X-Reading and Z-Reading need the current open shift
    $stmt = $pdo->prepare("SELECT * FROM `pos_shifts` WHERE `admin_id` = ? AND `status` = 'Open' LIMIT 1");
    $stmt->execute([$admin_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shift) {
        echo json_encode(['success' => false, 'error' => 'No open shift found.']);
        exit;
    }

    if ($action === 'x_reading') {
        // X-Reading: Just read current totals without closing
        echo json_encode([
            'success' => true,
            'shift_z_counter' => $shift['shift_z_counter'],
            'opening_time' => $shift['opening_time'],
            'opening_balance' => $shift['opening_balance'],
            'total_sales' => $shift['total_sales'],
            'total_discounts' => $shift['total_discounts'],
            'expected_drawer_cash' => floatval($shift['opening_balance']) + floatval($shift['total_sales'])
        ]);
        
        // Audit
        $pdo->prepare("INSERT INTO pos_audit_logs (admin_id, action_type, description) VALUES (?, 'X_READING', ?)")
            ->execute([$admin_id, "Generated X-Reading for Shift ID: " . $shift['id']]);

    } 
    elseif ($action === 'z_reading_close') {
        // Z-Reading: Close the shift permanently
        $input = json_decode(file_get_contents('php://input'), true);
        $actual_cash = isset($input['actual_cash']) ? floatval($input['actual_cash']) : 0.00;
        
        $expected = floatval($shift['opening_balance']) + floatval($shift['total_sales']);
        $variance = $actual_cash - $expected;

        $pdo->beginTransaction();

        $stmtClose = $pdo->prepare("UPDATE `pos_shifts` SET 
            `closing_time` = CURRENT_TIMESTAMP, 
            `actual_cash_counted` = ?, 
            `variance` = ?, 
            `status` = 'Closed' 
            WHERE `id` = ?");
        $stmtClose->execute([$actual_cash, $variance, $shift['id']]);

        // Audit
        $pdo->prepare("INSERT INTO pos_audit_logs (admin_id, action_type, description) VALUES (?, 'Z_READING_CLOSE', ?)")
            ->execute([$admin_id, "Closed Shift ID: {$shift['id']} with Actual Cash: {$actual_cash}, Variance: {$variance}"]);

        // Fetch System Grand Total for BIR Z-Reading Receipt
        $sys = $pdo->query("SELECT accumulated_grand_total, reset_counter FROM pos_system_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'shift_z_counter' => $shift['shift_z_counter'],
            'opening_time' => $shift['opening_time'],
            'opening_balance' => $shift['opening_balance'],
            'total_sales' => $shift['total_sales'],
            'actual_cash' => $actual_cash,
            'variance' => $variance,
            'accumulated_grand_total' => $sys['accumulated_grand_total'],
            'reset_counter' => $sys['reset_counter'],
            'message' => 'Shift closed successfully. Z-Reading generated.'
        ]);
    }
} catch (PDOException $e) {
    if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
