<?php $pageTitle = "'Dionisio Fitness Center | Programs'"; ?>
<?php
$pageTitle = $pageTitle ?? 'Dionisio Fitness Center';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dionisio Fitness Center - Focus. Train. Conquer. Improve.">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header" id="home">
    <!-- The original Dionisio logo/brand mark is kept here. -->
    <a href="index.php" class="brand" aria-label="Dionisio Fitness Center home">
      
        <span class="brand-text">
            <strong></strong>
             <img src="assets/navbar-brand.png" alt="Dionisio Fitness Center">
        </span>
    </a>

    <button class="menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="mainMenu">
        <span></span><span></span><span></span>
    </button>

    <nav class="main-nav" id="mainMenu">
        <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">HOME</a>
        <a class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" href="about.php">ABOUT</a>
        <a class="<?= $currentPage === 'services.php' ? 'active' : '' ?>" href="services.php">SERVICES</a>
        <a class="<?= $currentPage === 'programs.php' ? 'active' : '' ?>" href="programs.php">PROGRAMS</a>
        <a class="<?= $currentPage === 'merch.php' ? 'active' : '' ?>" href="merch.php">MERCH</a>
        <a class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>" href="gallery.php">GALLERY</a>
        <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="contact.php">CONTACT</a>
    </nav>

    <a class="header-cta" href="login.php">JOIN NOW</a>
</header>

<main>
    <section class="page-banner">
        <div class="page-banner-content reveal">
            <p class="eyebrow">PROGRAMS</p>
            <h1>CHOOSE YOUR <span>PLAN.</span></h1>
            <p>Membership options for different training goals.</p>
        </div>
    </section>

<section class="programs section">
    <div class="pricing-grid">
        <article class="price-card reveal">
            <div class="plan-name">BASIC</div><div class="price">₱999 <small>/ MONTH</small></div>
            <ul><li>Gym Access</li><li>Basic Equipment</li><li>Locker Room</li><li>Open Gym</li></ul>
            <a href="login.php" class="btn btn-outline dark">JOIN NOW</a>
        </article>
        <article class="price-card featured reveal">
            <div class="popular">MOST POPULAR</div><div class="plan-name">PREMIUM</div><div class="price">₱1,499 <small>/ MONTH</small></div>
            <ul><li>Gym Access</li><li>All Equipment</li><li>Personal Training (2x)</li><li>Nutrition Guidance</li><li>Locker Room</li></ul>
            <a href="login.php" class="btn btn-red">JOIN NOW</a>
        </article>
        <article class="price-card reveal">
            <div class="plan-name">VIP</div><div class="price">₱2,499 <small>/ MONTH</small></div>
            <ul><li>All Premium Benefits</li><li>Personal Training (4x)</li><li>Custom Meal Plan</li><li>Priority Booking</li><li>VIP Lounge Access</li></ul>
            <a href="login.php" class="btn btn-outline dark">JOIN NOW</a>
        </article>
    </div>
</section>

</main>

<footer class="footer">
    <div class="footer-top">
        <div>
            <div class="footer-brand">DIONISIO <span>FITNESS CENTER</span></div>
            <p>Focus. Train. Conquer. Improve.</p>
        </div>

        <div class="footer-links">
            <strong>QUICK LINKS</strong>
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="services.php">Services</a>
            <a href="programs.php">Programs</a>
            <a href="merch.php">Merch</a>
            <a href="gallery.php">Gallery</a>
            <a href="contact.php">Contact</a>
        </div>

        <div>
            <strong>CONTACT US</strong>
            <p>+63 955 855 1383</p>
            <p>dionisiobj@gmail.com</p>
        </div>
    </div>

    <div class="footer-bottom">
        <span>© <?= date('Y') ?> DIONISIO FITNESS CENTER. ALL RIGHTS RESERVED.</span>
        <span>BUILT FOR STRENGTH.</span>
    </div>
</footer>

<button id="topBtn" title="Back to top" aria-label="Back to top">↑</button>

<script src="js/script.js"></script>
</body>
</html>
