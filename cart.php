<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('cart.php');

$userId = currentUserId();

$message = '';
$error = '';


/* =========================================================
   UPDATE / REMOVE CART
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error = 'Your session expired. Please try again.';

    } else {

        $action = $_POST['action'] ?? '';
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);


        /* UPDATE */

        if ($action === 'update') {

            $quantity = (int)($_POST['quantity'] ?? 0);

            if ($cartItemId <= 0 || $quantity < 1) {

                $error = 'Please enter a valid quantity.';

            } else {

                $stmt = $pdo->prepare(
                    'SELECT
                        ci.cart_item_id,
                        ci.product_id,
                        p.stock
                     FROM cart_items ci
                     INNER JOIN products p
                        ON p.product_id = ci.product_id
                     WHERE ci.cart_item_id = ?
                     AND ci.user_id = ?
                     LIMIT 1'
                );

                $stmt->execute([
                    $cartItemId,
                    $userId
                ]);

                $item = $stmt->fetch();

                if (!$item) {

                    $error = 'Cart item not found.';

                } elseif ($quantity > (int)$item['stock']) {

                    $error =
                        'The requested quantity is greater than the available stock.';

                } else {

                    $update = $pdo->prepare(
                        'UPDATE cart_items
                         SET quantity = ?,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE cart_item_id = ?
                         AND user_id = ?'
                    );

                    $update->execute([
                        $quantity,
                        $cartItemId,
                        $userId
                    ]);

                    $message = 'Cart updated successfully.';
                }
            }


        /* REMOVE */

        } elseif ($action === 'remove') {

            if ($cartItemId <= 0) {

                $error = 'Invalid cart item.';

            } else {

                $remove = $pdo->prepare(
                    'DELETE FROM cart_items
                     WHERE cart_item_id = ?
                     AND user_id = ?'
                );

                $remove->execute([
                    $cartItemId,
                    $userId
                ]);

                if ($remove->rowCount() > 0) {

                    $message = 'Item removed from your cart.';

                } else {

                    $error = 'Cart item not found.';
                }
            }
        }
    }
}


/* =========================================================
   GET CART ITEMS
   ========================================================= */

$stmt = $pdo->prepare(
    'SELECT
        ci.cart_item_id,
        ci.product_id,
        ci.quantity,
        p.product_name,
        p.description,
        p.price,
        p.image,
        p.stock,
        (ci.quantity * p.price) AS subtotal
     FROM cart_items ci
     INNER JOIN products p
        ON p.product_id = ci.product_id
     WHERE ci.user_id = ?
     ORDER BY ci.created_at DESC'
);

$stmt->execute([$userId]);

$items = $stmt->fetchAll();


$total = 0;

foreach ($items as $item) {
    $total += (float)$item['subtotal'];
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
        My Cart | Dionisio Fitness Center
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
                SHOPPING CART
            </p>

            <h1>
                MY <span>CART.</span>
            </h1>

            <p>
                Review your selected Dionisio merchandise
                before checkout.
            </p>

        </div>


        <?php if ($message): ?>

            <div class="auth-success">
                <?= e($message) ?>
            </div>

        <?php endif; ?>


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
                    Add some Dionisio merchandise to continue.
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

            <div class="cart-list">

                <?php foreach ($items as $item): ?>

                    <div class="cart-row">

                        <div class="cart-product">

                            <div class="cart-thumb">

                                <?php if ($item['image']): ?>

                                    <img
                                        src="assets/<?= e($item['image']) ?>"
                                        alt="<?= e($item['product_name']) ?>"
                                    >

                                <?php endif; ?>

                            </div>


                            <div>

                                <h3>
                                    <?= e($item['product_name']) ?>
                                </h3>

                                <p>
                                    ₱<?= number_format((float)$item['price'], 2) ?>
                                    each
                                </p>

                            </div>

                        </div>


                        <strong>
                            ₱<?= number_format((float)$item['subtotal'], 2) ?>
                        </strong>


                        <form
                            method="POST"
                            class="cart-qty"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="update"
                            >

                            <input
                                type="hidden"
                                name="cart_item_id"
                                value="<?= (int)$item['cart_item_id'] ?>"
                            >

                            <input
                                type="number"
                                name="quantity"
                                value="<?= (int)$item['quantity'] ?>"
                                min="1"
                                max="<?= (int)$item['stock'] ?>"
                                aria-label="Quantity"
                            >

                            <button
                                type="submit"
                            >
                                UPDATE
                            </button>

                        </form>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="remove"
                            >

                            <input
                                type="hidden"
                                name="cart_item_id"
                                value="<?= (int)$item['cart_item_id'] ?>"
                            >

                            <button
                                type="submit"
                                class="remove-btn"
                                data-confirm="Are you sure you want to remove this item from your cart?"
                            >
                                REMOVE
                            </button>

                        </form>

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
                class="account-actions"
                style="justify-content:flex-end;"
            >

                <a href="merch.php">
                    CONTINUE SHOPPING
                </a>

                <a
                    href="checkout.php"
                    style="
                        background:#b01619;
                        border-color:#b01619;
                    "
                >
                    PROCEED TO CHECKOUT
                </a>

            </div>

        <?php endif; ?>


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


<script src="js/script.js"></script>

</body>
</html>