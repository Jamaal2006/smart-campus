<?php
$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('customer');

$userId    = $_SESSION['user_id'];
$cartItems = getCartItems($pdo, $userId);

if (empty($cartItems)) {
    header('Location: /customer/cart.php');
    exit;
}

// Fetch user details
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :uid');
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch();

$rewards = getLoyaltyRewards($pdo);
$subtotal = getCartTotal($cartItems);
$deliveryFee = 3.50;
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fulfillment   = $_POST['fulfillment_type'] ?? 'collection';
    $address       = trim($_POST['delivery_address'] ?? '');
    $slot          = trim($_POST['collection_slot'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');
    $rewardId      = (int)($_POST['reward_id'] ?? 0);
    $usePoints     = max(0, (int)($_POST['use_points'] ?? 0));

    if (!in_array($fulfillment, ['collection', 'delivery'])) {
        $errors[] = 'Invalid fulfilment type.';
    }
    if ($fulfillment === 'delivery' && $address === '') {
        $errors[] = 'Delivery address is required.';
    }
    if ($fulfillment === 'collection' && $slot === '') {
        $errors[] = 'Please select a collection slot.';
    }

    // Loyalty points discount
    $discountAmt = 0.0;
    $pointsUsed  = 0;
    if ($rewardId > 0) {
        $rewardStmt = $pdo->prepare('SELECT * FROM loyalty_rewards WHERE reward_id = :rid AND is_active = 1');
        $rewardStmt->execute([':rid' => $rewardId]);
        $reward = $rewardStmt->fetch();
        if ($reward && (int)$user['loyalty_points'] >= (int)$reward['points_required']) {
            $pointsUsed = (int)$reward['points_required'];
            if ($reward['discount_percent']) {
                $discountAmt = round($subtotal * ((float)$reward['discount_percent'] / 100), 2);
            } elseif ($reward['discount_amount'] !== null) {
                $discountAmt = (float)$reward['discount_amount'];
            }
        }
    }

    $finalDelivery = $fulfillment === 'delivery' ? $deliveryFee : 0.0;
    if (isset($reward) && $reward['discount_amount'] !== null && (float)$reward['discount_amount'] === 0.0) {
        // Free delivery reward
        $finalDelivery = 0.0;
    }
    $total = max(0, $subtotal + $finalDelivery - $discountAmt);
    $pointsEarned = calculateLoyaltyPointsEarned($total);

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Create order
            $oStmt = $pdo->prepare(
                'INSERT INTO orders
                    (user_id, status, fulfillment_type, delivery_address, collection_slot, total_amount, discount_amount, loyalty_points_used, loyalty_points_earned, notes)
                 VALUES
                    (:uid, :status, :ftype, :addr, :slot, :total, :disc, :pused, :pearned, :notes)'
            );
            $oStmt->execute([
                ':uid'     => $userId,
                ':status'  => 'pending',
                ':ftype'   => $fulfillment,
                ':addr'    => $fulfillment === 'delivery' ? $address : null,
                ':slot'    => $fulfillment === 'collection' ? $slot : null,
                ':total'   => $total,
                ':disc'    => $discountAmt,
                ':pused'   => $pointsUsed,
                ':pearned' => $pointsEarned,
                ':notes'   => $notes,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // Insert order items and update stock
            $iStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:oid, :pid, :qty, :price)'
            );
            $sStmt = $pdo->prepare(
                'UPDATE products SET stock_quantity = stock_quantity - :qty WHERE product_id = :pid AND stock_quantity >= :qty2'
            );
            foreach ($cartItems as $item) {
                $iStmt->execute([
                    ':oid'   => $orderId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':price' => $item['price'],
                ]);
                $sStmt->execute([':qty' => $item['quantity'], ':pid' => $item['product_id'], ':qty2' => $item['quantity']]);
            }

            // Update loyalty points
            $pointsDelta = $pointsEarned - $pointsUsed;
            $lpStmt = $pdo->prepare('UPDATE users SET loyalty_points = GREATEST(0, loyalty_points + :delta) WHERE user_id = :uid');
            $lpStmt->execute([':delta' => $pointsDelta, ':uid' => $userId]);

            // Clear cart
            $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid')->execute([':uid' => $userId]);

            $pdo->commit();

            header('Location: /customer/orders.php?id=' . $orderId . '&placed=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Checkout error: ' . $e->getMessage());
            $errors[] = 'Something went wrong placing your order. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Checkout</h1>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul style="margin:0;padding-left:1rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/customer/checkout.php" novalidate>
        <?= csrfField() ?>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:var(--space-xl);align-items:start;">

            <!-- Left: Checkout form -->
            <div>

                <!-- Fulfilment type -->
                <div class="card" style="padding:var(--space-lg);margin-bottom:var(--space-lg);">
                    <h2 style="font-size:1.2rem;margin-bottom:var(--space-md);">How would you like to receive your order?</h2>
                    <div class="d-flex gap-md flex-wrap">
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                            <input type="radio" name="fulfillment_type" value="collection" <?= ($_POST['fulfillment_type'] ?? 'collection') === 'collection' ? 'checked' : '' ?> required>
                            🏪 Collection from GLH
                        </label>
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                            <input type="radio" name="fulfillment_type" value="delivery" <?= ($_POST['fulfillment_type'] ?? '') === 'delivery' ? 'checked' : '' ?>>
                            🚚 Home Delivery (+<?= formatPrice($deliveryFee) ?>)
                        </label>
                    </div>

                    <div id="collection-field" class="form-group mt-md">
                        <label for="collection_slot">Collection date &amp; time</label>
                        <input type="datetime-local" id="collection_slot" name="collection_slot"
                               value="<?= e($_POST['collection_slot'] ?? '') ?>"
                               min="<?= date('Y-m-d\TH:i', strtotime('+1 day')) ?>">
                    </div>

                    <div id="delivery-field" class="form-group mt-md" style="display:none;">
                        <label for="delivery_address">Delivery address</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" autocomplete="street-address"><?= e($_POST['delivery_address'] ?? $user['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card" style="padding:var(--space-lg);margin-bottom:var(--space-lg);">
                    <div class="form-group">
                        <label for="notes">Order notes <span class="text-muted">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Any special instructions…"><?= e($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Loyalty rewards -->
                <?php if (!empty($rewards) && (int)$user['loyalty_points'] > 0): ?>
                    <div class="card" style="padding:var(--space-lg);margin-bottom:var(--space-lg);">
                        <h2 style="font-size:1.2rem;margin-bottom:var(--space-xs);">🌟 Use Loyalty Points</h2>
                        <p class="text-muted mb-md">You have <strong><?= (int)$user['loyalty_points'] ?> points</strong> available.</p>
                        <div class="rewards-grid">
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                                <input type="radio" name="reward_id" value="0" <?= ($_POST['reward_id'] ?? '0') === '0' ? 'checked' : '' ?>>
                                No reward
                            </label>
                            <?php foreach ($rewards as $reward): ?>
                                <?php $canUse = (int)$user['loyalty_points'] >= (int)$reward['points_required']; ?>
                                <label class="reward-card <?= $canUse ? 'available' : '' ?>" style="display:flex;align-items:flex-start;gap:0.5rem;cursor:<?= $canUse ? 'pointer' : 'not-allowed' ?>;">
                                    <input type="radio" name="reward_id" value="<?= (int)$reward['reward_id'] ?>"
                                           <?= !$canUse ? 'disabled' : '' ?>
                                           <?= ($_POST['reward_id'] ?? '') == $reward['reward_id'] ? 'checked' : '' ?>>
                                    <span>
                                        <strong><?= e($reward['name']) ?></strong> — <?= (int)$reward['points_required'] ?> pts<br>
                                        <span class="text-muted" style="font-size:0.85rem;"><?= e($reward['description']) ?></span>
                                        <?php if (!$canUse): ?>
                                            <br><span style="color:var(--clr-danger);font-size:0.8rem;">Need <?= (int)$reward['points_required'] - (int)$user['loyalty_points'] ?> more points</span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Order summary -->
            <aside class="order-summary" aria-label="Order summary">
                <h2 style="font-size:1.2rem;margin-bottom:var(--space-md);">Your Order</h2>
                <?php foreach ($cartItems as $item): ?>
                    <div class="summary-row">
                        <span><?= e($item['name']) ?> &times; <?= (int)$item['quantity'] ?></span>
                        <span><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></span>
                    </div>
                <?php endforeach; ?>
                <hr class="divider" style="margin-block:var(--space-sm);">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery</span>
                    <span id="delivery-cost"><?= formatPrice($deliveryFee) ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span id="checkout-total"><?= formatPrice($subtotal + $deliveryFee) ?></span>
                </div>
                <p class="form-text mt-sm">You will earn approx. <strong><?= calculateLoyaltyPointsEarned($subtotal) ?> loyalty points</strong> for this order.</p>
                <button type="submit" class="btn btn-primary btn-block btn-lg mt-md">Place Order</button>
                <a href="/customer/cart.php" class="btn btn-outline btn-block mt-sm">← Back to Cart</a>
            </aside>
        </div>
    </form>
</div>

<script>
(function () {
    var radios = document.querySelectorAll('[name="fulfillment_type"]');
    var colField = document.getElementById('collection-field');
    var delField = document.getElementById('delivery-field');
    var deliveryCostEl = document.getElementById('delivery-cost');
    var totalEl = document.getElementById('checkout-total');
    var subtotal = <?= json_encode($subtotal) ?>;
    var deliveryFee = <?= json_encode($deliveryFee) ?>;

    function update() {
        var selected = document.querySelector('[name="fulfillment_type"]:checked');
        if (!selected) return;
        if (selected.value === 'delivery') {
            colField.style.display = 'none';
            delField.style.display = '';
            deliveryCostEl.textContent = '£' + deliveryFee.toFixed(2);
            totalEl.textContent = '£' + (subtotal + deliveryFee).toFixed(2);
        } else {
            colField.style.display = '';
            delField.style.display = 'none';
            deliveryCostEl.textContent = '£0.00 (collection)';
            totalEl.textContent = '£' + subtotal.toFixed(2);
        }
    }

    radios.forEach(function (r) { r.addEventListener('change', update); });
    update();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
