<?php
// admin_API/dashboard_stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check Authentication
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login.']);
    exit();
}

// Connect to Database
require_once 'db.php';

try {
    // Initialize statistics
    $stats = [
        'total_active_subscribers' => 0,
        'total_active_agents' => 0,
        'total_subscribers_applications' => 0
    ];

    // Query 1: Total Active Subscribers
    $stmtSubscribers = $pdo->query("SELECT COUNT(*) as count FROM subscribers WHERE subscription_status = 'Active'");
    if ($stmtSubscribers) {
        $stats['total_active_subscribers'] = (int)$stmtSubscribers->fetch()['count'];
    }

    // Query 2: Total Active Agents
    $stmtAgents = $pdo->query("SELECT COUNT(*) as count FROM agents WHERE status = 'Active'");
    if ($stmtAgents) {
        $stats['total_active_agents'] = (int)$stmtAgents->fetch()['count'];
    }

    // Query 3: Total Subscribers Applications
    $stmtApps = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE type = 'subscriber'");
    if ($stmtApps) {
        $stats['total_subscribers_applications'] = (int)$stmtApps->fetch()['count'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $stats
    ]);

} catch (PDOException $e) {
    // If tables don't exist yet, we can catch the error and just return 0s so the dashboard doesn't break
    if ($e->getCode() === '42S02') { // 42S02 is "Base table or view not found"
        echo json_encode([
            'status' => 'success',
            'data' => $stats,
            'note' => 'Tables not found. Displaying default 0s.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database query failed.'
        ]);
    }
}
?>
