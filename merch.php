<?php $pageTitle = "'Dionisio Fitness Center | Merch'"; ?>
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
            <p class="eyebrow">MERCH</p>
            <h1>REP YOUR <span>GRIND.</span></h1>
            <p>Dionisio gear for training days and every day.</p>
        </div>
    </section>

<section class="merch section dark-section">
    <div class="merch-grid">
        <article class="product-card reveal"><div class="product-image"><img src="assets/124321312412-removebg-preview.png" alt="Dionisio Performance Tee"></div><h3>DIONISIO PERFORMANCE TEE</h3><strong>₱599</strong><button class="product-btn" data-product="Dionisio Performance Tee">VIEW PRODUCT</button></article>
        <article class="product-card reveal"><div class="product-image"><img src="assets/5B590DAD-32B1-481D-A8C5-B76ADF1CCFFE_copy-removebg-preview.png" alt="Dionisio Shaker Bottle"></div><h3>DIONISIO SHAKER BOTTLE</h3><strong>₱399</strong><button class="product-btn" data-product="Dionisio Shaker Bottle">VIEW PRODUCT</button></article>
        <article class="product-card reveal"><div class="product-image"><img src="assets/EWRWR4WE-removebg-preview.png" alt="Dionisio Hoodie"></div><h3>DIONISIO HOODIE</h3><strong>₱1,099</strong><button class="product-btn" data-product="Dionisio Hoodie">VIEW PRODUCT</button></article>
        <article class="product-card reveal"><div class="product-image"><img src="assets/cap.png" alt="Dionisio Cap"></div><h3>DIONISIO CAP</h3><strong>₱1,099</strong><button class="product-btn" data-product="Dionisio Cap">VIEW PRODUCT</button></article>
    </div>
</section>

<div class="modal" id="productModal" aria-hidden="true">
    <div class="modal-box">
        <button class="modal-close" aria-label="Close">×</button>
        <p class="eyebrow">DIONISIO MERCH</p>
        <h2 id="modalProduct">PRODUCT</h2>
        <p>For orders and availability, contact Dionisio Fitness Center directly.</p>
        <a class="btn btn-red" href="contact.php">CONTACT US</a>
    </div>
</div>

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
