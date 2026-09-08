<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('checkout.php');

$userId = currentUserId();

$error = '';


/* =========================================================
   GET USER
   ========================================================= */

$userStmt = $pdo->prepare(
    'SELECT full_name, email, phone
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$userStmt->execute([$userId]);

$user = $userStmt->fetch();

if (!$user) {

    session_destroy();

    header('Location: register.php');

    exit;
}


/* =========================================================
   GET CART
   ========================================================= */

function getCheckoutItems(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT
            ci.cart_item_id,
            ci.product_id,
            ci.quantity,
            p.product_name,
            p.price,
            p.stock,
            p.image,
            (ci.quantity * p.price) AS subtotal
         FROM cart_items ci
         INNER JOIN products p
            ON p.product_id = ci.product_id
         WHERE ci.user_id = ?
         ORDER BY ci.created_at'
    );

    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}


$items = getCheckoutItems($pdo, $userId);

$total = 0;

foreach ($items as $item) {
    $total += (float)$item['subtotal'];
}


/* =========================================================
   PLACE ORDER
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error = 'Your session expired. Please try again.';

    } elseif (!$items) {

        $error = 'Your cart is empty.';

    } else {

        try {

            $pdo->beginTransaction();


            /*
             * Re-read the cart while the transaction is active.
             */

            $items = getCheckoutItems($pdo, $userId);

            if (!$items) {

                throw new RuntimeException(
                    'Your cart is empty.'
                );
            }


            $total = 0;


            /*
             * Lock each product row and check stock.
             */

            foreach ($items as &$item) {

                $stockStmt = $pdo->prepare(
                    'SELECT
                        product_id,
                        product_name,
                        price,
                        stock
                     FROM products
                     WHERE product_id = ?
                     FOR UPDATE'
                );

                $stockStmt->execute([
                    $item['product_id']
                ]);

                $product = $stockStmt->fetch();

                if (!$product) {

                    throw new RuntimeException(
                        'One of the products is no longer available.'
                    );
                }


                if (
                    (int)$item['quantity'] >
                    (int)$product['stock']
                ) {

                    throw new RuntimeException(
                        'Not enough stock for ' .
                        $product['product_name'] . '.'
                    );
                }


                $item['product_name'] =
                    $product['product_name'];

                $item['price'] =
                    $product['price'];

                $item['stock'] =
                    $product['stock'];

                $item['subtotal'] =
                    (int)$item['quantity'] *
                    (float)$product['price'];


                $total += $item['subtotal'];
            }

            unset($item);


            /*
             * Create order.
             */

            $orderStmt = $pdo->prepare(
                "INSERT INTO orders
                (
                    user_id,
                    total_amount,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    'Pending'
                )"
            );

            $orderStmt->execute([
                $userId,
                $total
            ]);

            $orderId = (int)$pdo->lastInsertId();


            /*
             * Create order items and reduce stock.
             */

            foreach ($items as $item) {

                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        price,
                        quantity,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?, ?)'
                );

                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['price'],
                    $item['quantity'],
                    $item['subtotal']
                ]);


                $stockUpdate = $pdo->prepare(
                    'UPDATE products
                     SET stock = stock - ?
                     WHERE product_id = ?
                     AND stock >= ?'
                );

                $stockUpdate->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $item['quantity']
                ]);


                if ($stockUpdate->rowCount() !== 1) {

                    throw new RuntimeException(
                        'Stock changed while processing your order.'
                    );
                }
            }


            /*
             * Empty cart after successful order.
             */

            $clearCart = $pdo->prepare(
                'DELETE FROM cart_items
                 WHERE user_id = ?'
            );

            $clearCart->execute([$userId]);


            $pdo->commit();


            header(
                'Location: order_details.php?id=' .
                $orderId .
                '&success=1'
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();

            if (!$error) {
                $error =
                    'We could not place your order. Please try again.';
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
        Checkout | Dionisio Fitness Center
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
                CHECKOUT
            </p>

            <h1>
                COMPLETE<br>
                <span>ORDER.</span>
            </h1>

            <p>
                Review your order before placing it.
            </p>

        </div>


        <?php if ($error): ?>

            <div class="auth-alert">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <?php if (!$items): ?>

            <div class="empty-state">

                <h3>
                    YOUR CART IS EMPTY
                </h3>

                <p>
                    There are no products to checkout.
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


            <div class="checkout-note">

                <strong>
                    CUSTOMER
                </strong>

                <br>

                <?= e($user['full_name']) ?>

                <br>

                <?= e($user['email']) ?>

                <?php if (!empty($user['phone'])): ?>

                    <br>
                    <?= e($user['phone']) ?>

                <?php endif; ?>

            </div>


            <div
                class="checkout-list"
                style="margin-top:25px;"
            >

                <?php foreach ($items as $item): ?>

                    <div>

                        <div>

                            <strong>
                                <?= e($item['product_name']) ?>
                            </strong>

                            <br>

                            <small>
                                Quantity:
                                <?= (int)$item['quantity'] ?>
                            </small>

                        </div>


                        <strong>
                            ₱<?= number_format((float)$item['subtotal'], 2) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>


            <div class="cart-total">

                <span>
                    TOTAL
                </span>

                <strong>
                    ₱<?= number_format($total, 2) ?>
                </strong>

            </div>


            <div
                class="auth-alert"
                style="
                    margin-top:20px;
                    border-left-color:#777;
                    background:#0d0d0d;
                "
            >
                This is a local school-project checkout.
                No real online payment will be processed.
            </div>


            <form
                method="POST"
                class="auth-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-red auth-submit"
                    data-confirm="Are you sure you want to place this order?"
                >
                    PLACE ORDER
                </button>

            </form>

        <?php endif; ?>


        <div class="account-actions">

            <a href="cart.php">
                BACK TO CART
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