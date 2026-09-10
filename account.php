<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin('account.php');

$userId = currentUserId();

/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        full_name,
        email,
        phone,
        created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->execute([$userId]);

$account = $stmt->fetch();

if (!$account) {
    header('Location: logout.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET LATEST MEMBERSHIP
|--------------------------------------------------------------------------
*/

$membership = null;

try {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            plan,
            status,
            start_date
         FROM memberships
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 1'
    );

    $stmt->execute([$userId]);

    $membership = $stmt->fetch() ?: null;

} catch (PDOException $exception) {

    $membership = null;
}


/*
|--------------------------------------------------------------------------
| GET BOOKING SUMMARY
|--------------------------------------------------------------------------
*/

$totalBookings = 0;
$upcomingBookings = 0;
$latestBooking = null;

try {

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM bookings
         WHERE user_id = ?'
    );

    $stmt->execute([$userId]);

    $totalBookings =
        (int)$stmt->fetchColumn();


    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM bookings
         WHERE user_id = ?
           AND status IN ('Pending', 'Confirmed')
           AND booking_date >= CURDATE()"
    );

    $stmt->execute([$userId]);

    $upcomingBookings =
        (int)$stmt->fetchColumn();


    $stmt = $pdo->prepare(
        'SELECT
            booking_id,
            booking_type,
            booking_date,
            booking_time,
            status
         FROM bookings
         WHERE user_id = ?
         ORDER BY
            booking_date DESC,
            booking_time DESC
         LIMIT 1'
    );

    $stmt->execute([$userId]);

    $latestBooking =
        $stmt->fetch() ?: null;

} catch (PDOException $exception) {

    /*
     * Keep the account page working even if the
     * bookings table has not been created yet.
     */
}


/*
|--------------------------------------------------------------------------
| GET ORDER SUMMARY
|--------------------------------------------------------------------------
*/

$totalOrders = 0;

