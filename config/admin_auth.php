<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CHECK ADMIN LOGIN
|--------------------------------------------------------------------------
*/

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_logged_in'])
        && $_SESSION['admin_logged_in'] === true;
}


/*
|--------------------------------------------------------------------------
| PROTECT ADMIN PAGES
|--------------------------------------------------------------------------
*/

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

function adminLogin(string $email, string $password): bool
{
    global $pdo;

    $email = strtolower(trim($email));

    $stmt = $pdo->prepare("
        SELECT
            admin_id,
            full_name,
            email,
            password_hash
        FROM admins
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $admin = $stmt->fetch();


    if (
        $admin &&
        password_verify(
            $password,
            $admin['password_hash']
        )
    ) {

        session_regenerate_id(true);

        /*
         * Remove normal member session
         */
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email']
        );


        /*
         * Create admin session
         */
        $_SESSION['admin_logged_in'] = true;

        $_SESSION['admin_id'] =
            (int)$admin['admin_id'];

        $_SESSION['admin_username'] =
            $admin['full_name'];

        $_SESSION['admin_email'] =
            $admin['email'];


        return true;
    }


    return false;
}


/*
|--------------------------------------------------------------------------
| ADMIN LOGOUT
|--------------------------------------------------------------------------
*/

function adminLogout(): void
{
    unset(
        $_SESSION['admin_logged_in'],
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_email']
    );

    session_regenerate_id(true);

    header('Location: ../login.php');
    exit;
}