<div class="container mt-8">
    <div class="flex justify-between items-center mb-8">
        <h1>Admin Dashboard</h1>
        <div class="badge badge-luxury">System Administrator</div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-3 mb-8">
        <div class="card p-8 text-center">
            <h2 class="text-gold"><?= count($users) ?></h2>
            <p class="text-muted">Total Users</p>
        </div>
        <div class="card p-8 text-center">
            <h2 class="text-gold"><?= count($products) ?></h2>
            <p class="text-muted">Total Products</p>
        </div>
        <div class="card p-8 text-center">
            <h2 class="text-gold">₹<?= number_format(array_sum(array_column($products, 'price'))) ?></h2>
            <p class="text-muted">Inventory Value</p>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr; gap: 4rem;">
        
        <!-- Products Management -->
        <div class="card p-8">
            <h3 class="mb-4">Manage Inventory</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Img</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Tier</th>
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
                            <td><span class="badge badge-sm badge-<?= $p['tier'] ?>" style="position:static; font-size: 0.6rem;"><?= strtoupper($p['tier']) ?></span></td>
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
                                    <span style="color: red;">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'pending'): ?>
                                    <form action="/admin/user/approve" method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;" disabled>Manage</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
