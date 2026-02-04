<?php
// setup_sqlite.php
// Run this file once to create the database without needing MySQL!

require_once 'config/database.php';

echo "Setting up local SQLite database...\n";

try {
    $db = get_db_connection();
    
    // 1. Users
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✔ Users table created.\n";

    // 2. Categories
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT
    )");
    echo "✔ Categories table created.\n";

    // 3. Products
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        seller_id INTEGER NOT NULL,
        category_id INTEGER,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        tier TEXT NOT NULL DEFAULT 'budget',
        image_url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");
    echo "✔ Products table created.\n";

    // 4. Cart
    $db->exec("CREATE TABLE IF NOT EXISTS cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "✔ Cart table created.\n";

    // Seed Data
    // Admin
    $pass = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT OR IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@timepiece.com', $pass, 'admin']);
    
    // Categories
    $cats = ['Divers', 'Dress', 'Sports', 'Smart'];
    $stmt = $db->prepare("INSERT OR IGNORE INTO categories (name, description) VALUES (?, 'Standard category')");
    foreach ($cats as $c) {
        $stmt->execute([$c]);
    }

    echo "✔ Default data seeded.\n";
    echo "\nSuccess! Your database is ready in 'database.sqlite'.\n";
    echo "You can now start the server with: php -S localhost:8000 -t public\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
