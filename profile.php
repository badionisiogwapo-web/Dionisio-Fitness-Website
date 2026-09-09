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
        password_hash,
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
   UPDATE PROFILE / PASSWORD
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error = 'Your session expired. Please try again.';

    } else {

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';


        /* -------------------------------------------------
           BASIC PROFILE VALIDATION
        ------------------------------------------------- */

        if ($fullName === '') {

            $error = 'Please enter your full name.';

        } elseif (
            $email === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $error = 'Please enter a valid email address.';

        } else {


            /* -------------------------------------------------
               CHECK EMAIL
            ------------------------------------------------- */

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


                /* -------------------------------------------------
                   CHECK PASSWORD CHANGE
                ------------------------------------------------- */

                $changingPassword =
                    $currentPassword !== '' ||
                    $newPassword !== '' ||
                    $confirmPassword !== '';


                if ($changingPassword) {

                    if (
                        $currentPassword === '' ||
                        $newPassword === '' ||
                        $confirmPassword === ''
                    ) {

                        $error =
                            'Please complete all password fields.';

                    } elseif (
                        !password_verify(
                            $currentPassword,
                            $user['password_hash']
                        )
                    ) {

                        $error =
                            'Your current password is incorrect.';

                    } elseif (
                        strlen($newPassword) < 8
                    ) {

                        $error =
                            'Your new password must be at least 8 characters.';

                    } elseif (
                        $newPassword !== $confirmPassword
                    ) {

                        $error =
                            'The new passwords do not match.';

                    } elseif (
                        password_verify(
                            $newPassword,
                            $user['password_hash']
                        )
                    ) {

                        $error =
                            'Your new password must be different from your current password.';

                    }

                }


                /* -------------------------------------------------
                   SAVE CHANGES
                ------------------------------------------------- */

                if ($error === '') {

                    if ($changingPassword) {

                        $newPasswordHash =
                            password_hash(
                                $newPassword,
                                PASSWORD_DEFAULT
                            );


                        $update = $pdo->prepare(
                            'UPDATE users
                             SET full_name = ?,
                                 email = ?,
                                 phone = ?,
                                 password_hash = ?
                             WHERE id = ?'
                        );


                        $update->execute([
                            $fullName,
                            $email,
                            $phone !== '' ? $phone : null,
                            $newPasswordHash,
                            $userId
                        ]);


                        $success =
                            'Your profile and password have been updated successfully.';

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


                        $success =
                            'Your profile has been updated successfully.';
                    }


                    /* -------------------------------------------------
                       UPDATE SESSION
                    ------------------------------------------------- */

                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;


                    /* -------------------------------------------------
                       UPDATE DISPLAYED USER
                    ------------------------------------------------- */

                    $user['full_name'] = $fullName;
                    $user['email'] = $email;
                    $user['phone'] = $phone;


                    /* -------------------------------------------------
                       CLEAR PASSWORD FIELDS
                    ------------------------------------------------- */

                    $currentPassword = '';
                    $newPassword = '';
                    $confirmPassword = '';
                }

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


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- WEBSITE CSS -->

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


        <!-- HEADER -->

        <div class="auth-copy">

            <p class="eyebrow">
                MEMBER SETTINGS
            </p>


            <h1>
                MY <span>PROFILE.</span>
            </h1>


            <p>
                Update your personal information and
                password connected to your Dionisio
                member account.
            </p>

        </div>



        <!-- ERROR -->

        <?php if ($error): ?>

            <div class="auth-alert">

                <?= e($error) ?>

            </div>

        <?php endif; ?>



        <!-- SUCCESS -->

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
            id="profileForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >



            <!-- =================================================
                 PERSONAL INFORMATION
            ================================================== -->

            <div class="profile-section-title">
                PERSONAL INFORMATION
            </div>



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
                    autocomplete="name"
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
                    autocomplete="email"
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
                    autocomplete="tel"
                >

            </div>



            <!-- =================================================
                 CHANGE PASSWORD
            ================================================== -->

            <div class="profile-section-title password-section-title">

                CHANGE PASSWORD

            </div>


            <p class="password-help">

                Leave these fields empty if you do not want
                to change your password.

            </p>



            <!-- CURRENT PASSWORD -->

            <div class="form-group">

                <label for="current_password">
                    CURRENT PASSWORD
                </label>


                <div class="password-field">

                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-target="current_password"
                        aria-label="Show current password"
                    >
                        SHOW
                    </button>

                </div>

            </div>



            <!-- NEW PASSWORD -->

            <div class="form-group">

                <label for="new_password">
                    NEW PASSWORD
                </label>


                <div class="password-field">

                    <input
                        id="new_password"
                        type="password"
                        name="new_password"
                        minlength="8"
                        autocomplete="new-password"
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-target="new_password"
                        aria-label="Show new password"
                    >
                        SHOW
                    </button>

                </div>


                <small class="password-note">

                    Minimum of 8 characters.

                </small>

            </div>



            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    CONFIRM NEW PASSWORD
                </label>


                <div class="password-field">

                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-target="confirm_password"
                        aria-label="Show password confirmation"
                    >
                        SHOW
                    </button>

                </div>

            </div>



            <!-- =================================================
                 SAVE CHANGES
            ================================================== -->

            <button
                type="button"
                class="btn btn-red auth-submit"
                id="saveProfileButton"
                data-confirm="Are you sure you want to save your profile changes?"
                data-confirm-type="save"
            >
                SAVE CHANGES
            </button>


        </form>



        <!-- MEMBER SINCE -->

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


            <!-- LOGOUT -->

            <a
                href="logout.php"
                data-confirm="Are you sure you want to log out?"
                data-confirm-type="logout"
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
            id="confirmClose"
            aria-label="Close confirmation"
        >
            ×
        </button>


        <p class="eyebrow">
            CONFIRM ACTION
        </p>


        <h2 id="confirmTitle">
            ARE YOU <span>SURE?</span>
        </h2>


        <p id="confirmMessage">
            Are you sure?
        </p>


        <div class="confirm-actions">


            <button
                type="button"
                id="confirmCancel"
                class="btn btn-outline"
            >
                NO / CANCEL
            </button>


            <button
                type="button"
                id="confirmOkay"
                class="btn btn-red"
            >
                YES
            </button>


        </div>


    </div>

</div>



<!-- =========================================================
     CONFIRMATION + PASSWORD JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       PASSWORD SHOW / HIDE
    ===================================================== */

    document
        .querySelectorAll(".password-toggle")
        .forEach(function (button) {

            button.addEventListener("click", function () {

                const targetId =
                    button.getAttribute("data-target");

                const input =
                    document.getElementById(targetId);

                if (!input) {
                    return;
                }


                if (input.type === "password") {

                    input.type = "text";

                    button.textContent = "HIDE";

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    input.type = "password";

                    button.textContent = "SHOW";

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            });

        });



    /* =====================================================
       CONFIRMATION MODAL
    ===================================================== */

    const confirmModal =
        document.getElementById("confirmModal");

    const confirmTitle =
        document.getElementById("confirmTitle");

    const confirmMessage =
        document.getElementById("confirmMessage");

    const confirmCancel =
        document.getElementById("confirmCancel");

    const confirmOkay =
        document.getElementById("confirmOkay");

    const confirmClose =
        document.getElementById("confirmClose");


    let confirmType = "";

    let confirmTarget = null;



    /* -----------------------------------------------------
       OPEN CONFIRMATION
    ----------------------------------------------------- */

    document
        .querySelectorAll("[data-confirm]")
        .forEach(function (element) {

            element.addEventListener("click", function (event) {

                event.preventDefault();


                confirmTarget = element;


                confirmType =
                    element.getAttribute(
                        "data-confirm-type"
                    );


                confirmMessage.textContent =
                    element.getAttribute(
                        "data-confirm"
                    );


                /* SAVE */

                if (confirmType === "save") {

                    confirmTitle.innerHTML =
                        'SAVE <span>CHANGES?</span>';

                    confirmOkay.textContent =
                        "YES, SAVE CHANGES";
                }


                /* LOGOUT */

                else if (confirmType === "logout") {

                    confirmTitle.innerHTML =
                        'LOG <span>OUT?</span>';

                    confirmOkay.textContent =
                        "YES, LOG OUT";
                }


                confirmModal.classList.add("open");

                confirmModal.setAttribute(
                    "aria-hidden",
                    "false"
                );

                document.body.classList.add(
                    "modal-open"
                );

            });

        });



    /* -----------------------------------------------------
       CLOSE MODAL
    ----------------------------------------------------- */

    function closeConfirmModal() {

        confirmModal.classList.remove("open");

        confirmModal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-open"
        );


        confirmTarget = null;

        confirmType = "";

    }



    /* -----------------------------------------------------
       CANCEL
    ----------------------------------------------------- */

    confirmCancel.addEventListener(
        "click",
        function () {

            closeConfirmModal();

        }
    );



    /* -----------------------------------------------------
       CLOSE BUTTON
    ----------------------------------------------------- */

    confirmClose.addEventListener(
        "click",
        function () {

            closeConfirmModal();

        }
    );



    /* -----------------------------------------------------
       YES
    ----------------------------------------------------- */

    confirmOkay.addEventListener(
        "click",
        function () {


            /* LOGOUT */

            if (
                confirmType === "logout" &&
                confirmTarget
            ) {

                window.location.href =
                    confirmTarget.getAttribute("href");

                return;

            }


            /* SAVE */

            if (
                confirmType === "save" &&
                confirmTarget
            ) {

                const form =
                    document.getElementById(
                        "profileForm"
                    );


                if (form) {

                    form.submit();

                }

            }

        }
    );



    /* -----------------------------------------------------
       CLICK OUTSIDE MODAL
    ----------------------------------------------------- */

    confirmModal.addEventListener(
        "click",
        function (event) {

            if (event.target === confirmModal) {

                closeConfirmModal();

            }

        }
    );

});

</script>


</body>

</html>