<?php
// admin_API/get_document_template.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$type = $_GET['type'] ?? 'terms_and_conditions';

try {
    $stmt = $pdo->prepare("SELECT template_html FROM document_templates WHERE template_key = ?");
    $stmt->execute([$type]);
    $html = $stmt->fetchColumn();

    if ($html === false) {
        // Fallbacks
        if ($type === 'terms_and_conditions') {
            $html = "<h2>Terms and Conditions</h2><p>This certifies that {{AGENT_NAME}} is promoted to {{AGENT_RANK}}.</p>";
        } else {
            $html = "<h2>Certificate of Excellence</h2><p>{{AGENT_NAME}} is hereby promoted to {{AGENT_RANK}}.</p>";
        }
    }

    echo json_encode(['status' => 'success', 'data' => $html]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
