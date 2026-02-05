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
                        <form action="/cart/update" method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" style="width: 60px; padding: 0.25rem; background: #222; border: 1px solid #444; color: white; border-radius: 4px;">
                            <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.5rem;"><i class="fas fa-sync-alt"></i></button>
                        </form>
                        <form action="/cart/remove" method="POST">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <button type="submit" class="text-muted hover:text-red bg-transparent" style="cursor: pointer; border: none;"><i class="fas fa-trash"></i></button>
                        </form>
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
                <a href="/checkout" class="btn btn-primary" style="width: 100%; text-align: center; display: block;">Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>
