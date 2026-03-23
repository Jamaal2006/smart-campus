<?php
// CSRF token generation and validation

/**
 * Generate (or retrieve existing) CSRF token for the current session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field ready for inclusion in forms.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the CSRF token submitted with a request.
 * Terminates with 403 on failure.
 */
function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}
