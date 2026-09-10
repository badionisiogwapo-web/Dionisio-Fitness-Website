<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

requireAdmin();

$pageTitle = 'Dashboard';

$totalUsers = 0;

$totalMemberships = 0;
$activeMemberships = 0;
$pendingMemberships = 0;

$totalBookings = 0;
$confirmedBookings = 0;
$pendingBookings = 0;

$totalOrders = 0;
$pendingOrders = 0;
$totalSales = 0.00;

$recentUsers = [];
$recentBookings = [];
$recentOrders = [];

$totalMessages = 0;
$unreadMessages = 0;

$databaseError = false;


/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

try {

    $totalUsers = (int)$pdo->query(
        'SELECT COUNT(*) FROM users'
    )->fetchColumn();

} catch (PDOException $exception) {

    $databaseError = true;
}


/*
|--------------------------------------------------------------------------
| MEMBERSHIPS
|--------------------------------------------------------------------------
*/

try {

    $totalMemberships = (int)$pdo->query(
        'SELECT COUNT(*) FROM memberships'
    )->fetchColumn();


    $activeMemberships = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM memberships
         WHERE status = 'Active'"
    )->fetchColumn();


    $pendingMemberships = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM memberships
         WHERE status = 'Pending'"
    )->fetchColumn();

} catch (PDOException $exception) {

    $databaseError = true;

    $totalMemberships = 0;
    $activeMemberships = 0;
    $pendingMemberships = 0;
}


/*
|--------------------------------------------------------------------------
| BOOKINGS
|--------------------------------------------------------------------------
*/

try {

    $totalBookings = (int)$pdo->query(
        'SELECT COUNT(*) FROM bookings'
    )->fetchColumn();


    $confirmedBookings = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM bookings
         WHERE status = 'Confirmed'"
    )->fetchColumn();


    $pendingBookings = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM bookings
         WHERE status = 'Pending'"
    )->fetchColumn();


    $stmt = $pdo->query(
        'SELECT
            b.booking_id,
            b.booking_type,
            b.booking_date,
            b.booking_time,
            b.status,
            u.full_name
         FROM bookings b
         LEFT JOIN users u
            ON u.id = b.user_id
         ORDER BY
            b.booking_date DESC,
            b.booking_time DESC
         LIMIT 5'
    );

    $recentBookings =
        $stmt->fetchAll();

} catch (PDOException $exception) {

    $databaseError = true;

    $totalBookings = 0;
    $confirmedBookings = 0;
    $pendingBookings = 0;
    $recentBookings = [];
}


/*
|--------------------------------------------------------------------------
| ORDERS + SALES
|--------------------------------------------------------------------------
*/

try {

    $totalOrders = (int)$pdo->query(
        'SELECT COUNT(*) FROM orders'
    )->fetchColumn();


    $pendingOrders = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM orders
         WHERE status = 'Pending'"
    )->fetchColumn();


    $totalSales = (float)$pdo->query(
        'SELECT COALESCE(SUM(total_amount), 0)
         FROM orders'
    )->fetchColumn();

} catch (PDOException $exception) {

    $totalOrders = 0;
    $pendingOrders = 0;
    $totalSales = 0.00;
}


/*
|--------------------------------------------------------------------------
| RECENT USERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query(
        'SELECT
            id,
            full_name,
            email,
            created_at
         FROM users
         ORDER BY created_at DESC
         LIMIT 5'
    );

    $recentUsers =
        $stmt->fetchAll();

} catch (PDOException $exception) {

    $recentUsers = [];
}


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query(
        'SELECT
            o.order_id,
            o.total_amount,
            o.status,
            o.order_date,
            u.full_name
         FROM orders o
         LEFT JOIN users u
            ON u.id = o.user_id
         ORDER BY o.order_date DESC
         LIMIT 5'
    );

    $recentOrders =
        $stmt->fetchAll();

} catch (PDOException $exception) {

    $recentOrders = [];

$totalMessages = 0;
$unreadMessages = 0;
}


/*
|--------------------------------------------------------------------------
| CONTACT MESSAGES
|--------------------------------------------------------------------------
*/

try {

    $totalMessages = (int)$pdo->query(
        'SELECT COUNT(*) FROM contact_messages'
    )->fetchColumn();

    $unreadMessages = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM contact_messages
         WHERE status = 'Unread'"
    )->fetchColumn();

} catch (PDOException $exception) {

    $totalMessages = 0;
    $unreadMessages = 0;
}


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

$adminNameRaw =
    (string)(
        $_SESSION['admin_username']
        ?? 'System Administrator'
    );

