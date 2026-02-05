<div class="container mt-8">
    <div class="flex justify-between items-center mb-8">
        <h1>Admin Dashboard</h1>
        <div class="badge badge-luxury">System Administrator</div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-4 mb-8">
        <div class="card p-6 text-center">
            <h2 class="text-gold"><?= count($users) ?></h2>
            <p class="text-muted text-sm">Total Users</p>
        </div>
        <div class="card p-6 text-center">
            <h2 class="text-gold"><?= count($products) ?></h2>
            <p class="text-muted text-sm">Total Products</p>
        </div>
        <div class="card p-6 text-center">
            <h2 class="text-gold">₹<?= number_format($totalSales) ?></h2>
            <p class="text-muted text-sm">Total Sales</p>
        </div>
        <div class="card p-6 text-center">
            <h2 class="text-gold"><?= count($orders) ?></h2>
            <p class="text-muted text-sm">Total Orders</p>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr; gap: 3rem;">
        
        <!-- Orders Management -->
        <div class="card p-8">
            <h3 class="mb-6">Manage Orders</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tracking</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['user_name']) ?></td>
                            <td class="text-gold">₹<?= number_format($o['total']) ?></td>
                            <td>
                                <span class="badge badge-sm" style="background: <?= $o['status'] === 'paid' ? '#065f46' : ($o['status'] === 'shipped' ? '#1e40af' : '#374151') ?>">
                                    <?= strtoupper($o['status']) ?>
                                </span>
                            </td>
                            <td><?= $o['tracking_number'] ?: '<span class="text-muted">None</span>' ?></td>
                            <td>
                                <form action="/admin/order/update" method="POST" class="flex gap-2">
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-control" style="padding: 0.25rem; font-size: 0.7rem; width: 100px;">
                                        <option value="paid" <?= $o['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="shipped" <?= $o['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="completed" <?= $o['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <input type="text" name="tracking_number" placeholder="Tracking #" class="form-control" value="<?= htmlspecialchars($o['tracking_number'] ?? '') ?>" style="padding: 0.25rem; font-size: 0.7rem; width: 80px;">
                                    <button type="submit" class="btn btn-sm">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products Management -->
        <div class="card p-8">
            <h3 class="mb-4">Manage Inventory</h3>
            <!-- ... rest of products table ... -->
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Img</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Seller</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div style="width: 40px; height: 40px; background: #222; overflow: hidden; border-radius: 4px;">
                                    <?php if ($p['image_url']): ?>
                                        <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td class="text-gold">₹<?= number_format($p['price']) ?></td>
                            <td class="<?= ($p['stock'] ?? 10) < 5 ? 'text-red' : '' ?>"><?= $p['stock'] ?? 10 ?></td>
                            <td class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($p['seller_name']) ?></td>
                            <td>
                                <div class="flex gap-4">
                                    <a href="/admin/product/edit?id=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Edit</a>
                                    
                                    <form action="/admin/product/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="text-muted hover:text-red" style="padding: 0.25rem; font-size: 0.8rem; background: none; border: none; cursor: pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Management -->
        <div class="card p-8">
            <h3 class="mb-4">Recent Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= $u['role'] === 'admin' ? 'luxury' : ($u['role'] === 'seller' ? 'budget' : 'secondary') ?>">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'approved'): ?>
                                    <span class="text-gold">Active</span>
                                <?php elseif ($u['status'] === 'pending'): ?>
                                    <span style="color: orange;">Pending</span>
                                <?php else: ?>
                                    <span style="color: red;">Rejected/Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="/admin/user/approve" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <?php if ($u['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Reject</button>
                                    <?php elseif ($u['status'] === 'approved' && $u['role'] !== 'admin'): ?>
                                        <button type="submit" name="action" value="reject" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; color: var(--red);">Suspend</button>
                                    <?php elseif ($u['status'] === 'rejected'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Activate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>
</div>
