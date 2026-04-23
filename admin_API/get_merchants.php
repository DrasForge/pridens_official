<?php
// admin_API/get_merchants.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

$search  = trim($_GET['search'] ?? '');
$status  = $_GET['status'] ?? '';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$conditions = [];
$params = [];

if ($search) { $conditions[] = "(m.store_name LIKE ? OR m.merchant_code LIKE ? OR m.business_email LIKE ?)"; $t = "%$search%"; $params = array_merge($params, [$t, $t, $t]); }
if ($status) { $conditions[] = "m.status = ?"; $params[] = $status; }

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM merchants m $where");
$count->execute($params);
$total = $count->fetchColumn();

$stmt = $pdo->prepare("
    SELECT m.id, m.merchant_code, m.store_name, m.business_name, m.store_category,
           m.business_type, m.business_email, m.status, m.registered_at,
           m.store_logo, m.address_city, m.address_province,
           CONCAT(o.first_name, ' ', o.last_name) AS owner_name, o.contact_number
    FROM merchants m
    LEFT JOIN merchant_owners o ON o.merchant_id = m.id
    $where
    ORDER BY m.registered_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status'=>'success','data'=>$merchants,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
?>
