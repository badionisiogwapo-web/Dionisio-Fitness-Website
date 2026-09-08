<?php require_once __DIR__ . '/config/auth.php'; $pageTitle = "'Dionisio Fitness Center | Gallery'"; ?>
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
        <span class="brand-mark">D</span>
        <span class="brand-text">
            <strong>DIONISIO</strong>
            <small>FITNESS CENTER</small>
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

    <?php if (isLoggedIn()): ?>
        <a class="header-signin" href="account.php">MY ACCOUNT</a>
        <a class="header-cta" href="logout.php">LOG OUT</a>
    <?php else: ?>
        <a class="header-signin" href="login.php">SIGN IN</a>
        <a class="header-cta" href="join.php">JOIN NOW</a>
    <?php endif; ?>
</header>

<main>
    <section class="page-banner">
        <div class="page-banner-content reveal">
            <p class="eyebrow">TRAIN. SWEAT. ACHIEVE.</p>
            <h1>THE <span>GALLERY.</span></h1>
            <p>A look at training, equipment and the gym.</p>
        </div>
    </section>

<section class="gallery section">
    <div class="gallery-grid gallery-grid-page">
        <figure class="gallery-item tall reveal"><img src="assets/barbel.jpg" alt="Strength training"><figcaption>STRENGTH TRAINING</figcaption></figure>
        <figure class="gallery-item reveal"><img src="assets/nobody.webp" alt="Cardio training"><figcaption>CARDIO TRAINING</figcaption></figure>
        <figure class="gallery-item reveal"><img src="assets/woman.jpg" alt="Personal training"><figcaption>PERSONAL TRAINING</figcaption></figure>
        <figure class="gallery-item wide reveal"><img src="assets/dumbell.avif" alt="Gym facility"><figcaption>FACILITY</figcaption></figure>
        <figure class="gallery-item reveal"><img src="assets/barbel.jpg" alt="Workout"><figcaption>TRAIN HARD</figcaption></figure>
        <figure class="gallery-item reveal"><img src="assets/dumbell.avif" alt="Equipment"><figcaption>EQUIPMENT</figcaption></figure>
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
