<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

requireAdmin();

$pageTitle = 'Memberships';

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
$statusFilter = trim((string)($_GET['status'] ?? ''));
$planFilter = trim((string)($_GET['plan'] ?? ''));

$allowedStatuses = [
    'Active',
    'Pending',
    'Inactive'
];

$allowedPlans = [
    'Basic',
    'Premium',
    'VIP'
];

if (
    $statusFilter !== '' &&
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {
    $statusFilter = '';
}

if (
    $planFilter !== '' &&
    !in_array(
        $planFilter,
        $allowedPlans,
        true
    )
) {
    $planFilter = '';
}

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$perPage = 10;


/*
|--------------------------------------------------------------------------
| SUMMARY COUNTS
|--------------------------------------------------------------------------
*/

$totalMemberships = 0;
$activeMemberships = 0;
$pendingMemberships = 0;
$inactiveMemberships = 0;

try {

    $totalMemberships = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM memberships'
        )
        ->fetchColumn();


    $activeMemberships = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM memberships
             WHERE status = 'Active'"
        )
        ->fetchColumn();


    $pendingMemberships = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM memberships
             WHERE status = 'Pending'"
        )
        ->fetchColumn();


    $inactiveMemberships = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM memberships
             WHERE status = 'Inactive'"
        )
        ->fetchColumn();

} catch (PDOException $exception) {

    $totalMemberships = 0;
    $activeMemberships = 0;
    $pendingMemberships = 0;
    $inactiveMemberships = 0;
}


/*
|--------------------------------------------------------------------------
| BUILD FILTER QUERY
|--------------------------------------------------------------------------
*/

$whereParts = [];
$params = [];

if ($search !== '') {

    $whereParts[] = '
        (
            u.full_name LIKE ?
            OR u.email LIKE ?
            OR m.plan LIKE ?
        )
    ';

    $term =
        '%' . $search . '%';

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter !== '') {

    $whereParts[] =
        'm.status = ?';

    $params[] =
        $statusFilter;
}

if ($planFilter !== '') {

    $whereParts[] =
        'm.plan = ?';

    $params[] =
        $planFilter;
}

$whereSql = '';

if (!empty($whereParts)) {

    $whereSql =
        'WHERE '
        . implode(
            ' AND ',
            $whereParts
        );
}


/*
|--------------------------------------------------------------------------
| FILTERED COUNT
|--------------------------------------------------------------------------
*/

$filteredTotal = 0;

try {

    $stmt = $pdo->prepare(
        "
        SELECT COUNT(*)

        FROM memberships m

        LEFT JOIN users u
            ON u.id = m.user_id

        {$whereSql}
        "
    );

    $stmt->execute(
        $params
    );

    $filteredTotal =
        (int)$stmt->fetchColumn();

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
        $filteredTotal
        / $perPage
    )
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1)
    * $perPage;


/*
|--------------------------------------------------------------------------
| MEMBERSHIPS
|--------------------------------------------------------------------------
*/

$memberships = [];
$databaseError = false;

