<?php

require_once __DIR__ . '/config/auth.php';


// =========================================================
// LOG OUT USER
// =========================================================
//
// IMPORTANT:
// This ONLY destroys the LOGIN SESSION.
//
// It does NOT delete anything from MySQL.
//
// Memberships remain.
// Orders remain.
// Cart data remains.
// Transactions remain.
// User account remains.
// =========================================================


// Clear all session variables.

$_SESSION = [];


// =========================================================
// CLEAR SESSION COOKIE
// =========================================================

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'] ?? '',
        $params['secure'],
        $params['httponly']
    );
}


// =========================================================
// DESTROY SESSION
// =========================================================

session_destroy();


// =========================================================
// ALWAYS RETURN TO HOMEPAGE
// =========================================================

header('Location: index.php');
exit;