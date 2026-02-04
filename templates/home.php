<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1 class="mb-4">Time is Luxury.</h1>
        <p class="mb-8" style="font-size: 1.2rem; max-width: 600px; margin: 0 auto 2rem; color: var(--color-text-secondary);">
            Discover the finest collection of precision-engineered timepieces. 
            From the boardroom to the deep blue sea.
        </p>
        <div class="flex gap-4 justify-center">
            <a href="/?category=luxury" class="btn btn-primary">Shop Luxury</a>
            <a href="/?category=budget" class="btn btn-outline">View Everyday Collection</a>
        </div>
    </div>
</section>

<!-- Featured Collection -->
<section class="container mt-8">
    <div class="flex justify-between items-center mb-8">
        <h2>Latest Arrivals</h2>
        <a href="#" class="text-gold">View All <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center p-8" style="background: var(--color-bg-card); border-radius: var(--radius-md);">
            <p>No watches found in the collection yet.</p>
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'seller'): ?>
                <a href="/seller/dashboard" class="btn btn-primary mt-4">Add the first Watch</a>
            <?php else: ?>
                <p class="text-muted mt-2">Check back later for new arrivals.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-3">
            <?php foreach ($products as $product): ?>
            <div class="card">
                <div class="card-image">
                    <?php if ($product['image_url']): ?>
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div class="flex items-center justify-center" style="height:100%; color: #333;">
                            <i class="fas fa-clock fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <span class="badge badge-<?= $product['tier'] ?>"><?= strtoupper($product['tier']) ?></span>
                </div>
                <div class="card-body">
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($product['name']) ?></h3>
                    <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1rem; height: 3em; overflow: hidden;">
                        <?= htmlspecialchars($product['description']) ?>
                    </p>
                    <div class="flex justify-between items-center">
                        <span style="font-family: var(--font-heading); font-size: 1.25rem;">₹<?= number_format($product['price']) ?></span>
                        <form action="/cart/add" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
