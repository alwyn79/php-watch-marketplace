<?php
require_once 'config/database.php';

echo "Migrating database to V2 (Seller Approval & Advanced Features)...\n";

try {
    $db = get_db_connection();

    // 1. Update Users Table (Add status column)
    $columns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('status', $columns)) {
        echo "Adding 'status' column to users table...\n";
        $db->exec("ALTER TABLE users ADD COLUMN status TEXT DEFAULT 'approved'");
        echo "✔ Column added.\n";
    } else {
        echo "✔ Users table already has 'status' column.\n";
    }

    // 2. Ensure Orders Table exists
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        total REAL NOT NULL,
        status TEXT DEFAULT 'pending', -- pending, paid, shipped, completed, cancelled
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✔ Orders table check.\n";

    // 3. Ensure Order Items Table exists
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "✔ Order Items table check.\n";

    // 4. Ensure Wishlist Table exists with Unique Constraint
    // SQLite doesn't support adding UNIQUE constraints via ALTER TABLE easily.
    // We'll create it if it doesn't exist, but won't migrate existing data structure complexity for now to avoid data loss risk in this simple script.
    $db->exec("CREATE TABLE IF NOT EXISTS wishlist (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE(user_id, product_id)
    )");
    echo "✔ Wishlist table check.\n";

    echo "\nMigration Complete! You can now test the Seller Approval flow.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
