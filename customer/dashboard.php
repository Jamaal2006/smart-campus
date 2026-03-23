<?php
$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('customer');

require_once __DIR__ . '/../includes/header.php';

$userId  = $_SESSION['user_id'];
$orders  = getCustomerOrders($pdo, $userId);
$cartCount = getCartCount($pdo, $userId);

// Get user details
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :uid');
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch();

$recentOrders = array_slice($orders, 0, 5);
$totalSpend   = array_sum(array_column($orders, 'total_amount'));
?>

<div class="page-header">
    <div class="container">
        <h1>Welcome back, <?= e($user['name']) ?>!</h1>
        <p>Manage your orders, loyalty points and account details.</p>
    </div>
</div>

<div class="container">

    <?php if (isset($_GET['welcome'])): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="6000">
            🎉 Welcome to Greenfield Local Hub! Your account is ready — start shopping below.
        </div>
    <?php endif; ?>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" aria-label="Account navigation">
            <div class="sidebar-header">My Account</div>
            <nav class="sidebar-nav" aria-label="Dashboard sections">
                <a href="/customer/dashboard.php" class="active">Dashboard</a>
                <a href="/customer/products.php">Shop</a>
                <a href="/customer/cart.php">My Cart (<?= $cartCount ?>)</a>
                <a href="/customer/orders.php">Order History</a>
                <a href="/customer/account.php">Account &amp; Loyalty</a>
                <a href="/logout.php">Log Out</a>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="dashboard-content">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= count($orders) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= formatPrice($totalSpend) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= (int)$user['loyalty_points'] ?></div>
                    <div class="stat-label">Loyalty Points</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $cartCount ?></div>
                    <div class="stat-label">Items in Cart</div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="d-flex gap-md flex-wrap mb-lg">
                <a href="/customer/products.php" class="btn btn-primary">Browse Products</a>
                <a href="/customer/cart.php" class="btn btn-outline">View Cart</a>
                <a href="/customer/orders.php" class="btn btn-outline">All Orders</a>
            </div>

            <!-- Recent orders -->
            <h2 class="mb-md">Recent Orders</h2>
            <?php if (empty($recentOrders)): ?>
                <div class="card" style="padding:var(--space-xl);text-align:center;">
                    <p class="text-muted">You haven't placed any orders yet.</p>
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
                            <?php foreach ($recentOrders as $order): ?>
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
                                    <td><a href="/customer/orders.php?id=<?= (int)$order['order_id'] ?>">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($orders) > 5): ?>
                    <p class="mt-md"><a href="/customer/orders.php">View all <?= count($orders) ?> orders →</a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
