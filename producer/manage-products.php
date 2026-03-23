<?php
$pageTitle = 'Manage Products';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('producer');

$userId   = $_SESSION['user_id'];
$producer = getProducerByUserId($pdo, $userId);
if (!$producer) { header('Location: /producer/dashboard.php'); exit; }

$producerId = (int)$producer['producer_id'];
$categories = getCategories($pdo);

$action  = $_GET['action'] ?? 'list';
$editId  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$message = '';
$errors  = [];

// Handle form submission (add/edit/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'delete') {
        $pid = (int)($_POST['product_id'] ?? 0);
        // Only allow deleting own products
        $check = $pdo->prepare('SELECT product_id FROM products WHERE product_id = :pid AND producer_id = :prid');
        $check->execute([':pid' => $pid, ':prid' => $producerId]);
        if ($check->fetch()) {
            $pdo->prepare('DELETE FROM products WHERE product_id = :pid')->execute([':pid' => $pid]);
            $message = 'Product deleted.';
        }
        $action = 'list';

    } else {
        // Add or edit
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId  = $_POST['category_id'] ? (int)$_POST['category_id'] : null;
        $price       = (float)($_POST['price'] ?? 0);
        $unit        = trim($_POST['unit'] ?? 'each');
        $stock       = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $available   = isset($_POST['is_available']) ? 1 : 0;
        $productId   = (int)($_POST['product_id'] ?? 0);

        if ($name === '')   $errors[] = 'Product name is required.';
        if ($price <= 0)    $errors[] = 'Price must be greater than zero.';
        if ($unit === '')   $errors[] = 'Unit is required.';

        if (empty($errors)) {
            if ($productId > 0) {
                // Verify ownership
                $own = $pdo->prepare('SELECT product_id FROM products WHERE product_id = :pid AND producer_id = :prid');
                $own->execute([':pid' => $productId, ':prid' => $producerId]);
                if ($own->fetch()) {
                    $pdo->prepare(
                        'UPDATE products SET name=:n, description=:d, category_id=:cat, price=:p, unit=:u, stock_quantity=:s, is_available=:a
                         WHERE product_id=:pid AND producer_id=:prid'
                    )->execute([
                        ':n' => $name, ':d' => $description, ':cat' => $categoryId,
                        ':p' => $price, ':u' => $unit, ':s' => $stock, ':a' => $available,
                        ':pid' => $productId, ':prid' => $producerId,
                    ]);
                    $message = 'Product updated.';
                }
            } else {
                $pdo->prepare(
                    'INSERT INTO products (producer_id, category_id, name, description, price, unit, stock_quantity, is_available)
                     VALUES (:prid, :cat, :n, :d, :p, :u, :s, :a)'
                )->execute([
                    ':prid' => $producerId, ':cat' => $categoryId, ':n' => $name,
                    ':d' => $description, ':p' => $price, ':u' => $unit, ':s' => $stock, ':a' => $available,
                ]);
                $message = 'Product added successfully.';
            }
            $action = 'list';
        } else {
            $action = $productId > 0 ? 'edit' : 'add';
            $editId = $productId ?: null;
        }
    }
}

// Fetch product for edit
$editProduct = null;
if ($action === 'edit' && $editId) {
    $ep = $pdo->prepare('SELECT * FROM products WHERE product_id = :pid AND producer_id = :prid');
    $ep->execute([':pid' => $editId, ':prid' => $producerId]);
    $editProduct = $ep->fetch();
    if (!$editProduct) { $action = 'list'; }
}

$products = getProducerProducts($pdo, $producerId);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Manage Products</h1>
        <p><?= e($producer['farm_name']) ?></p>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="5000"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul style="margin:0;padding-left:1rem;"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-between align-center mb-lg flex-wrap gap-md">
            <h2>Your Products (<?= count($products) ?>)</h2>
            <a href="/producer/manage-products.php?action=add" class="btn btn-primary">+ Add Product</a>
        </div>

        <?php if (empty($products)): ?>
            <div class="card" style="padding:var(--space-xxl);text-align:center;">
                <p class="text-muted">No products yet.</p>
                <a href="/producer/manage-products.php?action=add" class="btn btn-primary mt-md">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th><th>Category</th><th>Price</th>
                            <th>Stock</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= e($product['name']) ?></td>
                                <td><?= e($product['category_name'] ?? '—') ?></td>
                                <td><?= formatPrice((float)$product['price']) ?> / <?= e($product['unit']) ?></td>
                                <td><?= (int)$product['stock_quantity'] ?></td>
                                <td><span class="badge <?= $product['is_available'] ? 'badge-success' : 'badge-danger' ?>"><?= $product['is_available'] ? 'Listed' : 'Hidden' ?></span></td>
                                <td class="d-flex gap-sm">
                                    <a href="/producer/manage-products.php?action=edit&id=<?= (int)$product['product_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <form method="post" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                data-confirm="Delete '<?= e($product['name']) ?>'? This cannot be undone.">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: // add or edit ?>
        <h2 class="mb-lg"><?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?></h2>

        <div class="card" style="padding:var(--space-xl);max-width:640px;">
            <form method="post" action="/producer/manage-products.php" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="save">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?= (int)$editProduct['product_id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Product name *</label>
                    <input type="text" id="name" name="name" required value="<?= e($_POST['name'] ?? $editProduct['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['category_id'] ?>"
                                <?= ((int)($_POST['category_id'] ?? $editProduct['category_id'] ?? 0)) === (int)$cat['category_id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?= e($_POST['description'] ?? $editProduct['description'] ?? '') ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);">
                    <div class="form-group">
                        <label for="price">Price (£) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0.01" required
                               value="<?= e($_POST['price'] ?? $editProduct['price'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <input type="text" id="unit" name="unit" placeholder="e.g. kg, bunch, each" required
                               value="<?= e($_POST['unit'] ?? $editProduct['unit'] ?? 'each') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required
                           value="<?= e($_POST['stock_quantity'] ?? $editProduct['stock_quantity'] ?? '0') ?>">
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;cursor:pointer;">
                        <input type="checkbox" name="is_available" value="1"
                               <?= ((int)($_POST['is_available'] ?? $editProduct['is_available'] ?? 1)) ? 'checked' : '' ?>>
                        List this product on the shop
                    </label>
                </div>

                <div class="d-flex gap-md">
                    <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Save Changes' : 'Add Product' ?></button>
                    <a href="/producer/manage-products.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
