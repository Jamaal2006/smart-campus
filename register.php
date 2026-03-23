<?php
$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: /customer/dashboard.php');
    exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = ($_GET['role'] ?? '') === 'producer' ? 'producer' : 'customer';
    $farmName = trim($_POST['farm_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    // Validation
    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if ($role === 'producer' && $farmName === '') {
        $errors[] = 'Farm / business name is required for producers.';
    }

    if (empty($errors)) {
        // Check email uniqueness
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, phone) VALUES (:name, :email, :hash, :role, :phone)'
            );
            $stmt->execute([
                ':name'  => $name,
                ':email' => $email,
                ':hash'  => $hash,
                ':role'  => $role,
                ':phone' => $phone,
            ]);
            $userId = (int) $pdo->lastInsertId();

            if ($role === 'producer') {
                $stmt2 = $pdo->prepare(
                    'INSERT INTO producers (user_id, farm_name) VALUES (:uid, :farm)'
                );
                $stmt2->execute([':uid' => $userId, ':farm' => $farmName]);
            }

            $pdo->commit();

            // Log them in immediately
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['name']    = $name;
            $_SESSION['role']    = $role;

            $redirect = $role === 'producer' ? '/producer/dashboard.php' : '/customer/dashboard.php';
            header('Location: ' . $redirect . '?welcome=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}

$isProducer = ($_GET['role'] ?? '') === 'producer';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:520px;padding-block:var(--space-xxl);">
    <div class="card" style="padding:var(--space-xl);">
        <h1 style="text-align:center;margin-bottom:var(--space-xs);">Create Your Account</h1>
        <p class="text-center text-muted" style="margin-bottom:var(--space-lg);">
            <?= $isProducer ? 'Joining as a <strong>Producer</strong>' : 'Joining as a <strong>Customer</strong>' ?>
            &mdash; <a href="/register.php<?= $isProducer ? '' : '?role=producer' ?>"><?= $isProducer ? 'Switch to customer' : 'Switch to producer' ?></a>
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul style="margin:0;padding-left:1rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/register.php<?= $isProducer ? '?role=producer' : '' ?>" novalidate>
            <?= csrfField() ?>

            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" required autocomplete="name" aria-required="true">
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email" aria-required="true">
            </div>

            <div class="form-group">
                <label for="phone">Phone number <span class="text-muted">(optional)</span></label>
                <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" autocomplete="tel">
            </div>

            <?php if ($isProducer): ?>
                <div class="form-group">
                    <label for="farm_name">Farm / business name</label>
                    <input type="text" id="farm_name" name="farm_name" value="<?= e($_POST['farm_name'] ?? '') ?>" required aria-required="true">
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" aria-required="true" aria-describedby="pw-hint" minlength="8">
                <span id="pw-hint" class="form-text">Minimum 8 characters.</span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" aria-required="true" minlength="8">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
        </form>

        <hr class="divider">
        <p class="text-center">Already have an account? <a href="/login.php">Log in</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
