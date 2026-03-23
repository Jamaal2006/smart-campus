<?php // GLH shared footer ?>
</main><!-- /#main-content -->

<footer class="site-footer" role="contentinfo">
    <div class="container footer-grid">
        <div class="footer-col">
            <p class="footer-brand">🌿 Greenfield Local Hub</p>
            <p class="footer-tagline">Fresh from the farm, straight to your door.</p>
        </div>
        <div class="footer-col">
            <h3>Quick Links</h3>
            <ul role="list">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/customer/products.php">Shop</a></li>
                <li><a href="/index.php#producers">Our Producers</a></li>
                <li><a href="/index.php#about">About GLH</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Customers</h3>
            <ul role="list">
                <li><a href="/register.php">Create Account</a></li>
                <li><a href="/login.php">Log In</a></li>
                <li><a href="/customer/orders.php">Order History</a></li>
                <li><a href="/customer/account.php">Loyalty Points</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Producers</h3>
            <ul role="list">
                <li><a href="/producer/dashboard.php">Producer Portal</a></li>
                <li><a href="/register.php?role=producer">Join GLH</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Accessibility</h3>
            <ul role="list">
                <li>
                    <button class="btn-link" id="font-increase" aria-label="Increase font size">A+</button> /
                    <button class="btn-link" id="font-decrease" aria-label="Decrease font size">A-</button>
                </li>
                <li>
                    <button class="btn-link" id="dark-mode-toggle" aria-label="Toggle dark mode">🌙 Dark mode</button>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Greenfield Local Hub. All rights reserved.</p>
            <p class="legal-links">
                <a href="#">Privacy Policy</a> &middot;
                <a href="#">Terms &amp; Conditions</a> &middot;
                <a href="#">Accessibility Statement</a>
            </p>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js"></script>
</body>
</html>