<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('profile.php');

$userId = currentUserId();

$error = '';
$success = '';


/* =========================================================
   GET USER
========================================================= */

$stmt = $pdo->prepare(
    'SELECT
        full_name,
        email,
        phone,
        created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {

    session_destroy();

    header('Location: register.php');

    exit;
}


/* =========================================================
   UPDATE PROFILE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error =
            'Your session expired. Please try again.';

    } else {

        $fullName =
            trim($_POST['full_name'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $phone =
            trim($_POST['phone'] ?? '');


        if ($fullName === '') {

            $error =
                'Please enter your full name.';

        } elseif (
            $email === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $error =
                'Please enter a valid email address.';

        } else {

            $emailCheck = $pdo->prepare(
                'SELECT id
                 FROM users
                 WHERE email = ?
                 AND id != ?
                 LIMIT 1'
            );

            $emailCheck->execute([
                $email,
                $userId
            ]);


            if ($emailCheck->fetch()) {

                $error =
                    'That email address is already being used.';

            } else {

                $update = $pdo->prepare(
                    'UPDATE users
                     SET full_name = ?,
                         email = ?,
                         phone = ?
                     WHERE id = ?'
                );

                $update->execute([
                    $fullName,
                    $email,
                    $phone !== '' ? $phone : null,
                    $userId
                ]);


                $_SESSION['user_name'] =
                    $fullName;


                $user['full_name'] =
                    $fullName;

                $user['email'] =
                    $email;

                $user['phone'] =
                    $phone;


                $success =
                    'Your profile has been updated successfully.';
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

    <title>
        My Profile | Dionisio Fitness Center
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

</head>


<body class="auth-page">


<div class="auth-shell">


    <!-- =====================================================
         BRAND
    ====================================================== -->

    <a
        href="index.php"
        class="auth-brand"
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



    <!-- =====================================================
         PROFILE CARD
    ====================================================== -->

    <div class="auth-card">


        <div class="auth-copy">

            <p class="eyebrow">
                MEMBER SETTINGS
            </p>

            <h1>
                MY <span>PROFILE.</span>
            </h1>

            <p>
                Update the information connected to your
                Dionisio member account.
            </p>

        </div>



        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if ($error): ?>

            <div class="auth-alert">

                <?= e($error) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if ($success): ?>

            <div class="auth-success">

                <?= e($success) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             PROFILE FORM
        ================================================== -->

        <form
            method="POST"
            class="auth-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >



            <!-- FULL NAME -->

            <div class="form-group">

                <label for="full_name">
                    FULL NAME
                </label>

                <input
                    id="full_name"
                    type="text"
                    name="full_name"
                    value="<?= e($user['full_name']) ?>"
                    maxlength="120"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    EMAIL ADDRESS
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($user['email']) ?>"
                    maxlength="190"
                    required
                >

            </div>



            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">

                    PHONE NUMBER

                    <span>
                        OPTIONAL
                    </span>

                </label>

                <input
                    id="phone"
                    type="text"
                    name="phone"
                    value="<?= e($user['phone']) ?>"
                    maxlength="30"
                >

            </div>



            <!-- SAVE BUTTON -->

            <button
                type="submit"
                class="btn btn-red auth-submit"
                data-confirm="Are you sure you want to save your profile changes?"
            >
                SAVE CHANGES
            </button>

        </form>



        <!-- =================================================
             MEMBER SINCE
        ================================================== -->

        <p class="profile-date">

            MEMBER SINCE:

            <strong>
                <?= e($user['created_at']) ?>
            </strong>

        </p>



        <!-- =================================================
             ACCOUNT ACTIONS
        ================================================== -->

        <div class="account-actions">

            <a href="account.php">
                MY ACCOUNT
            </a>

            <a href="index.php">
                BACK TO WEBSITE
            </a>

            <a
                href="logout.php"
                data-confirm="Are you sure you want to log out?"
            >
                LOG OUT
            </a>

        </div>


    </div>

</div>



<!-- =========================================================
     CONFIRMATION MODAL
========================================================= -->

<div
    id="confirmModal"
    class="confirm-modal"
    aria-hidden="true"
>

    <div class="confirm-box">


        <!-- CLOSE -->

        <button
            type="button"
            class="confirm-close"
            aria-label="Close confirmation"
        >
            ×
        </button>



        <!-- TITLE -->

        <p class="eyebrow">
            CONFIRM ACTION
        </p>


        <h2>
            SAVE <span>CHANGES?</span>
        </h2>


        <!-- MESSAGE -->

        <p id="confirmMessage">
            Are you sure you want to save your profile changes?
        </p>



        <!-- BUTTONS -->

        <div class="confirm-actions">

            <button
                type="button"
                id="confirmCancel"
                class="btn btn-outline"
            >
                CANCEL
            </button>


            <button
                type="button"
                id="confirmOkay"
                class="btn btn-red"
            >
                YES, SAVE CHANGES
            </button>

        </div>


    </div>

</div>



<script src="js/script.js"></script>


</body>
</html>