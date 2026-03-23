<?php
$pageTitle = 'My Cart';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('customer');

$userId = $_SESSION['user_id'];
$message = '';
$error   = '';

// Handle remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    verifyCsrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid AND product_id = :pid');
    $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    $message = 'Item removed from cart.';
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    verifyCsrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = (int)($_POST['quantity'] ?? 1);
    if ($qty < 1) {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid AND product_id = :pid');
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    } else {
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = :qty WHERE user_id = :uid AND product_id = :pid');
        $stmt->execute([':qty' => $qty, ':uid' => $userId, ':pid' => $productId]);
    }
    $message = 'Cart updated.';
}

$cartItems = getCartItems($pdo, $userId);
$subtotal  = getCartTotal($cartItems);
$delivery  = 3.50; // flat delivery fee

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Cart</h1>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="4000"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="card" style="padding:var(--space-xxl);text-align:center;">
            <p style="font-size:3rem;">🛒</p>
            <p class="text-muted mt-md">Your cart is empty.</p>
            <a href="/customer/products.php" class="btn btn-primary mt-lg">Browse Products</a>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 320px;gap:var(--space-xl);align-items:start;">

            <!-- Cart items -->
            <div>
                <div class="table-wrapper">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Price</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Line Total</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($item['name']) ?></strong><br>
                                        <span class="text-muted" style="font-size:0.85rem;">By <?= e($item['farm_name']) ?></span>
                                    </td>
                                    <td><?= formatPrice((float)$item['price']) ?> / <?= e($item['unit']) ?></td>
                                    <td>
                                        <form method="post" action="/customer/cart.php" class="d-flex gap-sm align-center">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?= (int)$item['quantity'] ?>"
                                                min="1"
                                                max="<?= (int)$item['stock_quantity'] ?>"
                                                class="qty-input"
                                                data-product-id="<?= (int)$item['product_id'] ?>"
                                                aria-label="Quantity for <?= e($item['name']) ?>"
                                            >
                                            <button type="submit" name="update_qty" class="btn btn-sm btn-outline">Update</button>
                                        </form>
                                    </td>
                                    <td id="line-<?= (int)$item['product_id'] ?>">
                                        <?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?>
                                    </td>
                                    <td>
                                        <form method="post" action="/customer/cart.php">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                            <button
                                                type="submit"
                                                name="remove_item"
                                                class="btn btn-sm btn-danger"
                                                data-confirm="Remove <?= e($item['name']) ?> from your cart?"
                                            >Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-md mt-md">
                    <a href="/customer/products.php" class="btn btn-outline">← Continue Shopping</a>
                </div>
            </div>

            <!-- Order summary -->
            <aside class="order-summary" aria-label="Order summary">
                <h2 style="font-size:1.2rem;margin-bottom:var(--space-md);">Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="cart-total"><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery (if applicable)</span>
                    <span><?= formatPrice($delivery) ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Estimated Total</span>
                    <span><?= formatPrice($subtotal + $delivery) ?></span>
                </div>
                <p class="form-text mt-sm">Delivery fee waived for collection orders. Final total calculated at checkout.</p>
                <a href="/customer/checkout.php" class="btn btn-primary btn-block btn-lg mt-md">Proceed to Checkout</a>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
