<?php
// Helper functions for Greenfield Local Hub (GLH)

/**
 * Safely escape output for HTML contexts.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format a price as GBP sterling.
 */
function formatPrice(float $amount): string
{
    return '£' . number_format($amount, 2);
}

// ── Products ──────────────────────────────────────────────────────────────────

/**
 * Fetch all available products, optionally filtered by category.
 */
function getProducts(PDO $pdo, ?int $categoryId = null): array
{
    $sql = 'SELECT p.*, c.name AS category_name, pr.farm_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN producers pr ON p.producer_id = pr.producer_id
            WHERE p.is_available = 1 AND p.stock_quantity > 0';
    $params = [];
    if ($categoryId !== null) {
        $sql .= ' AND p.category_id = :cat';
        $params[':cat'] = $categoryId;
    }
    $sql .= ' ORDER BY p.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Fetch a single product by ID.
 */
function getProduct(PDO $pdo, int $productId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, c.name AS category_name, pr.farm_name, pr.description AS producer_desc,
                pr.farming_methods, pr.location AS producer_location
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.category_id
         LEFT JOIN producers pr ON p.producer_id = pr.producer_id
         WHERE p.product_id = :id'
    );
    $stmt->execute([':id' => $productId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Fetch all categories.
 */
function getCategories(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

// ── Producers ─────────────────────────────────────────────────────────────────

/**
 * Fetch all producers with their user details.
 */
function getProducers(PDO $pdo): array
{
    return $pdo->query(
        'SELECT pr.*, u.name, u.email
         FROM producers pr
         JOIN users u ON pr.user_id = u.user_id
         ORDER BY pr.farm_name'
    )->fetchAll();
}

/**
 * Fetch a single producer by user_id.
 */
function getProducerByUserId(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM producers WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ── Cart ──────────────────────────────────────────────────────────────────────

/**
 * Fetch cart items for the current user, including product details.
 */
function getCartItems(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT ci.*, p.name, p.price, p.stock_quantity, p.image_path, p.unit,
                pr.farm_name
         FROM cart_items ci
         JOIN products p ON ci.product_id = p.product_id
         JOIN producers pr ON p.producer_id = pr.producer_id
         WHERE ci.user_id = :uid'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Calculate the subtotal of all cart items.
 */
function getCartTotal(array $cartItems): float
{
    $total = 0.0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Count total items in the user's cart.
 */
function getCartCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

// ── Orders ────────────────────────────────────────────────────────────────────

/**
 * Fetch orders for a customer, newest first.
 */
function getCustomerOrders(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Fetch a single order with its items and product names.
 */
function getOrderWithItems(PDO $pdo, int $orderId, ?int $userId = null): ?array
{
    $sql = 'SELECT o.*, u.name AS customer_name, u.email AS customer_email
            FROM orders o JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = :oid';
    $params = [':oid' => $orderId];
    if ($userId !== null) {
        $sql .= ' AND o.user_id = :uid';
        $params[':uid'] = $userId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }
    $stmt2 = $pdo->prepare(
        'SELECT oi.*, p.name AS product_name, p.unit, pr.farm_name
         FROM order_items oi
         JOIN products p ON oi.product_id = p.product_id
         JOIN producers pr ON p.producer_id = pr.producer_id
         WHERE oi.order_id = :oid'
    );
    $stmt2->execute([':oid' => $orderId]);
    $order['items'] = $stmt2->fetchAll();
    return $order;
}

// ── Loyalty ───────────────────────────────────────────────────────────────────

/**
 * Return a readable label for an order status.
 */
function orderStatusLabel(string $status): string
{
    $labels = [
        'pending'           => 'Pending',
        'confirmed'         => 'Confirmed',
        'ready'             => 'Ready',
        'out_for_delivery'  => 'Out for Delivery',
        'delivered'         => 'Delivered',
        'cancelled'         => 'Cancelled',
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Return a CSS class for the order status badge.
 */
function orderStatusClass(string $status): string
{
    $classes = [
        'pending'           => 'badge-warning',
        'confirmed'         => 'badge-info',
        'ready'             => 'badge-primary',
        'out_for_delivery'  => 'badge-secondary',
        'delivered'         => 'badge-success',
        'cancelled'         => 'badge-danger',
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Calculate loyalty points earned for a given spend (1 point per £1 spent).
 */
function calculateLoyaltyPointsEarned(float $amount): int
{
    return (int) floor($amount);
}

/**
 * Fetch all active loyalty rewards.
 */
function getLoyaltyRewards(PDO $pdo): array
{
    return $pdo->query(
        'SELECT * FROM loyalty_rewards WHERE is_active = 1 ORDER BY points_required'
    )->fetchAll();
}

// ── Producer helpers ──────────────────────────────────────────────────────────

/**
 * Fetch products managed by a specific producer.
 */
function getProducerProducts(PDO $pdo, int $producerId): array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.category_id
         WHERE p.producer_id = :pid
         ORDER BY p.name'
    );
    $stmt->execute([':pid' => $producerId]);
    return $stmt->fetchAll();
}

/**
 * Fetch orders containing products from a specific producer.
 */
function getProducerOrders(PDO $pdo, int $producerId): array
{
    $stmt = $pdo->prepare(
        'SELECT DISTINCT o.*, u.name AS customer_name
         FROM orders o
         JOIN order_items oi ON o.order_id = oi.order_id
         JOIN products p ON oi.product_id = p.product_id
         JOIN users u ON o.user_id = u.user_id
         WHERE p.producer_id = :pid
         ORDER BY o.created_at DESC'
    );
    $stmt->execute([':pid' => $producerId]);
    return $stmt->fetchAll();
}