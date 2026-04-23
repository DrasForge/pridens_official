<?php
// admin_API/save_merchant_product.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

/**
 * Shopee-Style Product Saver with Dual-SKU, Rich Content & Inclusions Support
 * Handles: Basic Info, Logistics, Variations, Models (SKUs), Gallery, Video, and Inclusions.
 */

function generatePridensSKU($prefix = 'PRD') {
    return $prefix . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

try {
    $pdo->beginTransaction();

    // 1. Basic Information
    $id             = intval($_POST['id'] ?? 0);
    $merchantId     = intval($_POST['merchant_id'] ?? 0);
    $sellerSku      = trim($_POST['seller_sku'] ?? ''); 
    $pridensSku     = trim($_POST['pridens_sku'] ?? ''); 
    
    $name           = trim($_POST['name'] ?? '');
    $description    = $_POST['description'] ?? ''; // Rich HTML content
    $category       = $_POST['category'] ?? 'Products';
    $customCatId    = intval($_POST['custom_category_id'] ?? 0);
    $brand          = trim($_POST['brand'] ?? 'No Brand');
    $status         = $_POST['status'] ?? 'Active';
    $isFeatured     = isset($_POST['is_featured']) && ($_POST['is_featured'] == '1' || $_POST['is_featured'] == 'true') ? 1 : 0;
    
    // Logistics
    $weight         = intval($_POST['weight_g'] ?? 0);
    $length         = intval($_POST['length_cm'] ?? 0);
    $width          = intval($_POST['width_cm'] ?? 0);
    $height         = intval($_POST['height_cm'] ?? 0);
    $inclusions     = $_POST['inclusions_data'] ?? '[]'; // JSON array of strings

    // Shipping & Payments Toggles
    $allowLocal      = isset($_POST['allow_local_deliv']) ? 1 : 0;
    $allowNationwide = isset($_POST['allow_nationwide_deliv']) ? 1 : 0;
    $allowPickup     = isset($_POST['allow_pickup']) ? 1 : 0;
    $allowCod        = isset($_POST['allow_cod']) ? 1 : 0;
    $allowBank       = isset($_POST['allow_bank']) ? 1 : 0;
    $allowEwallet    = isset($_POST['allow_ewallet']) ? 1 : 0;

    // Variations Flag
    $hasVariations  = isset($_POST['has_variations']) && ($_POST['has_variations'] == '1' || $_POST['has_variations'] == 'true') ? 1 : 0;

    // Pricing & Stock
    $price          = floatval($_POST['price'] ?? 0);
    $basePrice      = floatval($_POST['base_price'] ?? 0);
    $stock          = intval($_POST['stock_quantity'] ?? 0);
    $commission     = floatval($_POST['commission_percent'] ?? 0);
    $isAvailable    = isset($_POST['is_available']) && ($_POST['is_available'] == '1' || $_POST['is_available'] == 'true') ? 1 : 0;
    $isPreorder     = isset($_POST['is_preorder']) && ($_POST['is_preorder'] == '1' || $_POST['is_preorder'] == 'true') ? 1 : 0;
    $preorderDays   = intval($_POST['preorder_days'] ?? 0);
    $isExclusive    = isset($_POST['is_exclusive']) && ($_POST['is_exclusive'] == '1' || $_POST['is_exclusive'] == 'true') ? 1 : 0;

    if (!$merchantId || !$name) {
        throw new Exception("Required fields missing (Merchant ID, Name)");
    }

    // Generate Pridens SKU if empty (New Product)
    if (!$pridensSku) {
        $pridensSku = generatePridensSKU();
    }

    // Handle Main Image Upload
    $imagePath = $_POST['existing_image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_main_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    // Handle Dedicated Product Video Upload
    $videoPath = $_POST['existing_video'] ?? null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/products/videos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_vid_' . basename($_FILES['video']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['video']['tmp_name'], $targetFile)) {
            $videoPath = $targetFile;
        }
    }

    $cCat = $customCatId ? $customCatId : null;

    if ($id) {
        // Update Master Product
        $sql = "UPDATE merchant_products SET 
                seller_sku = ?, pridens_sku = ?, name = ?, brand = ?, description = ?, category = ?, custom_category_id = ?, has_variations = ?,
                price = ?, base_price = ?, commission_percent = ?, stock_quantity = ?, 
                weight_g = ?, length_cm = ?, width_cm = ?, height_cm = ?, inclusions = ?,
                allow_local_deliv = ?, allow_nationwide_deliv = ?, allow_pickup = ?, 
                allow_cod = ?, allow_bank = ?, allow_ewallet = ?,
                status = ?, is_available = ?, is_preorder = ?, preorder_days = ?, is_exclusive = ?, allow_direct_payment = ?, is_featured = ?, image_path = ?, video_path = ?, prep_time_mins = ?
                WHERE id = ? AND merchant_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $sellerSku, $pridensSku, $name, $brand, $description, $category, $cCat, $hasVariations,
            $price, $basePrice, $commission, $stock,
            $weight, $length, $width, $height, $inclusions,
            $allowLocal, $allowNationwide, $allowPickup,
            $allowCod, $allowBank, $allowEwallet,
            $status, $isAvailable, $isPreorder, $preorderDays, $isExclusive, $allowDirect, $isFeatured, $imagePath, $videoPath, $prepTime,
            $id, $merchantId
        ]);
        $productId = $id;
    } else {
        // Create Master Product
        $sql = "INSERT INTO merchant_products 
                (merchant_id, seller_sku, pridens_sku, name, brand, description, category, custom_category_id, has_variations,
                 price, base_price, commission_percent, stock_quantity, 
                 weight_g, length_cm, width_cm, height_cm, inclusions,
                 allow_local_deliv, allow_nationwide_deliv, allow_pickup, 
                 allow_cod, allow_bank, allow_ewallet,
                 status, is_available, is_preorder, preorder_days, is_exclusive, allow_direct_payment, is_featured, image_path, video_path, prep_time_mins)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $merchantId, $sellerSku, $pridensSku, $name, $brand, $description, $category, $cCat, $hasVariations,
            $price, $basePrice, $commission, $stock,
            $weight, $length, $width, $height, $inclusions,
            $allowLocal, $allowNationwide, $allowPickup,
            $allowCod, $allowBank, $allowEwallet,
            $status, $isAvailable, $isPreorder, $preorderDays, $isExclusive, $allowDirect, $isFeatured, $imagePath, $videoPath, $prepTime
        ]);
        $productId = $pdo->lastInsertId();
    }

    // 2. Handle Variations (Tiers and Models)
    if ($id) {
        $pdo->prepare("DELETE FROM product_variation_tiers WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("DELETE FROM product_models WHERE product_id = ?")->execute([$productId]);
    }

    if ($hasVariations) {
        $variationsJson = $_POST['variations_data'] ?? '[]';
        $variationTiers = json_decode($variationsJson, true);
        
        $modelsJson = $_POST['models_data'] ?? '[]';
        $models = json_decode($modelsJson, true);

        if ($variationTiers && is_array($variationTiers)) {
            foreach ($variationTiers as $idx => $tier) {
                $stmt = $pdo->prepare("INSERT INTO product_variation_tiers (product_id, name, tier_index) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $tier['name'], $idx + 1]);
                $tierId = $pdo->lastInsertId();

                if (isset($tier['options']) && is_array($tier['options'])) {
                    foreach ($tier['options'] as $oIdx => $opt) {
                        $optImgPath = $opt['existing_image'] ?? null;
                        
                        $fileKey = "option_image_" . $idx . "_" . $oIdx;
                        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                            $uploadDir = 'uploads/products/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                            $fileName = time() . '_opt_' . $idx . '_' . $oIdx . '_' . basename($_FILES[$fileKey]['name']);
                            $targetFile = $uploadDir . $fileName;
                            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetFile)) {
                                $optImgPath = $targetFile;
                            }
                        }

                        $stmt = $pdo->prepare("INSERT INTO product_variation_options (tier_id, name, image_path) VALUES (?, ?, ?)");
                        $stmt->execute([$tierId, $opt['name'], $optImgPath]);
                    }
                }
            }
        }

        if ($models && is_array($models)) {
            foreach ($models as $idx => $model) {
                $vPridensSku = $model['pridens_sku'] ?: ($pridensSku . '-V' . ($idx + 1));
                
                $stmt = $pdo->prepare("INSERT INTO product_models (product_id, pridens_sku, seller_sku, name, variation_key, price, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $productId, 
                    $vPridensSku,
                    $model['seller_sku'] ?? '', 
                    $model['name'], 
                    $model['variation_key'], 
                    $model['price'], 
                    $model['stock']
                ]);
            }
        }
    }

    // 3. Handle Wholesale Prices
    if ($id) {
        $pdo->prepare("DELETE FROM product_wholesale_prices WHERE product_id = ?")->execute([$productId]);
    }
    $wholesaleJson = $_POST['wholesale_data'] ?? '[]';
    $wholesale = json_decode($wholesaleJson, true);
    if ($wholesale && is_array($wholesale)) {
        foreach ($wholesale as $w) {
            $stmt = $pdo->prepare("INSERT INTO product_wholesale_prices (product_id, min_quantity, unit_price) VALUES (?, ?, ?)");
            $stmt->execute([$productId, $w['min_quantity'], $w['unit_price']]);
        }
    }

    // 4. Handle Gallery (Multiple Images)
    $removedIdsJson = $_POST['removed_image_ids'] ?? '[]';
    $removedIds = json_decode($removedIdsJson, true);
    if (!empty($removedIds)) {
        foreach ($removedIds as $imgId) {
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
            $stmt->execute([imgId]);
            $path = $stmt->fetchColumn();
            if ($path && file_exists($path)) { unlink($path); }
            $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);
        }
    }

    if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
        $uploadDir = 'uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        for ($i = 0; $i < count($_FILES['gallery']['name']); $i++) {
            if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = time() . '_gal_' . $i . '_' . basename($_FILES['gallery']['name'][$i]);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['gallery']['tmp_name'][$i], $targetFile)) {
                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                    $stmt->execute([$productId, $targetFile, $i + 1]);
                }
            }
        }
    }

    // 5. Handle PH Standard KYC
    $kycRegType = $_POST['kyc_reg_type'] ?? 'None';
    $kycLicense = $_POST['kyc_license_number'] ?? null;
    $kycExpiry = $_POST['kyc_expiry_date'] ?: null;
    $kycOrigin = $_POST['kyc_origin'] ?? 'Local-PH';
    $kycWarnings = $_POST['kyc_warnings'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO merchant_product_kyc (product_id, reg_type, license_number, expiry_date, manufacturing_origin, usage_warnings) 
                           VALUES (?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           reg_type=VALUES(reg_type), license_number=VALUES(license_number), expiry_date=VALUES(expiry_date), 
                           manufacturing_origin=VALUES(manufacturing_origin), usage_warnings=VALUES(usage_warnings)");
    $stmt->execute([$productId, $kycRegType, $kycLicense, $kycExpiry, $kycOrigin, $kycWarnings]);

    // 6. Handle Food Modifiers
    $modifiersJson = $_POST['modifiers_data'] ?? '[]';
    $modifierGroups = json_decode($modifiersJson, true);
    if ($productId && is_array($modifierGroups)) {
        // Clear existing modifiers for this product to prevent duplicates
        $pdo->prepare("DELETE FROM product_menu_modifier_groups WHERE product_id = ?")->execute([$productId]);
        
        foreach ($modifierGroups as $idx => $group) {
            if (empty($group['name'])) continue;
            
            $stmt = $pdo->prepare("INSERT INTO product_menu_modifier_groups (product_id, name, min_selection, max_selection, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $productId, 
                $group['name'], 
                $group['min_sel'] ?? 0, 
                $group['max_sel'] ?? 1, 
                ($group['is_required'] ? 1 : 0), 
                $idx
            ]);
            $groupId = $pdo->lastInsertId();
            
            if (isset($group['options']) && is_array($group['options'])) {
                foreach ($group['options'] as $oIdx => $opt) {
                    if (empty($opt['name'])) continue;
                    $optStmt = $pdo->prepare("INSERT INTO product_menu_modifier_options (group_id, name, extra_price, sort_order) VALUES (?, ?, ?, ?)");
                    $optStmt->execute([
                        $groupId, 
                        $opt['name'], 
                        $opt['price'] ?? 0, 
                        $oIdx
                    ]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Product saved successfully', 'product_id'=>$productId, 'pridens_sku'=>$pridensSku]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
