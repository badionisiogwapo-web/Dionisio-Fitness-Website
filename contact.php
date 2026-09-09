<?php $pageTitle = "'Dionisio Fitness Center | Contact'"; ?>
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
            <p class="eyebrow">GET STARTED</p>
            <h1>LET'S GET <span>STRONGER.</span></h1>
            <p>Questions about memberships, programs or merch? Get in touch.</p>
        </div>
    </section>

<section class="contact section dark-section">
    <div class="contact-grid">
        <div class="section-copy reveal">
            <p class="eyebrow">DIONISIO FITNESS CENTER</p>
            <h2>LET'S GET<br><span>STRONGER.</span></h2>
            <p>Ready to start? Contact us or visit the gym and take the first step toward becoming your strongest self.</p>
        </div>

        <div class="contact-info reveal">
            <div class="contact-line"><span>PHONE</span><a href="tel:+639558551383">+63 955 855 1383</a></div>
            <div class="contact-line"><span>EMAIL</span><a href="mailto:dionisiobj@gmail.com">dionisiobj@gmail.com</a></div>
            <div class="contact-line"><span>ADDRESS</span><p>Dr. Miciano Rd, Taclobo,<br>Dumaguete City, Negros Oriental</p></div>
            <div class="contact-line"><span>HOURS</span><p>MON - FRI &nbsp; 5:00 AM - 10:00 PM<br>SATURDAY &nbsp; 6:00 AM - 11:00 PM<br>SUNDAY &nbsp; 4:00 AM - 12:00 AM</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="contact-form-wrap reveal">
        <p class="eyebrow">SEND A MESSAGE</p>
        <h2>CONTACT <span>US.</span></h2>
        <form class="contact-form" action="#" method="post">
            <input type="text" name="name" placeholder="YOUR NAME" required>
            <input type="email" name="email" placeholder="YOUR EMAIL" required>
            <textarea name="message" rows="6" placeholder="YOUR MESSAGE" required></textarea>
            <button type="submit" class="btn btn-red">SEND MESSAGE</button>
        </form>
        <p class="form-note">The form is front-end only for now.</p>
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
