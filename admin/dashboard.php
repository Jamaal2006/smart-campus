<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('admin');

$message = '';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    verifyCsrf();
    $allowed = ['pending', 'confirmed', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];
    $status  = $_POST['new_status'];
    if (in_array($status, $allowed, true)) {
        $pdo->prepare('UPDATE orders SET status = :s WHERE order_id = :oid')
            ->execute([':s' => $status, ':oid' => (int)$_POST['order_id']]);
        $message = 'Order status updated.';
    }
}

// Stats
$userCount     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$producerCount = (int)$pdo->query("SELECT COUNT(*) FROM producers")->fetchColumn();
$productCount  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$orderCount    = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue       = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query(
    'SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.created_at DESC LIMIT 20'
)->fetchAll();

// All producers
$producers = getProducers($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Greenfield Local Hub — site overview and management.</p>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="5000"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $userCount ?></div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $producerCount ?></div>
            <div class="stat-label">Producers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $productCount ?></div>
            <div class="stat-label">Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $orderCount ?></div>
            <div class="stat-label">Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= formatPrice($revenue) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- Producers overview -->
    <h2 class="mt-xl mb-md">Producers (<?= count($producers) ?>)</h2>
    <?php if (empty($producers)): ?>
        <p class="text-muted">No producers registered yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Farm Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($producers as $producer): ?>
                        <tr>
                            <td><strong><?= e($producer['farm_name']) ?></strong></td>
                            <td><?= e($producer['name']) ?></td>
                            <td><?= e($producer['email']) ?></td>
                            <td><?= e($producer['location'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Recent orders -->
    <h2 class="mt-xl mb-md">Recent Orders</h2>
    <?php if (empty($recentOrders)): ?>
        <p class="text-muted">No orders yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Fulfilment</th>
                        <th>Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?= (int)$order['order_id'] ?></td>
                            <td><?= e($order['customer_name']) ?></td>
                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td><?= formatPrice((float)$order['total_amount']) ?></td>
                            <td><?= ucfirst(e($order['fulfillment_type'])) ?></td>
                            <td>
                                <span class="badge <?= orderStatusClass($order['status']) ?>">
                                    <?= orderStatusLabel($order['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" class="d-flex gap-sm align-center">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>">
                                    <label for="status-<?= (int)$order['order_id'] ?>" class="sr-only">New status for order #<?= (int)$order['order_id'] ?></label>
                                    <select id="status-<?= (int)$order['order_id'] ?>" name="new_status" style="font-size:0.85rem;padding:0.3rem 0.5rem;">
                                        <?php foreach (['pending','confirmed','ready','out_for_delivery','delivered','cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= orderStatusLabel($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
