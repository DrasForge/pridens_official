<?php
// admin_API/get_merchant_eligible_items.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized session']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
$type       = $_GET['type'] ?? 'Products'; // Products, Foods, Services, Spots

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $data = [];
    
    if ($type === 'Products') {
        $sql = "SELECT id, name, pridens_sku, price FROM merchant_products WHERE merchant_id = ? AND status IN ('active', 'Active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$merchantId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Include models/variations
        foreach ($items as &$item) {
            $stmtM = $pdo->prepare("SELECT id, name, pridens_sku, price FROM product_models WHERE product_id = ?");
            $stmtM->execute([$item['id']]);
            $item['models'] = $stmtM->fetchAll(PDO::FETCH_ASSOC);
            $item['has_variations'] = count($item['models']) > 0;
        }
        $data = $items;
    } 
    elseif ($type === 'Foods') {
        $sql = "SELECT id, name, base_price as price FROM merchant_foods WHERE merchant_id = ? AND status IN ('active', 'Active', 'Available')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$merchantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($type === 'Services') {
        $sql = "SELECT id, service_name as name, price FROM merchant_services WHERE merchant_id = ? AND status IN ('active', 'Active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$merchantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($type === 'Spots') {
        $sql = "SELECT id, spot_name as name, '0.00' as price FROM merchant_spots WHERE merchant_id = ? ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$merchantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
