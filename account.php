<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('account.php');

$uid = currentUserId();

$u = $pdo->prepare(
    'SELECT full_name, email, phone, created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$u->execute([$uid]);
$user = $u->fetch();

if (!$user) {
    session_destroy();
    header('Location: register.php');
    exit;
}

$membershipMessage = '';
$membershipError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_membership'])) {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $membershipError = 'Your session expired. Please try again.';
    } else {
        $cancel = $pdo->prepare(
            "UPDATE memberships
             SET status = 'Expired'
             WHERE user_id = ?
             AND status = 'Active'"
        );

        $cancel->execute([$uid]);

        if ($cancel->rowCount() > 0) {
            $membershipMessage = 'Your membership has been cancelled.';
        } else {
            $membershipError = 'There is no active membership to cancel.';
        }
    }
}

$m = $pdo->prepare(
    "SELECT *
     FROM memberships
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$m->execute([$uid]);
$memberships = $m->fetchAll();

$active = null;

foreach ($memberships as $row) {
    if (
        $row['status'] === 'Active' &&
        $row['expiration_date'] >= date('Y-m-d')
    ) {
        $active = $row;
        break;
    }
}

$days = 0;

if ($active) {
    $days = max(
        0,
        (int)(
            (
                strtotime($active['expiration_date']) -
                strtotime(date('Y-m-d'))
            ) / 86400
        )
    );
}

$c = $pdo->prepare(
    'SELECT COALESCE(SUM(quantity), 0) AS items
     FROM cart_items
     WHERE user_id = ?'
);

$c->execute([$uid]);
$cartCount = (int)$c->fetch()['items'];

$o = $pdo->prepare(
    'SELECT COUNT(*) AS cnt
     FROM orders
     WHERE user_id = ?'
);

$o->execute([$uid]);
$orderCount = (int)$o->fetch()['cnt'];

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
        My Account | Dionisio Fitness Center
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

<div class="auth-shell account-shell">

    <a
        href="index.php"
        class="auth-brand"
    >

        <span class="brand-mark">D</span>

        <span class="brand-text">

            <strong>DIONISIO</strong>

            <small>FITNESS CENTER</small>

        </span>

    </a>


    <div class="account-card">

        <div class="auth-copy">

            <p class="eyebrow">
                MEMBER AREA
            </p>

            <h1>
                MY <span>ACCOUNT.</span>
            </h1>

            <p>
                Welcome back,
                <?= e($user['full_name']) ?>.
            </p>

        </div>


        <?php if (isset($_GET['membership'])): ?>

            <div class="auth-success">
                Membership successfully activated
                and recorded on your account.
            </div>

        <?php endif; ?>


        <?php if ($membershipMessage): ?>

            <div class="auth-success">
                <?= e($membershipMessage) ?>
            </div>

        <?php endif; ?>


        <?php if ($membershipError): ?>

            <div class="auth-alert">
                <?= e($membershipError) ?>
            </div>

        <?php endif; ?>


        <div class="account-grid">


            <!-- MEMBERSHIP -->

            <div class="account-box">

                <span>
                    MY MEMBERSHIP
                </span>

                <?php if ($active): ?>

                    <h3>
                        <?= e(strtoupper($active['plan'])) ?>
                    </h3>

                    <strong class="account-status">
                        ACTIVE
                    </strong>

                    <p>
                        ₱<?= number_format((float)$active['price'], 2) ?>
                        /month
                    </p>

                    <p>
                        <?= e($active['start_date']) ?>
                        →
                        <?= e($active['expiration_date']) ?>
                    </p>

                    <b class="days-left">
                        <?= number_format($days) ?>
                        DAYS LEFT
                    </b>


                    <a
                        class="btn btn-red small-btn"
                        href="join.php"
                    >
                        RENEW / CHANGE PLAN
                    </a>


                    <form
                        method="POST"
                        style="margin-top:10px;"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="cancel_membership"
                            value="1"
                        >

                        <button
                            type="submit"
                            class="btn btn-outline small-btn"
                            data-confirm="Are you sure you want to cancel your current membership?"
                        >
                            CANCEL MEMBERSHIP
                        </button>

                    </form>

                <?php else: ?>

                    <h3>
                        NO ACTIVE MEMBERSHIP
                    </h3>

                    <p>
                        Join now to activate a membership.
                    </p>

                    <a
                        class="btn btn-red small-btn"
                        href="join.php"
                    >
                        JOIN NOW
                    </a>

                <?php endif; ?>

            </div>


            <!-- ORDERS -->

            <div class="account-box">

                <span>
                    MY ORDERS
                </span>

                <h3>
                    <?= number_format($orderCount) ?>
                </h3>

                <p>
                    Orders recorded on your account.
                </p>

                <a
                    class="btn btn-outline small-btn"
                    href="orders.php"
                >
                    VIEW ORDERS
                </a>

            </div>


            <!-- CART -->

            <div class="account-box">

                <span>
                    MY CART
                </span>

                <h3>
                    <?= number_format($cartCount) ?>
                    ITEMS
                </h3>

                <p>
                    Products waiting in your cart.
                </p>

                <a
                    class="btn btn-outline small-btn"
                    href="cart.php"
                >
                    VIEW CART
                </a>

            </div>


            <!-- PROFILE -->

            <div class="account-box">

                <span>
                    MY PROFILE
                </span>

                <h3>
                    <?= e($user['full_name']) ?>
                </h3>

                <p>
                    <?= e($user['email']) ?>
                </p>

                <a
                    class="btn btn-outline small-btn"
                    href="profile.php"
                >
                    EDIT PROFILE
                </a>

            </div>


            <!-- HISTORY -->

            <div class="account-box full-box">

                <span>
                    MEMBERSHIP HISTORY
                </span>

                <?php if ($memberships): ?>

                    <div class="history-list">

                        <?php foreach ($memberships as $hist): ?>

                            <div>

                                <b>
                                    <?= e(strtoupper($hist['plan'])) ?>
                                </b>

                                <span>
                                    ₱<?= number_format((float)$hist['price'], 2) ?>

                                    •

                                    <?= e($hist['start_date']) ?>

                                    to

                                    <?= e($hist['expiration_date']) ?>
                                </span>

                                <em>
                                    <?= e($hist['status']) ?>
                                </em>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p>
                        No previous membership records.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <div class="account-actions">

            <a href="merch.php">
                SHOP MERCH
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


<script src="js/script.js"></script>

</body>
</html>