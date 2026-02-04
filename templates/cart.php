<div class="container mt-8">
    <h1 class="mb-8">Your Shopping Cart</h1>
    
    <?php 
    // Fetch cart items (logic embedded here for simplicity of task)
    $userId = current_user()['id'];
    $stmt = db()->prepare("
        SELECT c.id as cart_id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
    
    $total = 0;
    ?>

    <?php if (empty($cartItems)): ?>
        <div class="card p-8 text-center">
            <p class="mb-4">Your cart is empty.</p>
            <a href="/" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Items -->
            <div>
                <?php foreach ($cartItems as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <div class="card mb-4 p-4 flex gap-4 items-center">
                    <div style="width: 100px; height: 100px; background: #222; border-radius: 4px; overflow: hidden;">
                        <?php if ($item['image_url']): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                        <p class="text-gold">₹<?= number_format($item['price']) ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <input type="number" value="<?= $item['quantity'] ?>" min="1" style="width: 60px; padding: 0.25rem;">
                        <button class="text-muted hover:text-red"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="card p-8" style="height: fit-content;">
                <h3 class="mb-4">Order Summary</h3>
                <div class="flex justify-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span>₹<?= number_format($total) ?></span>
                </div>
                <div class="flex justify-between mb-4">
                    <span class="text-muted">Shipping</span>
                    <span>Free</span>
                </div>
                <div class="flex justify-between mb-8" style="border-top: 1px solid #333; padding-top: 1rem; font-size: 1.25rem;">
                    <span>Total</span>
                    <span class="text-gold">₹<?= number_format($total) ?></span>
                </div>
                <button class="btn btn-primary" style="width: 100%;">Checkout</button>
            </div>
        </div>
    <?php endif; ?>
</div>