$adminName =
    htmlspecialchars(
        $adminNameRaw,
        ENT_QUOTES,
        'UTF-8'
    );


/*
|--------------------------------------------------------------------------
| BOOKING ACTIVITY RATE
|--------------------------------------------------------------------------
*/

$bookingActivityRate = 0;

if ($totalBookings > 0) {

    $bookingActivityRate =
        (int)round(
            (
                $confirmedBookings
                / $totalBookings
            ) * 100
        );
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
        strtolower(
            trim(
                $status
            )
        )
    ) {

        'confirmed',
        'completed',
        'paid' =>
            'status-success',

        'pending',
        'processing' =>
            'status-warning',

        'cancelled',
        'canceled' =>
            'status-danger',

        default =>
            'status-neutral'
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

    <title>
        <?= e($pageTitle) ?>
        | Dionisio Fitness Center
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

    <style>

        :root {
            --red: #d71920;
            --red-dark: #930d12;

            --bg: #080808;
            --sidebar: #090909;

            --panel: #101010;
            --panel-2: #131313;

            --border: #242424;
            --border-soft: #1c1c1c;

            --text: #f5f5f5;

            --muted: #777777;
            --muted-2: #555555;

            --green: #5fd08a;
            --yellow: #e7b94f;
        }


        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            margin: 0;

            min-height: 100vh;

            background: var(--bg);

            color: var(--text);

            font-family:
                "Montserrat",
                Arial,
                sans-serif;
        }


        a {
            color: inherit;
        }


        button,
        input,
        select,
        textarea {
            font: inherit;
        }


        /* =========================================================
           SIDEBAR
           ========================================================= */

        .admin-sidebar {

            position: fixed;

            inset:
                0
                auto
                0
                0;

            z-index: 1000;

            width: 260px;

            height: 100vh;

            display: flex;

            flex-direction: column;

            background: var(--sidebar);

            border-right:
                1px solid
                var(--border);
        }


        .sidebar-brand {

            min-height: 90px;

            display: flex;

            align-items: center;

            padding:
                0
                22px;

            border-bottom:
                1px solid
                var(--border-soft);
        }


        .sidebar-brand-link {

            display: flex;

            align-items: center;

            gap: 12px;

            text-decoration: none;
        }


        .sidebar-brand-mark {

            width: 42px;

            height: 42px;

            display: grid;

            place-items: center;

            background: var(--red);

            color: #ffffff;

            font-size: 22px;

            font-weight: 900;
        }


        .sidebar-brand-copy {

            display: flex;

            flex-direction: column;
        }


        .sidebar-brand-copy strong {

            font-size: 13px;

            font-weight: 900;
        }


        .sidebar-brand-copy small {

            margin-top: 3px;

            color: #676767;

            font-size: 8px;

            font-weight: 800;

            letter-spacing: 1.5px;
        }


        .sidebar-label {

            padding:
                27px
                22px
                10px;

            color: #454545;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2px;
        }


        .sidebar-nav {

            display: flex;

            flex-direction: column;

            gap: 4px;

            padding:
                0
                12px;
        }


        .sidebar-link {

            position: relative;

            min-height: 48px;

            display: flex;

            align-items: center;

            gap: 13px;

            padding:
                0
                14px;

            color: #777777;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        .sidebar-link:hover {

            background: #111111;

            color: #ffffff;
        }


        .sidebar-link.active {

            background: #151515;

            color: #ffffff;
        }


        .sidebar-link.active::before {

            content: "";

            position: absolute;

            top: 10px;
            bottom: 10px;
            left: 0;

            width: 3px;

            background: var(--red);
        }


        .sidebar-icon {

            width: 24px;

            display: inline-grid;

            place-items: center;

            color: #737373;

            font-size: 13px;

            font-weight: 900;
        }


        .sidebar-link.active
        .sidebar-icon {

            color: var(--red);
        }


        .sidebar-bottom {

            margin-top: auto;

            padding: 16px;

            border-top:
                1px solid
                var(--border-soft);
        }


        .sidebar-admin {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 12px;

            margin-bottom: 9px;

            background: #101010;

            border:
                1px solid
                #1f1f1f;
        }


        .sidebar-avatar {

            width: 38px;
            height: 38px;

            display: grid;

            place-items: center;

            border-radius: 50%;

            background:
                rgba(
                    215,
                    25,
                    32,
                    0.14
                );

            color: #ef555c;

            font-size: 12px;

            font-weight: 900;
        }


        .sidebar-admin-copy {

            min-width: 0;

            display: flex;

            flex-direction: column;
        }


        .sidebar-admin-copy span {

            margin-bottom: 3px;

            color: #525252;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 1.2px;
        }


        .sidebar-admin-copy strong {

            overflow: hidden;

            color: #e5e5e5;

            font-size: 9px;

            font-weight: 800;

            white-space: nowrap;

            text-overflow: ellipsis;
        }


        .sidebar-logout {

            min-height: 42px;

            display: flex;

            align-items: center;

            gap: 10px;

            padding:
                0
                12px;

            color: #747474;

            text-decoration: none;

            font-size: 9px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .sidebar-logout:hover {

            color: var(--red);

            background:
                rgba(
                    215,
                    25,
                    32,
                    0.08
                );
        }


        /* =========================================================
           MAIN AREA
           ========================================================= */

        .admin-main {

            min-height: 100vh;

            margin-left: 260px;
        }


        .admin-topbar {

            min-height: 72px;

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            padding:
                0
                28px;

            background: #0b0b0b;

            border-bottom:
                1px solid
                var(--border);
        }


        .topbar-title span {

            display: block;

            margin-bottom: 4px;

            color: #555555;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.6px;
        }


        .topbar-title strong {

            font-size: 15px;

            font-weight: 900;
        }


        .topbar-link {

            min-height: 38px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0
                14px;

            border:
                1px solid
                #292929;

            color: #858585;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .topbar-link:hover {

            color: #ffffff;

            border-color: #3b3b3b;
        }


        .admin-content {

            width:
                min(
                    1500px,
                    100%
                );

            margin:
                0
                auto;

            padding:
                24px
                26px
                32px;
        }


        /* =========================================================
           WELCOME
           ========================================================= */

        .dashboard-main-header {

            position: relative;

            min-height: 185px;

            display: flex;

            align-items: flex-end;

            justify-content:
                space-between;

            gap: 30px;

            padding: 30px;

            margin-bottom: 16px;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #171717,
                    #0d0d0d
                );

            border:
                1px solid
                var(--border);

            border-left:
                4px solid
                var(--red);
        }


        .dashboard-main-header::after {

            content: "DFC";

            position: absolute;

            top: -20px;
            right: 22px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.025
                );

            font-size: 112px;

            font-weight: 900;

            letter-spacing: -8px;

            pointer-events: none;
        }


        .dashboard-header-copy {

            position: relative;

            z-index: 2;
        }


        .dashboard-header-line {

            width: 42px;

            height: 4px;

            margin-bottom: 13px;

            background: var(--red);
        }


        .dashboard-header-copy > span {

            color: var(--red);

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2.6px;
        }


        .dashboard-header-copy h1 {

            margin:
                8px
                0
                0;

            font-size:
                clamp(
                    30px,
                    4vw,
                    48px
                );

            font-weight: 900;

            line-height: 1;

            letter-spacing: -2px;
        }


        .dashboard-header-copy h1 em {

            color: var(--red);

            font-style: normal;
        }


        .dashboard-header-copy p {

            max-width: 650px;

            margin:
                13px
                0
                0;

            color: #858585;

            font-size: 11px;

            line-height: 1.7;
        }


        .dashboard-date-box {

            position: relative;

            z-index: 2;

            min-width: 185px;

            padding:
                15px
                17px;

            background: #0d0d0d;

            border:
                1px solid
                #2a2a2a;
        }


        .dashboard-date-box span {

            display: block;

            margin-bottom: 5px;

            color: #626262;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.8px;
        }


        .dashboard-date-box strong {

            font-size: 11px;

            font-weight: 800;
        }


        /* =========================================================
           WARNING
           ========================================================= */

        .dashboard-warning {

            margin-bottom: 16px;

            padding:
                14px
                16px;

            background:
                rgba(
                    215,
                    25,
                    32,
                    0.08
                );

            border:
                1px solid
                rgba(
                    215,
                    25,
                    32,
                    0.28
                );

            color: #ee858a;

            font-size: 10px;
        }


        /* =========================================================
           STATS
           ========================================================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    6,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 13px;

            margin-bottom: 13px;
        }


        .stat-card {

            min-height: 145px;

            display: flex;

            flex-direction: column;

            justify-content:
                space-between;

            padding: 21px;

            background: var(--panel);

            border:
                1px solid
                var(--border);
        }


        .stat-card.featured {

            background:
                linear-gradient(
                    135deg,
                    var(--red),
                    var(--red-dark)
                );

            border-color: var(--red);
        }


        .stat-card-top {

            display: flex;

            align-items: center;

            justify-content:
                space-between;
        }


        .stat-label {

            color: #777777;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.35px;
        }


        .featured .stat-label {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.78
                );
        }


        .stat-icon {

            width: 31px;

            height: 31px;

            display: grid;

            place-items: center;

            border:
                1px solid
                #333333;

            color: #929292;

            font-size: 10px;

            font-weight: 900;
        }


        .featured .stat-icon {

            color: #ffffff;

            border-color:
                rgba(
                    255,
                    255,
                    255,
                    0.25
                );
        }


        .stat-value {

            display: block;

            margin-top: 20px;

            font-size: 30px;

            font-weight: 900;

            letter-spacing: -1px;
        }


        .stat-link {

            margin-top: 13px;

            color: #6f6f6f;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .stat-link:hover,
        .featured .stat-link {

            color: #ffffff;
        }


        /* =========================================================
           HEALTH
           ========================================================= */

        .health-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    5,
                    1fr
                );

            gap: 12px;

            margin-bottom: 16px;
        }


        .health-card {

            padding:
                16px
                18px;

            background: #0e0e0e;

            border:
                1px solid
                #222222;
        }


        .health-card span {

            display: block;

            margin-bottom: 7px;

            color: #626262;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.3px;
        }


        .health-card strong {

            font-size: 11px;

            font-weight: 900;
        }


        .health-green {

            color: var(--green);
        }


        .health-yellow {

            color: var(--yellow);
        }


        /* =========================================================
           PANELS
           ========================================================= */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                minmax(
                    0,
                    1.15fr
                )
                minmax(
                    350px,
                    0.85fr
                );

            gap: 16px;

            margin-bottom: 16px;
        }


        .dashboard-panel {

            overflow: hidden;

            background: #0d0d0d;

            border:
                1px solid
                var(--border);
        }


        .panel-header {

            min-height: 68px;

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 18px;

            padding:
                0
                21px;

            border-bottom:
                1px solid
                #222222;
        }


        .panel-header p {

            margin:
                0
                0
                4px;

            color: var(--red);

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2px;
        }


        .panel-header h2 {

            margin: 0;

            font-size: 14px;

            font-weight: 900;
        }


        .panel-header > a {

            color: #777777;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;
        }


        .panel-header > a:hover {

            color: #ffffff;
        }


        /* =========================================================
           TABLES
           ========================================================= */

        .table-wrap {

            overflow-x: auto;
        }


        .dashboard-table {

            width: 100%;

            border-collapse: collapse;
        }


        .dashboard-table th {

            padding:
                13px
                16px;

            color: #595959;

            text-align: left;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 1.2px;

            white-space: nowrap;

            border-bottom:
                1px solid
                #202020;
        }


        .dashboard-table td {

            padding:
                13px
                16px;

            color: #9c9c9c;

            font-size: 9px;

            border-bottom:
                1px solid
                #1b1b1b;
        }


        .dashboard-table tbody
        tr:last-child td {

            border-bottom: 0;
        }


        .dashboard-table tbody
        tr:hover {

            background: #121212;
        }


        .user-cell {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .user-avatar {

            width: 32px;
            height: 32px;

            display: grid;

            place-items: center;

            border-radius: 50%;

            background:
                rgba(
                    215,
                    25,
                    32,
                    0.13
                );

            color: #ef555c;

            font-size: 10px;

            font-weight: 900;
        }


        .user-cell strong {

            color: #dddddd;

            font-size: 9px;
        }


        .view-link {

            color: var(--red);

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;
        }


        .status-badge {

            display: inline-flex;

            align-items: center;

            min-height: 24px;

            padding:
                0
                9px;

            border:
                1px solid
                #2a2a2a;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 0.8px;
        }


        .status-success {

            color: var(--green);

            border-color:
                rgba(
                    95,
                    208,
                    138,
                    0.25
                );
        }


        .status-warning {

            color: var(--yellow);

            border-color:
                rgba(
                    231,
                    185,
                    79,
                    0.25
                );
        }


        .status-danger {

            color: #ef686e;

            border-color:
                rgba(
                    239,
                    104,
                    110,
                    0.25
                );
        }


        .status-neutral {

            color: #aaaaaa;
        }


        /* =========================================================
           QUICK ACTIONS
           ========================================================= */

        .quick-heading {

            margin:
                0
                0
                13px;
        }


        .quick-heading span {

            color: var(--red);

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2px;
        }


        .quick-heading h2 {

            margin:
                5px
                0
                0;

            font-size: 17px;

            font-weight: 900;
        }


        .quick-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap: 12px;
        }


        .quick-card {

            position: relative;

            min-height: 132px;

            display: flex;

            flex-direction: column;

            justify-content:
                flex-end;

            padding: 19px;

            background: #0e0e0e;

            border:
                1px solid
                var(--border);

            color: #ffffff;

            text-decoration: none;
        }


        .quick-card:hover {

            border-color: var(--red);
        }


        .quick-number {

            position: absolute;

            top: 15px;
            right: 17px;

            color: #333333;

            font-size: 20px;

            font-weight: 900;
        }


        .quick-card strong {

            margin-bottom: 5px;

            font-size: 10px;

            font-weight: 900;
        }


        .quick-card small {

            color: #666666;

            font-size: 8px;
        }


        .empty-state {

            padding:
                38px
                20px;

            color: #555555;

            text-align: center;

            font-size: 8px;

            font-weight: 900;
        }


        .footer {

            margin-top: 22px;

            padding:
                18px
                0
                0;

            border-top:
                1px solid
                #1c1c1c;

            color: #4f4f4f;

            font-size: 8px;

            text-align: center;
        }


        /* =========================================================
           MOBILE
           ========================================================= */

        .mobile-bar {

            display: none;
        }


        .mobile-toggle {

            width: 42px;

            height: 42px;

            display: grid;

            place-items: center;

            border:
                1px solid
                #303030;

            background: #111111;

            color: #ffffff;

            cursor: pointer;
        }


        @media (
            max-width: 1180px
        ) {

            .stats-grid,
            .quick-grid {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }


            .dashboard-grid {

                grid-template-columns:
                    1fr;
            }

        }


        @media (
            max-width: 900px
        ) {

            .admin-sidebar {

                transform:
                    translateX(
                        -100%
                    );

                transition:
                    transform
                    0.25s
                    ease;
            }


            .admin-sidebar.open {

                transform:
                    translateX(
                        0
                    );
            }


            .admin-main {

                margin-left: 0;
            }


            .admin-topbar {

                display: none;
            }


            .mobile-bar {

                position: sticky;

                top: 0;

                z-index: 900;

                min-height: 62px;

                display: flex;

                align-items: center;

                justify-content:
                    space-between;

                padding:
                    10px
                    14px;

                background: #0b0b0b;

                border-bottom:
                    1px solid
                    var(--border);
            }


            .admin-content {

                padding:
                    18px
                    14px
                    28px;
            }

        }


        @media (
            max-width: 640px
        ) {

            .dashboard-main-header {

                min-height: 0;

                align-items:
                    flex-start;

                flex-direction:
                    column;

                padding: 23px;
            }


            .dashboard-date-box {

                width: 100%;

                min-width: 0;
            }


            .stats-grid,
            .health-grid,
            .quick-grid {

                grid-template-columns:
                    1fr;
            }

        }

    </style>

