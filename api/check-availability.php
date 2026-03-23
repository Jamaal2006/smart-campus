<?php
/**
 * Products availability API — returns stock status for a product.
 * GET /api/check-availability.php?product_id=N
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id is required']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT product_id, name, stock_quantity, is_available FROM products WHERE product_id = :pid'
);
$stmt->execute([':pid' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

echo json_encode([
    'product_id'     => (int)$product['product_id'],
    'name'           => $product['name'],
    'available'      => (bool)$product['is_available'] && (int)$product['stock_quantity'] > 0,
    'stock_quantity' => (int)$product['stock_quantity'],
]);
