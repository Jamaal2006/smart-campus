<?php
$pageTitle = 'Producer Dashboard';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('producer');

$userId   = $_SESSION['user_id'];
$producer = getProducerByUserId($pdo, $userId);

if (!$producer) {
    // Producer record missing — prompt to complete profile
    header('Location: /producer/profile.php');
    exit;
}

$producerId = (int)$producer['producer_id'];
$products   = getProducerProducts($pdo, $producerId);
$orders     = getProducerOrders($pdo, $producerId);

$totalStock = array_sum(array_column($products, 'stock_quantity'));
$pendingOrders = array_filter($orders, fn($o) => $o['status'] === 'pending');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><?= e($producer['farm_name']) ?> — Producer Dashboard</h1>
        <p>Manage your products, stock and incoming orders.</p>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if (isset($_GET['welcome'])): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="6000">
            🎉 Welcome to GLH! Complete your producer profile to start listing products.
        </div>
    <?php endif; ?>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" aria-label="Producer navigation">
            <div class="sidebar-header">Producer Menu</div>
            <nav class="sidebar-nav">
                <a href="/producer/dashboard.php" class="active">Dashboard</a>
                <a href="/producer/manage-products.php">My Products</a>
                <a href="/producer/manage-stock.php">Stock Management</a>
                <a href="/producer/view-orders.php">Orders</a>
                <a href="/logout.php">Log Out</a>
            </nav>
        </aside>

        <div class="dashboard-content">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= count($products) ?></div>
                    <div class="stat-label">Products Listed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $totalStock ?></div>
                    <div class="stat-label">Total Stock Units</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count($orders) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count($pendingOrders) ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="d-flex gap-md flex-wrap mb-lg">
                <a href="/producer/manage-products.php?action=add" class="btn btn-primary">+ Add Product</a>
                <a href="/producer/manage-stock.php" class="btn btn-outline">Update Stock</a>
                <a href="/producer/view-orders.php" class="btn btn-outline">View Orders</a>
            </div>

            <!-- Low-stock alert -->
            <?php $lowStock = array_filter($products, fn($p) => (int)$p['stock_quantity'] <= 5 && (int)$p['stock_quantity'] > 0); ?>
            <?php if (!empty($lowStock)): ?>
                <div class="alert alert-warning" role="alert">
                    ⚠ <strong><?= count($lowStock) ?> product<?= count($lowStock) !== 1 ? 's' : '' ?></strong> are running low on stock:
                    <?= implode(', ', array_map(fn($p) => e($p['name']) . ' (' . (int)$p['stock_quantity'] . ' left)', $lowStock)) ?>
                </div>
            <?php endif; ?>

            <!-- Recent products -->
            <h2 class="mb-md">Your Products</h2>
            <?php if (empty($products)): ?>
                <div class="card" style="padding:var(--space-xl);text-align:center;">
                    <p class="text-muted">No products yet. Add your first product to start selling.</p>
                    <a href="/producer/manage-products.php?action=add" class="btn btn-primary mt-md">Add Product</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><strong><?= e($product['name']) ?></strong></td>
                                    <td><?= e($product['category_name'] ?? '—') ?></td>
                                    <td><?= formatPrice((float)$product['price']) ?> / <?= e($product['unit']) ?></td>
                                    <td>
                                        <?php if ((int)$product['stock_quantity'] === 0): ?>
                                            <span class="stock-out">0 (Out of stock)</span>
                                        <?php elseif ((int)$product['stock_quantity'] <= 5): ?>
                                            <span class="stock-low"><?= (int)$product['stock_quantity'] ?> (Low)</span>
                                        <?php else: ?>
                                            <span class="stock-good"><?= (int)$product['stock_quantity'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $product['is_available'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $product['is_available'] ? 'Listed' : 'Hidden' ?>
                                        </span>
                                    </td>
                                    <td class="d-flex gap-sm">
                                        <a href="/producer/manage-products.php?action=edit&id=<?= (int)$product['product_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <a href="/producer/manage-stock.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-sm btn-secondary">Stock</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Recent orders -->
            <h2 class="mt-xl mb-md">Recent Orders Containing Your Products</h2>
            <?php $recentOrders = array_slice($orders, 0, 5); ?>
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
                                <th>Fulfilment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
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
                                    <td><a href="/producer/view-orders.php?id=<?= (int)$order['order_id'] ?>">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
