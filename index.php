<?php $pageTitle = 'Dionisio Fitness Center | Home'; ?>
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
        <a class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>" href="bookings.php">BOOKING</a>
        <a class="<?= $currentPage === 'merch.php' ? 'active' : '' ?>" href="merch.php">MERCH</a>
        <a class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>" href="gallery.php">GALLERY</a>
        <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="contact.php">CONTACT</a>
    </nav>

    <a class="header-cta" href="login.php">JOIN NOW</a>
</header>

<main>
    <!-- HERO -->
    <section class="hero" id="home">
        <div class="hero-image"></div>
        <div class="hero-overlay"></div>

        <div class="hero-content reveal">
            <p class="eyebrow">DIONISIO FITNESS CENTER</p>
            <h1>BUILT FOR<br><span>STRENGTH.</span></h1>
            <p class="hero-copy">Push your limits. Build strength. Become your strongest self.</p>

            <div class="hero-actions">
                <a class="btn btn-red" href="#programs">JOIN NOW</a>
                <a class="btn btn-outline" href="#about">LEARN MORE</a>
            </div>
        </div>

        <div class="hero-bottom">
            <span>FOCUS.</span>
            <span>TRAIN.</span>
            <span>CONQUER.</span>
            <span>IMPROVE.</span>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="about section" id="about">
        <div class="section-grid">
            <div class="section-copy reveal">
                <p class="eyebrow">ABOUT US</p>
                <h2>BUILT FOR<br><span>YOU.</span></h2>
                <p>Dionisio Fitness Center is more than a gym. It’s a community built for those who are ready to push limits and live stronger, healthier lives.</p>
                <p>Whether you’re a beginner or an athlete, we’re here to help you achieve your goals.</p>
                <a class="text-link" href="about.php">EXPLORE MORE <b>→</b></a>
            </div>

            <div class="about-image image-frame reveal">
                <!-- PHOTO: keep your original declared image filename here -->
                <img src="assets/about-image.png" alt="Dionisio Fitness Center training">
                <div class="image-tag">DISCIPLINE<br>CONSISTENCY<br>PROGRESS</div>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="services section dark-section" id="services">
        <div class="section-heading reveal">
            <p class="eyebrow">WHAT WE OFFER</p>
            <h2>OUR <span>SERVICES</span></h2>
        </div>

        <div class="service-grid">
            <article class="service-card reveal">
                <div class="service-number">01</div>
                <div class="service-icon">✚</div>
                <h3>STRENGTH TRAINING</h3>
                <p>Build strength and muscle with our premium equipment.</p>
            </article>

            <article class="service-card reveal">
                <div class="service-number">02</div>
                <div class="service-icon">♡</div>
                <h3>CARDIO TRAINING</h3>
                <p>Improve endurance and burn calories with cardio machines.</p>
            </article>

            <article class="service-card reveal">
                <div class="service-number">03</div>
                <div class="service-icon">◉</div>
                <h3>PERSONAL TRAINING</h3>
                <p>Work one-on-one with certified trainers tailored for you.</p>
            </article>

            <article class="service-card reveal">
                <div class="service-number">04</div>
                <div class="service-icon">◆</div>
                <h3>NUTRITION GUIDANCE</h3>
                <p>Get expert advice to fuel your body and reach your goals.</p>
            </article>
        </div>

        <div class="center-action reveal">
            <a href="bookings.php" class="btn btn-red">BOOK A SESSION</a>
        </div>
    </section>

    <!-- PROGRAMS -->
    <section class="programs section" id="programs">
        <div class="section-heading centered reveal">
            <p class="eyebrow">PROGRAMS</p>
            <h2>CHOOSE YOUR <span>PLAN</span></h2>
        </div>

        <div class="pricing-grid">
            <article class="price-card reveal">
                <div class="plan-name">BASIC</div>
                <div class="price">₱999 <small>/ MONTH</small></div>
                <ul>
                    <li>Gym Access</li>
                    <li>Basic Equipment</li>
                    <li>Locker Room</li>
                    <li>Open Gym</li>
                </ul>
                <a href="contact.php" class="btn btn-outline dark">JOIN NOW</a>
            </article>

            <article class="price-card featured reveal">
                <div class="popular">MOST POPULAR</div>
                <div class="plan-name">PREMIUM</div>
                <div class="price">₱1,499 <small>/ MONTH</small></div>
                <ul>
                    <li>Gym Access</li>
                    <li>All Equipment</li>
                    <li>Personal Training (2x)</li>
                    <li>Nutrition Guidance</li>
                    <li>Locker Room</li>
                </ul>
                <a href="login.php" class="btn btn-red">JOIN NOW</a>
            </article>

            <article class="price-card reveal">
                <div class="plan-name">VIP</div>
                <div class="price">₱2,499 <small>/ MONTH</small></div>
                <ul>
                    <li>All Premium Benefits</li>
                    <li>Personal Training (4x)</li>
                    <li>Custom Meal Plan</li>
                    <li>Priority Booking</li>
                    <li>VIP Lounge Access</li>
                </ul>
                <a href="login.php" class="btn btn-outline dark">JOIN NOW</a>
            </article>
        </div>
    </section>

    <!-- MERCH -->
    <section class="merch section dark-section" id="merch">
        <div class="section-heading reveal">
            <p class="eyebrow">MERCH</p>
            <h2>REP YOUR <span>GRIND.</span></h2>
        </div>

        <div class="merch-grid">
            <article class="product-card reveal">
                <div class="product-image">
                    <img src="assets/124321312412-removebg-preview.png" alt="Dionisio Performance Tee">
                </div>
                <h3>DIONISIO PERFORMANCE TEE</h3>
                <strong>₱599</strong>
                <button class="product-btn" data-product="Dionisio Performance Tee">VIEW PRODUCT</button>
            </article>

            <article class="product-card reveal">
                <div class="product-image">
                    <img src="assets/5B590DAD-32B1-481D-A8C5-B76ADF1CCFFE_copy-removebg-preview.png" alt="Dionisio Shaker Bottle">
                </div>
                <h3>DIONISIO SHAKER BOTTLE</h3>
                <strong>₱399</strong>
                <button class="product-btn" data-product="Dionisio Shaker Bottle">VIEW PRODUCT</button>
            </article>

            <article class="product-card reveal">
                <div class="product-image">
                    <img src="assets/EWRWR4WE-removebg-preview.png" alt="Dionisio Hoodie">
                </div>
                <h3>DIONISIO HOODIE</h3>
                <strong>₱1,099</strong>
                <button class="product-btn" data-product="Dionisio Hoodie">VIEW PRODUCT</button>
            </article>

            <article class="product-card reveal">
                <div class="product-image">
                    <img src="assets/cap.png" alt="Dionisio Cap">
                </div>
                <h3>DIONISIO CAP</h3>
                <strong>₱1,099</strong>
                <button class="product-btn" data-product="Dionisio Cap">VIEW PRODUCT</button>
            </article>
        </div>

        <div class="center-action">
            <a href="merch.php" class="btn btn-red">VIEW ALL MERCH</a>
        </div>
    </section>

    <!-- GALLERY -->
    <section class="gallery section" id="gallery">
        <div class="section-heading centered reveal">
            <p class="eyebrow">TRAIN. SWEAT. ACHIEVE.</p>
            <h2>THE <span>GALLERY</span></h2>
        </div>

        <div class="gallery-grid">
            <figure class="gallery-item tall reveal">
                <img src="assets/barbel.jpg" alt="Strength training">
                <figcaption>STRENGTH TRAINING</figcaption>
            </figure>

            <figure class="gallery-item reveal">
                <img src="assets/nobody.webp" alt="Cardio training">
                <figcaption>CARDIO TRAINING</figcaption>
            </figure>

            <figure class="gallery-item reveal">
                <img src="assets/woman.jpg" alt="Personal training">
                <figcaption>PERSONAL TRAINING</figcaption>
            </figure>

            <figure class="gallery-item wide reveal">
                <img src="assets/dumbell.avif" alt="Gym facility">
                <figcaption>FACILITY</figcaption>
            </figure>
        </div>

        <div class="center-action">
            <a href="gallery.php" class="btn btn-outline dark">VIEW FULL GALLERY</a>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials section dark-section">
        <div class="section-heading centered reveal">
            <p class="eyebrow">WHAT OUR MEMBERS SAY</p>
            <h2>REAL PEOPLE.<br><span>REAL PROGRESS.</span></h2>
        </div>

        <div class="testimonial-slider reveal">
            <article class="testimonial active">
                <div class="quote">“</div>
                <p>Clean facility, great atmosphere, and very effective programs. Highly recommended!</p>
                <strong>— KEVIN L.</strong>
            </article>

            <article class="testimonial">
                <div class="quote">“</div>
                <p>The best gym I’ve ever been to! The equipment is top-notch and the staff are super friendly.</p>
                <strong>— JOHN D.</strong>
            </article>

            <article class="testimonial">
                <div class="quote">“</div>
                <p>The trainers really push you to become better. I’ve seen huge progress since I joined.</p>
                <strong>— MARIA C.</strong>
            </article>

            <div class="slider-controls">
                <button class="prev" aria-label="Previous testimonial">←</button>
                <span class="slider-count">01 / 03</span>
                <button class="next" aria-label="Next testimonial">→</button>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-overlay"></div>
        <div class="cta-content reveal">
            <p class="eyebrow">WORK FOR IT.</p>
            <h2>DON'T WISH<br>FOR IT.</h2>
            <a href="contact.php" class="btn btn-red">START YOUR JOURNEY</a>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="contact section" id="contact">
        <div class="contact-grid">
            <div class="section-copy reveal">
                <p class="eyebrow">DIONISIO FITNESS CENTER</p>
                <h2>LET'S GET<br><span>STRONGER.</span></h2>
                <p>Ready to start? Contact us or visit the gym and take the first step toward becoming your strongest self.</p>
            </div>

            <div class="contact-info reveal">
                <div class="contact-line">
                    <span>PHONE</span>
                    <a href="tel:+639558551383">+63 955 855 1383</a>
                </div>
                <div class="contact-line">
                    <span>EMAIL</span>
                    <a href="mailto:dionisiobj@gmail.com">dionisiobj@gmail.com</a>
                </div>
                <div class="contact-line">
                    <span>ADDRESS</span>
                    <p>Dr. Miciano Rd, Taclobo,<br>Dumaguete City, Negros Oriental</p>
                </div>
                <div class="contact-line">
                    <span>HOURS</span>
                    <p>MON - FRI &nbsp; 5:00 AM - 10:00 PM<br>SATURDAY &nbsp; 6:00 AM - 11:00 PM<br>SUNDAY &nbsp; 4:00 AM - 12:00 AM</p>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="modal" id="productModal" aria-hidden="true">
    <div class="modal-box">
        <button class="modal-close" aria-label="Close">×</button>
        <p class="eyebrow">DIONISIO MERCH</p>
        <h2 id="modalProduct">PRODUCT</h2>
        <p>For orders and availability, contact Dionisio Fitness Center directly.</p>
        <a class="btn btn-red" href="contact.php">CONTACT US</a>
    </div>
</div>

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
            <a href="bookings.php">Booking</a>
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
