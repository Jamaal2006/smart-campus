<?php
$pageTitle = 'Shop';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$categories = getCategories($pdo);
$selectedCat = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');

// Build product query with optional search
$sql = 'SELECT p.*, c.name AS category_name, pr.farm_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN producers pr ON p.producer_id = pr.producer_id
        WHERE p.is_available = 1 AND p.stock_quantity > 0';
$params = [];

if ($selectedCat) {
    $sql .= ' AND p.category_id = :cat';
    $params[':cat'] = $selectedCat;
}
if ($search !== '') {
    $sql .= ' AND (p.name LIKE :search OR p.description LIKE :search2)';
    $like = '%' . $search . '%';
    $params[':search']  = $like;
    $params[':search2'] = $like;
}
$sql .= ' ORDER BY p.name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Handle add to cart POST
$cartMessage = '';
$cartError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    verifyCsrf();
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=/customer/products.php');
        exit;
    }
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = max(1, (int)($_POST['quantity'] ?? 1));

    // Validate product exists and has stock
    $pCheck = getProduct($pdo, $productId);
    if (!$pCheck || $pCheck['stock_quantity'] < $qty) {
        $cartError = 'Sorry, this product is not available in the requested quantity.';
    } else {
        $stmt2 = $pdo->prepare(
            'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (:uid, :pid, :qty)
             ON DUPLICATE KEY UPDATE quantity = quantity + :qty2'
        );
        $stmt2->execute([
            ':uid'  => $_SESSION['user_id'],
            ':pid'  => $productId,
            ':qty'  => $qty,
            ':qty2' => $qty,
        ]);
        $cartMessage = 'Added to your cart!';
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1>Our Shop</h1>
        <p>Fresh, local produce direct from Greenfield farmers.</p>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($cartMessage): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="4000"><?= e($cartMessage) ?></div>
    <?php endif; ?>
    <?php if ($cartError): ?>
        <div class="alert alert-danger" role="alert"><?= e($cartError) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="d-flex gap-md flex-wrap align-center mb-lg" style="justify-content:space-between;">
        <form method="get" action="/customer/products.php" role="search" class="d-flex gap-sm flex-wrap">
            <label for="search" class="sr-only">Search products</label>
            <input type="text" id="search" name="search" value="<?= e($search) ?>" placeholder="Search products…" style="width:220px;">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if ($search || $selectedCat): ?>
                <a href="/customer/products.php" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <nav aria-label="Category filter">
            <ul class="d-flex gap-sm flex-wrap" role="list">
                <li>
                    <a href="/customer/products.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
                       class="btn btn-sm <?= !$selectedCat ? 'btn-primary' : 'btn-outline' ?>"
                       <?= !$selectedCat ? 'aria-current="true"' : '' ?>>All</a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/customer/products.php?category=<?= $cat['category_id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="btn btn-sm <?= $selectedCat === (int)$cat['category_id'] ? 'btn-primary' : 'btn-outline' ?>"
                           <?= $selectedCat === (int)$cat['category_id'] ? 'aria-current="true"' : '' ?>>
                            <?= e($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>

    <!-- Product grid -->
    <?php if (empty($products)): ?>
        <p class="text-center text-muted" style="padding:var(--space-xxl) 0;">No products found. <a href="/customer/products.php">Clear filters</a></p>
    <?php else: ?>
        <p class="text-muted mb-md"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> found</p>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="card" aria-label="<?= e($product['name']) ?>">
                    <?php if ($product['image_path']): ?>
                        <img class="card-img" src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-img-placeholder" role="img" aria-label="<?= e($product['name']) ?> image placeholder">🥕</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title" style="font-size:1rem;"><?= e($product['name']) ?></h2>
                        <p class="card-subtitle">By <?= e($product['farm_name']) ?> &mdash; <?= e($product['category_name'] ?? 'Uncategorised') ?></p>
                        <p class="card-text"><?= e(mb_substr($product['description'] ?? '', 0, 80)) ?><?= strlen($product['description'] ?? '') > 80 ? '…' : '' ?></p>
                        <p class="product-price mt-sm">
                            <?= formatPrice((float)$product['price']) ?>
                            <span class="product-unit">/ <?= e($product['unit']) ?></span>
                        </p>
                        <?php
                            $qty = (int)$product['stock_quantity'];
                            if ($qty > 10):
                        ?>
                            <p class="product-stock stock-good">✓ In stock</p>
                        <?php elseif ($qty > 0): ?>
                            <p class="product-stock stock-low">⚠ Only <?= $qty ?> left</p>
                        <?php else: ?>
                            <p class="product-stock stock-out">✗ Out of stock</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <?php if ($qty > 0): ?>
                            <form method="post" action="/customer/products.php">
                                <?= csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                                <div class="d-flex gap-sm align-center">
                                    <label for="qty-<?= $product['product_id'] ?>" class="sr-only">Quantity</label>
                                    <input type="number" id="qty-<?= $product['product_id'] ?>" name="quantity" value="1" min="1" max="<?= $qty ?>" style="width:60px;" aria-label="Quantity">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm" style="flex:1;">Add to Cart</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-outline btn-sm btn-block" disabled aria-disabled="true">Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
