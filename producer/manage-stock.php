<?php
$pageTitle = 'Stock Management';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('producer');

$userId   = $_SESSION['user_id'];
$producer = getProducerByUserId($pdo, $userId);
if (!$producer) { header('Location: /producer/dashboard.php'); exit; }

$producerId = (int)$producer['producer_id'];
$message = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $updates = $_POST['stock'] ?? [];
    foreach ($updates as $productId => $newQty) {
        $productId = (int)$productId;
        $newQty    = max(0, (int)$newQty);

        // Ensure product belongs to this producer
        $check = $pdo->prepare('SELECT product_id FROM products WHERE product_id = :pid AND producer_id = :prid');
        $check->execute([':pid' => $productId, ':prid' => $producerId]);
        if ($check->fetch()) {
            $pdo->prepare('UPDATE products SET stock_quantity = :qty WHERE product_id = :pid')
                ->execute([':qty' => $newQty, ':pid' => $productId]);
        }
    }
    $message = 'Stock levels updated successfully.';
}

// Focus on specific product if id given
$focusId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$products = getProducerProducts($pdo, $producerId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Stock Management</h1>
        <p><?= e($producer['farm_name']) ?></p>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="5000"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="card" style="padding:var(--space-xxl);text-align:center;">
            <p class="text-muted">No products to manage.</p>
            <a href="/producer/manage-products.php?action=add" class="btn btn-primary mt-md">Add a Product</a>
        </div>
    <?php else: ?>
        <form method="post" action="/producer/manage-stock.php">
            <?= csrfField() ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Current Stock</th>
                            <th>New Stock Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr id="product-<?= (int)$product['product_id'] ?>"
                                <?= $focusId === (int)$product['product_id'] ? 'style="background:var(--clr-primary-lt);"' : '' ?>>
                                <td><strong><?= e($product['name']) ?></strong></td>
                                <td><?= e($product['category_name'] ?? '—') ?></td>
                                <td><?= e($product['unit']) ?></td>
                                <td>
                                    <?php if ((int)$product['stock_quantity'] === 0): ?>
                                        <span class="stock-out"><?= (int)$product['stock_quantity'] ?></span>
                                    <?php elseif ((int)$product['stock_quantity'] <= 5): ?>
                                        <span class="stock-low"><?= (int)$product['stock_quantity'] ?> ⚠</span>
                                    <?php else: ?>
                                        <span class="stock-good"><?= (int)$product['stock_quantity'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <label for="stock-<?= (int)$product['product_id'] ?>" class="sr-only">
                                        New stock for <?= e($product['name']) ?>
                                    </label>
                                    <input
                                        type="number"
                                        id="stock-<?= (int)$product['product_id'] ?>"
                                        name="stock[<?= (int)$product['product_id'] ?>]"
                                        value="<?= (int)$product['stock_quantity'] ?>"
                                        min="0"
                                        style="width:90px;"
                                    >
                                </td>
                                <td>
                                    <span class="badge <?= $product['is_available'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $product['is_available'] ? 'Listed' : 'Hidden' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-md mt-lg">
                <button type="submit" class="btn btn-primary">Save Stock Levels</button>
                <a href="/producer/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
