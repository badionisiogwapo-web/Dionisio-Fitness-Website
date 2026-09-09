<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

requireAdmin();

$pageTitle = 'View User';

$adminNameRaw = (string)($_SESSION['admin_username'] ?? 'System Administrator');

$adminName = htmlspecialchars(
    $adminNameRaw,
    ENT_QUOTES,
    'UTF-8'
);


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$userId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$userId || $userId < 1) {

    header('Location: users.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$user = null;
$databaseError = false;

try {

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

    $stmt->execute([
        $userId
    ]);

    $user = $stmt->fetch();

} catch (PDOException $exception) {

    $databaseError = true;
}


if (!$user) {

    http_response_code(404);
}


/*
|--------------------------------------------------------------------------
| LATEST MEMBERSHIP
|--------------------------------------------------------------------------
*/

$membership = null;

if ($user) {

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

        $stmt->execute([
            $userId
        ]);

        $membership = $stmt->fetch();

    } catch (PDOException $exception) {

        $membership = null;
    }

}


/*
|--------------------------------------------------------------------------
| ORDER SUMMARY
|--------------------------------------------------------------------------
*/

$totalOrders = 0;
$totalSpent = 0.00;
$pendingOrders = 0;
$recentOrders = [];

if ($user) {

    try {

        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM orders
             WHERE user_id = ?'
        );

        $stmt->execute([
            $userId
        ]);

        $totalOrders =
            (int)$stmt->fetchColumn();


        $stmt = $pdo->prepare(
            'SELECT COALESCE(
                SUM(total_amount),
                0
             )
             FROM orders
             WHERE user_id = ?'
        );

        $stmt->execute([
            $userId
        ]);

        $totalSpent =
            (float)$stmt->fetchColumn();


        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM orders
             WHERE user_id = ?
             AND status = 'Pending'"
        );

        $stmt->execute([
            $userId
        ]);

        $pendingOrders =
            (int)$stmt->fetchColumn();


        $stmt = $pdo->prepare(
            'SELECT
                order_id,
                total_amount,
                status,
                order_date
             FROM orders
             WHERE user_id = ?
             ORDER BY order_date DESC
             LIMIT 8'
        );

        $stmt->execute([
            $userId
        ]);

        $recentOrders =
            $stmt->fetchAll();

    } catch (PDOException $exception) {

        $totalOrders = 0;
        $totalSpent = 0.00;
        $pendingOrders = 0;
        $recentOrders = [];
    }

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

        'active',
        'completed',
        'paid' =>
            'status-success',

        'pending',
        'processing' =>
            'status-warning',

        'inactive',
        'cancelled',
        'canceled' =>
            'status-danger',

        default =>
            'status-neutral'
    };
}


$joinedDate = '—';

if (
    $user &&
    !empty(
        $user['created_at']
    )
) {

    $timestamp =
        strtotime(
            (string)$user['created_at']
        );

    if ($timestamp !== false) {

        $joinedDate =
            date(
                'F d, Y',
                $timestamp
            );
    }
}


$membershipStart = '—';

if (
    $membership &&
    !empty(
        $membership['start_date']
    )
) {

    $timestamp =
        strtotime(
            (string)$membership['start_date']
        );

    if ($timestamp !== false) {

        $membershipStart =
            date(
                'F d, Y',
                $timestamp
            );
    }
}


$userInitial = 'U';

