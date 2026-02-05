<div class="container mt-8">
    <div class="flex justify-between items-center mb-8">
        <h1>Edit Product</h1>
        <a href="/admin/dashboard" class="btn btn-outline">Back to Dashboard</a>
    </div>

    <div class="card p-8" style="max-width: 600px; margin: 0 auto;">
        <form action="/admin/product/edit?id=<?= $product['id'] ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Watch Model / Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="1" <?= $product['category_id'] == 1 ? 'selected' : '' ?>>Divers</option>
                    <option value="2" <?= $product['category_id'] == 2 ? 'selected' : '' ?>>Dress</option>
                    <option value="3" <?= $product['category_id'] == 3 ? 'selected' : '' ?>>Sports</option>
                    <option value="4" <?= $product['category_id'] == 4 ? 'selected' : '' ?>>Smart</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tier</label>
                <select name="tier" class="form-control">
                    <option value="budget" <?= $product['tier'] === 'budget' ? 'selected' : '' ?>>Budget (Everyday)</option>
                    <option value="luxury" <?= $product['tier'] === 'luxury' ? 'selected' : '' ?>>Luxury (Premium)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Price (₹)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>New Image (Optional)</label>
                <?php if ($product['image_url']): ?>
                    <div class="mb-4">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" style="height: 100px; width: auto; border-radius: 4px;">
                        <p class="text-muted" style="font-size: 0.8rem;">Current Image</p>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*" style="padding: 0.5rem;">
            </div>
            
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
