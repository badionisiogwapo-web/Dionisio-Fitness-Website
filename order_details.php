<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('order_details.php');

$userId = currentUserId();

$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {

    header('Location: orders.php');
    exit;
}


/* =========================================================
   ORDER
   ========================================================= */

$orderStmt = $pdo->prepare(
    'SELECT
        order_id,
        user_id,
        total_amount,
        status,
        order_date
     FROM orders
     WHERE order_id = ?
     AND user_id = ?
     LIMIT 1'
);

$orderStmt->execute([
    $orderId,
    $userId
]);

$order = $orderStmt->fetch();

if (!$order) {

    header('Location: orders.php');
    exit;
}


/* =========================================================
   ORDER ITEMS
   ========================================================= */

$itemStmt = $pdo->prepare(
    'SELECT
        order_item_id,
        product_id,
        product_name,
        price,
        quantity,
        subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY order_item_id'
);

$itemStmt->execute([$orderId]);

$items = $itemStmt->fetchAll();

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
        Order #<?= (int)$order['order_id'] ?>
        | Dionisio Fitness Center
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
                ORDER DETAILS
            </p>

            <h1>
                ORDER
                <span>#<?= (int)$order['order_id'] ?></span>
            </h1>

            <?php if (isset($_GET['success'])): ?>

                <div class="auth-success">
                    Your order has been successfully recorded.
                </div>

            <?php endif; ?>

        </div>


        <div class="account-grid">

            <div class="account-box">

                <span>
                    ORDER STATUS
                </span>

                <h3>
                    <?= e($order['status']) ?>
                </h3>

                <p>
                    Order date:
                    <?= e($order['order_date']) ?>
                </p>

            </div>


            <div class="account-box">

                <span>
                    ORDER TOTAL
                </span>

                <h3>
                    ₱<?= number_format((float)$order['total_amount'], 2) ?>
                </h3>

                <p>
                    Total amount for this order.
                </p>

            </div>

        </div>


        <div
            class="account-box"
            style="margin-top:16px;"
        >

            <span>
                ITEMS
            </span>


            <?php if ($items): ?>

                <div class="checkout-list">

                    <?php foreach ($items as $item): ?>

                        <div>

                            <div>

                                <strong>
                                    <?= e($item['product_name']) ?>
                                </strong>

                                <br>

                                <small>
                                    ₱<?= number_format((float)$item['price'], 2) ?>
                                    ×
                                    <?= (int)$item['quantity'] ?>
                                </small>

                            </div>


                            <strong>
                                ₱<?= number_format((float)$item['subtotal'], 2) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p>
                    No items found for this order.
                </p>

            <?php endif; ?>

        </div>


        <div class="cart-total">

            <span>
                TOTAL
            </span>

            <strong>
                ₱<?= number_format((float)$order['total_amount'], 2) ?>
            </strong>

        </div>


        <div class="account-actions">

            <a href="orders.php">
                BACK TO ORDERS
            </a>

            <a href="merch.php">
                SHOP MERCH
            </a>

            <a href="account.php">
                MY ACCOUNT
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