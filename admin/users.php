<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

requireAdmin();

$pageTitle = 'Users';

$currentPage = basename($_SERVER['PHP_SELF']);

$adminNameRaw = (string)($_SESSION['admin_username'] ?? 'System Administrator');

$adminName = htmlspecialchars(
    $adminNameRaw,
    ENT_QUOTES,
    'UTF-8'
);


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search = trim((string)($_GET['search'] ?? ''));

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$perPage = 10;


/*
|--------------------------------------------------------------------------
| USER COUNTS
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$newUsersThisMonth = 0;
$usersWithActiveMembership = 0;

try {

    $totalUsers = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM users'
        )
        ->fetchColumn();


    $newUsersThisMonth = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM users
             WHERE YEAR(created_at) = YEAR(CURRENT_DATE())
               AND MONTH(created_at) = MONTH(CURRENT_DATE())"
        )
        ->fetchColumn();


    $usersWithActiveMembership = (int)$pdo
        ->query(
            "SELECT COUNT(DISTINCT user_id)
             FROM memberships
             WHERE status = 'Active'"
        )
        ->fetchColumn();

} catch (PDOException $exception) {

    $totalUsers = 0;
    $newUsersThisMonth = 0;
    $usersWithActiveMembership = 0;
}


/*
|--------------------------------------------------------------------------
| FILTERED TOTAL
|--------------------------------------------------------------------------
*/

$whereSql = '';
$params = [];

if ($search !== '') {

    $whereSql = '
        WHERE
            u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
    ';

    $term = '%' . $search . '%';

    $params = [
        $term,
        $term,
        $term
    ];
}


try {

    $countStmt = $pdo->prepare(
        "
        SELECT COUNT(*)
        FROM users u
        {$whereSql}
        "
    );

    $countStmt->execute($params);

    $filteredTotal =
        (int)$countStmt->fetchColumn();

} catch (PDOException $exception) {

    $filteredTotal = 0;
}


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int)ceil(
        $filteredTotal / $perPage
    )
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

$users = [];
$databaseError = false;

