<?php
require_once 'admin_API/db.php';

// Mock session
$_SESSION['admin_logged_in'] = true;

try {
    $status_filter = 'All';

    $query = "SELECT s.account_id, s.email, s.first_name, s.last_name, s.subscription_status as status, s.insurance_status, s.avatar_initials, 
                     DATE_FORMAT(s.joined_date, '%m/%d/%Y') as joined_date,
                     TRIM(CONCAT(adm1.first_name, ' ', adm1.last_name)) as approved_by_name,
                     DATE_FORMAT(s.approved_at, '%m/%d/%Y %h:%i %p') as approved_at_formatted,
                     TRIM(CONCAT(adm2.first_name, ' ', adm2.last_name)) as insurance_approved_by_name,
                     DATE_FORMAT(s.insurance_approved_at, '%m/%d/%Y %h:%i %p') as insurance_approved_at_formatted
              FROM subscribers s
              LEFT JOIN admins adm1 ON s.approved_by = adm1.id
              LEFT JOIN admins adm2 ON s.insurance_approved_by = adm2.id";
    
    $query .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([]);
    
    $accounts = $stmt->fetchAll();
    echo "Success: " . count($accounts) . " rows found.\n";

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
    echo "Stack Trace: \n" . $e->getTraceAsString() . "\n";
}
?>
