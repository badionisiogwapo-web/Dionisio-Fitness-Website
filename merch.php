<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';

$pageTitle = 'Dionisio Fitness Center | Merch';
$currentPage = 'merch.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isLoggedIn()) {
        header('Location: register.php');
        exit;
    }

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId <= 0 || $quantity < 1) {
            $error = 'Please select a valid product and quantity.';
        } else {

            $stmt = $pdo->prepare(
                'SELECT product_id, product_name, stock
                 FROM products
                 WHERE product_id = ?
                 LIMIT 1'
            );

            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {

                $error = 'Product not found.';

            } elseif ((int)$product['stock'] <= 0) {

                $error = 'This product is currently out of stock.';

            } else {

                $cartStmt = $pdo->prepare(
                    'SELECT quantity
                     FROM cart_items
                     WHERE user_id = ? AND product_id = ?
                     LIMIT 1'
                );

                $cartStmt->execute([
                    currentUserId(),
                    $productId
                ]);

                $existing = $cartStmt->fetch();

                $existingQuantity = $existing
                    ? (int)$existing['quantity']
                    : 0;

                $newQuantity = $existingQuantity + $quantity;

                if ($newQuantity > (int)$product['stock']) {

                    $error = 'You cannot add more than the available stock.';

                } else {

                    $insert = $pdo->prepare(
                        'INSERT INTO cart_items
                        (user_id, product_id, quantity)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        quantity = VALUES(quantity),
                        updated_at = CURRENT_TIMESTAMP'
                    );

                    $insert->execute([
                        currentUserId(),
                        $productId,
                        $newQuantity
                    ]);

                    $message = $product['product_name'] . ' added to your cart.';
                }
            }
        }
    }
}

$products = $pdo
    ->query(
        'SELECT product_id, product_name, description, price, image, stock
         FROM products
         ORDER BY product_id'
    )
    ->fetchAll();

$loggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= e($pageTitle) ?></title>

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

<body>

<header class="site-header">

    <a href="index.php" class="brand">

        <span class="brand-mark">D</span>

        <span class="brand-text">

            <strong>DIONISIO</strong>

            <small>FITNESS CENTER</small>

        </span>

    </a>


    <nav class="main-nav">

        <a href="index.php">HOME</a>

        <a href="about.php">ABOUT</a>

        <a href="services.php">SERVICES</a>

        <a href="programs.php">PROGRAMS</a>

        <a
            href="merch.php"
            class="active"
        >
            MERCH
        </a>

        <a href="gallery.php">GALLERY</a>

        <a href="contact.php">CONTACT</a>

    </nav>


    <?php if ($loggedIn): ?>

        <a
            class="header-signin"
            href="account.php"
        >
            MY ACCOUNT
        </a>

        <a
            class="header-cta"
            href="cart.php"
        >
            CART
        </a>

        <a
            class="header-cta"
            href="logout.php"
            data-confirm="Are you sure you want to log out?"
        >
            LOG OUT
        </a>

    <?php else: ?>

        <a
            class="header-signin"
            href="login.php"
        >
            SIGN IN
        </a>

        <a
            class="header-cta"
            href="register.php"
        >
            JOIN NOW
        </a>

    <?php endif; ?>


    <button
        class="menu-toggle"
        type="button"
        aria-expanded="false"
        aria-label="Open navigation"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

</header>


<main>

    <section class="page-banner">

        <div class="page-banner-content">

            <p class="eyebrow">DIONISIO STORE</p>

            <h1>
                GEAR<br>
                <span>UP.</span>
            </h1>

            <p>
                Official Dionisio Fitness Center merchandise
                made for training days and everyday wear.
            </p>

        </div>

    </section>


    <?php if ($message): ?>

        <div class="shop-message auth-success">
            <?= e($message) ?>

            <a href="cart.php">
                VIEW CART
            </a>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="shop-message auth-alert">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <section class="section">

        <div class="section-heading centered">

            <p class="eyebrow">OFFICIAL MERCH</p>

            <h2>
                TRAIN.<br>
                <span>REPRESENT.</span>
            </h2>

        </div>


        <div class="merch-grid">

            <?php foreach ($products as $product): ?>

                <article class="product-card">

                    <div
                        class="product-image"
                        title="Click image to enlarge"
                    >

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="assets/<?= e($product['image']) ?>"
                                alt="<?= e($product['product_name']) ?>"
                            >

                        <?php else: ?>

                            <div
                                style="
                                    height:100%;
                                    display:grid;
                                    place-items:center;
                                    color:#777;
                                    font-size:12px;
                                "
                            >
                                NO IMAGE
                            </div>

                        <?php endif; ?>

                    </div>


                    <h3>
                        <?= e($product['product_name']) ?>
                    </h3>


                    <strong>
                        ₱<?= number_format((float)$product['price'], 2) ?>
                    </strong>


                    <?php if (!empty($product['description'])): ?>

                        <p
                            style="
                                margin:10px 20px 0;
                                font-size:11px;
                                color:#888;
                            "
                        >
                            <?= e($product['description']) ?>
                        </p>

                    <?php endif; ?>


                    <?php if ((int)$product['stock'] > 0): ?>

                        <p class="stock-left">
                            <?= (int)$product['stock'] ?> AVAILABLE
                        </p>

                    <?php else: ?>

                        <p class="stock-left out-stock">
                            OUT OF STOCK
                        </p>

                    <?php endif; ?>


                    <?php if ($loggedIn): ?>

                        <?php if ((int)$product['stock'] > 0): ?>

                            <form
                                method="POST"
                                class="add-cart-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrfToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int)$product['product_id'] ?>"
                                >

                                <input
                                    class="cart-quantity"
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="<?= (int)$product['stock'] ?>"
                                    aria-label="Quantity"
                                >

                                <button
                                    type="submit"
                                    class="product-btn"
                                >
                                    ADD TO CART
                                </button>

                            </form>

                        <?php else: ?>

                            <button
                                type="button"
                                class="product-btn"
                                disabled
                            >
                                OUT OF STOCK
                            </button>

                        <?php endif; ?>

                    <?php else: ?>

                        <a
                            class="product-btn product-link"
                            href="register.php"
                        >
                            REGISTER TO BUY
                        </a>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

</main>


<footer class="footer">

    <div class="footer-top">

        <div>

            <div class="footer-brand">
                DIONISIO <span>FITNESS CENTER</span>
            </div>

            <p>
                Train hard. Stay disciplined.
                Become stronger.
            </p>

        </div>


        <div class="footer-links">

            <strong>QUICK LINKS</strong>

            <a href="index.php">HOME</a>
            <a href="about.php">ABOUT</a>
            <a href="services.php">SERVICES</a>
            <a href="programs.php">PROGRAMS</a>

        </div>


        <div class="footer-links">

            <strong>MEMBERS</strong>

            <?php if ($loggedIn): ?>

                <a href="account.php">MY ACCOUNT</a>
                <a href="cart.php">CART</a>
                <a
                    href="logout.php"
                    data-confirm="Are you sure you want to log out?"
                >
                    LOG OUT
                </a>

            <?php else: ?>

                <a href="login.php">SIGN IN</a>
                <a href="register.php">REGISTER</a>

            <?php endif; ?>

        </div>

    </div>


    <div class="footer-bottom">

        <span>
            © <?= date('Y') ?> DIONISIO FITNESS CENTER
        </span>

        <span>
            ALL RIGHTS RESERVED
        </span>

    </div>

</footer>


<script src="js/script.js"></script>

</body>
</html>