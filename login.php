<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';


// =========================================================
// IF ALREADY LOGGED IN
// =========================================================

if (isset($_SESSION['user_id'])) {

    header('Location: account.php');
    exit;
}


// =========================================================
// VARIABLES
// =========================================================

$error = '';


// =========================================================
// LOGIN FORM
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check CSRF token
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error = 'Invalid request. Please refresh the page and try again.';

    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';


        // =================================================
        // VALIDATION
        // =================================================

        if ($email === '' || $password === '') {

            $error = 'Please enter your email and password.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Please enter a valid email address.';

        } else {


            // =================================================
            // FIND USER
            // =================================================
            //
            // Your database uses:
            // full_name
            // email
            // password_hash
            //
            // So we use those exact column names.
            // =================================================

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    full_name,
                    email,
                    password_hash,
                    phone
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            $user = $stmt->fetch();


            // =================================================
            // CHECK PASSWORD
            // =================================================

            if (
                $user &&
                password_verify(
                    $password,
                    $user['password_hash']
                )
            ) {


                // =================================================
                // CREATE NEW SESSION ID
                // =================================================

                session_regenerate_id(true);


                // =================================================
                // SAVE USER SESSION
                // =================================================

                $_SESSION['user_id'] = (int) $user['id'];

                $_SESSION['user_name'] = $user['full_name'];

                $_SESSION['user_email'] = $user['email'];


                if (isset($user['phone'])) {

                    $_SESSION['user_phone'] = $user['phone'];

                }


                // =================================================
                // IMPORTANT
                // =================================================
                //
                // Remove any previous page that may have been
                // saved before login.
                //
                // This prevents the system from sending the
                // user back to merch.php, cart.php, etc.
                // =================================================

                unset($_SESSION['login_redirect']);


                // =================================================
                // ALWAYS SEND USER TO MY ACCOUNT
                // =================================================

                header('Location: account.php');
                exit;


            } else {

                $error = 'Incorrect email or password.';

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sign In | Dionisio Fitness Center</title>


    <!-- Bootstrap 5 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Montserrat -->

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- Main Website CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body class="auth-page">


<div class="auth-shell">


    <!-- =====================================================
         BRAND
         ===================================================== -->

    <div class="auth-brand">

        <a
            href="index.php"
            class="brand"
        >

            <span class="brand-mark">
                D
            </span>


            <span class="brand-text">

                <strong>
                    DIONISIO
                </strong>

                <small>
                    FITNESS CENTER
                </small>

            </span>

        </a>

    </div>



    <!-- =====================================================
         LOGIN CARD
         ===================================================== -->

    <div class="auth-card">


        <div class="auth-copy">

            <p class="section-label">
                MEMBER ACCESS
            </p>


            <h1>
                SIGN IN
            </h1>


            <p>
                Welcome back to Dionisio Fitness Center.
                Sign in to access your account.
            </p>

        </div>



        <!-- =================================================
             ERROR MESSAGE
             ================================================= -->

        <?php if ($error !== ''): ?>

            <div class="auth-alert">

                <?= e($error) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             LOGIN FORM
             ================================================= -->

        <form
            method="POST"
            action="login.php"
            class="auth-form"
        >


            <!-- CSRF TOKEN -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >



            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    EMAIL ADDRESS
                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    required
                >

            </div>



            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    PASSWORD
                </label>


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>



            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="btn btn-primary w-100"
            >
                SIGN IN
            </button>


        </form>



        <!-- =================================================
             REGISTER / BACK LINKS
             ================================================= -->

        <div class="auth-footer">

            <p>
                Don't have an account?
            </p>


            <a href="register.php">
                CREATE AN ACCOUNT
            </a>


            <br><br>


            <a href="index.php">
                ← BACK TO WEBSITE
            </a>

        </div>


    </div>

</div>


</body>
</html>