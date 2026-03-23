<?php
$pageTitle = 'Fresh Local Food & Drink';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$producers = getProducers($pdo);
$featuredProducts = getProducts($pdo);
$featuredProducts = array_slice($featuredProducts, 0, 6);
?>

<!-- Hero -->
<section class="hero" aria-labelledby="hero-heading">
    <div class="container">
        <h1 id="hero-heading">Fresh from Your Local Farms</h1>
        <p>Greenfield Local Hub connects you directly with trusted local farmers and food producers. Real food, real people, fair prices — delivered or ready for collection.</p>
        <div class="hero-cta">
            <a href="/customer/products.php" class="btn btn-accent btn-lg">Shop Now</a>
            <a href="#about" class="btn btn-outline btn-lg" style="border-color:#fff;color:#fff;">Learn More</a>
        </div>
    </div>
</section>

<!-- Benefits strip -->
<section class="section" style="padding-block:var(--space-xl);background:var(--clr-primary-lt);" aria-label="Key benefits">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">🌱</div>
                <h3 class="feature-title">Locally Grown</h3>
                <p class="text-muted">Produce travels fewer miles, retaining more nutrients and flavour.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">💚</div>
                <h3 class="feature-title">Support Farmers</h3>
                <p class="text-muted">More of every pound goes directly to the people who grow your food.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">🏷️</div>
                <h3 class="feature-title">Transparent Pricing</h3>
                <p class="text-muted">Clear, honest prices with no hidden charges. See exactly what you pay.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">🚚</div>
                <h3 class="feature-title">Collection or Delivery</h3>
                <p class="text-muted">Choose a convenient collection slot or opt for local home delivery.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">⭐</div>
                <h3 class="feature-title">Loyalty Rewards</h3>
                <p class="text-muted">Earn points with every order and redeem them for discounts.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured products -->
<section class="section" aria-labelledby="featured-heading">
    <div class="container">
        <h2 class="section-heading" id="featured-heading">Featured Products</h2>
        <p class="section-subheading">Seasonal produce from our local farmers, available now.</p>

        <?php if (empty($featuredProducts)): ?>
            <p class="text-center text-muted">Products coming soon — check back shortly!</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <article class="card" aria-label="<?= e($product['name']) ?>">
                        <?php if ($product['image_path']): ?>
                            <img class="card-img" src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-img-placeholder" role="img" aria-label="<?= e($product['name']) ?> — no image available">🥦</div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?= e($product['name']) ?></h3>
                            <p class="card-subtitle">By <?= e($product['farm_name']) ?></p>
                            <p class="card-text"><?= e(mb_substr($product['description'] ?? '', 0, 80)) ?><?= strlen($product['description'] ?? '') > 80 ? '…' : '' ?></p>
                            <p class="product-price mt-sm">
                                <?= formatPrice((float)$product['price']) ?>
                                <span class="product-unit">/ <?= e($product['unit']) ?></span>
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="/customer/products.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-primary btn-sm btn-block">View Product</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="text-center mt-xl">
                <a href="/customer/products.php" class="btn btn-outline btn-lg">View All Products</a>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Our Producers -->
<section class="section" id="producers" style="background:var(--clr-primary-lt);" aria-labelledby="producers-heading">
    <div class="container">
        <h2 class="section-heading" id="producers-heading">Meet Our Producers</h2>
        <p class="section-subheading">Real farmers and food makers from the Greenfield area who care deeply about quality and sustainability.</p>

        <?php if (empty($producers)): ?>
            <p class="text-center text-muted">Producer profiles coming soon.</p>
        <?php else: ?>
            <div class="producer-grid">
                <?php foreach ($producers as $producer): ?>
                    <article class="card" aria-label="<?= e($producer['farm_name']) ?>">
                        <?php if ($producer['image_path']): ?>
                            <img class="card-img" src="<?= e($producer['image_path']) ?>" alt="<?= e($producer['farm_name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-img-placeholder" role="img" aria-label="<?= e($producer['farm_name']) ?>">🌾</div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?= e($producer['farm_name']) ?></h3>
                            <p class="card-subtitle">📍 <?= e($producer['location'] ?? '') ?></p>
                            <p class="card-text"><?= e(mb_substr($producer['description'] ?? '', 0, 120)) ?><?= strlen($producer['description'] ?? '') > 120 ? '…' : '' ?></p>
                            <?php if ($producer['farming_methods']): ?>
                                <p class="mt-sm" style="font-size:0.85rem;"><strong>Methods:</strong> <?= e(mb_substr($producer['farming_methods'], 0, 80)) ?>…</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- About GLH -->
<section class="section" id="about" aria-labelledby="about-heading">
    <div class="container" style="max-width:800px;">
        <h2 class="section-heading" id="about-heading">About Greenfield Local Hub</h2>
        <p class="section-subheading">A community cooperative connecting you with the people who produce your food.</p>
        <div style="font-size:1.05rem;line-height:1.8;">
            <p>Greenfield Local Hub is a cooperative of local farmers and food producers committed to making fresh, local, sustainably produced food accessible to everyone in our community.</p>
            <p class="mt-md">We believe in <strong>transparent pricing</strong> — you always know exactly where your money goes. We believe in <strong>traceability</strong> — every product on our platform is linked to the farm it came from. And we believe in <strong>community</strong> — by buying local you support jobs, reduce food miles, and enjoy fresher, tastier produce.</p>
            <p class="mt-md">Join thousands of local customers already shopping with us, and discover the difference that real, local food makes.</p>
        </div>
        <div class="hero-cta mt-xl">
            <a href="/register.php" class="btn btn-primary btn-lg">Join as a Customer</a>
            <a href="/register.php?role=producer" class="btn btn-outline btn-lg">Join as a Producer</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
