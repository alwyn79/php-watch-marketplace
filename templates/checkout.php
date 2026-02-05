<div class="container mt-8">
    <h1 class="mb-8">Checkout</h1>
    
    <?php 
    $userId = current_user()['id'];
    $stmt = db()->prepare("
        SELECT c.quantity, p.price, p.name 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
    
    if (empty($cartItems)) {
        redirect('/cart');
    }
    
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    ?>

    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div>
            <div class="card p-8">
                <h3 class="mb-6">Shipping Details</h3>
                <form action="/checkout/process" method="POST">
                    <div class="mb-4">
                        <label class="block mb-2">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars(current_user()['name']) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-2">Shipping Address</label>
                        <textarea name="address" class="form-control" rows="4" placeholder="Enter your full address with PIN code" required></textarea>
                    </div>
                    <div class="mb-6">
                        <label class="block mb-2">Payment Method</label>
                        <div class="card p-4" style="background: #222; border: 1px solid var(--gold);">
                            <input type="radio" name="payment" value="cod" checked> Cash on Delivery (Demo Mode)
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Complete Purchase</button>
                </form>
            </div>
        </div>

        <div>
            <div class="card p-8">
                <h3 class="mb-4">Your Order</h3>
                <?php foreach ($cartItems as $item): ?>
                <div class="flex justify-between mb-2">
                    <span class="text-muted"><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                    <span>₹<?= number_format($item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="flex justify-between mt-4 pt-4" style="border-top: 1px solid #333;">
                    <strong>Total to Pay</strong>
                    <strong class="text-gold">₹<?= number_format($total) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>
