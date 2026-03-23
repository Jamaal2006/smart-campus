<?php
/**
 * Cart API — handles AJAX cart operations.
 * Accepts JSON body: { action, product_id, quantity, csrf_token }
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// CSRF check
$submitted = $body['csrf_token'] ?? '';
if (!hash_equals(csrfToken(), $submitted)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$userId    = $_SESSION['user_id'];
$action    = $body['action'] ?? '';
$productId = (int)($body['product_id'] ?? 0);
$qty       = (int)($body['quantity'] ?? 1);

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

switch ($action) {
    case 'update':
        if ($productId <= 0) jsonResponse(['success' => false, 'error' => 'Invalid product'], 400);
        if ($qty < 1) {
            // Remove item
            $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid AND product_id = :pid')
                ->execute([':uid' => $userId, ':pid' => $productId]);
        } else {
            // Validate stock
            $ps = $pdo->prepare('SELECT price, stock_quantity FROM products WHERE product_id = :pid AND is_available = 1');
            $ps->execute([':pid' => $productId]);
            $product = $ps->fetch();
            if (!$product || $product['stock_quantity'] < $qty) {
                jsonResponse(['success' => false, 'error' => 'Insufficient stock'], 400);
            }
            $pdo->prepare('UPDATE cart_items SET quantity = :qty WHERE user_id = :uid AND product_id = :pid')
                ->execute([':qty' => $qty, ':uid' => $userId, ':pid' => $productId]);
            $lineTotal = (float)$product['price'] * $qty;
        }
        $cartItems  = getCartItems($pdo, $userId);
        $cartTotal  = getCartTotal($cartItems);
        $cartCount  = array_sum(array_column($cartItems, 'quantity'));
        jsonResponse([
            'success'    => true,
            'cart_count' => $cartCount,
            'cart_total' => round($cartTotal, 2),
            'line_total' => $lineTotal ?? null,
        ]);

    case 'remove':
        if ($productId <= 0) jsonResponse(['success' => false, 'error' => 'Invalid product'], 400);
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid AND product_id = :pid')
            ->execute([':uid' => $userId, ':pid' => $productId]);
        $cartItems = getCartItems($pdo, $userId);
        $cartTotal = getCartTotal($cartItems);
        $cartCount = array_sum(array_column($cartItems, 'quantity'));
        jsonResponse([
            'success'    => true,
            'cart_count' => $cartCount,
            'cart_total' => round($cartTotal, 2),
        ]);

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
}
