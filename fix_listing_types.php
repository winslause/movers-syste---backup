<?php
require_once 'db.php';

echo "<h2>Database Diagnostic & Fix</h2>";

try {
    // Check if listing_type column exists
    echo "<h3>Checking database columns...</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM houses LIKE 'listing_type'");
    if (!$stmt->fetch()) {
        echo "<p style='color: red;'>Column 'listing_type' does NOT exist. Adding it...</p>";
        $pdo->exec("ALTER TABLE houses ADD COLUMN listing_type ENUM('rent', 'airbnb', 'sale') DEFAULT 'rent'");
        echo "<p style='color: green;'>Column 'listing_type' added.</p>";
    } else {
        echo "<p style='color: green;'>Column 'listing_type' exists.</p>";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM houses LIKE 'price_night'");
    if (!$stmt->fetch()) {
        echo "<p style='color: red;'>Column 'price_night' does NOT exist. Adding it...</p>";
        $pdo->exec("ALTER TABLE houses ADD COLUMN price_night DECIMAL(10,2)");
        echo "<p style='color: green;'>Column 'price_night' added.</p>";
    } else {
        echo "<p style='color: green;'>Column 'price_night' exists.</p>";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM houses LIKE 'price_sale'");
    if (!$stmt->fetch()) {
        echo "<p style='color: red;'>Column 'price_sale' does NOT exist. Adding it...</p>";
        $pdo->exec("ALTER TABLE houses ADD COLUMN price_sale DECIMAL(12,2)");
        echo "<p style='color: green;'>Column 'price_sale' added.</p>";
    } else {
        echo "<p style='color: green;'>Column 'price_sale' exists.</p>";
    }
    
    // Check if price column is nullable
    $stmt = $pdo->query("SHOW COLUMNS FROM houses LIKE 'price'");
    $row = $stmt->fetch();
    if ($row && strpos($row['Type'], 'NOT NULL') !== false) {
        echo "<p style='color: red;'>Column 'price' is NOT NULL. Making it nullable...</p>";
        $pdo->exec("ALTER TABLE houses MODIFY COLUMN price DECIMAL(10,2)");
        echo "<p style='color: green;'>Column 'price' is now nullable.</p>";
    } else {
        echo "<p style='color: green;'>Column 'price' is nullable.</p>";
    }
    
    // Update existing houses to have listing_type = 'rent' if null
    echo "<h3>Updating existing properties...</h3>";
    $affected = $pdo->exec("UPDATE houses SET listing_type = 'rent' WHERE listing_type IS NULL");
    echo "<p>Updated $affected properties with listing_type = 'rent'.</p>";
    
    // Show current data
    echo "<h3>Current Properties in Database</h3>";
    $stmt = $pdo->query("SELECT id, title, listing_type, price, price_sale, price_night FROM houses");
    $houses = $stmt->fetchAll();
    
    if (count($houses) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Title</th><th>Listing Type</th><th>Price (Rent)</th><th>Price (Sale)</th><th>Price (Night)</th></tr>";
        foreach ($houses as $house) {
            echo "<tr>";
            echo "<td>" . $house['id'] . "</td>";
            echo "<td>" . htmlspecialchars($house['title']) . "</td>";
            echo "<td>" . ($house['listing_type'] ?: 'NULL') . "</td>";
            echo "<td>" . ($house['price'] ?: 'NULL') . "</td>";
            echo "<td>" . ($house['price_sale'] ?: 'NULL') . "</td>";
            echo "<td>" . ($house['price_night'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No properties found in database.</p>";
    }
    
    // Test API query
    echo "<h3>Testing API Query...</h3>";
    $landlord_id = isset($_GET['landlord_id']) ? (int)$_GET['landlord_id'] : null;
    if ($landlord_id) {
        $stmt = $pdo->prepare("SELECT * FROM houses WHERE landlord_id = ?");
        $stmt->execute([$landlord_id]);
        $houses = $stmt->fetchAll();
        echo "<p>Found " . count($houses) . " properties for landlord_id = $landlord_id</p>";
    }
    
    echo "<h3 style='color: green;'>Database fix completed! Please refresh your profile page.</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
