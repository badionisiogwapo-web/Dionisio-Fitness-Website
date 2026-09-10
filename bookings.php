<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('bookings.php');

$pageTitle = 'Dionisio Fitness Center | Booking';
$currentPage = basename($_SERVER['PHP_SELF']);

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$bookingTypes = [
    'Personal Training',
    'Gym Session',
    'Group Class',
    'Boxing Session',
    'Strength Training',
    'Cardio Session'
];

$success = '';
$error = '';

$selectedType = trim((string)($_POST['booking_type'] ?? ''));
$selectedDate = trim((string)($_POST['booking_date'] ?? ''));
$selectedTime = trim((string)($_POST['booking_time'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? null;

    if (!verifyCsrf(is_string($token) ? $token : null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!in_array($selectedType, $bookingTypes, true)) {
        $error = 'Please select a valid booking type.';
    } elseif ($selectedDate === '') {
        $error = 'Please select a booking date.';
    } elseif ($selectedTime === '') {
        $error = 'Please select a booking time.';
    } else {

        $today = date('Y-m-d');

        if ($selectedDate < $today) {
            $error = 'Please choose today or a future date.';
        } elseif (strlen($notes) > 255) {
            $error = 'Notes must be 255 characters or fewer.';
        } else {

            try {

                /*
                 * Prevent the same customer from creating an exact duplicate
                 * pending/confirmed booking for the same type/date/time.
                 */
                $duplicate = $pdo->prepare("
                    SELECT booking_id
                    FROM bookings
                    WHERE user_id = ?
                      AND booking_type = ?
                      AND booking_date = ?
                      AND booking_time = ?
                      AND status IN ('Pending', 'Confirmed')
                    LIMIT 1
                ");

                $duplicate->execute([
                    currentUserId(),
                    $selectedType,
                    $selectedDate,
                    $selectedTime
                ]);

                if ($duplicate->fetch()) {

                    $error =
                        'You already have this booking scheduled.';

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO bookings
                        (
                            user_id,
                            booking_type,
                            booking_date,
                            booking_time,
                            status,
                            notes
                        )
                        VALUES (?, ?, ?, ?, 'Pending', ?)
                    ");

                    $stmt->execute([
                        currentUserId(),
                        $selectedType,
                        $selectedDate,
                        $selectedTime,
                        $notes !== '' ? $notes : null
                    ]);

                    $success =
                        'Booking submitted successfully. Your request is now pending admin confirmation.';

                    $selectedType = '';
                    $selectedDate = '';
                    $selectedTime = '';
                    $notes = '';
                }

            } catch (PDOException $exception) {

                error_log($exception->getMessage());

                $error =
                    'Your booking could not be submitted. Please try again.';
            }
        }
    }
}

$myBookings = [];

try {

    $stmt = $pdo->prepare("
        SELECT
            booking_id,
            booking_type,
            booking_date,
            booking_time,
            status,
            notes,
            created_at
        FROM bookings
        WHERE user_id = ?
        ORDER BY booking_date DESC, booking_time DESC
        LIMIT 6
    ");

    $stmt->execute([
        currentUserId()
    ]);

    $myBookings = $stmt->fetchAll();

} catch (PDOException $exception) {

    error_log($exception->getMessage());
}

function bookingStatusClass(string $status): string
{
    return match (strtolower(trim($status))) {
        'confirmed' => 'confirmed',
        'completed' => 'completed',
        'cancelled', 'canceled' => 'cancelled',
        default => 'pending',
    };
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

    <meta
        name="description"
        content="Book a gym session at Dionisio Fitness Center."
    >

    <title><?= h($pageTitle) ?></title>

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

    <style>

        .booking-page {
            background: #080808;
            color: #f5f5f5;
            min-height: 100vh;
        }

        .booking-hero {
            position: relative;
            min-height: 390px;
            display: flex;
            align-items: flex-end;
            padding: 70px 7vw 55px;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(215, 25, 32, 0.22),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #171717,
                    #090909 65%
                );
            border-bottom: 1px solid #202020;
        }

        .booking-hero::after {
            content: "BOOK";
            position: absolute;
            right: 4vw;
            top: 20px;
            color: rgba(255,255,255,0.025);
            font-size: clamp(90px, 18vw, 250px);
            font-weight: 900;
            line-height: 1;
            pointer-events: none;
        }

        .booking-hero-copy {
            position: relative;
            z-index: 2;
            max-width: 760px;
        }

        .booking-hero-copy .eyebrow {
            color: #d71920;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 2.5px;
        }

        .booking-hero-copy h1 {
            margin: 10px 0 14px;
            font-size: clamp(44px, 7vw, 88px);
            line-height: .92;
            letter-spacing: -4px;
            font-weight: 900;
        }

        .booking-hero-copy h1 span {
            color: #d71920;
        }

        .booking-hero-copy p {
            max-width: 650px;
            color: #8c8c8c;
            line-height: 1.75;
            font-size: 13px;
        }

        .booking-section {
            padding: 70px 7vw 90px;
        }

        .booking-layout {
            width: min(1250px, 100%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr);
            gap: 26px;
            align-items: start;
        }

        .booking-panel,
        .history-panel {
            background: #0f0f0f;
            border: 1px solid #242424;
        }

        .panel-heading {
            padding: 24px 26px;
            border-bottom: 1px solid #242424;
        }

        .panel-heading span {
            display: block;
            margin-bottom: 8px;
            color: #d71920;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .panel-heading h2 {
            margin: 0;
            font-size: 25px;
            font-weight: 900;
        }

        .booking-form {
            padding: 26px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #727272;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1.2px;
        }

        .form-control {
            width: 100%;
            min-height: 50px;
            padding: 0 14px;
            border: 1px solid #2a2a2a;
            background: #111111;
            color: #eeeeee;
            outline: none;
            font-family: inherit;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            padding-top: 14px;
        }

        .form-control:focus {
            border-color: #d71920;
        }

        .booking-submit {
            width: 100%;
            min-height: 52px;
            margin-top: 4px;
            border: 0;
            background: #d71920;
            color: white;
            cursor: pointer;
            font-weight: 900;
            font-size: 10px;
            letter-spacing: 1.5px;
        }

        .booking-submit:hover {
            background: #ef222a;
        }

        .booking-message {
            margin: 0 26px 20px;
            padding: 15px;
            border: 1px solid;
            font-size: 11px;
            line-height: 1.6;
        }

        .booking-message.success {
            color: #71d495;
            border-color: rgba(95,208,138,.28);
            background: rgba(95,208,138,.06);
        }

        .booking-message.error {
            color: #ed7c82;
            border-color: rgba(215,25,32,.3);
            background: rgba(215,25,32,.08);
        }

        .booking-note {
            margin-top: 16px;
            color: #626262;
            font-size: 10px;
            line-height: 1.7;
        }

        .history-list {
            padding: 8px 0;
        }

        .history-item {
            padding: 18px 22px;
            border-bottom: 1px solid #1f1f1f;
        }

        .history-item:last-child {
            border-bottom: 0;
        }

        .history-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .history-top strong {
            color: #eeeeee;
            font-size: 11px;
        }

        .booking-status {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: 0 9px;
            border: 1px solid;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: .9px;
        }

        .booking-status.pending {
            color: #e7b94f;
            border-color: rgba(231,185,79,.28);
        }

        .booking-status.confirmed {
            color: #5fd08a;
            border-color: rgba(95,208,138,.28);
        }

        .booking-status.completed {
            color: #69a7ff;
            border-color: rgba(105,167,255,.28);
        }

        .booking-status.cancelled {
            color: #ef686e;
            border-color: rgba(239,104,110,.28);
        }

        .history-meta {
            color: #666666;
            font-size: 9px;
            line-height: 1.8;
        }

        .history-empty {
            padding: 35px 22px;
            color: #555;
            font-size: 10px;
            text-align: center;
        }

        .booking-help {
            margin-top: 18px;
            padding: 20px 22px;
            border: 1px solid #242424;
            background: #0c0c0c;
        }

        .booking-help strong {
            display: block;
            margin-bottom: 9px;
            color: #fff;
            font-size: 11px;
        }

        .booking-help p {
            margin: 0;
            color: #686868;
            font-size: 10px;
            line-height: 1.75;
        }

        @media (max-width: 900px) {

            .booking-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {

            .booking-hero {
                min-height: 310px;
                padding: 55px 20px 40px;
            }

            .booking-hero-copy h1 {
                letter-spacing: -2px;
            }

            .booking-section {
                padding: 45px 16px 65px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .booking-form {
                padding: 20px;
            }

            .booking-message {
                margin-left: 20px;
                margin-right: 20px;
            }
        }

    </style>

</head>

<body>

<header
    class="site-header"
    id="home"
>

    <a
        href="index.php"
        class="brand"
        aria-label="Dionisio Fitness Center home"
    >

        <span class="brand-mark"></span>

        <span class="brand-text">

            <strong>
                DIONISIO
            </strong>

            <small>
                FITNESS CENTER
            </small>

        </span>

    </a>

    <button
        class="menu-toggle"
        aria-label="Open navigation"
        aria-expanded="false"
        aria-controls="mainMenu"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav
        class="main-nav"
        id="mainMenu"
    >

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

        <a
            class="active"
            href="bookings.php"
        >
            BOOKING
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

    <a
        class="header-cta"
        href="account.php"
    >
        MY ACCOUNT
    </a>

</header>

<main class="booking-page">

    <section class="booking-hero">

        <div class="booking-hero-copy">

            <p class="eyebrow">
                DIONISIO FITNESS CENTER
            </p>

            <h1>
                BOOK YOUR
                <br>
                <span>SESSION.</span>
            </h1>

            <p>
                Choose your training session, preferred date
                and time. Your booking will be submitted as
                pending until it is confirmed by the gym admin.
            </p>

        </div>

    </section>

    <section class="booking-section">

        <div class="booking-layout">

            <section class="booking-panel">

                <div class="panel-heading">

                    <span>
                        NEW BOOKING
                    </span>

                    <h2>
                        RESERVE YOUR SESSION
                    </h2>

                </div>

                <?php if ($success !== ''): ?>

                    <div class="booking-message success">
                        <?= h($success) ?>
                    </div>

                <?php endif; ?>

                <?php if ($error !== ''): ?>

                    <div class="booking-message error">
                        <?= h($error) ?>
                    </div>

                <?php endif; ?>

                <form
                    method="post"
                    action="bookings.php"
                    class="booking-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h(csrfToken()) ?>"
                    >

                    <div class="form-grid">

                        <div class="form-group full">

                            <label for="booking_type">
                                BOOKING TYPE
                            </label>

                            <select
                                id="booking_type"
                                name="booking_type"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    Select a session
                                </option>

                                <?php foreach ($bookingTypes as $type): ?>

                                    <option
                                        value="<?= h($type) ?>"
                                        <?= $selectedType === $type
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= h($type) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="form-group">

                            <label for="booking_date">
                                DATE
                            </label>

                            <input
                                id="booking_date"
                                type="date"
                                name="booking_date"
                                class="form-control"
                                min="<?= h(date('Y-m-d')) ?>"
                                value="<?= h($selectedDate) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="booking_time">
                                TIME
                            </label>

                            <input
                                id="booking_time"
                                type="time"
                                name="booking_time"
                                class="form-control"
                                value="<?= h($selectedTime) ?>"
                                required
                            >

                        </div>

                        <div class="form-group full">

                            <label for="notes">
                                NOTES
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                maxlength="255"
                                placeholder="Optional message for the gym admin..."
                            ><?= h($notes) ?></textarea>

                        </div>

                        <div class="form-group full">

                            <button
                                type="submit"
                                class="booking-submit"
                            >
                                SUBMIT BOOKING
                            </button>

                            <p class="booking-note">
                                Your booking will first appear as
                                <strong>Pending</strong>.
                                The administrator can confirm or cancel
                                the request from the admin booking panel.
                            </p>

                        </div>

                    </div>

                </form>

            </section>

            <aside>

                <section class="history-panel">

                    <div class="panel-heading">

                        <span>
                            MY BOOKINGS
                        </span>

                        <h2>
                            RECENT REQUESTS
                        </h2>

                    </div>

                    <?php if (!empty($myBookings)): ?>

                        <div class="history-list">

                            <?php foreach ($myBookings as $booking): ?>

                                <?php
                                $dateText = '—';
                                $timeText = '—';

                                if (!empty($booking['booking_date'])) {
                                    $stamp = strtotime((string)$booking['booking_date']);

                                    if ($stamp !== false) {
                                        $dateText = date('M d, Y', $stamp);
                                    }
                                }

                                if (!empty($booking['booking_time'])) {
                                    $stamp = strtotime((string)$booking['booking_time']);

                                    if ($stamp !== false) {
                                        $timeText = date('g:i A', $stamp);
                                    }
                                }
                                ?>

                                <article class="history-item">

                                    <div class="history-top">

                                        <strong>
                                            <?= h($booking['booking_type']) ?>
                                        </strong>

                                        <span
                                            class="booking-status
                                            <?= h(
                                                bookingStatusClass(
                                                    (string)$booking['status']
                                                )
                                            ) ?>"
                                        >
                                            <?= h(
                                                strtoupper(
                                                    (string)$booking['status']
                                                )
                                            ) ?>
                                        </span>

                                    </div>

                                    <div class="history-meta">

                                        BOOKING #<?= (int)$booking['booking_id'] ?>
                                        <br>

                                        <?= h($dateText) ?>
                                        ·
                                        <?= h($timeText) ?>

                                        <?php if (!empty($booking['notes'])): ?>

                                            <br>
                                            <?= h($booking['notes']) ?>

                                        <?php endif; ?>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="history-empty">
                            YOU HAVE NO BOOKINGS YET.
                        </div>

                    <?php endif; ?>

                </section>

                <div class="booking-help">

                    <strong>
                        HOW IT WORKS
                    </strong>

                    <p>
                        Submit your preferred schedule.
                        The request is sent to the administrator.
                        Once approved, the status changes from
                        Pending to Confirmed. After the session,
                        it can be marked Completed.
                    </p>

                </div>

            </aside>

        </div>

    </section>

</main>

<footer class="footer">

    <div class="footer-top">

        <div>

            <div class="footer-brand">
                DIONISIO
                <span>
                    FITNESS CENTER
                </span>
            </div>

            <p>
                Focus. Train. Conquer. Improve.
            </p>

        </div>

        <div class="footer-links">

            <strong>
                QUICK LINKS
            </strong>

            <a href="index.php">
                Home
            </a>

            <a href="about.php">
                About
            </a>

            <a href="services.php">
                Services
            </a>

            <a href="programs.php">
                Programs
            </a>

            <a href="bookings.php">
                Booking
            </a>

            <a href="merch.php">
                Merch
            </a>

            <a href="gallery.php">
                Gallery
            </a>

            <a href="contact.php">
                Contact
            </a>

        </div>

        <div>

            <strong>
                CONTACT US
            </strong>

            <p>
                +63 955 855 1383
            </p>

            <p>
                dionisiobj@gmail.com
            </p>

        </div>

    </div>

    <div class="footer-bottom">

        <span>
            © <?= date('Y') ?>
            DIONISIO FITNESS CENTER.
            ALL RIGHTS RESERVED.
        </span>

        <span>
            BUILT FOR STRENGTH.
        </span>

    </div>

</footer>

<button
    id="topBtn"
    title="Back to top"
    aria-label="Back to top"
>
    ↑
</button>

<script src="js/script.js"></script>

</body>
</html>
