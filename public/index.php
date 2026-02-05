<?php
require_once __DIR__ . '/../src/functions.php';

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Simple Router
switch ($path) {
    case '/':
        // Fetch products with filters
        $pdo = db();
        $params = [];
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $sql .= " AND p.name LIKE ?";
            $params[] = '%' . $_GET['search'] . '%';
        }
        
        if (isset($_GET['tier']) && !empty($_GET['tier'])) {
            $sql .= " AND p.tier = ?";
            $params[] = $_GET['tier'];
        }

        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $sql .= " AND (c.name = ? OR p.tier = ?)"; // Support legacy tier-as-category links
            $params[] = $_GET['category'];
            $params[] = $_GET['category'];
        }
        
        if (isset($_GET['min_price'])) {
            $sql .= " AND p.price >= ?";
            $params[] = (float)$_GET['min_price'];
        }
        
        if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = (float)$_GET['max_price'];
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
            $stock = $_POST['stock'] ?? 10;
            
            $imageUrl = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $imageUrl = upload_image($_FILES['image']);
            }
            
            $stmt = db()->prepare("INSERT INTO products (seller_id, category_id, name, description, price, tier, stock, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([current_user()['id'], $category, $name, $desc, $price, $tier, $stock, $imageUrl]);
            
            redirect('/seller/dashboard');
        }
        break;

    case '/admin/dashboard':
        require_role('admin');
        $pdo = db();
        // Fetch stats
        $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
        $products = $pdo->query("SELECT p.id, p.seller_id, p.category_id, p.name, p.description, p.price, p.tier, p.stock, p.image_url, p.created_at, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id ORDER BY p.created_at DESC")->fetchAll();
        $orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
        
        $totalSales = $pdo->query("SELECT SUM(total) FROM orders WHERE status = 'paid' OR status = 'shipped' OR status = 'completed'")->fetchColumn();
        
        view('header');
        view('dashboard_admin', [
            'users' => $users, 
            'products' => $products, 
            'orders' => $orders,
            'totalSales' => $totalSales ?: 0
        ]);
        view('footer');
        break;

    case '/admin/order/update':
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $tracking = $_POST['tracking_number'];
            
            $stmt = db()->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
            $stmt->execute([$status, $tracking, $id]);
            redirect('/admin/dashboard');
        }
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
        require_role('user');
        view('header');
        view('cart');
        view('footer');
        break;

    case '/cart/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_logged_in()) redirect('/login');
            
            $productId = $_POST['product_id'];
            $user = current_user();
            
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
            redirect('/cart');
        }
        break;

    case '/cart/update':
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cartId = $_POST['cart_id'];
            $quantity = (int)$_POST['quantity'];
            $userId = current_user()['id'];
            
            $stmt = db()->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cartId, $userId]);
            redirect('/cart');
        }
        break;

    case '/cart/remove':
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cartId = $_POST['cart_id'];
            $userId = current_user()['id'];
            
            $stmt = db()->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            redirect('/cart');
        }
        break;

    case '/checkout':
        require_role('user');
        view('header');
        view('checkout');
        view('footer');
        break;

    case '/checkout/process':
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = current_user()['id'];
            $address = $_POST['address'];
            
            $pdo = db();
            $pdo->beginTransaction();
            
            try {
                // 1. Get Cart Items
                $stmt = $pdo->prepare("SELECT c.*, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
                $stmt->execute([$userId]);
                $items = $stmt->fetchAll();
                
                if (empty($items)) throw new Exception("Cart is empty");
                
                $total = 0;
                foreach ($items as $item) {
                    $total += $item['price'] * $item['quantity'];
                    if ($item['stock'] < $item['quantity']) {
                        throw new Exception("Insufficent stock for " . $item['product_id']);
                    }
                }
                
                // 2. Create Order
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, shipping_address, status) VALUES (?, ?, ?, 'paid')");
                $stmt->execute([$userId, $total, $address]);
                $orderId = $pdo->lastInsertId();
                
                // 3. Create Order Items & Update Stock
                foreach ($items as $item) {
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                    
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                // 4. Clear Cart
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                $pdo->commit();
                redirect('/user/orders');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                die("Checkout Failed: " . $e->getMessage());
            }
        }
        break;

    case '/user/dashboard':
    case '/user/orders':
        require_role('user');
        $userId = current_user()['id'];
        $orders = db()->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $orders->execute([$userId]);
        $orders = $orders->fetchAll();
        
        view('header');
        view('dashboard_user', ['orders' => $orders]);
        view('footer');
        break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
