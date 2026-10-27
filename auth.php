<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Allow 15 minutes of inactivity.
$timeout = 15 * 60;

// Check whether the user is logged in.
if (isset($_SESSION['user_id'])) {

    $lastActivity = $_SESSION['last_activity'] ?? 0;

    if (
        !is_numeric($lastActivity) ||
        time() - (int)$lastActivity >= $timeout
    ) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]);
        }

        session_destroy();

        header('Location: login.html?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

// Require authentication before displaying protected pages.
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
}

// Require a particular role.
function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('403 Forbidden: You do not have permission to access this page.');
    }
}