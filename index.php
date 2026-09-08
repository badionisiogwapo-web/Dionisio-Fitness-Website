<?php

require_once __DIR__ . '/config/auth.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dionisio Fitness Center</title>


    <!-- Bootstrap 5 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Montserrat -->

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- Website CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="site-header">

    <div class="container">

        <div class="header-inner">


            <!-- =================================================
                 BRAND
                 ================================================= -->

            <a href="index.php" class="brand">

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



            <!-- =================================================
                 NAVIGATION
                 ================================================= -->

            <nav class="main-nav">

                <a href="index.php">
                    HOME
                </a>

                <a href="about.php">
                    ABOUT
                </a>

                <a href="services.php">
                    SERVICES
                </a>

                <a href="programs.php">
                    PROGRAMS
                </a>

                <a href="merch.php">
                    MERCH
                </a>

                <a href="gallery.php">
                    GALLERY
                </a>

                <a href="contact.php">
                    CONTACT
                </a>

            </nav>



            <!-- =================================================
                 ACCOUNT BUTTONS
                 ================================================= -->

            <div class="header-actions">

                <?php if (isLoggedIn()): ?>

                    <!-- =========================================
                         LOGGED IN
                         ========================================= -->

                    <a
                        class="header-signin"
                        href="account.php"
                    >
                        MY ACCOUNT
                    </a>


                    <a
                        class="header-signin"
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

                    <!-- =========================================
                         LOGGED OUT
                         ========================================= -->

                    <a
                        class="header-signin"
                        href="login.php"
                    >
                        SIGN IN
                    </a>


                    <a
                        class="header-cta"
                        href="join.php"
                    >
                        JOIN NOW
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</header>



<!-- =========================================================
     HERO SECTION
     ========================================================= -->

<section class="hero">


    <div class="hero-image">

        <img
            src="assets/hero.jpg"
            alt="Dionisio Fitness Center"
        >

    </div>


    <div class="hero-overlay"></div>


    <div class="container">

        <div class="hero-content">


            <p class="hero-label">
                DIONISIO FITNESS CENTER
            </p>


            <h1>
                BUILT FOR<br>
                STRENGTH.
            </h1>


            <p>
                Train harder. Get stronger.
                Become the best version of yourself.
            </p>



            <!-- =================================================
                 HERO BUTTONS
                 ================================================= -->

            <div class="hero-buttons">


                <!-- LEARN MORE ALWAYS SHOWS -->

                <a
                    href="about.php"
                    class="btn btn-outline-light"
                >
                    LEARN MORE
                </a>



                <?php if (isLoggedIn()): ?>

                    <!-- =========================================
                         LOGGED IN
                         JOIN NOW IS NOT SHOWN
                         ========================================= -->

                    <a
                        href="account.php"
                        class="btn btn-primary"
                    >
                        MY ACCOUNT
                    </a>


                <?php else: ?>

                    <!-- =========================================
                         LOGGED OUT
                         ========================================= -->

                    <a
                        href="join.php"
                        class="btn btn-primary"
                    >
                        JOIN NOW
                    </a>

                <?php endif; ?>


            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     ABOUT SECTION
     ========================================================= -->

<section class="section">

    <div class="container">

        <div class="row align-items-center g-5">


            <div class="col-lg-6">

                <div class="about-image">

                    <img
                        src="assets/about.jpg"
                        alt="Dionisio Fitness Center"
                    >

                </div>

            </div>


            <div class="col-lg-6">

                <p class="section-label">
                    ABOUT US
                </p>


                <h2>
                    BUILT FOR YOU.
                </h2>


                <p>
                    Dionisio Fitness Center is a place
                    where dedication, strength, and
                    consistency come together.
                </p>


                <p>
                    Whether you're just starting your
                    fitness journey or you're already
                    experienced, our goal is to help you
                    become stronger and more confident.
                </p>


                <a
                    href="about.php"
                    class="btn btn-primary"
                >
                    LEARN MORE
                </a>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     SERVICES SECTION
     ========================================================= -->

<section class="section services-section">

    <div class="container">


        <div class="section-heading">

            <p class="section-label">
                WHAT WE OFFER
            </p>

            <h2>
                OUR SERVICES
            </h2>

        </div>



        <div class="service-grid">


            <div class="service-card">

                <h3>
                    STRENGTH TRAINING
                </h3>

                <p>
                    Build strength and improve your
                    overall fitness with quality equipment.
                </p>

            </div>



            <div class="service-card">

                <h3>
                    CARDIO TRAINING
                </h3>

                <p>
                    Improve endurance and keep your
                    body active with effective cardio workouts.
                </p>

            </div>



            <div class="service-card">

                <h3>
                    PERSONAL TRAINING
                </h3>

                <p>
                    Get guidance and support from trainers
                    who can help you reach your goals.
                </p>

            </div>



            <div class="service-card">

                <h3>
                    NUTRITION GUIDANCE
                </h3>

                <p>
                    Learn better habits that support your
                    training and fitness goals.
                </p>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     PROGRAMS SECTION
     ========================================================= -->

