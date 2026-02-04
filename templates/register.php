<div class="container flex justify-center items-center" style="min-height: 70vh;">
    <div class="card p-8" style="width: 100%; max-width: 500px;">
        <h2 class="text-center mb-8">Join Chronos</h2>
        
        <?php if (isset($error)): ?>
            <div style="background: rgba(255,0,0,0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="/register" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Account Type</label>
                <select name="role">
                    <option value="user">Customer (I want to buy)</option>
                    <option value="seller">Seller (I want to list watches)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
        </form>
        
        <p class="text-center mt-4 text-muted">
            Already a member? <a href="/login" class="text-gold">Login</a>
        </p>
    </div>
</div>
