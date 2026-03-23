<?php
$pageTitle = 'My Account & Loyalty';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireRole('customer');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :uid');
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch();

$rewards  = getLoyaltyRewards($pdo);
$message  = '';
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPw   = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';

    if ($newPw !== '') {
        if (strlen($newPw) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPw !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
    }

    if (empty($errors)) {
        if ($newPw !== '') {
            $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET name=:name, phone=:phone, address=:addr, password_hash=:hash WHERE user_id=:uid')
                ->execute([':name' => $name, ':phone' => $phone, ':addr' => $address, ':hash' => $hash, ':uid' => $userId]);
        } else {
            $pdo->prepare('UPDATE users SET name=:name, phone=:phone, address=:addr WHERE user_id=:uid')
                ->execute([':name' => $name, ':phone' => $phone, ':addr' => $address, ':uid' => $userId]);
        }
        $_SESSION['name'] = $name;
        $message = 'Account details updated successfully.';

        // Refresh user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $user = $stmt->fetch();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Account &amp; Loyalty</h1>
    </div>
</div>

<div class="container" style="padding-block:var(--space-xl);">

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert" data-auto-dismiss="5000"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul style="margin:0;padding-left:1rem;">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-xl);">

        <!-- Account details -->
        <div>
            <h2 class="mb-md">Account Details</h2>
            <div class="card" style="padding:var(--space-lg);">
                <form method="post" action="/customer/account.php" novalidate>
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled aria-disabled="true">
                        <span class="form-text">Email cannot be changed. Contact support if needed.</span>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Default delivery address</label>
                        <textarea id="address" name="address" rows="3"><?= e($user['address'] ?? '') ?></textarea>
                    </div>

                    <hr class="divider">
                    <h3 style="font-size:1rem;margin-bottom:var(--space-md);">Change Password <span class="text-muted">(leave blank to keep current)</span></h3>

                    <div class="form-group">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Loyalty -->
        <div>
            <h2 class="mb-md">Loyalty Points</h2>

            <div class="loyalty-card">
                <h3>🌟 Your Points Balance</h3>
                <p class="loyalty-points-big"><?= (int)$user['loyalty_points'] ?></p>
                <p class="loyalty-pts-label">points available</p>
                <p class="mt-md" style="font-size:0.9rem;opacity:0.9;">Earn 1 point for every £1 spent. Redeem at checkout for discounts.</p>
            </div>

            <h3 class="mb-md">Available Rewards</h3>
            <div class="rewards-grid">
                <?php foreach ($rewards as $reward): ?>
                    <?php $canUse = (int)$user['loyalty_points'] >= (int)$reward['points_required']; ?>
                    <div class="reward-card <?= $canUse ? 'available' : '' ?>">
                        <p style="font-size:1.5rem;" aria-hidden="true">🎁</p>
                        <strong><?= e($reward['name']) ?></strong>
                        <p class="text-muted" style="font-size:0.85rem;"><?= e($reward['description']) ?></p>
                        <p style="font-weight:700;color:var(--clr-primary);"><?= (int)$reward['points_required'] ?> pts</p>
                        <?php if ($canUse): ?>
                            <span class="badge badge-success">Available</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Need <?= (int)$reward['points_required'] - (int)$user['loyalty_points'] ?> more</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="form-text mt-md">Rewards are applied at checkout. Choose a reward when placing your next order.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
