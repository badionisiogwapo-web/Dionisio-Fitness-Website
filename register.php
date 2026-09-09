<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: join.php');
    exit;
}

$error = '';
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $agree = isset($_POST['agree']);

    if (
        $fullName === ''
        || $email === ''
        || $password === ''
        || $confirmPassword === ''
    ) {

        $error =
            'Please complete all required fields.';

    } elseif (
        strlen($fullName) < 2
    ) {

        $error =
            'Please enter your full name.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } elseif (
        $phone !== ''
        && !preg_match(
            '/^[0-9+\-\s()]{7,20}$/',
            $phone
        )
    ) {

        $error =
            'Please enter a valid phone number.';

    } elseif (
        strlen($password) < 8
    ) {

        $error =
            'Your password must be at least 8 characters.';

    } elseif (
        $password !== $confirmPassword
    ) {

        $error =
            'The passwords do not match.';

    } elseif (!$agree) {

        $error =
            'Please agree to create a Dionisio Fitness Center account.';

    } else {

        try {

            $check =
                $pdo->prepare(
                    'SELECT id
                     FROM users
                     WHERE email = ?
                     LIMIT 1'
                );

            $check->execute([$email]);

            if ($check->fetch()) {

                $error =
                    'That email is already registered. Please sign in instead.';

            } else {

                $hash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt =
                    $pdo->prepare(
                        'INSERT INTO users
                        (
                            full_name,
                            email,
                            password_hash,
                            phone
                        )
                        VALUES (?, ?, ?, ?)'
                    );

                $stmt->execute([
                    $fullName,
                    $email,
                    $hash,
                    $phone !== ''
                        ? $phone
                        : null
                ]);

                session_regenerate_id(true);

                $_SESSION['user_id'] =
                    (int)$pdo->lastInsertId();

                $_SESSION['user_name'] =
                    $fullName;

                $_SESSION['user_email'] =
                    $email;

                header(
                    'Location: join.php'
                );

                exit;
            }

        } catch (PDOException $exception) {

            $error =
                'Unable to create your account right now. Please try again.';
        }
    }
}

