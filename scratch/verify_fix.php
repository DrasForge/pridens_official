<?php
require_once 'admin_API/db.php';

// Mock session
$_SESSION['admin_id'] = 1;

try {
    echo "Verifying get_insurance_approvals.php logic...\n";
    
    // Test the specific query that was failing
    $query = "
        SELECT 
            s.account_id, s.first_name, s.last_name, s.email, s.sales_invoice_number,
            s.insurance_document_path, s.insurance_ready_for_approval,
            s.subscription_status
        FROM subscribers s
        WHERE s.insurance_status = 'Pending'
        ORDER BY s.created_at DESC
    ";
    
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Successfully fetched " . count($results) . " pending approvals.\n";
    
    echo "Verification complete!\n";
} catch (Exception $e) {
    echo "Verification failed: " . $e->getMessage() . "\n";
}
?>
