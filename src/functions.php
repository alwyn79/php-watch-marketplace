<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Helper to get DB connection
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = get_db_connection();
    }
    return $pdo;
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function request_string(string $key, int $maxLen = 5000, ?string $from = null): ?string {
    $src = match ($from) {
        'get' => $_GET,
        'post' => $_POST,
        default => ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET,
    };
    if (!isset($src[$key])) return null;
    $v = trim((string)$src[$key]);
    if ($v === '') return null;
    if (mb_strlen($v) > $maxLen) $v = mb_substr($v, 0, $maxLen);
    return $v;
}

function request_int(string $key, ?int $min = null, ?int $max = null, ?string $from = null): ?int {
    $v = request_string($key, 100, $from);
    if ($v === null) return null;
    if (!preg_match('/^-?\d+$/', $v)) return null;
    $i = (int)$v;
    if ($min !== null && $i < $min) return null;
    if ($max !== null && $i > $max) return null;
    return $i;
}

function request_float(string $key, ?float $min = null, ?float $max = null, ?string $from = null): ?float {
    $v = request_string($key, 100, $from);
    if ($v === null) return null;
    if (!is_numeric($v)) return null;
    $f = (float)$v;
    if ($min !== null && $f < $min) return null;
    if ($max !== null && $f > $max) return null;
    return $f;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['_csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_is_valid(?string $token): bool {
    if (!$token || empty($_SESSION['_csrf'])) return false;
    return hash_equals((string)$_SESSION['_csrf'], (string)$token);
}

// Render a template
function view($name, $data = []) {
    extract($data);
    require __DIR__ . "/../templates/{$name}.php";
}

// Redirect helper
function redirect($url) {
    $url = (string)$url;
    // prevent response splitting/header injection
    $url = str_replace(["\r", "\n"], '', $url);
    // keep redirects local by default (avoid open redirects)
    if ($url === '' || $url[0] !== '/') {
        $url = '/';
    }
    header("Location: {$url}", true, 302);
    exit;
}

// Authentication
function login($user) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
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
        $s3Config = __DIR__ . '/../config/s3.php';
        if (!file_exists($s3Config)) {
            return upload_local($file);
        }
        require_once $s3Config;
        try {
            if (!function_exists('get_s3_client')) {
                return upload_local($file);
            }
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
        mkdir($targetDir, 0755, true);
    }
    $maxBytes = 5 * 1024 * 1024; // 5MB
    if (!isset($file['tmp_name'], $file['name'], $file['size']) || (int)$file['size'] <= 0 || (int)$file['size'] > $maxBytes) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        return null;
    }

    $ext = $allowed[$mime];
    $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo((string)$file['name'], PATHINFO_FILENAME));
    if ($safeBase === '' || $safeBase === '.' || $safeBase === '..') {
        $safeBase = 'image';
    }
    $fileName = uniqid('', true) . '-' . $safeBase . '.' . $ext;
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