try {

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM orders
         WHERE user_id = ?'
    );

    $stmt->execute([$userId]);

    $totalOrders =
        (int)$stmt->fetchColumn();

} catch (PDOException $exception) {

    $totalOrders = 0;
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function statusClass(string $status): string
{
    return match (
        strtolower(trim($status))
    ) {
        'active',
        'confirmed',
        'completed' => 'status-good',

        'pending' => 'status-pending',

        'inactive',
        'cancelled',
        'canceled' => 'status-bad',

        default => 'status-neutral'
    };
}


function displayDate(?string $date): string
{
    if (!$date) {
        return 'Not set yet';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('M d, Y', $timestamp);
}


function displayTime(?string $time): string
{
    if (!$time) {
        return '—';
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return $time;
    }

    return date('g:i A', $timestamp);
}

$pageTitle =
    'My Account | Dionisio Fitness Center';

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
        <?= e($pageTitle) ?>
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

    <style>

        :root {
            --account-red: #d71920;
            --account-dark: #080808;
            --account-panel: #101010;
            --account-border: #252525;
            --account-muted: #777777;
            --account-green: #5fd08a;
            --account-yellow: #e7b94f;
        }


        * {
            box-sizing: border-box;
        }


        body.account-page {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(215, 25, 32, 0.08),
                    transparent 35%
                ),
                var(--account-dark);
            color: #f5f5f5;
            font-family:
                "Montserrat",
                Arial,
                sans-serif;
        }


        .member-shell {
            width:
                min(
                    1180px,
                    calc(100% - 32px)
                );
            margin: 0 auto;
            padding: 26px 0 50px;
        }


        .member-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
        }


        .member-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            text-decoration: none;
        }


        .member-brand-mark {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            background: var(--account-red);
            font-size: 23px;
            font-weight: 900;
        }


        .member-brand-copy {
            display: flex;
            flex-direction: column;
        }


        .member-brand-copy strong {
            font-size: 13px;
            font-weight: 900;
        }


        .member-brand-copy small {
            margin-top: 3px;
            color: #666666;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }


        .member-top-actions {
            display: flex;
            gap: 8px;
        }


        .member-top-link {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border: 1px solid #2b2b2b;
            color: #858585;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }


        .member-top-link:hover {
            color: #ffffff;
            border-color: #474747;
        }


        .account-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: flex-end;
            min-height: 230px;
            padding: 36px;
            margin-bottom: 16px;
            background:
                linear-gradient(
                    135deg,
                    #191919,
                    #0c0c0c
                );
            border: 1px solid var(--account-border);
            border-left: 4px solid var(--account-red);
        }


        .account-hero::after {
            content: "MEMBER";
            position: absolute;
            top: -10px;
            right: 18px;
            color: rgba(255, 255, 255, 0.02);
            font-size: 80px;
            font-weight: 900;
            letter-spacing: -5px;
            pointer-events: none;
        }


        .account-hero-copy {
            position: relative;
            z-index: 2;
            max-width: 690px;
        }


        .account-eyebrow {
            margin: 0;
            color: var(--account-red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 2.4px;
        }


        .account-hero h1 {
            margin: 9px 0 0;
            font-size:
                clamp(
                    36px,
                    6vw,
                    58px
                );
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -3px;
        }


        .account-hero h1 span {
            color: var(--account-red);
        }


        .account-hero p {
            margin: 16px 0 0;
            color: #858585;
            font-size: 10px;
            line-height: 1.8;
        }


        .account-dashboard {
            display: grid;
            grid-template-columns:
                minmax(0, 1.15fr)
                minmax(0, 0.85fr);
            gap: 14px;
        }


        .account-panel {
            background: var(--account-panel);
            border: 1px solid var(--account-border);
        }


        .panel-heading {
            padding: 19px 20px;
            border-bottom: 1px solid #202020;
        }


        .panel-heading span {
            display: block;
            margin-bottom: 5px;
            color: var(--account-red);
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1.8px;
        }


        .panel-heading h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 900;
        }


        .profile-info {
            padding: 20px;
        }


        .profile-name {
            margin: 0 0 18px;
            font-size: 23px;
            font-weight: 900;
        }


        .info-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            gap: 10px;
        }


        .info-item {
            min-height: 86px;
            padding: 14px;
            background: #0c0c0c;
            border: 1px solid #202020;
        }


        .info-item span {
            display: block;
            margin-bottom: 8px;
            color: #5e5e5e;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1.1px;
        }


        .info-item strong,
        .info-item p {
            margin: 0;
            color: #dedede;
            font-size: 9px;
            line-height: 1.6;
            word-break: break-word;
        }


        .account-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }


        .account-button {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0.8px;
        }


        .account-button.red {
            background: var(--account-red);
            border: 1px solid var(--account-red);
            color: #ffffff;
        }


        .account-button.red:hover {
            background: #ef252d;
        }


        .account-button.outline {
            border: 1px solid #303030;
            color: #878787;
        }


        .account-button.outline:hover {
            color: #ffffff;
            border-color: #4a4a4a;
        }


        .membership-content {
            padding: 20px;
        }


        .membership-plan {
            margin: 0;
            font-size: 30px;
            font-weight: 900;
            letter-spacing: -1px;
        }


        .membership-status-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }


        .membership-status {
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #303030;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 0.8px;
        }


        .status-good {
            color: var(--account-green);
            border-color: rgba(95, 208, 138, 0.28);
        }


        .status-pending {
            color: var(--account-yellow);
            border-color: rgba(231, 185, 79, 0.28);
        }


        .status-bad {
            color: #ef686e;
            border-color: rgba(239, 104, 110, 0.28);
        }


        .status-neutral {
            color: #888888;
        }


        .membership-date {
            margin: 15px 0 0;
            color: #757575;
            font-size: 9px;
            line-height: 1.7;
        }


        .membership-empty {
            margin: 0;
            color: #777777;
            font-size: 10px;
            line-height: 1.8;
        }


        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );
            gap: 10px;
            margin-top: 14px;
        }


        .mini-stat {
            padding: 17px;
            background: #0d0d0d;
            border: 1px solid #222222;
        }


        .mini-stat span {
            display: block;
            margin-bottom: 9px;
            color: #5e5e5e;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1px;
        }


        .mini-stat strong {
            color: #ffffff;
            font-size: 22px;
            font-weight: 900;
        }


        .latest-booking {
            margin-top: 14px;
            padding: 18px;
            background: #0d0d0d;
            border: 1px solid #222222;
        }


        .latest-booking > span {
            display: block;
            margin-bottom: 12px;
            color: var(--account-red);
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }


        .latest-booking-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }


        .latest-booking-copy strong {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 900;
        }


        .latest-booking-copy p {
            margin: 0;
            color: #777777;
            font-size: 8px;
            line-height: 1.7;
        }


        .quick-links {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            gap: 10px;
            padding: 20px;
        }


        .quick-link {
            min-height: 86px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 15px;
            background: #0c0c0c;
            border: 1px solid #222222;
            color: #ffffff;
            text-decoration: none;
        }


        .quick-link span {
            margin-bottom: 7px;
            color: var(--account-red);
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1.2px;
        }


        .quick-link strong {
            font-size: 10px;
            font-weight: 900;
        }


        .quick-link:hover {
            border-color: #444444;
            background: #111111;
        }


        .signout-row {
            display: flex;
            justify-content: center;
            margin-top: 22px;
        }


        .signout-link {
            color: #606060;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.2px;
        }


        .signout-link:hover {
            color: var(--account-red);
        }


        @media (max-width: 850px) {

            .account-dashboard {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 620px) {

            .member-shell {
                width:
                    min(
                        100% - 20px,
                        1180px
                    );
                padding-top: 16px;
            }


            .member-topbar {
                align-items: flex-start;
                flex-direction: column;
            }


            .member-top-actions {
                width: 100%;
            }


            .member-top-link {
                flex: 1;
            }


            .account-hero {
                min-height: 0;
                padding: 28px 22px;
            }


            .account-hero::after {
                font-size: 56px;
            }


            .info-grid,
            .stats-grid,
            .quick-links {
                grid-template-columns: 1fr;
            }


            .latest-booking-row {
                flex-direction: column;
            }

        }

    </style>

</head>


<body class="account-page">


<main class="member-shell">


    <header class="member-topbar">


        <a
            href="index.php"
            class="member-brand"
        >

            <span class="member-brand-mark">
                D
            </span>

            <span class="member-brand-copy">

                <strong>
                    DIONISIO
                </strong>

                <small>
                    FITNESS CENTER
                </small>

            </span>

        </a>


        <div class="member-top-actions">

            <a
                href="profile.php"
                class="member-top-link"
            >
                PROFILE
            </a>

            <a
                href="index.php"
                class="member-top-link"
            >
                WEBSITE
            </a>

        </div>


    </header>


    <section class="account-hero">


        <div class="account-hero-copy">

            <p class="account-eyebrow">
                MEMBER AREA
            </p>

            <h1>

                MY
                <span>
                    ACCOUNT.
                </span>

            </h1>

            <p>

                Manage your Dionisio Fitness Center
                membership, bookings, profile and
                merchandise orders from one place.

            </p>

        </div>


    </section>


    <section class="account-dashboard">


        <!-- LEFT COLUMN -->

        <div>


            <article class="account-panel">


                <div class="panel-heading">

                    <span>
                        ACCOUNT INFORMATION
                    </span>

                    <h2>
                        MEMBER PROFILE
                    </h2>

                </div>


                <div class="profile-info">


                    <h3 class="profile-name">

                        <?= e(
                            $account['full_name']
                        ) ?>

                    </h3>


                    <div class="info-grid">


                        <div class="info-item">

                            <span>
                                EMAIL ADDRESS
                            </span>

                            <strong>

                                <?= e(
                                    $account['email']
                                ) ?>

                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                PHONE NUMBER
                            </span>

                            <strong>

                                <?= !empty(
                                    $account['phone']
                                )
                                    ? e(
                                        $account['phone']
                                    )
                                    : 'Not provided' ?>

                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                MEMBER SINCE
                            </span>

                            <strong>

                                <?= e(
                                    displayDate(
                                        $account['created_at']
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                ACCOUNT ID
                            </span>

                            <strong>

                                #<?= (int)$account['id'] ?>

                            </strong>

                        </div>


                    </div>


                    <div class="account-card-actions">

                        <a
                            href="profile.php"
                            class="account-button outline"
                        >
                            EDIT PROFILE
                        </a>

                    </div>


                </div>


            </article>


            <div class="stats-grid">


                <article class="mini-stat">

                    <span>
                        TOTAL BOOKINGS
                    </span>

                    <strong>
                        <?= number_format(
                            $totalBookings
                        ) ?>
                    </strong>

                </article>


                <article class="mini-stat">

                    <span>
                        UPCOMING
                    </span>

                    <strong>
                        <?= number_format(
                            $upcomingBookings
                        ) ?>
                    </strong>

                </article>


                <article class="mini-stat">

                    <span>
                        ORDERS
                    </span>

                    <strong>
                        <?= number_format(
                            $totalOrders
                        ) ?>
                    </strong>

                </article>


            </div>


            <?php if ($latestBooking): ?>


                <div class="latest-booking">


                    <span>
                        LATEST BOOKING
                    </span>


                    <div class="latest-booking-row">


                        <div class="latest-booking-copy">

                            <strong>

                                <?= e(
                                    strtoupper(
                                        (string)$latestBooking['booking_type']
                                    )
                                ) ?>

                            </strong>

                            <p>

                                <?= e(
                                    displayDate(
                                        $latestBooking['booking_date']
                                    )
                                ) ?>

                                ·

                                <?= e(
                                    displayTime(
                                        $latestBooking['booking_time']
                                    )
                                ) ?>

                            </p>

                        </div>


                        <span
                            class="membership-status
                            <?= statusClass(
                                (string)$latestBooking['status']
                            ) ?>"
                        >

                            <?= e(
                                strtoupper(
                                    (string)$latestBooking['status']
                                )
                            ) ?>

                        </span>


                    </div>


                </div>


            <?php endif; ?>


        </div>


        <!-- RIGHT COLUMN -->

        <div>


            <article class="account-panel">


                <div class="panel-heading">

                    <span>
                        MEMBERSHIP
                    </span>

                    <h2>
                        MY PLAN
                    </h2>

                </div>


                <div class="membership-content">


                    <?php if ($membership): ?>


                        <h3 class="membership-plan">

                            <?= e(
                                strtoupper(
                                    (string)$membership['plan']
                                )
                            ) ?>

                        </h3>


                        <div class="membership-status-row">

                            <span
                                class="membership-status
                                <?= statusClass(
                                    (string)$membership['status']
                                ) ?>"
                            >

                                <?= e(
                                    strtoupper(
                                        (string)$membership['status']
                                    )
                                ) ?>

                            </span>

                        </div>


                        <p class="membership-date">

                            Start date:
                            <strong>

                                <?= e(
                                    displayDate(
                                        $membership['start_date']
                                    )
                                ) ?>

                            </strong>

                        </p>


                    <?php else: ?>


                        <h3 class="membership-plan">
                            NO PLAN YET
                        </h3>

                        <p class="membership-empty">

                            You have not selected a
                            membership plan yet. Choose
                            a plan to submit your
                            membership request.

                        </p>


                    <?php endif; ?>


                    <div class="account-card-actions">


                        <a
                            href="choose_membership.php"
                            class="account-button red"
                        >

                            <?= $membership
                                ? 'VIEW / CHANGE MEMBERSHIP'
                                : 'CHOOSE MEMBERSHIP' ?>

                        </a>


                    </div>


                </div>


            </article>


            <article
                class="account-panel"
                style="margin-top:14px;"
            >


                <div class="panel-heading">

                    <span>
                        QUICK ACCESS
                    </span>

                    <h2>
                        MEMBER ACTIONS
                    </h2>

                </div>


                <div class="quick-links">


                    <a
                        href="choose_membership.php"
                        class="quick-link"
                    >

                        <span>
                            MEMBERSHIP
                        </span>

                        <strong>
                            CHOOSE PLAN →
                        </strong>

                    </a>


                    <a
                        href="bookings.php"
                        class="quick-link"
                    >

                        <span>
                            FITNESS
                        </span>

                        <strong>
                            BOOK SESSION →
                        </strong>

                    </a>


                    <a
                        href="gallery.php"
                        class="quick-link"
                    >

                        <span>
                            MERCHANDISE
                        </span>

                        <strong>
                            GALLERY →
                        </strong>

                    </a>


                    <a
                        href="services.php"
                        class="quick-link"
                    >

                        <span>
                            SERVICES
                        </span>

                        <strong>
                            SERVICES →
                        </strong>

                    </a>


                </div>


            </article>


        </div>


    </section>


    <div class="signout-row">

        <a
            href="logout.php"
            class="signout-link"
        >
            SIGN OUT
        </a>

    </div>


</main>


</body>

</html>
