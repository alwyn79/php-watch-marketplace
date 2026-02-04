<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chronos | Luxury & Precision</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<header>
    <div class="container flex justify-between items-center">
        <a href="/" class="logo">CHRONOS.</a>
        
        <nav class="nav-links">
            <ul class="flex gap-8 items-center">
                <li><a href="/">Collection</a></li>
                <li><a href="/?category=luxury">Luxury</a></li>
                <li><a href="/?category=budget">Everyday</a></li>
                
                <?php if (is_logged_in()): ?>
                    <?php if (current_user()['role'] === 'seller'): ?>
                        <li><a href="/seller/dashboard" class="text-gold">Seller Dashboard</a></li>
                    <?php elseif (current_user()['role'] === 'admin'): ?>
                        <li><a href="/admin/dashboard" class="text-gold">Admin</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/cart"><i class="fas fa-shopping-bag"></i> (<?= get_cart_count() ?>)</a></li>
                    <li><a href="/logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login">Login</a></li>
                    <li><a href="/register" class="btn btn-outline">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main>
