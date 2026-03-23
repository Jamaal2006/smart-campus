<?php
$pageTitle = 'View Orders';
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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['confirmed', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];

    if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
        // Only allow updating orders that contain this producer's products
        $owns = $pdo->prepare(
            'SELECT COUNT(*) FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             WHERE oi.order_id = :oid AND p.producer_id = :prid'
        );
        $owns->execute([':oid' => $orderId, ':prid' => $producerId]);
        if ((int)$owns->fetchColumn() > 0) {
            $pdo->prepare('UPDATE orders SET status = :status WHERE order_id = :oid')
                ->execute([':status' => $newStatus, ':oid' => $orderId]);
            $message = 'Order #' . $orderId . ' status updated to ' . orderStatusLabel($newStatus) . '.';
        }
    }
}

// Single order view
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];

    // Verify producer access
    $owns = $pdo->prepare(
        'SELECT COUNT(*) FROM order_items oi
         JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id = :oid AND p.producer_id = :prid'
    );
    $owns->execute([':oid' => $orderId, ':prid' => $producerId]);
    if ((int)$owns->fetchColumn() === 0) {
        header('Location: /producer/view-orders.php');
        exit;
    }

    $order = getOrderWithItems($pdo, $orderId);

    // Filter items to only this producer's products
    $order['items'] = array_filter($order['items'], function ($item) use ($pdo, $producerId) {
        $s = $pdo->prepare('SELECT producer_id FROM products WHERE product_id = :pid');
        $s->execute([':pid' => $item['product_id']]);
        $row = $s->fetch();
        return $row && (int)$row['producer_id'] === $producerId;
    });

    $pageTitle = 'Order #' . $orderId;
    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div class="page-header">
        <div class="container">
            <h1>Order #<?= (int)$order['order_id'] ?></h1>
            <p><?= date('d M Y H:i', strtotime($order['created_at'])) ?></p>
        </div>
    </div>

    <div class="container" style="padding-block:var(--space-xl);">

        <?php if ($message): ?>
            <div class="alert alert-success" role="alert" data-auto-dismiss="5000"><?= e($message) ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-xl);">

            <div>
                <!-- Items from this producer only -->
                <div class="card" style="margin-bottom:var(--space-lg);">
                    <div class="card-body">
                        <h2 style="font-size:1.1rem;margin-bottom:var(--space-md);">Items from <?= e($producer['farm_name']) ?></h2>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td><?= e($item['product_name']) ?></td>
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

                <!-- Update status -->
                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                    <div class="card" style="padding:var(--space-lg);">
                        <h2 style="font-size:1.1rem;margin-bottom:var(--space-md);">Update Order Status</h2>
                        <form method="post" action="/producer/view-orders.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>">
                            <div class="d-flex gap-md align-center flex-wrap">
                                <label for="new_status" class="sr-only">New status</label>
                                <select id="new_status" name="new_status">
                                    <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                    <?php if ($order['fulfillment_type'] === 'delivery'): ?>
                                        <option value="out_for_delivery" <?= $order['status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                    <?php endif; ?>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered / Collected</option>
                                    <option value="cancelled">Cancel Order</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Summary -->
            <aside class="order-summary" aria-label="Order summary">
                <h2 style="font-size:1.1rem;margin-bottom:var(--space-md);">Order Details</h2>
                <div class="summary-row">
                    <span>Customer</span>
                    <span><?= e($order['customer_name']) ?></span>
                </div>
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
                        <span>Collection slot</span>
                        <span><?= date('d M Y H:i', strtotime($order['collection_slot'])) ?></span>
                    </div>
                <?php elseif ($order['delivery_address']): ?>
                    <div class="summary-row" style="flex-direction:column;">
                        <span>Delivery to</span>
                        <span style="font-size:0.9rem;"><?= nl2br(e($order['delivery_address'])) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['notes']): ?>
                    <div class="summary-row" style="flex-direction:column;">
                        <span>Notes</span>
                        <span style="font-size:0.9rem;"><?= e($order['notes']) ?></span>
                    </div>
                <?php endif; ?>
                <a href="/producer/view-orders.php" class="btn btn-outline btn-block mt-md">← All Orders</a>
            </aside>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Orders list
$orders = getProducerOrders($pdo, $producerId);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Orders</h1>
        <p>Orders containing products from <?= e($producer['farm_name']) ?></p>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="5000"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p class="text-muted">No orders yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Fulfilment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= (int)$order['order_id'] ?></td>
                            <td><?= e($order['customer_name']) ?></td>
                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td><?= ucfirst(e($order['fulfillment_type'])) ?></td>
                            <td>
                                <span class="badge <?= orderStatusClass($order['status']) ?>">
                                    <?= orderStatusLabel($order['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/producer/view-orders.php?id=<?= (int)$order['order_id'] ?>" class="btn btn-sm btn-outline">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
