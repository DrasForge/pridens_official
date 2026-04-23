<?php
// admin_API/get_agent_commissions.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$agentId    = $_GET['agent_id'] ?? null;
$type       = $_GET['type'] ?? null; // One-Time, Residual, Collection Fee, Position-Based
$status     = $_GET['status'] ?? null;
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

try {
    // Build filter
    $conditions = [];
    $params = [];

    if ($agentId) {
        $conditions[] = "ac.agent_id LIKE ?";
        $params[] = "%$agentId%";
    }
    if ($type) {
        $conditions[] = "ac.commission_type = ?";
        $params[] = $type;
    }
    if ($status) {
        $conditions[] = "ac.status = ?";
        $params[] = $status;
    }

    $whereClause = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM agent_commissions ac $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Fetch commissions with agent name
    $stmt = $pdo->prepare("
        SELECT ac.*, 
               CONCAT(a.first_name, ' ', a.last_name) AS agent_name,
               r.rank_name
        FROM agent_commissions ac
        LEFT JOIN agents a ON ac.agent_id = a.agent_id
        LEFT JOIN ranks r ON a.rank_id = r.id
        $whereClause
        ORDER BY ac.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary totals for this agent (if filtered)
    $summaryData = [];
    if ($agentId) {
        $sumStmt = $pdo->prepare("
            SELECT commission_type, status, SUM(amount) as total, COUNT(*) as count
            FROM agent_commissions
            WHERE agent_id = ?
            GROUP BY commission_type, status
        ");
        $sumStmt->execute([$agentId]);
        $summaryData = $sumStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $commissions,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'summary' => $summaryData
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
