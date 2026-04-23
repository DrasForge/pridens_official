<?php
// admin_API/get_pos_reports.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $range = isset($_GET['range']) ? $_GET['range'] : '30days';
    $dateFilter = "";
    
    switch ($range) {
        case 'today':
            $dateFilter = "AND DATE(t.created_at) = CURDATE()";
            break;
        case '7days':
            $dateFilter = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case '30days':
            $dateFilter = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'all':
        default:
            $dateFilter = "";
            break;
    }

    // 1. Aggregates (Excl. Voided)
    $stmtAgg = $pdo->prepare("
        SELECT 
            COUNT(t.id) as total_transactions,
            SUM(t.grand_total) as total_revenue,
            SUM(ti.total_cost) as total_cost
        FROM pos_transactions t
        LEFT JOIN (
            SELECT transaction_id, SUM(cost_price_snapshot * quantity) as total_cost
            FROM pos_transaction_items
            GROUP BY transaction_id
        ) ti ON t.id = ti.transaction_id
        WHERE t.status != 'Voided' $dateFilter
    ");
    $stmtAgg->execute();
    $aggregates = $stmtAgg->fetch(PDO::FETCH_ASSOC);

    // 2. Daily Trends (Last 30 Days)
    $stmtTrend = $pdo->prepare("
        SELECT 
            DATE(t.created_at) as date,
            SUM(t.grand_total) as revenue,
            SUM(ti.total_cost) as cost
        FROM pos_transactions t
        LEFT JOIN (
            SELECT transaction_id, SUM(cost_price_snapshot * quantity) as total_cost
            FROM pos_transaction_items
            GROUP BY transaction_id
        ) ti ON t.id = ti.transaction_id
        WHERE t.status != 'Voided' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(t.created_at)
        ORDER BY DATE(t.created_at) ASC
    ");
    $stmtTrend->execute();
    $trends = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // 3. Category Breakdown
    $stmtCat = $pdo->prepare("
        SELECT 
            p.category,
            SUM(ti.subtotal) as revenue,
            SUM(ti.cost_price_snapshot * ti.quantity) as cost
        FROM pos_transaction_items ti
        JOIN pos_transactions t ON ti.transaction_id = t.id
        JOIN pos_products p ON ti.product_id = p.id
        WHERE t.status != 'Voided' $dateFilter
        GROUP BY p.category
        ORDER BY revenue DESC
    ");
    $stmtCat->execute();
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top Products (By Profit)
    $stmtProducts = $pdo->prepare("
        SELECT 
            ti.product_name_snapshot as name,
            SUM(ti.quantity) as total_qty,
            SUM(ti.subtotal) as revenue,
            SUM(ti.subtotal - (ti.cost_price_snapshot * ti.quantity)) as profit
        FROM pos_transaction_items ti
        JOIN pos_transactions t ON ti.transaction_id = t.id
        WHERE t.status != 'Voided' $dateFilter
        GROUP BY ti.product_id
        ORDER BY profit DESC
        LIMIT 10
    ");
    $stmtProducts->execute();
    $topProducts = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'aggregates' => [
                'revenue' => (float)($aggregates['total_revenue'] ?? 0),
                'cost' => (float)($aggregates['total_cost'] ?? 0),
                'profit' => (float)(($aggregates['total_revenue'] ?? 0) - ($aggregates['total_cost'] ?? 0)),
                'count' => (int)($aggregates['total_transactions'] ?? 0)
            ],
            'trends' => $trends,
            'categories' => $categories,
            'top_products' => $topProducts
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
