<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/admin_auth.php';


/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

/*
 * Existing administrator.
 */

if (isAdminLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}


/*
 * Existing customer/member.
 */

if (isLoggedIn()) {
    header('Location: account.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$error = '';
$email = '';

$redirect =
    $_GET['redirect']
    ?? $_POST['redirect']
    ?? 'account.php';


$allowedRedirects = [
    'join.php',
    'account.php',
    'cart.php',
    'checkout.php',
    'orders.php',
    'profile.php',
    'merch.php'
];


if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'account.php';
}


/*
|--------------------------------------------------------------------------
| LOGIN REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * This is intentionally named "email" so the
     * visible customer interface stays unchanged.
     *
     * Customers enter an email.
     * Admin can enter the admin username.
     */

    $email =
        strtolower(
            trim($_POST['email'] ?? '')
        );


    $password =
        $_POST['password'] ?? '';


    if ($email === '' || $password === '') {

        $error =
            'Please enter your email and password.';

    } else {

        $loggedIn = false;


        /*
        |--------------------------------------------------------------------------
        | 1. TRY CUSTOMER LOGIN
        |--------------------------------------------------------------------------
        |
        | Only perform the users-table lookup when
        | the entered value is a valid email address.
        |
        */

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $stmt =
                $pdo->prepare(
                    'SELECT
                        id,
                        full_name,
                        email,
                        password_hash
                     FROM users
                     WHERE email = ?
                     LIMIT 1'
                );


            $stmt->execute([$email]);

            $user =
                $stmt->fetch();


            if (
                $user
                && password_verify(
                    $password,
                    $user['password_hash']
                )
            ) {

                session_regenerate_id(true);


                /*
                 * Make sure this browser session
                 * is not simultaneously an admin.
                 */

                unset(
                    $_SESSION['admin_logged_in'],
                    $_SESSION['admin_username']
                );


                $_SESSION['user_id'] =
                    (int)$user['id'];

                $_SESSION['user_name'] =
                    $user['full_name'];

                $_SESSION['user_email'] =
                    $user['email'];


                $loggedIn = true;


                header(
                    'Location: ' . $redirect
                );

                exit;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 2. TRY ADMIN LOGIN SILENTLY
        |--------------------------------------------------------------------------
        |
        | Nothing in the interface tells the visitor
        | that administrator authentication exists.
        |
        */

        if (!$loggedIn) {

            if (
                adminLogin(
                    $email,
                    $password
                )
            ) {

                header(
                    'Location: admin/index.php'
                );

                exit;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN FAILED
        |--------------------------------------------------------------------------
        |
        | Generic error deliberately does not reveal
        | whether the attempted account was a member
        | or administrator.
        |
        */

        $error =
            'Incorrect email or password.';
    }
}


$pageTitle =
    'Sign In | Dionisio Fitness Center';
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Sign in to your Dionisio Fitness Center member account."
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body.login-page {
            margin: 0;
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;
            background:
                radial-gradient(
                    circle at 15% 10%,
                    rgba(216, 32, 32, 0.18),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 90%,
                    rgba(255, 255, 255, 0.05),
                    transparent 35%
                ),
                #080808;

            color: #ffffff;
        }

        .login-wrapper {
            min-height: 100vh;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .login-wrapper::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 50%;
            top: -180px;
            right: -150px;
        }

        .login-wrapper::after {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            border: 1px solid rgba(216,32,32,0.08);
            border-radius: 50%;
            bottom: -280px;
            left: -180px;
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1120px;
        }

        .login-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            text-decoration: none;
            color: #ffffff;
        }

        .login-brand-mark {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            background: #d71920;
            color: #ffffff;
            font-size: 25px;
            font-weight: 900;
            border-radius: 6px;
            box-shadow:
                0 10px 30px rgba(215, 25, 32, 0.28);
        }

        .login-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .login-brand-text strong {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1.4px;
        }

        .login-brand-text small {
            margin-top: 5px;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 3px;
            color: #9a9a9a;
        }

        .login-card {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                minmax(380px, 0.85fr);

            min-height: 620px;
            background: #111111;
            border: 1px solid #242424;
            box-shadow:
                0 35px 80px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .login-visual {
            position: relative;
            padding: 65px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            overflow: hidden;
            background:
                linear-gradient(
                    180deg,
                    rgba(0,0,0,0.15),
                    rgba(0,0,0,0.92)
                ),
                linear-gradient(
                    135deg,
                    #222 0%,
                    #111 45%,
                    #080808 100%
                );
        }

        .login-visual::before {
            content: "D";
            position: absolute;
            top: -80px;
            right: -20px;
            font-size: 390px;
            line-height: 1;
            font-weight: 900;
            color: rgba(255,255,255,0.025);
            pointer-events: none;
        }

        .login-visual-line {
            width: 52px;
            height: 5px;
            margin-bottom: 22px;
            background: #d71920;
        }

        .login-visual .eyebrow {
            margin: 0 0 14px;
            color: #d71920;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 4px;
        }

        .login-visual h2 {
            margin: 0;
            max-width: 560px;
            font-size: clamp(
                42px,
                5vw,
                72px
            );
            line-height: 0.92;
            letter-spacing: -3px;
            font-weight: 900;
        }

        .login-visual h2 span {
            color: #d71920;
        }

        .login-visual > p:last-of-type {
            max-width: 480px;
            margin: 24px 0 0;
            color: #a9a9a9;
            font-size: 14px;
            line-height: 1.9;
        }

        .login-highlights {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 30px;
        }

        .login-highlights span {
            padding: 9px 13px;
            border: 1px solid #303030;
            color: #c7c7c7;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .login-form-side {
            padding: 62px 54px;
            background: #0d0d0d;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-heading {
            margin-bottom: 34px;
        }

        .login-form-heading .eyebrow {
            margin: 0 0 12px;
            color: #d71920;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 3px;
        }

        .login-form-heading h1 {
            margin: 0;
            font-size: 42px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -1.8px;
        }

        .login-form-heading h1 span {
            color: #d71920;
        }

        .login-form-heading p {
            margin: 14px 0 0;
            color: #858585;
            font-size: 13px;
            line-height: 1.7;
        }

        .login-alert {
            margin-bottom: 22px;
            padding: 14px 16px;
            background:
                rgba(215, 25, 32, 0.1);
            border:
                1px solid rgba(215, 25, 32, 0.35);
            color: #ff878b;
            font-size: 12px;
            line-height: 1.5;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .login-field label {
            display: block;
            margin-bottom: 9px;
            color: #bdbdbd;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.7px;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-wrap input {
            width: 100%;
            height: 54px;
            padding: 0 46px 0 16px;
            background: #151515;
            border: 1px solid #292929;
            color: #ffffff;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .login-input-wrap input::placeholder {
            color: #555;
        }

        .login-input-wrap input:focus {
            border-color: #d71920;
            background: #181818;
            box-shadow:
                0 0 0 3px rgba(215,25,32,0.08);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            border: 0;
            padding: 5px;
            background: transparent;
            color: #787878;
            cursor: pointer;
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .password-toggle:hover {
            color: #ffffff;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: -3px;
        }

        .remember-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #838383;
            font-size: 11px;
            cursor: pointer;
        }

        .remember-label input {
            accent-color: #d71920;
        }

        .login-help {
            color: #838383;
            font-size: 11px;
            text-decoration: none;
        }

        .login-help:hover {
            color: #ffffff;
        }

        .login-submit {
            width: 100%;
            min-height: 56px;
            margin-top: 4px;
            border: 0;
            background: #d71920;
            color: #ffffff;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.6px;
            transition:
                transform 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .login-submit:hover {
            background: #ef222a;
            transform: translateY(-2px);
            box-shadow:
                0 12px 28px rgba(215,25,32,0.2);
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 22px;
            color: #555;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .login-divider::before,
        .login-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #252525;
        }

        .create-account {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            border: 1px solid #313131;
            color: #ffffff;
            text-decoration: none;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.3px;
            transition:
                border-color 0.2s ease,
                background 0.2s ease;
        }

        .create-account:hover {
            border-color: #d71920;
            background:
                rgba(215,25,32,0.05);
        }

        .login-footer-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 26px;
        }

        .login-footer-links a {
            color: #656565;
            text-decoration: none;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
        }

        .login-footer-links a:hover {
            color: #ffffff;
        }

        @media (
            max-width: 900px
        ) {

            .login-card {
                grid-template-columns: 1fr;
            }

            .login-visual {
                min-height: 330px;
                padding: 45px;
            }

            .login-visual h2 {
                font-size: 48px;
            }

        }

        @media (
            max-width: 600px
        ) {

            .login-wrapper {
                padding: 16px;
                align-items: flex-start;
            }

            .login-container {
                padding-top: 15px;
            }

            .login-brand {
                margin-bottom: 18px;
            }

            .login-card {
                min-height: auto;
            }

            .login-visual {
                display: none;
            }

            .login-form-side {
                padding: 38px 25px;
            }

            .login-form-heading h1 {
                font-size: 36px;
            }

            .login-options {
                align-items: flex-start;
                flex-direction: column;
            }

            .login-footer-links {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>


<body class="login-page">


<div class="login-wrapper">


    <div class="login-container">


        <!-- BRAND -->

        <a
            href="index.php"
            class="login-brand"
        >

            <span class="login-brand-mark">
                D
            </span>

            <span class="login-brand-text">

                <strong>
                    DIONISIO
                </strong>

                <small>
                    FITNESS CENTER
                </small>

            </span>

        </a>


        <!-- LOGIN CARD -->

        <div class="login-card">


            <!-- LEFT SIDE -->

            <div class="login-visual">


                <div class="login-visual-line">
                </div>


                <p class="eyebrow">
                    MEMBER PORTAL
                </p>


                <h2>

                    BUILT FOR<br>

                    <span>
                        STRENGTH.
                    </span>

                </h2>


                <p>

                    Your training continues beyond
                    the gym floor. Sign in to manage
                    your membership, orders and
                    Dionisio account.

                </p>


                <div class="login-highlights">

                    <span>
                        MEMBERSHIP
                    </span>

                    <span>
                        ORDERS
                    </span>

                    <span>
                        PROFILE
                    </span>

                    <span>
                        MERCH
                    </span>

                </div>


            </div>


            <!-- RIGHT SIDE -->

            <div class="login-form-side">


                <div class="login-form-heading">

                    <p class="eyebrow">
                        MEMBER ACCESS
                    </p>


                    <h1>

                        MEMBER
                        <span>LOGIN.</span>

                    </h1>


                    <p>
                        Enter your account details
                        to continue.
                    </p>

                </div>


                <!-- ERROR -->

                <?php if ($error !== ''): ?>

                    <div class="login-alert">

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form
                    class="login-form"
                    method="POST"
                    action="login.php"
                >

                    <input
                        type="hidden"
                        name="redirect"
                        value="<?= htmlspecialchars(
                            $redirect,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <!-- EMAIL -->

                    <div class="login-field">

                        <label for="email">
                            EMAIL ADDRESS
                        </label>


                        <div class="login-input-wrap">

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                placeholder="you@example.com"
                                required
                                autocomplete="email"
                            >

                        </div>

                    </div>


                    <!-- PASSWORD -->

                    <div class="login-field">

                        <label for="password">
                            PASSWORD
                        </label>


                        <div class="login-input-wrap">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                aria-label="Show password"
                            >
                                SHOW
                            </button>

                        </div>

                    </div>


                    <!-- OPTIONS -->

                    <div class="login-options">


                        <label class="remember-label">

                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >

                            <span>
                                Remember me
                            </span>

                        </label>


                        <a
                            href="contact.php"
                            class="login-help"
                        >
                            NEED HELP?
                        </a>


                    </div>


                    <!-- LOGIN BUTTON -->

                    <button
                        class="login-submit"
                        type="submit"
                    >
                        SIGN IN
                    </button>


                </form>


                <div class="login-divider">
                    NEW TO DIONISIO?
                </div>


                <a
                    href="register.php"
                    class="create-account"
                >
                    CREATE AN ACCOUNT
                </a>


                <div class="login-footer-links">


                    <a href="index.php">
                        ← BACK TO WEBSITE
                    </a>


                    <a href="join.php">
                        VIEW MEMBERSHIPS
                    </a>


                </div>


            </div>


        </div>


    </div>


</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const passwordInput =
            document.getElementById(
                'password'
            );

        const passwordToggle =
            document.getElementById(
                'passwordToggle'
            );


        if (
            passwordInput
            && passwordToggle
        ) {

            passwordToggle.addEventListener(
                'click',
                function () {

                    const isPassword =
                        passwordInput.type
                        === 'password';


                    passwordInput.type =
                        isPassword
                            ? 'text'
                            : 'password';


                    passwordToggle.textContent =
                        isPassword
                            ? 'HIDE'
                            : 'SHOW';


                    passwordToggle.setAttribute(
                        'aria-label',
                        isPassword
                            ? 'Hide password'
                            : 'Show password'
                    );

                }
            );

        }

    }
);

</script>


</body>

</html>
```
