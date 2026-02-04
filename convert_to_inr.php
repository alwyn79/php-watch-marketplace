<?php
require_once 'config/database.php';

echo "Converting prices to Indian Rupees (INR)...\n";

try {
    $db = get_db_connection();

    // Mapping approximation (1 USD = ~85 INR)
    // We update all prices in the DB assuming they are currently in USD
    
    // Check if we already converted (naive check: if a budget watch is > 1000)
    $check = $db->query("SELECT price FROM products WHERE name LIKE '%Casio%'")->fetch();
    if ($check && $check['price'] > 1000) {
        echo "Prices seem to be already in INR (Casio is ₹{$check['price']}). Skipping conversion.\n";
        exit;
    }

    $rate = 85;
    $db->exec("UPDATE products SET price = price * $rate");

    echo "✔ Successfully updated all watch prices to INR.\n";
    echo "Refreshed prices example:\n";
    
    $stmt = $db->query("SELECT name, price FROM products LIMIT 3");
    while ($row = $stmt->fetch()) {
        echo "- {$row['name']}: ₹" . number_format($row['price']) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
