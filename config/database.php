<?php
// config/database.php

function get_db_connection() {
    // 1. Check for Environment Variables (Docker / K8s)
    if (getenv('DB_HOST')) {
        $host = getenv('DB_HOST');
        $db   = getenv('DB_NAME') ?: 'watch_store';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: 'password'; 
        
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $user, $pass, $options);
    }

    // 2. Try to use SQLite (File-based, no installation needed)
    $sqlitePath = __DIR__ . '/../database.sqlite';
    // ... rest of SQLite logic
    $useSqlite = true; 

    if ($useSqlite) {
        try {
            if (!file_exists($sqlitePath)) {
                touch($sqlitePath);
            }
            $pdo = new PDO("sqlite:" . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            return $pdo;
        } catch (\PDOException $e) {
            // Fallback
        }
    }

    // 3. MySQL Local Fallback
    $host = '127.0.0.1';
    $db   = 'watch_store';
    $user = 'root';
    $pass = ''; 
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}
