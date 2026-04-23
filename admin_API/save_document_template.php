<?php
// admin_API/save_document_template.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['template_key']) || !isset($input['template_html'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO document_templates (template_key, template_html) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE template_html = VALUES(template_html)
    ");
    $stmt->execute([$input['template_key'], $input['template_html']]);

    echo json_encode(['status' => 'success', 'message' => 'Template saved successfully']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
