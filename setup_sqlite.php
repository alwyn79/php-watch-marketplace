<?php
// setup_sqlite.php
$dbPath = __DIR__ . '/database.sqlite';

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creating tables in SQLite...\n";

    // Users
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT CHECK(role IN ('user', 'seller', 'admin')) DEFAULT 'user',
        status TEXT CHECK(status IN ('pending', 'approved', 'rejected')) DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Categories
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT
    )");

    // Products
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        seller_id INTEGER NOT NULL,
        category_id INTEGER,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        tier TEXT CHECK(tier IN ('budget', 'luxury')) NOT NULL DEFAULT 'budget',
        stock INTEGER DEFAULT 10,
        image_url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    // Cart
    $db->exec("CREATE TABLE IF NOT EXISTS cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE(user_id, product_id)
    )");

    // Wishlist
    $db->exec("CREATE TABLE IF NOT EXISTS wishlist (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE(user_id, product_id)
    )");

    // Orders
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        total REAL NOT NULL,
        status TEXT CHECK(status IN ('pending', 'paid', 'shipped', 'completed', 'cancelled')) DEFAULT 'pending',
        tracking_number TEXT DEFAULT NULL,
        shipping_address TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Order Items
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    // Default Data
    $db->exec("INSERT OR IGNORE INTO categories (name, description) VALUES 
        ('Divers', 'Water resistant watches for diving'),
        ('Dress', 'Elegant watches for formal occasions'),
        ('Sports', 'Durable watches for active lifestyles'),
        ('Smart', 'Connected timepieces with smart features')");

    // Admin
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT OR IGNORE INTO users (name, email, password, role, status) VALUES 
        ('System Admin', 'admin@timepiece.com', '$adminPass', 'admin', 'approved')");

    echo "✔ Success! Your SQLite database is ready.\n";

} catch (Exception $e) {
    die("Error setting up database: " . $e->getMessage());
}