<section class="section">

    <div class="container">


        <div class="section-heading">

            <p class="section-label">
                MEMBERSHIP
            </p>

            <h2>
                OUR PROGRAMS
            </h2>

        </div>



        <div class="row g-4">


            <!-- BASIC -->

            <div class="col-md-4">

                <div class="price-card">

                    <h3>
                        BASIC
                    </h3>


                    <div class="price">
                        ₱999
                        <span>/ MONTH</span>
                    </div>


                    <p>
                        Perfect for getting started.
                    </p>


                    <?php if (isLoggedIn()): ?>

                        <a
                            href="account.php"
                            class="btn btn-primary"
                        >
                            VIEW PLAN
                        </a>

                    <?php else: ?>

                        <a
                            href="join.php"
                            class="btn btn-primary"
                        >
                            JOIN NOW
                        </a>

                    <?php endif; ?>

                </div>

            </div>



            <!-- PREMIUM -->

            <div class="col-md-4">

                <div class="price-card featured">

                    <h3>
                        PREMIUM
                    </h3>


                    <div class="price">
                        ₱1,499
                        <span>/ MONTH</span>
                    </div>


                    <p>
                        More benefits for serious training.
                    </p>


                    <?php if (isLoggedIn()): ?>

                        <a
                            href="account.php"
                            class="btn btn-primary"
                        >
                            VIEW PLAN
                        </a>

                    <?php else: ?>

                        <a
                            href="join.php"
                            class="btn btn-primary"
                        >
                            JOIN NOW
                        </a>

                    <?php endif; ?>

                </div>

            </div>



            <!-- VIP -->

            <div class="col-md-4">

                <div class="price-card">

                    <h3>
                        VIP
                    </h3>


                    <div class="price">
                        ₱2,499
                        <span>/ MONTH</span>
                    </div>


                    <p>
                        The complete Dionisio experience.
                    </p>


                    <?php if (isLoggedIn()): ?>

                        <a
                            href="account.php"
                            class="btn btn-primary"
                        >
                            VIEW PLAN
                        </a>

                    <?php else: ?>

                        <a
                            href="join.php"
                            class="btn btn-primary"
                        >
                            JOIN NOW
                        </a>

                    <?php endif; ?>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     CTA SECTION
     ========================================================= -->

<section class="cta-section">

    <div class="container">

        <div class="cta-content">


            <p class="section-label">
                YOUR NEXT LEVEL STARTS HERE
            </p>


            <h2>
                DON'T WISH FOR IT.
            </h2>


            <p>
                WORK FOR IT.
            </p>



            <?php if (isLoggedIn()): ?>

                <a
                    href="account.php"
                    class="btn btn-primary"
                >
                    GO TO MY ACCOUNT
                </a>

            <?php else: ?>

                <a
                    href="join.php"
                    class="btn btn-primary"
                >
                    JOIN NOW
                </a>

            <?php endif; ?>


        </div>

    </div>

</section>



<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="footer">

    <div class="container">

        <div class="row g-4">


            <div class="col-md-6">

                <div class="brand">

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

                </div>


                <p>
                    Built for strength.
                    Built for you.
                </p>

            </div>



            <div class="col-md-3">

                <h4>
                    QUICK LINKS
                </h4>


                <a href="index.php">
                    HOME
                </a>


                <a href="about.php">
                    ABOUT
                </a>


                <a href="services.php">
                    SERVICES
                </a>


                <a href="programs.php">
                    PROGRAMS
                </a>

            </div>



            <div class="col-md-3">

                <h4>
                    CONTACT
                </h4>


                <p>
                    +63 955 855 1383
                </p>


                <p>
                    dionisiobj@gmail.com
                </p>


                <p>
                    Dr. Miciano Rd,<br>
                    Taclobo, Dumaguete City
                </p>

            </div>


        </div>



        <div class="footer-bottom">

            <p>
                © 2026 Dionisio Fitness Center.
                All Rights Reserved.
            </p>

        </div>

    </div>

</footer>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script src="js/script.js"></script>

</body>
</html>