if ($user) {

    $name =
        trim(
            (string)$user['full_name']
        );

    if ($name !== '') {

        $userInitial =
            strtoupper(
                substr(
                    $name,
                    0,
                    1
                )
            );
    }
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
        View User | Dionisio Fitness Center
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


        button {
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

            color: #ffffff;

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

            letter-spacing: 0.4px;
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

            flex:
                0
                0
                auto;

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

            transition:
                background 0.2s ease,
                color 0.2s ease;
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
           MAIN
           ========================================================= */

        .admin-main {

            min-height: 100vh;

            margin-left: 260px;
        }


        .admin-topbar {

            min-height: 72px;

            display: flex;

            align-items: center;

            justify-content: space-between;

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


        .topbar-actions {

            display: flex;

            align-items: center;

            gap: 10px;
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

            transition:
                border-color 0.2s ease,
                color 0.2s ease;
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
           BACK LINK
           ========================================================= */

        .back-row {

            margin-bottom: 13px;
        }


        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            color: #777777;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;

            transition:
                color 0.2s ease;
        }


        .back-link:hover {

            color: #ffffff;
        }


        /* =========================================================
           PROFILE HERO
           ========================================================= */

        .profile-hero {

            position: relative;

            display: grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                auto;

            align-items: end;

            gap: 28px;

            min-height: 210px;

            padding: 30px;

            margin-bottom: 15px;

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


        .profile-hero::after {

            content: "MEMBER";

            position: absolute;

            top: -8px;

            right: 18px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.02
                );

            font-size: 90px;

            font-weight: 900;

            letter-spacing: -5px;

            pointer-events: none;
        }


        .profile-main {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            gap: 18px;
        }


        .profile-avatar {

            width: 72px;

            height: 72px;

            flex:
                0
                0
                auto;

            display: grid;

            place-items: center;

            border-radius: 50%;

            background:
                rgba(
                    215,
                    25,
                    32,
                    0.15
                );

            border:
                1px solid
                rgba(
                    215,
                    25,
                    32,
                    0.28
                );

            color: #ef555c;

            font-size: 24px;

            font-weight: 900;
        }


        .profile-copy span {

            display: block;

            margin-bottom: 8px;

            color: var(--red);

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2.2px;
        }


        .profile-copy h1 {

            margin: 0;

            color: #ffffff;

            font-size:
                clamp(
                    28px,
                    4vw,
                    44px
                );

            font-weight: 900;

            line-height: 1;

            letter-spacing: -1.8px;
        }


        .profile-copy p {

            margin:
                10px
                0
                0;

            color: #777777;

            font-size: 10px;
        }


        .profile-id {

            position: relative;

            z-index: 2;

            min-width: 150px;

            padding: 15px 17px;

            background: #0d0d0d;

            border:
                1px solid
                #2a2a2a;
        }


        .profile-id span {

            display: block;

            margin-bottom: 5px;

            color: #626262;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 1.5px;
        }


        .profile-id strong {

            font-size: 12px;

            font-weight: 900;
        }


        /* =========================================================
           STATS
           ========================================================= */

        .profile-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

            gap: 12px;

            margin-bottom: 16px;
        }


        .profile-stat {

            padding: 18px;

            background: #0f0f0f;

            border:
                1px solid
                var(--border);
        }


        .profile-stat span {

            display: block;

            margin-bottom: 10px;

            color: #626262;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.3px;
        }


        .profile-stat strong {

            color: #ffffff;

            font-size: 22px;

            font-weight: 900;
        }


        .profile-stat.featured {

            background:
                linear-gradient(
                    135deg,
                    var(--red),
                    var(--red-dark)
                );

            border-color: var(--red);
        }


        .profile-stat.featured span {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.75
                );
        }


        /* =========================================================
           MAIN GRID
           ========================================================= */

        .detail-grid {

            display: grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                minmax(
                    320px,
                    0.7fr
                );

            gap: 16px;

            margin-bottom: 16px;
        }


        .detail-panel {

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


        /* =========================================================
           INFORMATION
           ========================================================= */

        .info-list {

            padding: 5px 21px;
        }


        .info-row {

            display: grid;

            grid-template-columns:
                150px
                minmax(
                    0,
                    1fr
                );

            gap: 20px;

            padding:
                17px
                0;

            border-bottom:
                1px solid
                #1d1d1d;
        }


        .info-row:last-child {

            border-bottom: 0;
        }


        .info-row span {

            color: #555555;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .info-row strong {

            overflow-wrap: anywhere;

            color: #d7d7d7;

            font-size: 10px;

            font-weight: 700;
        }


        /* =========================================================
           MEMBERSHIP
           ========================================================= */

        .membership-content {

            padding: 21px;
        }


        .membership-plan {

            margin-bottom: 16px;

            color: #ffffff;

            font-size: 26px;

            font-weight: 900;

            letter-spacing: -1px;
        }


        .membership-status {

            display: inline-flex;

            align-items: center;

            min-height: 28px;

            padding:
                0
                10px;

            margin-bottom: 22px;

            border:
                1px solid
                #2a2a2a;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 1px;
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

            background:
                rgba(
                    95,
                    208,
                    138,
                    0.05
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

            background:
                rgba(
                    231,
                    185,
                    79,
                    0.05
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

            color: #777777;
        }


        .membership-meta {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 10px;
        }


        .membership-meta div {

            padding: 14px;

            background: #111111;

            border:
                1px solid
                #222222;
        }


        .membership-meta span {

            display: block;

            margin-bottom: 6px;

            color: #555555;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .membership-meta strong {

            color: #d5d5d5;

            font-size: 9px;
        }


        /* =========================================================
           ORDERS
           ========================================================= */

        .orders-panel {

            margin-bottom: 16px;
        }


        .table-wrap {

            overflow-x: auto;
        }


        .orders-table {

            width: 100%;

            border-collapse: collapse;
        }


        .orders-table th {

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


        .orders-table td {

            padding:
                14px
                16px;

            color: #9c9c9c;

            font-size: 9px;

            border-bottom:
                1px solid
                #1b1b1b;
        }


        .orders-table tbody
        tr:last-child td {

            border-bottom: 0;
        }


        .orders-table tbody
        tr:hover {

            background: #121212;
        }


        .status-badge {

            display: inline-flex;

            align-items: center;

            min-height: 25px;

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


        .view-order {

            color: var(--red);

            text-decoration: none;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 0.8px;
        }


        .empty-state {

            padding: 45px 20px;

            color: #555555;

            text-align: center;

            font-size: 9px;

            font-weight: 900;

            letter-spacing: 1.2px;
        }


        .error-box {

            padding: 50px 25px;

            text-align: center;

            background: #0e0e0e;

            border:
                1px solid
                var(--border);
        }


        .error-box span {

            display: block;

            margin-bottom: 8px;

            color: var(--red);

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2px;
        }


        .error-box h1 {

            margin:
                0
                0
                12px;

            font-size: 30px;
        }


        .error-box p {

            margin:
                0
                0
                20px;

            color: #777777;

            font-size: 10px;
        }


        .error-box a {

            display: inline-flex;

            min-height: 40px;

            align-items: center;

            padding:
                0
                15px;

            background: var(--red);

            color: #ffffff;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
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

            background: #111111;

            border:
                1px solid
                #303030;

            color: #ffffff;

            cursor: pointer;
        }


        @media (
            max-width: 1050px
        ) {

            .detail-grid {

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


            .mobile-bar strong {

                font-size: 11px;

                font-weight: 900;
            }


            .admin-content {

                padding:
                    18px
                    14px
                    28px;
            }

        }


        @media (
            max-width: 650px
        ) {

            .profile-hero {

                grid-template-columns:
                    1fr;

                align-items:
                    flex-start;

                padding: 23px;
            }


            .profile-main {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .profile-id {

                width: 100%;
            }


            .profile-stats {

                grid-template-columns:
                    1fr;
            }


            .info-row {

                grid-template-columns:
                    1fr;

                gap: 7px;
            }


            .membership-meta {

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
            class="sidebar-link"
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
            class="sidebar-link active"
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
            href="orders.php"
            class="sidebar-link"
        >

            <span class="sidebar-icon">
                O
            </span>

            <span>
                Orders
            </span>

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
                View User
            </strong>

        </div>


        <div class="topbar-actions">

            <a
                href="users.php"
                class="topbar-link"
            >
                USERS
            </a>

            <a
                href="../index.php"
                target="_blank"
                rel="noopener noreferrer"
                class="topbar-link"
            >
                VIEW WEBSITE ↗
            </a>

        </div>


    </header>


    <main class="admin-content">


        <div class="back-row">

            <a
                href="users.php"
                class="back-link"
            >
                ← BACK TO USERS
            </a>

        </div>


        <?php if (!$user): ?>


            <section class="error-box">

                <span>
                    USER DATABASE
                </span>

                <h1>
                    USER NOT FOUND
                </h1>

                <p>

                    The requested user does not exist
                    or could not be loaded.

                </p>

                <a href="users.php">
                    RETURN TO USERS
                </a>

            </section>


        <?php else: ?>


            <section class="profile-hero">


                <div class="profile-main">


                    <div class="profile-avatar">

                        <?= e(
                            $userInitial
                        ) ?>

                    </div>


                    <div class="profile-copy">

                        <span>
                            REGISTERED USER
                        </span>

                        <h1>
                            <?= e(
                                $user['full_name']
                            ) ?>
                        </h1>

                        <p>

                            Member since
                            <?= e(
                                $joinedDate
                            ) ?>

                        </p>

                    </div>


                </div>


                <div class="profile-id">

                    <span>
                        USER ID
                    </span>

                    <strong>
                        #<?= (int)$user['id'] ?>
                    </strong>

                </div>


            </section>


            <?php if ($databaseError): ?>

                <div class="error-box">

                    Some user information could not
                    be loaded from the database.

                </div>

            <?php endif; ?>


            <section class="profile-stats">


                <article class="profile-stat">

                    <span>
                        TOTAL ORDERS
                    </span>

                    <strong>
                        <?= number_format(
                            $totalOrders
                        ) ?>
                    </strong>

                </article>


                <article class="profile-stat">

                    <span>
                        PENDING ORDERS
                    </span>

                    <strong>
                        <?= number_format(
                            $pendingOrders
                        ) ?>
                    </strong>

                </article>


                <article class="profile-stat featured">

                    <span>
                        TOTAL SPENT
                    </span>

                    <strong>

                        ₱<?= number_format(
                            $totalSpent,
                            2
                        ) ?>

                    </strong>

                </article>


            </section>


            <div class="detail-grid">


                <section class="detail-panel">


                    <div class="panel-header">

                        <div>

                            <p>
                                ACCOUNT INFORMATION
                            </p>

                            <h2>
                                USER DETAILS
                            </h2>

                        </div>

                    </div>


                    <div class="info-list">


                        <div class="info-row">

                            <span>
                                FULL NAME
                            </span>

                            <strong>
                                <?= e(
                                    $user['full_name']
                                ) ?>
                            </strong>

                        </div>


                        <div class="info-row">

                            <span>
                                EMAIL ADDRESS
                            </span>

                            <strong>
                                <?= e(
                                    $user['email']
                                ) ?>
                            </strong>

                        </div>


                        <div class="info-row">

                            <span>
                                PHONE NUMBER
                            </span>

                            <strong>

                                <?= !empty(
                                    $user['phone']
                                )
                                    ? e(
                                        $user['phone']
                                    )
                                    : '—' ?>

                            </strong>

                        </div>


                        <div class="info-row">

                            <span>
                                REGISTERED
                            </span>

                            <strong>
                                <?= e(
                                    $joinedDate
                                ) ?>
                            </strong>

                        </div>


                    </div>


                </section>


                <section class="detail-panel">


                    <div class="panel-header">

                        <div>

                            <p>
                                MEMBERSHIP
                            </p>

                            <h2>
                                CURRENT PLAN
                            </h2>

                        </div>

                    </div>


                    <?php if ($membership): ?>


                        <div class="membership-content">


                            <div class="membership-plan">

                                <?= e(
                                    strtoupper(
                                        (string)$membership['plan']
                                    )
                                ) ?>

                            </div>


                            <div
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

                            </div>


                            <div class="membership-meta">


                                <div>

                                    <span>
                                        START DATE
                                    </span>

                                    <strong>
                                        <?= e(
                                            $membershipStart
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        MEMBERSHIP ID
                                    </span>

                                    <strong>
                                        #<?= (int)$membership['id'] ?>
                                    </strong>

                                </div>


                            </div>


                        </div>


                    <?php else: ?>


                        <div class="empty-state">

                            NO MEMBERSHIP FOUND

                        </div>


                    <?php endif; ?>


                </section>


            </div>


            <section class="detail-panel orders-panel">


                <div class="panel-header">

                    <div>

                        <p>
                            STORE ACTIVITY
                        </p>

                        <h2>
                            RECENT ORDERS
                        </h2>

                    </div>

                </div>


                <?php if (!empty($recentOrders)): ?>


                    <div class="table-wrap">


                        <table class="orders-table">


                            <thead>

                                <tr>

                                    <th>
                                        ORDER
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

                                    <th>
                                        ACTION
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach ($recentOrders as $order): ?>


                                <?php

                                $orderStatus =
                                    (string)(
                                        $order['status']
                                        ?? ''
                                    );

                                $orderTimestamp =
                                    strtotime(
                                        (string)$order['order_date']
                                    );

                                ?>


                                <tr>


                                    <td>
                                        #<?= (int)$order['order_id'] ?>
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

                                        <?= $orderTimestamp !== false
                                            ? date(
                                                'M d, Y',
                                                $orderTimestamp
                                            )
                                            : '—' ?>

                                    </td>


                                    <td>

                                        <a
                                            href="order_details.php?id=<?= (int)$order['order_id'] ?>"
                                            class="view-order"
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

                        NO ORDERS FOUND FOR THIS USER

                    </div>


                <?php endif; ?>


            </section>


        <?php endif; ?>


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