try {

    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.created_at,

            (
                SELECT m.plan
                FROM memberships m
                WHERE m.user_id = u.id
                ORDER BY m.id DESC
                LIMIT 1
            ) AS membership_plan,

            (
                SELECT m.status
                FROM memberships m
                WHERE m.user_id = u.id
                ORDER BY m.id DESC
                LIMIT 1
            ) AS membership_status

        FROM users u

        {$whereSql}

        ORDER BY u.created_at DESC

        LIMIT {$perPage}
        OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $users = $stmt->fetchAll();

} catch (PDOException $exception) {

    $databaseError = true;
    $users = [];
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function userStatusClass(?string $status): string
{
    $status = strtolower(
        trim(
            (string)$status
        )
    );

    return match ($status) {

        'active' =>
            'status-active',

        'pending' =>
            'status-pending',

        'inactive',
        'cancelled',
        'canceled' =>
            'status-inactive',

        default =>
            'status-none'
    };
}


function buildUsersPageUrl(
    int $targetPage,
    string $search
): string {

    $query = [
        'page' => $targetPage
    ];

    if ($search !== '') {
        $query['search'] = $search;
    }

    return 'users.php?'
        . http_build_query($query);
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
        Users | Dionisio Fitness Center
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
        input {
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
           PAGE HEADER
           ========================================================= */

        .page-header {

            position: relative;

            min-height: 170px;

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

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


        .page-header::after {

            content: "USERS";

            position: absolute;

            top: -15px;

            right: 20px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.025
                );

            font-size: 96px;

            font-weight: 900;

            letter-spacing: -6px;

            pointer-events: none;
        }


        .page-header-copy {

            position: relative;

            z-index: 2;
        }


        .page-header-line {

            width: 42px;

            height: 4px;

            margin-bottom: 13px;

            background: var(--red);
        }


        .page-header-copy > span {

            color: var(--red);

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 2.5px;
        }


        .page-header-copy h1 {

            margin:
                8px
                0
                0;

            font-size:
                clamp(
                    30px,
                    4vw,
                    46px
                );

            font-weight: 900;

            line-height: 1;

            letter-spacing: -2px;
        }


        .page-header-copy h1 em {

            color: var(--red);

            font-style: normal;
        }


        .page-header-copy p {

            max-width: 650px;

            margin:
                13px
                0
                0;

            color: #858585;

            font-size: 11px;

            line-height: 1.7;
        }


        /* =========================================================
           STATS
           ========================================================= */

        .user-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

            gap: 12px;

            margin-bottom: 16px;
        }


        .user-stat {

            padding: 19px;

            background: #0f0f0f;

            border:
                1px solid
                var(--border);
        }


        .user-stat span {

            display: block;

            margin-bottom: 12px;

            color: #626262;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.4px;
        }


        .user-stat strong {

            color: #ffffff;

            font-size: 24px;

            font-weight: 900;
        }


        .user-stat.featured {

            background:
                linear-gradient(
                    135deg,
                    var(--red),
                    var(--red-dark)
                );

            border-color: var(--red);
        }


        .user-stat.featured span {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.75
                );
        }


        /* =========================================================
           TOOLBAR
           ========================================================= */

        .users-toolbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 16px;

            margin-bottom: 14px;

            background: #0d0d0d;

            border:
                1px solid
                var(--border);
        }


        .search-form {

            flex:
                1
                1
                520px;

            display: flex;

            gap: 9px;
        }


        .search-input {

            width: 100%;

            min-height: 42px;

            padding:
                0
                14px;

            outline: none;

            background: #111111;

            border:
                1px solid
                #2a2a2a;

            color: #ffffff;

            font-size: 10px;

            transition:
                border-color 0.2s ease;
        }


        .search-input:focus {

            border-color: var(--red);
        }


        .search-input::placeholder {

            color: #555555;
        }


        .search-button,
        .clear-button {

            min-height: 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0
                16px;

            border: 0;

            cursor: pointer;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .search-button {

            background: var(--red);

            color: #ffffff;
        }


        .search-button:hover {

            background: #ef222a;
        }


        .clear-button {

            background: #171717;

            border:
                1px solid
                #2a2a2a;

            color: #888888;
        }


        .clear-button:hover {

            color: #ffffff;
        }


        .results-count {

            flex:
                0
                0
                auto;

            color: #666666;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        /* =========================================================
           PANEL + TABLE
           ========================================================= */

        .users-panel {

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

            justify-content: space-between;

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


        .table-wrap {

            overflow-x: auto;
        }


        .users-table {

            width: 100%;

            border-collapse: collapse;
        }


        .users-table th {

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


        .users-table td {

            padding:
                14px
                16px;

            color: #9c9c9c;

            font-size: 9px;

            vertical-align: middle;

            border-bottom:
                1px solid
                #1b1b1b;
        }


        .users-table tbody
        tr:last-child td {

            border-bottom: 0;
        }


        .users-table tbody
        tr:hover {

            background: #121212;
        }


        .user-cell {

            min-width: 180px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .user-avatar {

            width: 34px;

            height: 34px;

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
                    0.13
                );

            color: #ef555c;

            font-size: 10px;

            font-weight: 900;
        }


        .user-copy {

            min-width: 0;

            display: flex;

            flex-direction: column;
        }


        .user-copy strong {

            color: #dddddd;

            font-size: 9px;

            font-weight: 800;
        }


        .user-copy small {

            margin-top: 4px;

            color: #565656;

            font-size: 7px;
        }


        .membership-badge {

            display: inline-flex;

            align-items: center;

            min-height: 25px;

            padding:
                0
                9px;

            border:
                1px solid
                #2b2b2b;

            color: #b6b6b6;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 0.8px;
        }


        .membership-badge.status-active {

            color: var(--green);

            border-color:
                rgba(
                    95,
                    208,
                    138,
                    0.24
                );

            background:
                rgba(
                    95,
                    208,
                    138,
                    0.05
                );
        }


        .membership-badge.status-pending {

            color: var(--yellow);

            border-color:
                rgba(
                    231,
                    185,
                    79,
                    0.24
                );

            background:
                rgba(
                    231,
                    185,
                    79,
                    0.05
                );
        }


        .membership-badge.status-inactive {

            color: #ef686e;

            border-color:
                rgba(
                    239,
                    104,
                    110,
                    0.24
                );
        }


        .membership-badge.status-none {

            color: #666666;
        }


        .action-link {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 32px;

            padding:
                0
                11px;

            color: var(--red);

            text-decoration: none;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 0.9px;

            border:
                1px solid
                rgba(
                    215,
                    25,
                    32,
                    0.28
                );

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        .action-link:hover {

            background: var(--red);

            color: #ffffff;
        }


        .empty-state {

            padding: 48px 20px;

            color: #555555;

            text-align: center;

            font-size: 9px;

            font-weight: 900;

            letter-spacing: 1.3px;
        }


        .database-warning {

            margin-bottom: 14px;

            padding: 14px 16px;

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
                    0.25
                );

            color: #ed858a;

            font-size: 10px;
        }


        /* =========================================================
           PAGINATION
           ========================================================= */

        .pagination {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 14px;

            padding: 16px;

            border-top:
                1px solid
                #202020;
        }


        .pagination-info {

            color: #5d5d5d;

            font-size: 8px;

            font-weight: 800;

            letter-spacing: 0.8px;
        }


        .pagination-links {

            display: flex;

            align-items: center;

            gap: 6px;
        }


        .page-link {

            min-width: 34px;

            height: 34px;

            display: grid;

            place-items: center;

            padding:
                0
                8px;

            background: #111111;

            border:
                1px solid
                #292929;

            color: #777777;

            text-decoration: none;

            font-size: 8px;

            font-weight: 900;

            transition:
                border-color 0.2s ease,
                color 0.2s ease,
                background 0.2s ease;
        }


        .page-link:hover {

            color: #ffffff;

            border-color: #3b3b3b;
        }


        .page-link.active {

            background: var(--red);

            border-color: var(--red);

            color: #ffffff;
        }


        .page-link.disabled {

            opacity: 0.35;

            pointer-events: none;
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
            max-width: 1000px
        ) {

            .user-stats {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }

            .users-toolbar {

                align-items: stretch;

                flex-direction: column;
            }

            .results-count {

                padding-left: 2px;
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
            max-width: 640px
        ) {

            .page-header {

                min-height: 0;

                padding: 23px;
            }


            .page-header::after {

                font-size: 70px;
            }


            .user-stats {

                grid-template-columns:
                    1fr;
            }


            .search-form {

                flex-direction: column;
            }


            .search-button,
            .clear-button {

                width: 100%;
            }


            .pagination {

                align-items:
                    flex-start;

                flex-direction:
                    column;
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

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            $adminNameRaw,
                            0,
                            1
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
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
                Users
            </strong>

        </div>


        <div class="topbar-actions">

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


        <section class="page-header">


            <div class="page-header-copy">

                <div class="page-header-line"></div>

                <span>
                    MEMBER DATABASE
                </span>

                <h1>
                    MANAGE
                    <em>
                        USERS.
                    </em>
                </h1>

                <p>
                    Review registered Dionisio Fitness Center
                    accounts, membership status and customer
                    information.
                </p>

            </div>


        </section>


        <?php if ($databaseError): ?>

            <div class="database-warning">

                User information could not be fully
                loaded from the database.

            </div>

        <?php endif; ?>


        <section class="user-stats">


            <article class="user-stat">

                <span>
                    TOTAL REGISTERED USERS
                </span>

                <strong>
                    <?= number_format(
                        $totalUsers
                    ) ?>
                </strong>

            </article>


            <article class="user-stat">

                <span>
                    ACTIVE MEMBERS
                </span>

                <strong>
                    <?= number_format(
                        $usersWithActiveMembership
                    ) ?>
                </strong>

            </article>


            <article class="user-stat featured">

                <span>
                    NEW THIS MONTH
                </span>

                <strong>
                    <?= number_format(
                        $newUsersThisMonth
                    ) ?>
                </strong>

            </article>


        </section>


        <section class="users-toolbar">


            <form
                method="get"
                action="users.php"
                class="search-form"
            >


                <input
                    type="search"
                    name="search"
                    class="search-input"
                    placeholder="Search name, email or phone..."
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <button
                    type="submit"
                    class="search-button"
                >
                    SEARCH
                </button>


                <?php if ($search !== ''): ?>

                    <a
                        href="users.php"
                        class="clear-button"
                    >
                        CLEAR
                    </a>

                <?php endif; ?>


            </form>


            <div class="results-count">

                <?= number_format(
                    $filteredTotal
                ) ?>

                RESULT<?= $filteredTotal === 1 ? '' : 'S' ?>

            </div>


        </section>


        <section class="users-panel">


            <div class="panel-header">

                <div>

                    <p>
                        USER MANAGEMENT
                    </p>

                    <h2>
                        REGISTERED USERS
                    </h2>

                </div>

            </div>


            <?php if (!empty($users)): ?>


                <div class="table-wrap">


                    <table class="users-table">


                        <thead>

                            <tr>

                                <th>
                                    USER
                                </th>

                                <th>
                                    EMAIL
                                </th>

                                <th>
                                    PHONE
                                </th>

                                <th>
                                    MEMBERSHIP
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


                        <?php foreach ($users as $user): ?>


                            <?php

                            $membershipPlan =
                                $user['membership_plan']
                                ?? null;

                            $membershipStatus =
                                $user['membership_status']
                                ?? null;

                            ?>


                            <tr>


                                <td>


                                    <div class="user-cell">


                                        <div class="user-avatar">

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    substr(
                                                        (string)$user['full_name'],
                                                        0,
                                                        1
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <div class="user-copy">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    (string)$user['full_name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </strong>

                                            <small>

                                                ID:
                                                <?= (int)$user['id'] ?>

                                            </small>

                                        </div>


                                    </div>


                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        (string)$user['email'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        (string)(
                                            $user['phone']
                                            ?: '—'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>


                                    <span
                                        class="membership-badge
                                        <?= userStatusClass(
                                            is_string(
                                                $membershipStatus
                                            )
                                                ? $membershipStatus
                                                : null
                                        ) ?>"
                                    >

                                        <?php if ($membershipPlan): ?>

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    (string)$membershipPlan
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                            ·

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    (string)$membershipStatus
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        <?php else: ?>

                                            NO MEMBERSHIP

                                        <?php endif; ?>

                                    </span>


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
    class="action-link"
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

                    <?php if ($search !== ''): ?>

                        NO USERS MATCHED YOUR SEARCH

                    <?php else: ?>

                        NO REGISTERED USERS FOUND

                    <?php endif; ?>

                </div>


            <?php endif; ?>


            <?php if ($filteredTotal > 0): ?>


                <div class="pagination">


                    <div class="pagination-info">

                        PAGE
                        <?= number_format(
                            $page
                        ) ?>

                        OF

                        <?= number_format(
                            $totalPages
                        ) ?>

                    </div>


                    <div class="pagination-links">


                        <a
                            href="<?= htmlspecialchars(
                                buildUsersPageUrl(
                                    max(
                                        1,
                                        $page - 1
                                    ),
                                    $search
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="page-link
                            <?= $page <= 1
                                ? 'disabled'
                                : '' ?>"
                        >
                            ←
                        </a>


                        <?php

                        $startPage =
                            max(
                                1,
                                $page - 2
                            );

                        $endPage =
                            min(
                                $totalPages,
                                $page + 2
                            );

                        ?>


                        <?php for (
                            $i = $startPage;
                            $i <= $endPage;
                            $i++
                        ): ?>


                            <a
                                href="<?= htmlspecialchars(
                                    buildUsersPageUrl(
                                        $i,
                                        $search
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="page-link
                                <?= $i === $page
                                    ? 'active'
                                    : '' ?>"
                            >
                                <?= $i ?>
                            </a>


                        <?php endfor; ?>


                        <a
                            href="<?= htmlspecialchars(
                                buildUsersPageUrl(
                                    min(
                                        $totalPages,
                                        $page + 1
                                    ),
                                    $search
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="page-link
                            <?= $page >= $totalPages
                                ? 'disabled'
                                : '' ?>"
                        >
                            →
                        </a>


                    </div>


                </div>


            <?php endif; ?>


        </section>


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
