<?php
$pageTitle = 'Log In';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect already-logged-in users
if (isLoggedIn()) {
    $redirect = match ($_SESSION['role'] ?? '') {
        'producer' => '/producer/dashboard.php',
        'admin'    => '/admin/dashboard.php',
        default    => '/customer/dashboard.php',
    };
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, name, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            $redirect = $_GET['redirect'] ?? match ($user['role']) {
                'producer' => '/producer/dashboard.php',
                'admin'    => '/admin/dashboard.php',
                default    => '/customer/dashboard.php',
            };
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:480px;padding-block:var(--space-xxl);">
    <div class="card" style="padding:var(--space-xl);">
        <h1 style="text-align:center;margin-bottom:var(--space-lg);">Log In to GLH</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" novalidate>
            <?= csrfField() ?>

            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                    aria-required="true"
                    aria-describedby="email-hint"
                >
                <span id="email-hint" class="form-text">Enter the email you registered with.</span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    aria-required="true"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Log In</button>
        </form>

        <hr class="divider">

        <p class="text-center">Don't have an account? <a href="/register.php">Register here</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
