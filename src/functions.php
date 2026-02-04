<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Helper to get DB connection
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = get_db_connection();
    }
    return $pdo;
}

// Render a template
function view($name, $data = []) {
    extract($data);
    require __DIR__ . "/../templates/{$name}.php";
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit;
}

// Authentication
function login($user) {
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}

function logout() {
    session_destroy();
    redirect('/');
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_role($role) {
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        if (!$user) {
            redirect('/login');
        } else {
            redirect('/'); 
        }
    }
}

// File Upload (S3)
function upload_image($file) {
    // Check if vendor/autoload exists, else use local folder
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../config/s3.php';
        try {
            $s3 = get_s3_client();
            $bucket = 'watch-store-images';
            
            // Ensure bucket exists
            if (!$s3->doesBucketExist($bucket)) {
                $s3->createBucket(['Bucket' => $bucket]);
            }

            $key = 'watches/' . uniqid() . '-' . basename($file['name']);
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SourceFile' => $file['tmp_name'],
                'ACL'    => 'public-read',
            ]);
            
            return $result['ObjectURL'];
        } catch (Exception $e) {
            // Fallback to local if S3 fails or not set up
            return upload_local($file);
        }
    } else {
        return upload_local($file);
    }
}

function upload_local($file) {
    $targetDir = __DIR__ . '/../public/uploads/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = uniqid() . '-' . basename($file['name']);
    $targetFilePath = $targetDir . $fileName;
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return '/uploads/' . $fileName;
    }
    return null;
}

// Cart Logic
function get_cart_count() {
    if (!is_logged_in()) return 0;
    
    $stmt = db()->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([current_user()['id']]);
    $res = $stmt->fetch();
    return $res['count'] ?? 0;
}
