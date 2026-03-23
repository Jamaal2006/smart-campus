<?php
$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('customer');

$userId = $_SESSION['user_id'];

// Single order view
if (isset($_GET['id'])) {
    $order = getOrderWithItems($pdo, (int)$_GET['id'], $userId);
    if (!$order) {
        header('Location: /customer/orders.php');
        exit;
    }

    $pageTitle = 'Order #' . $order['order_id'];
    require_once __DIR__ . '/../includes/header.php';

    // Tracking step index
    $steps  = ['pending', 'confirmed', 'ready', 'out_for_delivery', 'delivered'];
    $current = array_search($order['status'], $steps);
    if ($current === false) $current = -1;
    ?>

    <div class="page-header">
        <div class="container">
            <h1>Order #<?= (int)$order['order_id'] ?></h1>
            <p>Placed on <?= date('d M Y \a\t H:i', strtotime($order['created_at'])) ?></p>
        </div>
    </div>

    <div class="container" style="padding-block:var(--space-xl);">

        <?php if (isset($_GET['placed'])): ?>
            <div class="alert alert-success" role="alert" data-auto-dismiss="8000">
                🎉 Your order has been placed! You'll be notified when it's confirmed.
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-xl);align-items:start;">

            <div>
                <!-- Tracking -->
                <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="card" style="padding:var(--space-lg);margin-bottom:var(--space-lg);">
                        <h2 style="font-size:1.1rem;margin-bottom:var(--space-md);">Order Tracking</h2>
                        <div class="tracking-steps" role="list" aria-label="Order progress">
                            <?php
                            $stepLabels = [
                                'pending'          => 'Pending',
                                'confirmed'        => 'Confirmed',
                                'ready'            => 'Ready',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered'        => 'Delivered',
                            ];
                            foreach ($steps as $i => $step):
                                $cls = $i < $current ? 'done' : ($i === $current ? 'current' : '');
                            ?>
                                <div class="tracking-step <?= $cls ?>" role="listitem" aria-label="<?= $stepLabels[$step] ?> <?= $cls === 'done' ? '(completed)' : ($cls === 'current' ? '(current)' : '') ?>">
                                    <div class="step-dot" aria-hidden="true"></div>
                                    <span class="step-label"><?= $stepLabels[$step] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">This order was cancelled.</div>
                <?php endif; ?>

                <!-- Items -->
                <div class="card">
                    <div class="card-body">
                        <h2 style="font-size:1.1rem;margin-bottom:var(--space-md);">Items Ordered</h2>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Producer</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td><?= e($item['product_name']) ?></td>
                                            <td><?= e($item['farm_name']) ?></td>
                                            <td><?= (int)$item['quantity'] ?> <?= e($item['unit']) ?></td>
                                            <td><?= formatPrice((float)$item['unit_price']) ?></td>
                                            <td><?= formatPrice((float)$item['unit_price'] * (int)$item['quantity']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <aside class="order-summary" aria-label="Order summary">
                <h2 style="font-size:1.1rem;margin-bottom:var(--space-md);">Summary</h2>

                <div class="summary-row">
                    <span>Status</span>
                    <span><span class="badge <?= orderStatusClass($order['status']) ?>"><?= orderStatusLabel($order['status']) ?></span></span>
                </div>
                <div class="summary-row">
                    <span>Fulfilment</span>
                    <span><?= ucfirst(e($order['fulfillment_type'])) ?></span>
                </div>
                <?php if ($order['fulfillment_type'] === 'collection' && $order['collection_slot']): ?>
                    <div class="summary-row">
                        <span>Slot</span>
                        <span><?= date('d M Y H:i', strtotime($order['collection_slot'])) ?></span>
                    </div>
                <?php elseif ($order['fulfillment_type'] === 'delivery' && $order['delivery_address']): ?>
                    <div class="summary-row" style="flex-direction:column;">
                        <span>Delivery to</span>
                        <span style="font-size:0.9rem;"><?= nl2br(e($order['delivery_address'])) ?></span>
                    </div>
                <?php endif; ?>
                <hr class="divider" style="margin-block:var(--space-sm);">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?= formatPrice((float)$order['total_amount'] + (float)$order['discount_amount']) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="summary-row" style="color:var(--clr-success);">
                        <span>Loyalty Discount</span>
                        <span>-<?= formatPrice((float)$order['discount_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row summary-total">
                    <span>Total Paid</span>
                    <span><?= formatPrice((float)$order['total_amount']) ?></span>
                </div>
                <div class="summary-row" style="color:var(--clr-primary);">
                    <span>Points Earned</span>
                    <span>+<?= (int)$order['loyalty_points_earned'] ?></span>
                </div>
                <?php if ($order['notes']): ?>
                    <p class="form-text mt-sm"><strong>Notes:</strong> <?= e($order['notes']) ?></p>
                <?php endif; ?>

                <a href="/customer/orders.php" class="btn btn-outline btn-block mt-md">← All Orders</a>
            </aside>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Order list view
$orders = getCustomerOrders($pdo, $userId);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Orders</h1>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">
    <?php if (empty($orders)): ?>
        <div class="card" style="padding:var(--space-xxl);text-align:center;">
            <p class="text-muted">No orders yet.</p>
            <a href="/customer/products.php" class="btn btn-primary mt-md">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Order #</th>
                        <th scope="col">Date</th>
                        <th scope="col">Total</th>
                        <th scope="col">Fulfilment</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= (int)$order['order_id'] ?></td>
                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td><?= formatPrice((float)$order['total_amount']) ?></td>
                            <td><?= ucfirst(e($order['fulfillment_type'])) ?></td>
                            <td>
                                <span class="badge <?= orderStatusClass($order['status']) ?>">
                                    <?= orderStatusLabel($order['status']) ?>
                                </span>
                            </td>
                            <td><a href="/customer/orders.php?id=<?= (int)$order['order_id'] ?>">View Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
