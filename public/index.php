<?php
require_once __DIR__ . '/../src/functions.php';

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Simple Router
switch ($path) {
    case '/':
        // Fetch products
        $pdo = db();
        $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
        $products = $stmt->fetchAll();
        
        view('header');
        view('home', ['products' => $products]);
        view('footer');
        break;

    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];
            
            $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'approved') {
                    $error = "Your account is " . $user['status'] . ". Contact support.";
                    view('header');
                    view('login', ['error' => $error]);
                    view('footer');
                } else {
                    login($user);
                    redirect('/');
                }
            } else {
                $error = "Invalid credentials";
                view('header');
                view('login', ['error' => $error]);
                view('footer');
            }
        } else {
            view('header');
            view('login');
            view('footer');
        }
        break;
        
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role']; 
            
            if ($role === 'admin') $role = 'user';
            
            // New Logic: Sellers are pending by default, Users are approved
            $status = ($role === 'seller') ? 'pending' : 'approved';
            
            $stmt = db()->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$name, $email, $password, $role, $status]);
                
                // If seller, show specific message
                if ($role === 'seller') {
                    $error = "Account created! Please wait for Admin approval before logging in.";
                    view('header');
                    view('login', ['error' => $error]); // Re-use login view to show message
                    view('footer');
                } else {
                    redirect('/login');
                }
            } catch (Exception $e) {
                view('header');
                view('register', ['error' => 'Email already exists']);
                view('footer');
            }
        } else {
            view('header');
            view('register');
            view('footer');
        }
        break;

    case '/logout':
        logout();
        break;
        
    case '/seller/dashboard':
        require_role('seller');
        $user = current_user();
        
        // Fetch seller's products
        $stmt = db()->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $products = $stmt->fetchAll();
        
        view('header');
        view('dashboard_seller', ['products' => $products]);
        view('footer');
        break;

    case '/seller/add-product':
        require_role('seller');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $desc = $_POST['description'];
            $price = $_POST['price'];
            $tier = $_POST['tier'];
            $category = $_POST['category_id'];
            
            $imageUrl = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $imageUrl = upload_image($_FILES['image']);
            }
            
            $stmt = db()->prepare("INSERT INTO products (seller_id, category_id, name, description, price, tier, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([current_user()['id'], $category, $name, $desc, $price, $tier, $imageUrl]);
            
            redirect('/seller/dashboard');
        }
        break;

    case '/admin/dashboard':
        require_role('admin');
        // Fetch stats
        $users = db()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
        // Fixed: Explicitly select product id to avoid ambiguity if column names clash, though here it's fine. 
        // More importantly, fetch Status for users.
        $products = db()->query("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id ORDER BY p.created_at DESC")->fetchAll();
        
        view('header');
        view('dashboard_admin', ['users' => $users, 'products' => $products]);
        view('footer');
        break;

    case '/admin/user/approve':
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $action = $_POST['action']; // 'approve' or 'reject'
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            $stmt = db()->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            redirect('/admin/dashboard');
        }
        break;

    case '/admin/product/delete':
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            redirect('/admin/dashboard');
        }
        break;

    case '/admin/product/edit':
        require_role('admin');
        $id = $_GET['id'] ?? null;
        if (!$id) redirect('/admin/dashboard');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $desc = $_POST['description'];
            $price = $_POST['price'];
            $tier = $_POST['tier'];
            $category = $_POST['category_id'];
            
            // Check if Image uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $imageUrl = upload_image($_FILES['image']);
                $stmt = db()->prepare("UPDATE products SET name=?, description=?, price=?, tier=?, category_id=?, image_url=? WHERE id=?");
                $stmt->execute([$name, $desc, $price, $tier, $category, $imageUrl, $id]);
            } else {
                $stmt = db()->prepare("UPDATE products SET name=?, description=?, price=?, tier=?, category_id=? WHERE id=?");
                $stmt->execute([$name, $desc, $price, $tier, $category, $id]);
            }
            redirect('/admin/dashboard');
        } else {
            $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            view('header');
            view('edit_product', ['product' => $product]);
            view('footer');
        }
        break;
        
    case '/cart':
        // Show cart
        view('header');
        view('cart'); // Need to implement cart fetching logic here or in template
        view('footer');
        break;

    case '/cart/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_logged_in()) redirect('/login');
            
            $productId = $_POST['product_id'];
            $user = current_user();
            
            // Check if exists
            $stmt = db()->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user['id'], $productId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = db()->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
                $stmt->execute([$existing['id']]);
            } else {
                $stmt = db()->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $stmt->execute([$user['id'], $productId]);
            }
            redirect('/');
        }
        break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