$pageTitle =
    'Create Account | Dionisio Fitness Center';
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
        content="Create your Dionisio Fitness Center member account."
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

        body.register-page {
            margin: 0;
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;

            background:
                radial-gradient(
                    circle at 10% 15%,
                    rgba(215, 25, 32, 0.18),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 90%,
                    rgba(255,255,255,0.05),
                    transparent 35%
                ),
                #080808;

            color: #ffffff;
        }

        .register-wrapper {
            position: relative;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px;

            overflow: hidden;
        }

        .register-wrapper::before {
            content: "";

            position: absolute;

            width: 480px;
            height: 480px;

            top: -220px;
            right: -180px;

            border-radius: 50%;

            border:
                1px solid rgba(255,255,255,0.05);
        }

        .register-wrapper::after {
            content: "";

            position: absolute;

            width: 560px;
            height: 560px;

            bottom: -310px;
            left: -220px;

            border-radius: 50%;

            border:
                1px solid rgba(215,25,32,0.08);
        }

        .register-container {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 1180px;
        }

        .register-brand {
            display: inline-flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 24px;

            color: #ffffff;

            text-decoration: none;
        }

        .register-brand-mark {
            width: 48px;
            height: 48px;

            display: grid;
            place-items: center;

            background: #d71920;

            border-radius: 6px;

            color: #ffffff;

            font-size: 25px;
            font-weight: 900;

            box-shadow:
                0 10px 30px rgba(215,25,32,0.28);
        }

        .register-brand-text {
            display: flex;
            flex-direction: column;

            line-height: 1;
        }

        .register-brand-text strong {
            font-size: 18px;

            font-weight: 900;

            letter-spacing: 1.4px;
        }

        .register-brand-text small {
            margin-top: 5px;

            color: #9a9a9a;

            font-size: 9px;

            font-weight: 600;

            letter-spacing: 3px;
        }

        .register-card {
            display: grid;

            grid-template-columns:
                minmax(0, 0.9fr)
                minmax(440px, 1.1fr);

            min-height: 700px;

            overflow: hidden;

            background: #111111;

            border: 1px solid #242424;

            box-shadow:
                0 35px 80px rgba(0,0,0,0.5);
        }

        .register-visual {
            position: relative;

            display: flex;
            flex-direction: column;
            justify-content: flex-end;

            padding: 65px;

            overflow: hidden;

            background:
                linear-gradient(
                    180deg,
                    rgba(0,0,0,0.05),
                    rgba(0,0,0,0.93)
                ),
                linear-gradient(
                    135deg,
                    #232323 0%,
                    #111111 50%,
                    #070707 100%
                );
        }

        .register-visual::before {
            content: "D";

            position: absolute;

            top: -75px;
            right: -20px;

            color:
                rgba(255,255,255,0.025);

            font-size: 390px;
            font-weight: 900;
            line-height: 1;
        }

        .register-visual-line {
            width: 52px;
            height: 5px;

            margin-bottom: 22px;

            background: #d71920;
        }

        .register-visual .eyebrow {
            margin: 0 0 14px;

            color: #d71920;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 4px;
        }

        .register-visual h2 {
            margin: 0;

            max-width: 520px;

            font-size:
                clamp(
                    42px,
                    5vw,
                    70px
                );

            font-weight: 900;

            line-height: 0.92;

            letter-spacing: -3px;
        }

        .register-visual h2 span {
            color: #d71920;
        }

        .register-visual > p:last-of-type {
            max-width: 460px;

            margin: 24px 0 0;

            color: #a9a9a9;

            font-size: 14px;

            line-height: 1.9;
        }

        .register-benefits {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;

            margin-top: 28px;
        }

        .register-benefits span {
            padding: 12px 14px;

            border: 1px solid #303030;

            color: #bcbcbc;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1px;
        }

        .register-form-side {
            display: flex;

            flex-direction: column;

            justify-content: center;

            padding: 52px 54px;

            background: #0d0d0d;
        }

        .register-form-heading {
            margin-bottom: 28px;
        }

        .register-form-heading .eyebrow {
            margin: 0 0 12px;

            color: #d71920;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 3px;
        }

        .register-form-heading h1 {
            margin: 0;

            font-size: 42px;

            font-weight: 900;

            line-height: 1;

            letter-spacing: -1.8px;
        }

        .register-form-heading h1 span {
            color: #d71920;
        }

        .register-form-heading p {
            margin: 14px 0 0;

            color: #858585;

            font-size: 13px;

            line-height: 1.7;
        }

        .register-alert {
            margin-bottom: 20px;

            padding: 14px 16px;

            background:
                rgba(215,25,32,0.1);

            border:
                1px solid
                rgba(215,25,32,0.35);

            color: #ff878b;

            font-size: 12px;

            line-height: 1.5;
        }

        .register-form {
            display: flex;

            flex-direction: column;

            gap: 18px;
        }

        .register-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 16px;
        }

        .register-field label {
            display: block;

            margin-bottom: 8px;

            color: #bdbdbd;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.5px;
        }

        .register-field label span {
            color: #555;

            font-size: 8px;
        }

        .register-input-wrap {
            position: relative;
        }

        .register-input-wrap input {
            width: 100%;
            height: 52px;

            padding:
                0 46px 0 16px;

            background: #151515;

            border: 1px solid #292929;

            outline: none;

            color: #ffffff;

            font-family: inherit;

            font-size: 13px;

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .register-input-wrap input::placeholder {
            color: #555;
        }

        .register-input-wrap input:focus {
            background: #181818;

            border-color: #d71920;

            box-shadow:
                0 0 0 3px
                rgba(215,25,32,0.08);
        }

        .password-toggle {
            position: absolute;

            top: 50%;
            right: 14px;

            transform:
                translateY(-50%);

            padding: 5px;

            border: 0;

            background: transparent;

            color: #777;

            cursor: pointer;

            font-family: inherit;

            font-size: 9px;

            font-weight: 800;

            letter-spacing: 1px;
        }

        .password-toggle:hover {
            color: #ffffff;
        }

        .password-info {
            margin-top: 7px;

            color: #606060;

            font-size: 9px;

            line-height: 1.5;
        }

        .password-status {
            min-height: 14px;

            margin-top: 5px;

            font-size: 9px;

            font-weight: 700;
        }

        .password-status.match {
            color: #66c985;
        }

        .password-status.no-match {
            color: #ff686e;
        }

        .register-check {
            display: flex;

            align-items: flex-start;

            gap: 9px;

            color: #7e7e7e;

            font-size: 10px;

            line-height: 1.6;

            cursor: pointer;
        }

        .register-check input {
            margin-top: 3px;

            accent-color: #d71920;
        }

        .register-submit {
            width: 100%;

            min-height: 56px;

            margin-top: 2px;

            border: 0;

            background: #d71920;

            color: #ffffff;

            cursor: pointer;

            font-family: inherit;

            font-size: 12px;

            font-weight: 900;

            letter-spacing: 1.5px;

            transition:
                transform 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .register-submit:hover {
            background: #ef222a;

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 28px
                rgba(215,25,32,0.2);
        }

        .register-divider {
            display: flex;

            align-items: center;

            gap: 12px;

            margin: 24px 0 20px;

            color: #555;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 1.4px;
        }

        .register-divider::before,
        .register-divider::after {
            content: "";

            flex: 1;

            height: 1px;

            background: #252525;
        }

        .register-login {
            display: flex;

            align-items: center;

            justify-content: center;

            min-height: 52px;

            border: 1px solid #313131;

            color: #ffffff;

            text-decoration: none;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 1.2px;

            transition:
                border-color 0.2s ease,
                background 0.2s ease;
        }

        .register-login:hover {
            border-color: #d71920;

            background:
                rgba(215,25,32,0.05);
        }

        .register-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: 24px;
        }

        .register-footer a {
            color: #656565;

            text-decoration: none;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 0.8px;
        }

        .register-footer a:hover {
            color: #ffffff;
        }

        @media (
            max-width: 980px
        ) {

            .register-card {
                grid-template-columns: 1fr;
            }

            .register-visual {
                min-height: 340px;

                padding: 45px;
            }

            .register-visual h2 {
                font-size: 48px;
            }

        }

        @media (
            max-width: 650px
        ) {

            .register-wrapper {
                padding: 16px;

                align-items: flex-start;
            }

            .register-container {
                padding-top: 14px;
            }

            .register-brand {
                margin-bottom: 18px;
            }

            .register-card {
                min-height: auto;
            }

            .register-visual {
                display: none;
            }

            .register-form-side {
                padding: 38px 24px;
            }

            .register-form-heading h1 {
                font-size: 35px;
            }

            .register-grid {
                grid-template-columns: 1fr;
            }

            .register-footer {
                align-items: flex-start;

                flex-direction: column;
            }

        }

    </style>

</head>


<body class="register-page">


<div class="register-wrapper">


    <div class="register-container">


        <!-- BRAND -->

        <a
            href="index.php"
            class="register-brand"
        >

            <span class="register-brand-mark">
                D
            </span>


            <span class="register-brand-text">

                <strong>
                    DIONISIO
                </strong>

                <small>
                    FITNESS CENTER
                </small>

            </span>

        </a>


        <!-- MAIN CARD -->

        <div class="register-card">


            <!-- LEFT CONTENT -->

            <div class="register-visual">


                <div class="register-visual-line">
                </div>


                <p class="eyebrow">
                    JOIN THE COMMUNITY
                </p>


                <h2>

                    START YOUR<br>

                    <span>
                        JOURNEY.
                    </span>

                </h2>


                <p>

                    Create your Dionisio account,
                    choose your membership plan,
                    manage your profile and keep
                    your fitness journey in one place.

                </p>


                <div class="register-benefits">

                    <span>
                        MEMBERSHIP ACCESS
                    </span>

                    <span>
                        MEMBER PROFILE
                    </span>

                    <span>
                        MERCH ORDERS
                    </span>

                    <span>
                        ACCOUNT MANAGEMENT
                    </span>

                </div>


            </div>


            <!-- FORM SIDE -->

            <div class="register-form-side">


                <div class="register-form-heading">


                    <p class="eyebrow">
                        CREATE ACCOUNT
                    </p>


                    <h1>

                        JOIN
                        <span>DIONISIO.</span>

                    </h1>


                    <p>

                        Enter your information below
                        to create your member account.

                    </p>


                </div>


                <!-- ERROR -->

                <?php if ($error !== ''): ?>

                    <div class="register-alert">

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form
                    class="register-form"
                    method="POST"
                    action="register.php"
                    id="registerForm"
                >


                    <!-- NAME -->

                    <div class="register-field">

                        <label for="full_name">
                            FULL NAME
                        </label>


                        <div class="register-input-wrap">

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= htmlspecialchars(
                                    $fullName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                placeholder="Enter your full name"
                                required
                                autocomplete="name"
                            >

                        </div>

                    </div>


                    <!-- EMAIL + PHONE -->

                    <div class="register-grid">


                        <div class="register-field">

                            <label for="email">
                                EMAIL ADDRESS
                            </label>


                            <div class="register-input-wrap">

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


                        <div class="register-field">

                            <label for="phone">

                                PHONE NUMBER

                                <span>
                                    OPTIONAL
                                </span>

                            </label>


                            <div class="register-input-wrap">

                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="<?= htmlspecialchars(
                                        $phone,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    placeholder="+63 9XX XXX XXXX"
                                    autocomplete="tel"
                                >

                            </div>

                        </div>


                    </div>


                    <!-- PASSWORDS -->

                    <div class="register-grid">


                        <div class="register-field">

                            <label for="password">
                                PASSWORD
                            </label>


                            <div class="register-input-wrap">

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    minlength="8"
                                    placeholder="Create password"
                                    required
                                    autocomplete="new-password"
                                >


                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password"
                                >
                                    SHOW
                                </button>

                            </div>


                            <div class="password-info">
                                Minimum of 8 characters.
                            </div>

                        </div>


                        <div class="register-field">

                            <label for="confirm_password">
                                CONFIRM PASSWORD
                            </label>


                            <div class="register-input-wrap">

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    placeholder="Repeat password"
                                    required
                                    autocomplete="new-password"
                                >


                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="confirm_password"
                                >
                                    SHOW
                                </button>

                            </div>


                            <div
                                class="password-status"
                                id="passwordStatus"
                            >
                            </div>

                        </div>


                    </div>


                    <!-- AGREEMENT -->

                    <label class="register-check">

                        <input
                            type="checkbox"
                            name="agree"
                            value="1"
                            required
                        >

                        <span>

                            I agree to create a
                            Dionisio Fitness Center
                            member account.

                        </span>

                    </label>


                    <!-- SUBMIT -->

                    <button
                        class="register-submit"
                        type="submit"
                    >
                        CREATE ACCOUNT
                    </button>


                </form>


                <div class="register-divider">
                    ALREADY A MEMBER?
                </div>


                <a
                    href="login.php"
                    class="register-login"
                >
                    SIGN IN TO YOUR ACCOUNT
                </a>


                <div class="register-footer">


                    <a href="index.php">
                        ← BACK TO WEBSITE
                    </a>


                    <a href="programs.php">
                        VIEW PROGRAMS
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

        /*
        |--------------------------------------------------------------------------
        | SHOW / HIDE PASSWORD
        |--------------------------------------------------------------------------
        */

        const toggleButtons =
            document.querySelectorAll(
                '.password-toggle'
            );


        toggleButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const targetId =
                            button.getAttribute(
                                'data-target'
                            );


                        const input =
                            document.getElementById(
                                targetId
                            );


                        if (!input) {
                            return;
                        }


                        const hidden =
                            input.type === 'password';


                        input.type =
                            hidden
                                ? 'text'
                                : 'password';


                        button.textContent =
                            hidden
                                ? 'HIDE'
                                : 'SHOW';

                    }
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | PASSWORD MATCH INDICATOR
        |--------------------------------------------------------------------------
        */

        const password =
            document.getElementById(
                'password'
            );


        const confirmPassword =
            document.getElementById(
                'confirm_password'
            );


        const passwordStatus =
            document.getElementById(
                'passwordStatus'
            );


        function checkPasswords() {

            if (
                !password
                || !confirmPassword
                || !passwordStatus
            ) {
                return;
            }


            if (
                confirmPassword.value === ''
            ) {

                passwordStatus.textContent = '';

                passwordStatus.className =
                    'password-status';

                return;
            }


            if (
                password.value
                === confirmPassword.value
            ) {

                passwordStatus.textContent =
                    'PASSWORDS MATCH';

                passwordStatus.className =
                    'password-status match';

            } else {

                passwordStatus.textContent =
                    'PASSWORDS DO NOT MATCH';

                passwordStatus.className =
                    'password-status no-match';

            }

        }


        password.addEventListener(
            'input',
            checkPasswords
        );


        confirmPassword.addEventListener(
            'input',
            checkPasswords
        );

    }
);

</script>


</body>

</html>
