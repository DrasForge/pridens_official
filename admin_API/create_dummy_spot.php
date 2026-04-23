<?php
require_once 'db.php';

try {
    // 1. Get first merchant
    $mId = $pdo->query("SELECT id FROM merchants LIMIT 1")->fetchColumn();
    if (!$mId) die("No merchants found to attach a spot to.");

    // 2. Clear existing dummy spots if any (optional, but good for clean test)
    // $pdo->exec("DELETE FROM merchant_spots WHERE merchant_id = $mId AND spot_name LIKE 'Dummy%'");

    // 3. Insert a spot
    $stmt = $pdo->prepare("INSERT INTO merchant_spots 
        (merchant_id, spot_name, spot_tagline, spot_description, spot_cover, barangay, operating_hours, amenities) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $name = "Pridens Tech Hub - Main";
    $tagline = "Innovating the Future of Trading";
    $desc = "Our flagship innovation spot featuring high-speed trading floors, luxury developer lounges, and state-of-the-art server rooms.";
    $cover = "https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80";
    $barangay = "Barangay San Antonio";
    $hours = json_encode([
        "Monday" => "08:00 AM - 09:00 PM",
        "Tuesday" => "08:00 AM - 09:00 PM",
        "Wednesday" => "08:00 AM - 09:00 PM",
        "Thursday" => "08:00 AM - 09:00 PM",
        "Friday" => "08:00 AM - 11:00 PM",
        "Saturday" => "10:00 AM - 06:00 PM",
        "Sunday" => "Closed"
    ]);
    $amenities = json_encode(["Gigabit Wi-Fi", "Free Parking", "P-Voucher Accepted", "Coffee Bar", "Indoor Garden"]);

    $stmt->execute([$mId, $name, $tagline, $desc, $cover, $barangay, $hours, $amenities]);
    $spotId = $pdo->lastInsertId();

    // 4. Add some media entries
    $mediaStmt = $pdo->prepare("INSERT INTO merchant_spot_media (spot_id, section, media_type, file_path, caption) VALUES (?, ?, ?, ?, ?)");
    
    // Add 1 interior pic
    $mediaStmt->execute([$spotId, 'interior', 'image', 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=800&q=80', 'Modern open-plan interior']);
    
    // Add 1 exterior pic
    $mediaStmt->execute([$spotId, 'exterior', 'image', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80', 'Glass-front building entrance']);

    echo "Dummy spot created successfully for Merchant ID: $mId\n";
    echo "Spot ID: $spotId\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
