<div class="container mt-8">
    <div class="flex justify-between items-center mb-8">
        <h1>Seller Dashboard</h1>
        <div class="badge badge-luxury">Authorized Dealer</div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Add Product Form -->
        <div>
            <div class="card p-8 sticky" style="top: 100px;">
                <h3 class="mb-4">List New Timepiece</h3>
                <form action="/seller/add-product" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Watch Model / Name</label>
                        <input type="text" name="name" placeholder="e.g. Rolex Submariner" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <!-- Ideally fetch from DB, hardcoded for now -->
                            <option value="1">Divers</option>
                            <option value="2">Dress</option>
                            <option value="3">Sports</option>
                            <option value="4">Smart</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tier</label>
                        <select name="tier">
                            <option value="budget">Budget (Everyday)</option>
                            <option value="luxury">Luxury (Premium)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Watch Image</label>
                        <input type="file" name="image" accept="image/*">
                        <small class="text-muted">Uploads to S3 Cloud Storage</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">List Watch</button>
                </form>
            </div>
        </div>

        <!-- Product List -->
        <div>
            <h3 class="mb-4">Your Inventory</h3>
            <?php if (empty($products)): ?>
                <div class="card p-8 text-center">
                    <p>You haven't listed any watches yet.</p>
                </div>
            <?php else: ?>
                <div class="flex flex-col gap-4">
                    <?php foreach ($products as $product): ?>
                        <div class="card flex" style="flex-direction: row; height: 150px;">
                            <div style="width: 150px; background: #222;">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            </div>
                            <div class="p-4 flex flex-col justify-between" style="flex: 1;">
                                <div>
                                    <div class="flex justify-between">
                                        <h4><?= htmlspecialchars($product['name']) ?></h4>
                                        <span class="text-gold">₹<?= number_format($product['price']) ?></span>
                                    </div>
                                    <span class="badge badge-sm badge-<?= $product['tier'] ?>" style="position:static; font-size: 0.7rem;"><?= strtoupper($product['tier']) ?></span>
                                </div>
                                <div class="flex justify-between items-end">
                                    <span class="text-muted" style="font-size: 0.8rem;">Listed: <?= date('M d, Y', strtotime($product['created_at'])) ?></span>
                                    <div>
                                        <!-- Edit/Delete actions could go here -->
                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">Edit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
