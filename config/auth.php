<?php

// =========================================================
// SESSION SETTINGS
// =========================================================

if (session_status() === PHP_SESSION_NONE) {

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


// =========================================================
// CHECK IF USER IS LOGGED IN
// =========================================================

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'])
        && is_numeric($_SESSION['user_id']);
}


// =========================================================
// GET CURRENT USER ID
// =========================================================

function currentUserId(): ?int
{
    if (isLoggedIn()) {
        return (int) $_SESSION['user_id'];
    }

    return null;
}


// =========================================================
// REQUIRE LOGIN
// =========================================================

function requireLogin(string $redirect = 'account.php'): void
{
    if (!isLoggedIn()) {

        $allowed = [
            'account.php',
            'join.php',
            'cart.php',
            'checkout.php',
            'orders.php',
            'profile.php',
            'merch.php'
        ];

        if (!in_array($redirect, $allowed, true)) {
            $redirect = 'account.php';
        }

        /*
         * Save where the user originally tried to go.
         *
         * This is still available for the rest of the system,
         * but LOGIN itself will always send the user to account.php.
         */
        $_SESSION['login_redirect'] = $redirect;

        header(
            'Location: login.php?redirect=' . urlencode($redirect)
        );

        exit;
    }
}


// =========================================================
// ESCAPE HTML
// =========================================================

function e(?string $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =========================================================
// LOGIN REDIRECT
// =========================================================
//
// IMPORTANT:
// Successful login should ALWAYS go to account.php.
//
// We intentionally do NOT send the user back to the page
// they opened before logging out.
//

function redirectAfterLogin(): void
{
    // Remove any old saved destination.
    unset($_SESSION['login_redirect']);

    // Always go to My Account.
    header('Location: account.php');
    exit;
}