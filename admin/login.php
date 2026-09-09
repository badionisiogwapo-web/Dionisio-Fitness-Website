<?php

require_once __DIR__ . '/../config/admin_auth.php';


if (isAdminLoggedIn()) {

    header('Location: index.php');
    exit;

}


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';


    if (adminLogin($username, $password)) {

        header('Location: index.php');
        exit;

    }


    $error = 'Invalid username or password.';

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
        Admin Login | Dionisio Fitness Center
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

</head>


<body class="admin-login-page">


<div class="login-wrapper">


    <div class="login-card">


        <!-- LOGO -->

        <div class="login-logo">

            <div class="login-mark">
                D
            </div>

            <div class="login-brand-text">

                <h1>DIONISIO</h1>

                <span>
                    FITNESS CENTER
                </span>

            </div>

        </div>


        <!-- TITLE -->

        <div class="login-heading">

            <p>
                ADMINISTRATION
            </p>

            <h2>
                WELCOME BACK.
            </h2>

            <span>
                Sign in to manage your fitness center.
            </span>

        </div>


        <!-- ERROR -->

        <?php if ($error): ?>

            <div class="login-error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form method="POST">


            <div class="admin-form-group">

                <label for="username">
                    USERNAME
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter username"
                    autocomplete="username"
                    required
                >

            </div>


            <div class="admin-form-group">

                <label for="password">
                    PASSWORD
                </label>


                <div class="password-field">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        onclick="togglePassword()"
                    >
                        SHOW
                    </button>

                </div>

            </div>


            <button
                type="submit"
                class="admin-login-btn"
            >
                SIGN IN
            </button>


        </form>


        <a
            href="../index.php"
            class="back-website"
        >
            ← BACK TO WEBSITE
        </a>


    </div>


</div>


<script>

function togglePassword()
{
    const password =
        document.getElementById('password');

    const button =
        document.querySelector('.password-field button');


    if (password.type === 'password')
    {
        password.type = 'text';
        button.textContent = 'HIDE';
    }
    else
    {
        password.type = 'password';
        button.textContent = 'SHOW';
    }
}

</script>


</body>

</html>