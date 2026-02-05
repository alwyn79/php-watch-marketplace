<div class="container flex justify-center items-center" style="min-height: 60vh;">
    <div class="card p-8" style="width: 100%; max-width: 400px;">
        <h2 class="text-center mb-8">Login</h2>
        
        <?php if (isset($error)): ?>
            <div style="background: rgba(255,0,0,0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
        </form>
        
        <p class="text-center mt-4 text-muted">
            Don't have an account? <a href="/register" class="text-gold">Register</a>
        </p>
    </div>
</div>
