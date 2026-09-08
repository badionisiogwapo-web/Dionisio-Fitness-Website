<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('orders.php');

$userId = currentUserId();

$stmt = $pdo->prepare(
    'SELECT
        order_id,
        total_amount,
        status,
        order_date
     FROM orders
     WHERE user_id = ?
     ORDER BY order_date DESC'
);

$stmt->execute([$userId]);

$orders = $stmt->fetchAll();
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
        My Orders | Dionisio Fitness Center
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

<div class="auth-shell wide-shell">

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
                MY <span>ORDERS.</span>
            </h1>

            <p>
                View your Dionisio merchandise orders.
            </p>

        </div>


        <?php if (!$orders): ?>

            <div class="empty-state">

                <h3>
                    NO ORDERS YET
                </h3>

                <p>
                    Your completed purchases will appear here.
                </p>

                <a
                    href="merch.php"
                    class="btn btn-red"
                    style="margin-top:18px;"
                >
                    SHOP MERCH
                </a>

            </div>

        <?php else: ?>

            <div class="order-list">

                <?php foreach ($orders as $order): ?>

                    <a
                        class="order-row"
                        href="order_details.php?id=<?= (int)$order['order_id'] ?>"
                    >

                        <div>

                            <span>
                                ORDER #<?= (int)$order['order_id'] ?>
                            </span>

                            <b>
                                ₱<?= number_format((float)$order['total_amount'], 2) ?>
                            </b>

                            <span>
                                <?= e($order['order_date']) ?>
                            </span>

                        </div>


                        <div>

                            <em>
                                <?= e($order['status']) ?>
                            </em>

                            <span>
                                VIEW DETAILS →
                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <div class="account-actions">

            <a href="account.php">
                MY ACCOUNT
            </a>

            <a href="merch.php">
                SHOP MERCH
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