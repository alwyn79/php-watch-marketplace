<div class="container mt-8">
    <div class="flex justify-between items-center mb-8">
        <h1>User Dashboard</h1>
        <div class="flex gap-4">
            <span class="text-muted">Welcome, <strong><?= htmlspecialchars(current_user()['name']) ?></strong></span>
            <a href="/logout" class="text-red">Logout</a>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 3fr; gap: 2rem;">
        <!-- Sidebar -->
        <div>
            <div class="card p-4">
                <nav>
                    <a href="/user/orders" class="block p-2 text-gold" style="border-left: 2px solid var(--gold);">Order History</a>
                    <a href="/" class="block p-2 text-muted hover:text-white mt-2">Back to Shop</a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div>
            <h3 class="mb-4">My Orders</h3>
            <?php if (empty($orders)): ?>
                <div class="card p-8 text-center">
                    <p class="text-muted">You haven't placed any orders yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="card mb-4 p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-muted block text-sm">Order #<?= $order['id'] ?></span>
                            <span class="text-sm"><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="badge" style="background: <?= $order['status'] === 'paid' ? '#065f46' : '#92400e' ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                            <h4 class="mt-2 text-gold">₹<?= number_format($order['total']) ?></h4>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid #333; padding-top: 1rem;">
                        <p class="text-sm text-muted"><strong>Shipping Address:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        <?php if ($order['tracking_number']): ?>
                            <p class="text-sm text-gold mt-2"><strong>Tracking:</strong> <?= htmlspecialchars($order['tracking_number']) ?></p>
                        <?php else: ?>
                            <p class="text-sm text-muted mt-2"><em>Tracking number will be assigned once shipped.</em></p>
                        <?php endif; ?>
                    </div>

                    <?php 
                    // Fetch order items
                    $stmt = db()->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $stmt->execute([$order['id']]);
                    $items = $stmt->fetchAll();
                    ?>
                    <div class="mt-4 flex gap-4 overflow-x-auto pb-2">
                        <?php foreach ($items as $item): ?>
                            <div class="text-xs card p-2" style="min-width: 150px; background: #1a1a1a;">
                                <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                Qty: <?= $item['quantity'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