</head>


<body>


<aside
    class="admin-sidebar"
    id="adminSidebar"
>


    <div class="sidebar-brand">

        <a
            href="index.php"
            class="sidebar-brand-link"
        >

            <span class="sidebar-brand-mark">
                D
            </span>

            <span class="sidebar-brand-copy">

                <strong>
                    DIONISIO
                </strong>

                <small>
                    FITNESS CENTER
                </small>

            </span>

        </a>

    </div>


    <div class="sidebar-label">
        MAIN MENU
    </div>


    <nav class="sidebar-nav">


        <a
            href="index.php"
            class="sidebar-link active"
        >

            <span class="sidebar-icon">
                ▦
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="users.php"
            class="sidebar-link"
        >

            <span class="sidebar-icon">
                U
            </span>

            <span>
                Users
            </span>

        </a>


        <a
            href="memberships.php"
            class="sidebar-link"
        >

            <span class="sidebar-icon">
                M
            </span>

            <span>
                Memberships
            </span>

        </a>


        <a
            href="bookings.php"
            class="sidebar-link"
        >

            <span class="sidebar-icon">
                B
            </span>

            <span>
                Bookings
            </span>

        </a>

        <a
            href="messages.php"
            class="sidebar-link"
        >
            <span class="sidebar-icon">✉</span>
            <span>Messages</span>
        </a>

    </nav>


    <div class="sidebar-bottom">


        <div class="sidebar-admin">


            <div class="sidebar-avatar">

                <?= e(
                    strtoupper(
                        substr(
                            $adminNameRaw,
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div class="sidebar-admin-copy">

                <span>
                    ADMINISTRATOR
                </span>

                <strong>
                    <?= $adminName ?>
                </strong>

            </div>


        </div>


        <a
            href="logout.php"
            class="sidebar-logout"
        >

            <span>
                ↪
            </span>

            LOG OUT

        </a>


    </div>


</aside>


<div class="admin-main">


    <div class="mobile-bar">

        <button
            class="mobile-toggle"
            id="sidebarToggle"
            type="button"
            aria-label="Open admin navigation"
        >
            ☰
        </button>

        <strong>
            DIONISIO ADMIN
        </strong>

    </div>


    <header class="admin-topbar">


        <div class="topbar-title">

            <span>
                ADMIN PANEL
            </span>

            <strong>
                Dashboard
            </strong>

        </div>


        <a
            href="../index.php"
            target="_blank"
            rel="noopener noreferrer"
            class="topbar-link"
        >
            VIEW WEBSITE ↗
        </a>


    </header>


    <main class="admin-content">


        <section class="dashboard-main-header">


            <div class="dashboard-header-copy">

                <div class="dashboard-header-line"></div>

                <span>
                    ADMINISTRATION
                </span>

                <h1>

                    WELCOME,

                    <em>
                        <?= strtoupper(
                            $adminName
                        ) ?>.
                    </em>

                </h1>

                <p>

                    Manage Dionisio Fitness Center users,
                    memberships, bookings, orders and business
                    activity from one organized dashboard.

                </p>

            </div>


            <div class="dashboard-date-box">

                <span>
                    TODAY
                </span>

                <strong>
                    <?= date(
                        'F d, Y'
                    ) ?>
                </strong>

            </div>


        </section>


        <?php if ($databaseError): ?>

            <div class="dashboard-warning">

                Some dashboard information could not
                be loaded. Make sure the memberships,
                bookings and orders tables exist and match
                the required structure.

            </div>

        <?php endif; ?>


        <section class="stats-grid">


            <article class="stat-card">

                <div class="stat-card-top">

                    <span class="stat-label">
                        REGISTERED USERS
                    </span>

                    <span class="stat-icon">
                        U
                    </span>

                </div>

                <strong class="stat-value">
                    <?= number_format(
                        $totalUsers
                    ) ?>
                </strong>

                <a
                    href="users.php"
                    class="stat-link"
                >
                    MANAGE USERS →
                </a>

            </article>


            <article class="stat-card">

                <div class="stat-card-top">

                    <span class="stat-label">
                        TOTAL MEMBERSHIPS
                    </span>

                    <span class="stat-icon">
                        M
                    </span>

                </div>

                <strong class="stat-value">
                    <?= number_format(
                        $totalMemberships
                    ) ?>
                </strong>

                <a
                    href="memberships.php"
                    class="stat-link"
                >
                    MANAGE MEMBERSHIPS →
                </a>

            </article>


            <article class="stat-card">

                <div class="stat-card-top">

                    <span class="stat-label">
                        TOTAL BOOKINGS
                    </span>

                    <span class="stat-icon">
                        B
                    </span>

                </div>

                <strong class="stat-value">
                    <?= number_format(
                        $totalBookings
                    ) ?>
                </strong>

                <a
                    href="bookings.php"
                    class="stat-link"
                >
                    MANAGE BOOKINGS →
                </a>

            </article>


            <article class="stat-card">

                <div class="stat-card-top">

                    <span class="stat-label">
                        TOTAL ORDERS
                    </span>

                    <span class="stat-icon">
                        O
                    </span>

                </div>

                <strong class="stat-value">
                    <?= number_format(
                        $totalOrders
                    ) ?>
                </strong>

                <a
                    href="orders.php"
                    class="stat-link"
                >
                    VIEW ORDERS →
                </a>

            </article>


            <article class="stat-card featured">

                <div class="stat-card-top">

                    <span class="stat-label">
                        TOTAL SALES
                    </span>

                    <span class="stat-icon">
                        ₱
                    </span>

                </div>

                <strong class="stat-value">

                    ₱<?= number_format(
                        $totalSales,
                        2
                    ) ?>

                </strong>

                <a
                    href="orders.php"
                    class="stat-link"
                >
                    VIEW SALES →
                </a>

            </article>


            <article class="stat-card">

                <div class="stat-card-top">

                    <span class="stat-label">
                        CONTACT MESSAGES
                    </span>

                    <span class="stat-icon">
                        ✉
                    </span>

                </div>

                <strong class="stat-value">
                    <?= number_format($totalMessages) ?>
                </strong>

                <a
                    href="messages.php"
                    class="stat-link"
                >
                    VIEW MESSAGES →
                </a>

            </article>


        </section>


        <section class="health-grid">


            <div class="health-card">

                <span>
                    BOOKING ACTIVITY
                </span>

                <strong class="health-green">

                    <?= number_format(
                        $bookingActivityRate
                    ) ?>% CONFIRMED

                </strong>

            </div>


            <div class="health-card">

                <span>
                    PENDING MEMBERSHIPS
                </span>

                <strong class="health-yellow">

                    <?= number_format(
                        $pendingMemberships
                    ) ?>

                    REQUESTS

                </strong>

                <?php if ($pendingMemberships > 0): ?>

                    <div style="margin-top:8px;">

                        <a
                            href="memberships.php?status=Pending"
                            class="stat-link"
                            style="margin-top:0;"
                        >
                            REVIEW →
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <div class="health-card">

                <span>
                    PENDING BOOKINGS
                </span>

                <strong class="health-yellow">

                    <?= number_format(
                        $pendingBookings
                    ) ?>

                    REQUESTS

                </strong>

            </div>


            <div class="health-card">

                <span>
                    PENDING ORDERS
                </span>

                <strong>

                    <?= number_format(
                        $pendingOrders
                    ) ?>

                    ORDERS

                </strong>

            </div>


            <div class="health-card">

                <span>
                    UNREAD MESSAGES
                </span>

                <strong class="health-yellow">
                    <?= number_format($unreadMessages) ?>
                    MESSAGES
                </strong>

                <?php if ($unreadMessages > 0): ?>

                    <div style="margin-top:8px;">
                        <a
                            href="messages.php?status=Unread"
                            class="stat-link"
                            style="margin-top:0;"
                        >
                            REVIEW →
                        </a>
                    </div>

                <?php endif; ?>

            </div>


        </section>


        <div class="dashboard-grid">


            <section class="dashboard-panel">


                <div class="panel-header">

                    <div>

                        <p>
                            MEMBER DATABASE
                        </p>

                        <h2>
                            RECENT USERS
                        </h2>

                    </div>

                    <a href="users.php">
                        VIEW ALL →
                    </a>

                </div>


                <?php if (!empty($recentUsers)): ?>


                    <div class="table-wrap">


                        <table class="dashboard-table">


                            <thead>

                                <tr>

                                    <th>
                                        USER
                                    </th>

                                    <th>
                                        EMAIL
                                    </th>

                                    <th>
                                        JOINED
                                    </th>

                                    <th>
                                        ACTION
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $recentUsers
                                as $user
                            ): ?>


                                <tr>


                                    <td>

                                        <div class="user-cell">


                                            <div class="user-avatar">

                                                <?= e(
                                                    strtoupper(
                                                        substr(
                                                            (string)$user['full_name'],
                                                            0,
                                                            1
                                                        )
                                                    )
                                                ) ?>

                                            </div>


                                            <strong>

                                                <?= e(
                                                    $user['full_name']
                                                ) ?>

                                            </strong>


                                        </div>

                                    </td>


                                    <td>

                                        <?= e(
                                            $user['email']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php

                                        $joined =
                                            strtotime(
                                                (string)$user['created_at']
                                            );

                                        echo
                                            $joined !== false
                                                ? date(
                                                    'M d, Y',
                                                    $joined
                                                )
                                                : '—';

                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="view_users.php?id=<?= (int)$user['id'] ?>"
                                            class="view-link"
                                        >
                                            VIEW
                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        NO REGISTERED USERS FOUND

                    </div>


                <?php endif; ?>


            </section>


            <section class="dashboard-panel">


                <div class="panel-header">

                    <div>

                        <p>
                            BOOKING ACTIVITY
                        </p>

                        <h2>
                            RECENT BOOKINGS
                        </h2>

                    </div>

                    <a href="bookings.php">
                        MANAGE →
                    </a>

                </div>


                <?php if (
                    !empty(
                        $recentBookings
                    )
                ): ?>


                    <div class="table-wrap">


                        <table class="dashboard-table">


                            <thead>

                                <tr>

                                    <th>
                                        CUSTOMER
                                    </th>

                                    <th>
                                        TYPE
                                    </th>

                                    <th>
                                        STATUS
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $recentBookings
                                as $booking
                            ): ?>


                                <tr>


                                    <td>

                                        <?= e(
                                            $booking['full_name']
                                            ?? 'Unknown Customer'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            strtoupper(
                                                (string)$booking['booking_type']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge
                                            <?= statusClass(
                                                (string)$booking['status']
                                            ) ?>"
                                        >

                                            <?= e(
                                                strtoupper(
                                                    (string)$booking['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        NO BOOKINGS FOUND

                    </div>


                <?php endif; ?>


            </section>


        </div>


        <section
            class="dashboard-panel"
            style="margin-bottom:16px;"
        >


            <div class="panel-header">

                <div>

                    <p>
                        STORE ACTIVITY
                    </p>

                    <h2>
                        RECENT ORDERS
                    </h2>

                </div>

                <a href="orders.php">
                    VIEW ALL →
                </a>

            </div>


            <?php if (!empty($recentOrders)): ?>


                <div class="table-wrap">


                    <table class="dashboard-table">


                        <thead>

                            <tr>

                                <th>
                                    ORDER
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    TOTAL
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    DATE
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>


                            <?php

                            $orderStatus =
                                (string)(
                                    $order['status']
                                    ?? ''
                                );

                            ?>


                            <tr>


                                <td>

                                    #<?= (int)$order['order_id'] ?>

                                </td>


                                <td>

                                    <?= e(
                                        $order['full_name']
                                        ?? 'Unknown Customer'
                                    ) ?>

                                </td>


                                <td>

                                    ₱<?= number_format(
                                        (float)$order['total_amount'],
                                        2
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="status-badge
                                        <?= statusClass(
                                            $orderStatus
                                        ) ?>"
                                    >

                                        <?= e(
                                            strtoupper(
                                                $orderStatus
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    $orderDate =
                                        strtotime(
                                            (string)$order['order_date']
                                        );

                                    echo
                                        $orderDate !== false
                                            ? date(
                                                'M d, Y',
                                                $orderDate
                                            )
                                            : '—';

                                    ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    NO ORDERS FOUND

                </div>


            <?php endif; ?>


        </section>


        <section>


            <div class="quick-heading">

                <span>
                    ADMIN TOOLS
                </span>

                <h2>
                    QUICK ACTIONS
                </h2>

            </div>


            <div class="quick-grid">


                <a
                    href="users.php"
                    class="quick-card"
                >

                    <span class="quick-number">
                        01
                    </span>

                    <strong>
                        MANAGE USERS
                    </strong>

                    <small>
                        View registered customer accounts
                    </small>

                </a>


                <a
                    href="memberships.php"
                    class="quick-card"
                >

                    <span class="quick-number">
                        02
                    </span>

                    <strong>
                        MEMBERSHIPS
                    </strong>

                    <small>
                        Review and approve membership requests
                    </small>

                </a>


                <a
                    href="bookings.php"
                    class="quick-card"
                >

                    <span class="quick-number">
                        03
                    </span>

                    <strong>
                        BOOKINGS
                    </strong>

                    <small>
                        Review and manage booking requests
                    </small>

                </a>


                <a
                    href="orders.php"
                    class="quick-card"
                >

                    <span class="quick-number">
                        04
                    </span>

                    <strong>
                        ORDERS
                    </strong>

                    <small>
                        Manage customer purchases
                    </small>

                </a>


                <a
                    href="messages.php"
                    class="quick-card"
                >

                    <span class="quick-number">
                        06
                    </span>

                    <strong>
                        MESSAGES
                    </strong>

                    <small>
                        Review Contact Us form submissions
                    </small>

                </a>


                <a
                    href="../index.php"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="quick-card"
                >

                    <span class="quick-number">
                        05
                    </span>

                    <strong>
                        VIEW WEBSITE
                    </strong>

                    <small>
                        Open the public Dionisio website
                    </small>

                </a>


            </div>


        </section>


        <footer class="footer">

            © <?= date('Y') ?>
            Dionisio Fitness Center.
            Admin Dashboard.

        </footer>


    </main>


</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const toggle =
            document.getElementById(
                'sidebarToggle'
            );

        const sidebar =
            document.getElementById(
                'adminSidebar'
            );


        if (!toggle || !sidebar) {
            return;
        }


        toggle.addEventListener(
            'click',
            function () {

                sidebar.classList.toggle(
                    'open'
                );

            }
        );


        document.addEventListener(
            'click',
            function (event) {

                if (
                    window.innerWidth <= 900 &&
                    sidebar.classList.contains(
                        'open'
                    ) &&
                    !sidebar.contains(
                        event.target
                    ) &&
                    !toggle.contains(
                        event.target
                    )
                ) {

                    sidebar.classList.remove(
                        'open'
                    );

                }

            }
        );

    }
);

</script>


</body>

</html>
