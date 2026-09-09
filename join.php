```php
<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SHOW PHP ERRORS WHILE DEVELOPING
|--------------------------------------------------------------------------
| Remove these 3 lines when your website is already finished/live.
*/

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';


/*
|--------------------------------------------------------------------------
| REQUIRE USER TO BE LOGGED IN
|--------------------------------------------------------------------------
| If the user is not logged in, login.php should bring them back here.
*/

requireLogin('join.php');


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| GET USER INFORMATION
|--------------------------------------------------------------------------
*/

try {

    $userStmt = $pdo->prepare(
        'SELECT
            id,
            full_name,
            email,
            phone
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $userStmt->execute([
        $userId
    ]);

    $user = $userStmt->fetch();

} catch (PDOException $exception) {

    die(
        'Unable to load your account.<br><br>' .
        htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


/*
|--------------------------------------------------------------------------
| USER DOES NOT EXIST
|--------------------------------------------------------------------------
*/

if (!$user) {

    $_SESSION = [];

    session_destroy();

    header(
        'Location: login.php?redirect=join.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

$allowedPlans = [
    'Basic',
    'Premium',
    'VIP'
];


/*
|--------------------------------------------------------------------------
| DEFAULT SELECTED PLAN
|--------------------------------------------------------------------------
| Also allows:
| join.php?plan=Basic
| join.php?plan=Premium
| join.php?plan=VIP
*/

$selectedPlan =
    $_POST['plan']
    ?? $_GET['plan']
    ?? 'Premium';


if (!in_array(
    $selectedPlan,
    $allowedPlans,
    true
)) {
    $selectedPlan = 'Premium';
}


/*
|--------------------------------------------------------------------------
| PROCESS MEMBERSHIP FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | VERIFY CSRF
    |--------------------------------------------------------------------------
    */

    if (!verifyCsrf(
        $_POST['csrf_token'] ?? null
    )) {

        $error =
            'Your session expired. Please refresh the page and try again.';

    } elseif (!in_array(
        $selectedPlan,
        $allowedPlans,
        true
    )) {

        $error =
            'Please select a valid membership plan.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | BEGIN TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING MEMBERSHIP
            |--------------------------------------------------------------------------
            */

            $existingStmt = $pdo->prepare(
                "SELECT
                    id,
                    plan,
                    status
                 FROM memberships
                 WHERE user_id = ?
                 AND status IN ('Pending', 'Active')
                 ORDER BY id DESC
                 LIMIT 1"
            );

            $existingStmt->execute([
                $userId
            ]);

            $membership =
                $existingStmt->fetch();


            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING MEMBERSHIP
            |--------------------------------------------------------------------------
            */

            if ($membership) {

                $updateStmt = $pdo->prepare(
                    'UPDATE memberships
                     SET
                        plan = ?,
                        start_date = CURDATE()
                     WHERE id = ?
                     AND user_id = ?'
                );

                $updateStmt->execute([
                    $selectedPlan,
                    (int)$membership['id'],
                    $userId
                ]);

            /*
            |--------------------------------------------------------------------------
            | CREATE NEW MEMBERSHIP
            |--------------------------------------------------------------------------
            */

            } else {

                $insertStmt = $pdo->prepare(
                    "INSERT INTO memberships
                    (
                        user_id,
                        plan,
                        status,
                        start_date
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        'Pending',
                        CURDATE()
                    )"
                );

                $insertStmt->execute([
                    $userId,
                    $selectedPlan
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT TO ACCOUNT
            |--------------------------------------------------------------------------
            */

            header(
                'Location: account.php?membership=success'
            );

            exit;

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Unable to save your membership right now. ' .
                $exception->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Join Dionisio | Dionisio Fitness Center';


/*
|--------------------------------------------------------------------------
| SAFE OUTPUT HELPER
|--------------------------------------------------------------------------
| Only create this if auth.php does not already provide e().
*/

if (!function_exists('e')) {

    function e(mixed $value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
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

    <meta
        name="description"
        content="Choose your membership plan at Dionisio Fitness Center."
    >

    <title>
        <?= e($pageTitle) ?>
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


    <!-- =========================================================
         BRAND
    ========================================================== -->

    <a
        href="index.php"
        class="auth-brand"
        aria-label="Dionisio Fitness Center Home"
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


    <!-- =========================================================
         JOIN CARD
    ========================================================== -->

    <div class="join-card">


        <!-- INTRO -->

        <div class="auth-copy">

            <p class="eyebrow">
                MEMBERSHIP
            </p>


            <h1>
                CHOOSE YOUR<br>
                <span>PLAN.</span>
            </h1>


            <p>

                Welcome,

                <strong>
                    <?= e($user['full_name']) ?>
                </strong>.

                Select the membership you want
                to request.

            </p>

        </div>


        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if ($error !== ''): ?>

            <div class="auth-alert">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             MEMBERSHIP FORM
        ====================================================== -->

        <form
            method="POST"
            action="join.php"
            id="membershipForm"
        >


            <!-- CSRF TOKEN -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >


            <div class="membership-options">


                <!-- =================================================
                     BASIC PLAN
                ================================================== -->

                <label
                    class="
                        membership-option
                        <?= $selectedPlan === 'Basic'
                            ? 'selected'
                            : '' ?>
                    "
                >

                    <input
                        type="radio"
                        name="plan"
                        value="Basic"

                        <?= $selectedPlan === 'Basic'
                            ? 'checked'
                            : '' ?>
                    >


                    <span>

                        <strong>
                            BASIC
                        </strong>


                        <b>

                            ₱999

                            <small>
                                / MONTH
                            </small>

                        </b>


                        <em>
                            Gym access •
                            Locker room •
                            Open gym
                        </em>

                    </span>

                </label>


                <!-- =================================================
                     PREMIUM PLAN
                ================================================== -->

                <label
                    class="
                        membership-option
                        featured-option
                        <?= $selectedPlan === 'Premium'
                            ? 'selected'
                            : '' ?>
                    "
                >

                    <input
                        type="radio"
                        name="plan"
                        value="Premium"

                        <?= $selectedPlan === 'Premium'
                            ? 'checked'
                            : '' ?>
                    >


                    <span>

                        <strong>

                            PREMIUM

                            <i>
                                MOST POPULAR
                            </i>

                        </strong>


                        <b>

                            ₱1,499

                            <small>
                                / MONTH
                            </small>

                        </b>


                        <em>
                            All equipment •
                            Personal training •
                            Nutrition guidance
                        </em>

                    </span>

                </label>


                <!-- =================================================
                     VIP PLAN
                ================================================== -->

                <label
                    class="
                        membership-option
                        <?= $selectedPlan === 'VIP'
                            ? 'selected'
                            : '' ?>
                    "
                >

                    <input
                        type="radio"
                        name="plan"
                        value="VIP"

                        <?= $selectedPlan === 'VIP'
                            ? 'checked'
                            : '' ?>
                    >


                    <span>

                        <strong>
                            VIP
                        </strong>


                        <b>

                            ₱2,499

                            <small>
                                / MONTH
                            </small>

                        </b>


                        <em>
                            Premium benefits •
                            Custom meal plan •
                            VIP lounge
                        </em>

                    </span>

                </label>


            </div>


            <!-- =====================================================
                 CONTINUE BUTTON
            ====================================================== -->

            <button
                class="btn btn-red auth-submit"
                type="submit"
                id="membershipSubmit"
            >

                CONTINUE WITH
                <?= e(
                    strtoupper(
                        $selectedPlan
                    )
                ) ?>

            </button>


        </form>


        <!-- =========================================================
             ACCOUNT INFORMATION
        ========================================================== -->

        <div class="account-mini">


            <span>

                Signed in as

                <strong>
                    <?= e($user['email']) ?>
                </strong>

            </span>


            <a href="account.php">
                MY ACCOUNT
            </a>


            <a href="logout.php">
                SIGN OUT
            </a>


        </div>


        <!-- =========================================================
             BACK TO WEBSITE
        ========================================================== -->

        <a
            href="index.php"
            class="auth-back"
        >

            ← BACK TO WEBSITE

        </a>


    </div>


</div>


<!-- =============================================================
     MEMBERSHIP SELECTION SCRIPT
============================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const membershipInputs =
            document.querySelectorAll(
                '.membership-option input[type="radio"]'
            );


        const membershipButton =
            document.getElementById(
                'membershipSubmit'
            );


        membershipInputs.forEach(
            function (input) {

                input.addEventListener(
                    'change',
                    function () {

                        /*
                        ---------------------------------------------
                        REMOVE SELECTED CLASS
                        ---------------------------------------------
                        */

                        document
                            .querySelectorAll(
                                '.membership-option'
                            )
                            .forEach(
                                function (option) {

                                    option.classList.remove(
                                        'selected'
                                    );

                                }
                            );


                        /*
                        ---------------------------------------------
                        ADD SELECTED CLASS
                        ---------------------------------------------
                        */

                        const selectedOption =
                            input.closest(
                                '.membership-option'
                            );


                        if (selectedOption) {

                            selectedOption.classList.add(
                                'selected'
                            );

                        }


                        /*
                        ---------------------------------------------
                        CHANGE BUTTON TEXT
                        ---------------------------------------------
                        */

                        if (membershipButton) {

                            membershipButton.textContent =
                                'CONTINUE WITH '
                                + input.value.toUpperCase();

                        }

                    }
                );

            }
        );

    }
);

</script>


</body>

</html>
```