try {

    $sql = "
        SELECT
            m.id,
            m.user_id,
            m.plan,
            m.status,
            m.start_date,
            u.full_name,
            u.email,
            u.phone

        FROM memberships m

        LEFT JOIN users u
            ON u.id = m.user_id

        {$whereSql}

        ORDER BY m.id DESC

        LIMIT {$perPage}
        OFFSET {$offset}
    ";

    $stmt =
        $pdo->prepare(
            $sql
        );

    $stmt->execute(
        $params
    );

    $memberships =
        $stmt->fetchAll();

} catch (PDOException $exception) {

    $databaseError = true;
    $memberships = [];
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


function membershipStatusClass(
    string $status
): string {

    return match (
        strtolower(
            trim(
                $status
            )
        )
    ) {

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


function buildMembershipUrl(
    int $targetPage,
    string $search,
    string $status,
    string $plan
): string {

    $query = [
        'page' =>
            $targetPage
    ];

    if ($search !== '') {

        $query['search'] =
            $search;
    }

    if ($status !== '') {

        $query['status'] =
            $status;
    }

    if ($plan !== '') {

        $query['plan'] =
            $plan;
    }

    return 'memberships.php?'
        . http_build_query(
            $query
        );
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
        Memberships | Dionisio Fitness Center
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
        select {
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
           HEADER
           ========================================================= */

        .page-header {

            position: relative;

            min-height: 170px;

            display: flex;

            align-items: flex-end;

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

            content: "MEMBERS";

            position: absolute;

            top: -16px;

            right: 18px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.02
                );

            font-size: 84px;

            font-weight: 900;

            letter-spacing: -5px;

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

            letter-spacing: 2.4px;
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

        .membership-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap: 12px;

            margin-bottom: 16px;
        }


        .membership-stat {

            padding: 19px;

            background: #0f0f0f;

            border:
                1px solid
                var(--border);
        }


        .membership-stat span {

            display: block;

            margin-bottom: 12px;

            color: #626262;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.3px;
        }


        .membership-stat strong {

            color: #ffffff;

            font-size: 24px;

            font-weight: 900;
        }


        .membership-stat.featured {

            background:
                linear-gradient(
                    135deg,
                    var(--red),
                    var(--red-dark)
                );

            border-color: var(--red);
        }


        .membership-stat.featured span {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.75
                );
        }


        /* =========================================================
           FILTERS
           ========================================================= */

        .filter-bar {

            display: grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                180px
                180px
                auto
                auto;

            gap: 9px;

            padding: 15px;

            margin-bottom: 14px;

            background: #0d0d0d;

            border:
                1px solid
                var(--border);
        }


        .field {

            min-height: 42px;

            width: 100%;

            padding:
                0
                13px;

            outline: none;

            background: #111111;

            border:
                1px solid
                #292929;

            color: #ffffff;

            font-size: 9px;
        }


        .field:focus {

            border-color: var(--red);
        }


        .field::placeholder {

            color: #555555;
        }


        .filter-button,
        .clear-button {

            min-height: 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0
                15px;

            text-decoration: none;

            border: 0;

            cursor: pointer;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .filter-button {

            background: var(--red);

            color: #ffffff;
        }


        .clear-button {

            background: #171717;

            border:
                1px solid
                #292929;

            color: #888888;
        }


        .clear-button:hover {

            color: #ffffff;
        }


        /* =========================================================
           TABLE
           ========================================================= */

        .memberships-panel {

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


        .panel-count {

            color: #656565;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1px;
        }


        .table-wrap {

            overflow-x: auto;
        }


        .memberships-table {

            width: 100%;

            border-collapse: collapse;
        }


        .memberships-table th {

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


        .memberships-table td {

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


        .memberships-table tbody
        tr:last-child td {

            border-bottom: 0;
        }


        .memberships-table tbody
        tr:hover {

            background: #121212;
        }


        .member-cell {

            min-width: 190px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .member-avatar {

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


        .member-copy {

            min-width: 0;

            display: flex;

            flex-direction: column;
        }


        .member-copy strong {

            color: #dddddd;

            font-size: 9px;
        }


        .member-copy small {

            margin-top: 4px;

            color: #565656;

            font-size: 7px;
        }


        .plan-badge {

            display: inline-flex;

            align-items: center;

            min-height: 25px;

            padding:
                0
                9px;

            color: #dddddd;

            border:
                1px solid
                #303030;

            background: #111111;

            font-size: 7px;

            font-weight: 900;

            letter-spacing: 0.8px;
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


        .status-active {

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


        .status-pending {

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


        .status-inactive {

            color: #ef686e;

            border-color:
                rgba(
                    239,
                    104,
                    110,
                    0.25
                );
        }


        .status-none {

            color: #777777;
        }


        .view-link {

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

            letter-spacing: 0.8px;

            border:
                1px solid
                rgba(
                    215,
                    25,
                    32,
                    0.28
                );
        }


        .view-link:hover {

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

            justify-content:
                space-between;

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
            max-width: 1150px
        ) {

            .membership-stats {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }


            .filter-bar {

                grid-template-columns:
                    1fr
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

            .page-header {

                min-height: 0;

                padding: 23px;
            }


            .page-header::after {

                font-size: 62px;
            }


            .membership-stats {

                grid-template-columns:
                    1fr;
            }


            .filter-bar {

                grid-template-columns:
                    1fr;
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
            class="sidebar-link active"
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
                Memberships
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


        <section class="page-header">


            <div class="page-header-copy">

                <div class="page-header-line"></div>

                <span>
                    MEMBERSHIP MANAGEMENT
                </span>

                <h1>
                    MANAGE
                    <em>
                        MEMBERS.
                    </em>
                </h1>

                <p>

                    Review membership plans,
                    account status and member
                    information from one place.

                </p>

            </div>


        </section>


        <?php if ($databaseError): ?>

            <div class="database-warning">

                Membership information could not
                be fully loaded from the database.

            </div>

        <?php endif; ?>


        <section class="membership-stats">


            <article class="membership-stat">

                <span>
                    TOTAL MEMBERSHIPS
                </span>

                <strong>
                    <?= number_format(
                        $totalMemberships
                    ) ?>
                </strong>

            </article>


            <article class="membership-stat">

                <span>
                    ACTIVE
                </span>

                <strong>
                    <?= number_format(
                        $activeMemberships
                    ) ?>
                </strong>

            </article>


            <article class="membership-stat">

                <span>
                    PENDING
                </span>

                <strong>
                    <?= number_format(
                        $pendingMemberships
                    ) ?>
                </strong>

            </article>


            <article class="membership-stat featured">

                <span>
                    INACTIVE
                </span>

                <strong>
                    <?= number_format(
                        $inactiveMemberships
                    ) ?>
                </strong>

            </article>


        </section>


        <form
            method="get"
            action="memberships.php"
            class="filter-bar"
        >


            <input
                type="search"
                name="search"
                class="field"
                placeholder="Search member, email or plan..."
                value="<?= e(
                    $search
                ) ?>"
            >


            <select
                name="status"
                class="field"
            >

                <option value="">
                    ALL STATUS
                </option>

                <?php foreach (
                    $allowedStatuses
                    as $status
                ): ?>

                    <option
                        value="<?= e(
                            $status
                        ) ?>"
                        <?= $statusFilter === $status
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e(
                            strtoupper(
                                $status
                            )
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <select
                name="plan"
                class="field"
            >

                <option value="">
                    ALL PLANS
                </option>

                <?php foreach (
                    $allowedPlans
                    as $plan
                ): ?>

                    <option
                        value="<?= e(
                            $plan
                        ) ?>"
                        <?= $planFilter === $plan
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e(
                            strtoupper(
                                $plan
                            )
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <button
                type="submit"
                class="filter-button"
            >
                FILTER
            </button>


            <?php if (
                $search !== ''
                || $statusFilter !== ''
                || $planFilter !== ''
            ): ?>

                <a
                    href="memberships.php"
                    class="clear-button"
                >
                    CLEAR
                </a>

            <?php endif; ?>


        </form>


        <section class="memberships-panel">


            <div class="panel-header">


                <div>

                    <p>
                        MEMBER DATABASE
                    </p>

                    <h2>
                        MEMBERSHIPS
                    </h2>

                </div>


                <div class="panel-count">

                    <?= number_format(
                        $filteredTotal
                    ) ?>

                    RESULT<?= $filteredTotal === 1
                        ? ''
                        : 'S' ?>

                </div>


            </div>


            <?php if (!empty($memberships)): ?>


                <div class="table-wrap">


                    <table class="memberships-table">


                        <thead>

                            <tr>

                                <th>
                                    MEMBER
                                </th>

                                <th>
                                    PLAN
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    START DATE
                                </th>

                                <th>
                                    PHONE
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $memberships
                            as $membership
                        ): ?>


                            <?php

                            $memberName =
                                trim(
                                    (string)(
                                        $membership['full_name']
                                        ?? ''
                                    )
                                );

                            $memberInitial =
                                $memberName !== ''
                                    ? strtoupper(
                                        substr(
                                            $memberName,
                                            0,
                                            1
                                        )
                                    )
                                    : 'U';

                            $startDate =
                                '—';

                            if (
                                !empty(
                                    $membership['start_date']
                                )
                            ) {

                                $timestamp =
                                    strtotime(
                                        (string)$membership['start_date']
                                    );

                                if (
                                    $timestamp !== false
                                ) {

                                    $startDate =
                                        date(
                                            'M d, Y',
                                            $timestamp
                                        );
                                }
                            }

                            ?>


                            <tr>


                                <td>


                                    <div class="member-cell">


                                        <div class="member-avatar">

                                            <?= e(
                                                $memberInitial
                                            ) ?>

                                        </div>


                                        <div class="member-copy">

                                            <strong>

                                                <?= e(
                                                    $memberName !== ''
                                                        ? $memberName
                                                        : 'Unknown Member'
                                                ) ?>

                                            </strong>

                                            <small>

                                                <?= e(
                                                    $membership['email']
                                                    ?? 'No email'
                                                ) ?>

                                            </small>

                                        </div>


                                    </div>


                                </td>


                                <td>

                                    <span class="plan-badge">

                                        <?= e(
                                            strtoupper(
                                                (string)$membership['plan']
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="status-badge
                                        <?= membershipStatusClass(
                                            (string)$membership['status']
                                        ) ?>"
                                    >

                                        <?= e(
                                            strtoupper(
                                                (string)$membership['status']
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= e(
                                        $startDate
                                    ) ?>

                                </td>


                                <td>

                                    <?= !empty(
                                        $membership['phone']
                                    )
                                        ? e(
                                            $membership['phone']
                                        )
                                        : '—' ?>

                                </td>


                                <td>

                                    <a
                                        href="view_users.php?id=<?= (int)$membership['user_id'] ?>"
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

                    NO MEMBERSHIPS FOUND

                </div>


            <?php endif; ?>


            <?php if (
                $filteredTotal > 0
            ): ?>


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
                            href="<?= e(
                                buildMembershipUrl(
                                    max(
                                        1,
                                        $page - 1
                                    ),
                                    $search,
                                    $statusFilter,
                                    $planFilter
                                )
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
                                href="<?= e(
                                    buildMembershipUrl(
                                        $i,
                                        $search,
                                        $statusFilter,
                                        $planFilter
                                    )
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
                            href="<?= e(
                                buildMembershipUrl(
                                    min(
                                        $totalPages,
                                        $page + 1
                                    ),
                                    $search,
                                    $statusFilter,
                                    $planFilter
                                )
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
