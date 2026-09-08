<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('checkout.php');

$userId = currentUserId();

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Get Customer
|--------------------------------------------------------------------------
*/

$userStmt = $conn->prepare(
    "SELECT full_name, email, phone
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$userStmt) {
    die('Unable to load customer information.');
}

$userStmt->bind_param('i', $userId);
$userStmt->execute();

$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

$userStmt->close();

if (!$user) {
    session_unset();
    session_destroy();

    header('Location: register.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Active Membership
|--------------------------------------------------------------------------
*/

$membership = null;

$membershipStmt = $conn->prepare(
    "SELECT
        id,
        plan,
        price,
        status,
        start_date,
        expiration_date
     FROM memberships
     WHERE user_id = ?
     AND status = 'Active'
     AND start_date <= CURDATE()
     AND expiration_date >= CURDATE()
     ORDER BY expiration_date DESC
     LIMIT 1"
);

if ($membershipStmt) {

    $membershipStmt->bind_param('i', $userId);
    $membershipStmt->execute();

    $membershipResult = $membershipStmt->get_result();
    $membership = $membershipResult->fetch_assoc();

    $membershipStmt->close();
}

/*
|--------------------------------------------------------------------------
| Membership Discount
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The exact discount percentages are not defined in the current
| checkout.php/database information, so we do not invent percentages.
|
| Set these values when your official membership discount rules
| are confirmed.
|
*/

$discountRate = 0;

if ($membership) {

    /*
     * Change these only when your actual membership discount
     * percentages are confirmed.
     */
    if ($membership['plan'] === 'Basic') {
        $discountRate = 0;
    } elseif ($membership['plan'] === 'Premium') {
        $discountRate = 0;
    } elseif ($membership['plan'] === 'VIP') {
        $discountRate = 0;
    }
}

/*
|--------------------------------------------------------------------------
| Get Cart
|--------------------------------------------------------------------------
*/

function getCheckoutItems(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare(
        "SELECT
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
         ORDER BY ci.created_at ASC"
    );

    if (!$stmt) {
        throw new RuntimeException('Unable to load your cart.');
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();

    return $items;
}

try {
    $items = getCheckoutItems($conn, $userId);
} catch (Throwable $e) {
    $items = [];
    $error = $e->getMessage();
}

/*
|--------------------------------------------------------------------------
| Calculate Totals
|--------------------------------------------------------------------------
*/

$subtotal = 0;
$totalItems = 0;

foreach ($items as $item) {

    $quantity = (int) $item['quantity'];
    $price = (float) $item['price'];

    $subtotal += $quantity * $price;
    $totalItems += $quantity;
}

$discountAmount = $subtotal * ($discountRate / 100);

$finalTotal = $subtotal - $discountAmount;

if ($finalTotal < 0) {
    $finalTotal = 0;
}

/*
|--------------------------------------------------------------------------
| Place Order
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        $error = 'Your session expired. Please try again.';

    } elseif (!$items) {

        $error = 'Your cart is empty.';

    } else {

        $paymentMethod = trim($_POST['payment_method'] ?? '');

        $allowedPaymentMethods = [
            'Cash',
            'GCash',
            'Bank Transfer'
        ];

        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {

            $error = 'Please select a valid payment method.';

        } else {

            try {

                $conn->begin_transaction();

                /*
                 * Re-read the cart inside the transaction.
                 */
                $items = getCheckoutItems($conn, $userId);

                if (!$items) {
                    throw new RuntimeException(
                        'Your cart is empty.'
                    );
                }

                /*
                 * Re-check active membership.
                 */
                $membership = null;

                $membershipStmt = $conn->prepare(
                    "SELECT
                        id,
                        plan,
                        price,
                        status,
                        start_date,
                        expiration_date
                     FROM memberships
                     WHERE user_id = ?
                     AND status = 'Active'
                     AND start_date <= CURDATE()
                     AND expiration_date >= CURDATE()
                     ORDER BY expiration_date DESC
                     LIMIT 1
                     FOR UPDATE"
                );

                if ($membershipStmt) {

                    $membershipStmt->bind_param(
                        'i',
                        $userId
                    );

                    $membershipStmt->execute();

                    $membershipResult =
                        $membershipStmt->get_result();

                    $membership =
                        $membershipResult->fetch_assoc();

                    $membershipStmt->close();
                }

                /*
                 * Recalculate discount.
                 */
                $discountRate = 0;

                if ($membership) {

                    if ($membership['plan'] === 'Basic') {
                        $discountRate = 0;
                    } elseif ($membership['plan'] === 'Premium') {
                        $discountRate = 0;
                    } elseif ($membership['plan'] === 'VIP') {
                        $discountRate = 0;
                    }
                }

                $subtotal = 0;

                /*
                 * Lock products and check stock.
                 */
                foreach ($items as &$item) {

                    $stockStmt = $conn->prepare(
                        "SELECT
                            product_id,
                            product_name,
                            price,
                            stock
                         FROM products
                         WHERE product_id = ?
                         FOR UPDATE"
                    );

                    if (!$stockStmt) {
                        throw new RuntimeException(
                            'Unable to verify product stock.'
                        );
                    }

                    $productId = (int) $item['product_id'];

                    $stockStmt->bind_param(
                        'i',
                        $productId
                    );

                    $stockStmt->execute();

                    $stockResult =
                        $stockStmt->get_result();

                    $product =
                        $stockResult->fetch_assoc();

                    $stockStmt->close();

                    if (!$product) {
                        throw new RuntimeException(
                            'One of the products is no longer available.'
                        );
                    }

                    $quantity = (int) $item['quantity'];
                    $stock = (int) $product['stock'];

                    if ($quantity <= 0) {
                        throw new RuntimeException(
                            'Invalid product quantity.'
                        );
                    }

                    if ($quantity > $stock) {
                        throw new RuntimeException(
                            'Not enough stock for ' .
                            $product['product_name'] .
                            '. Only ' .
                            $stock .
                            ' item(s) are available.'
                        );
                    }

                    $item['product_name'] =
                        $product['product_name'];

                    $item['price'] =
                        (float) $product['price'];

                    $item['stock'] =
                        $stock;

                    $item['subtotal'] =
                        $quantity *
                        (float) $product['price'];

                    $subtotal +=
                        $item['subtotal'];
                }

                unset($item);

                $discountAmount =
                    $subtotal *
                    ($discountRate / 100);

                $finalTotal =
                    $subtotal -
                    $discountAmount;

                if ($finalTotal < 0) {
                    $finalTotal = 0;
                }

                /*
                 * Create order using the existing orders table.
                 */
                $orderStmt = $conn->prepare(
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

                if (!$orderStmt) {
                    throw new RuntimeException(
                        'Unable to create your order.'
                    );
                }

                $orderStmt->bind_param(
                    'id',
                    $userId,
                    $finalTotal
                );

                if (!$orderStmt->execute()) {
                    $orderStmt->close();

                    throw new RuntimeException(
                        'Unable to create your order.'
                    );
                }

                $orderId =
                    (int) $conn->insert_id;

                $orderStmt->close();

                /*
                 * Create order items.
                 */
                foreach ($items as $item) {

                    $orderItemStmt = $conn->prepare(
                        "INSERT INTO order_items
                        (
                            order_id,
                            product_id,
                            product_name,
                            price,
                            quantity,
                            subtotal
                        )
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    if (!$orderItemStmt) {
                        throw new RuntimeException(
                            'Unable to save order items.'
                        );
                    }

                    $itemOrderId = $orderId;
                    $itemProductId =
                        (int) $item['product_id'];

                    $itemName =
                        (string) $item['product_name'];

                    $itemPrice =
                        (float) $item['price'];

                    $itemQuantity =
                        (int) $item['quantity'];

                    $itemSubtotal =
                        (float) $item['subtotal'];

                    $orderItemStmt->bind_param(
                        'iisdid',
                        $itemOrderId,
                        $itemProductId,
                        $itemName,
                        $itemPrice,
                        $itemQuantity,
                        $itemSubtotal
                    );

                    if (!$orderItemStmt->execute()) {
                        $orderItemStmt->close();

                        throw new RuntimeException(
                            'Unable to save order items.'
                        );
                    }

                    $orderItemStmt->close();

                    /*
                     * Reduce stock.
                     */
                    $stockUpdate = $conn->prepare(
                        "UPDATE products
                         SET stock = stock - ?
                         WHERE product_id = ?
                         AND stock >= ?"
                    );

                    if (!$stockUpdate) {
                        throw new RuntimeException(
                            'Unable to update product stock.'
                        );
                    }

                    $stockUpdate->bind_param(
                        'iii',
                        $itemQuantity,
                        $itemProductId,
                        $itemQuantity
                    );

                    $stockUpdate->execute();

                    if ($stockUpdate->affected_rows !== 1) {

                        $stockUpdate->close();

                        throw new RuntimeException(
                            'Stock changed while processing your order.'
                        );
                    }

                    $stockUpdate->close();
                }

                /*
                 * Clear cart.
                 */
                $clearCart = $conn->prepare(
                    "DELETE FROM cart_items
                     WHERE user_id = ?"
                );

                if (!$clearCart) {
                    throw new RuntimeException(
                        'Unable to clear your cart.'
                    );
                }

                $clearCart->bind_param(
                    'i',
                    $userId
                );

                $clearCart->execute();
                $clearCart->close();

                /*
                 * Save checkout information in the session
                 * temporarily until the order table receives
                 * dedicated payment/discount columns.
                 */
                $_SESSION['last_order_payment_method'] =
                    $paymentMethod;

                $_SESSION['last_order_subtotal'] =
                    $subtotal;

                $_SESSION['last_order_discount'] =
                    $discountAmount;

                $_SESSION['last_order_discount_rate'] =
                    $discountRate;

                $_SESSION['last_order_final_total'] =
                    $finalTotal;

                $conn->commit();

                header(
                    'Location: order_details.php?id=' .
                    $orderId .
                    '&success=1'
                );

                exit;

            } catch (Throwable $e) {

                if ($conn->in_transaction) {
                    $conn->rollback();
                }

                $error = $e->getMessage();

                if (!$error) {
                    $error =
                        'We could not place your order. Please try again.';
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
        Checkout | Dionisio Fitness Center
    </title>

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
                                <?= (int) $item['quantity'] ?>
                            </small>

                        </div>

                        <strong>
                            ₱<?= number_format(
                                (float) $item['subtotal'],
                                2
                            ) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- MEMBERSHIP -->

            <div
                class="checkout-note"
                style="margin-top:20px;"
            >

                <strong>
                    MEMBERSHIP
                </strong>

                <br>

                <?php if ($membership): ?>

                    <?= e($membership['plan']) ?>
                    MEMBER

                    <?php if ($discountRate > 0): ?>

                        <br>

                        <small>
                            <?= number_format(
                                $discountRate,
                                0
                            ) ?>% merchandise discount
                        </small>

                    <?php else: ?>

                        <br>

                        <small>
                            Active membership detected.
                        </small>

                    <?php endif; ?>

                <?php else: ?>

                    NON-MEMBER

                    <br>

                    <small>
                        Regular merchandise pricing applies.
                    </small>

                <?php endif; ?>

            </div>


            <!-- PRICE SUMMARY -->

            <div class="cart-total">

                <span>
                    SUBTOTAL
                </span>

                <strong>
                    ₱<?= number_format(
                        $subtotal,
                        2
                    ) ?>
                </strong>

            </div>


            <?php if ($discountAmount > 0): ?>

                <div class="cart-total">

                    <span>
                        MEMBERSHIP DISCOUNT
                    </span>

                    <strong>
                        -₱<?= number_format(
                            $discountAmount,
                            2
                        ) ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div class="cart-total">

                <span>
                    TOTAL
                </span>

                <strong>
                    ₱<?= number_format(
                        $finalTotal,
                        2
                    ) ?>
                </strong>

            </div>


            <!-- PAYMENT METHOD -->

            <div
                class="checkout-note"
                style="margin-top:20px;"
            >

                <strong>
                    MODE OF PAYMENT
                </strong>

            </div>


            <form
                method="POST"
                class="auth-form"
                id="checkoutForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <div class="form-group">

                    <label for="payment_method">
                        PAYMENT METHOD
                    </label>

                    <select
                        name="payment_method"
                        id="payment_method"
                        required
                        style="
                            width:100%;
                            padding:14px;
                            background:#111;
                            color:#fff;
                            border:1px solid #333;
                            border-radius:5px;
                        "
                    >

                        <option value="">
                            Select payment method
                        </option>

                        <option value="Cash">
                            Cash
                        </option>

                        <option value="GCash">
                            GCash
                        </option>

                        <option value="Bank Transfer">
                            Bank Transfer
                        </option>

                    </select>

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