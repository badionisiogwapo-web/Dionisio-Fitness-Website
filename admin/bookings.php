<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

requireAdmin();

$pageTitle = 'Bookings';

$adminNameRaw = (string)($_SESSION['admin_username'] ?? 'System Administrator');
$adminName = htmlspecialchars($adminNameRaw, ENT_QUOTES, 'UTF-8');


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$typeFilter = trim((string)($_GET['type'] ?? ''));

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'Completed',
    'Cancelled'
];

$allowedTypes = [
    'Personal Training',
    'Gym Session',
    'Group Class',
    'Boxing Session',
    'Strength Training',
    'Cardio Session'
];

if (
    $statusFilter !== '' &&
    !in_array($statusFilter, $allowedStatuses, true)
) {
    $statusFilter = '';
}

if (
    $typeFilter !== '' &&
    !in_array($typeFilter, $allowedTypes, true)
) {
    $typeFilter = '';
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

$totalBookings = 0;
$pendingBookings = 0;
$confirmedBookings = 0;
$completedBookings = 0;

try {

    $totalBookings = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM bookings'
        )
        ->fetchColumn();


    $pendingBookings = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM bookings
             WHERE status = 'Pending'"
        )
        ->fetchColumn();


    $confirmedBookings = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM bookings
             WHERE status = 'Confirmed'"
        )
        ->fetchColumn();


    $completedBookings = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM bookings
             WHERE status = 'Completed'"
        )
        ->fetchColumn();

} catch (PDOException $exception) {

    $totalBookings = 0;
    $pendingBookings = 0;
    $confirmedBookings = 0;
    $completedBookings = 0;
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
            OR b.booking_type LIKE ?
            OR CAST(b.booking_id AS CHAR) LIKE ?
        )
    ';

    $term = '%' . $search . '%';

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter !== '') {

    $whereParts[] =
        'b.status = ?';

    $params[] =
        $statusFilter;
}

if ($typeFilter !== '') {

    $whereParts[] =
        'b.booking_type = ?';

    $params[] =
        $typeFilter;
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
| FILTERED TOTAL
|--------------------------------------------------------------------------
*/

$filteredTotal = 0;

try {

    $stmt = $pdo->prepare(
        "
        SELECT COUNT(*)

        FROM bookings b

        LEFT JOIN users u
            ON u.id = b.user_id

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
| BOOKINGS
|--------------------------------------------------------------------------
*/

$bookings = [];
$databaseError = false;

try {

    $sql = "
        SELECT
            b.booking_id,
            b.user_id,
            b.booking_type,
            b.booking_date,
            b.booking_time,
            b.status,
            b.notes,
            b.created_at,
            u.full_name,
            u.email,
            u.phone

        FROM bookings b

        LEFT JOIN users u
            ON u.id = b.user_id

        {$whereSql}

        ORDER BY
            b.booking_date DESC,
            b.booking_time DESC

        LIMIT {$perPage}
        OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute(
        $params
    );

    $bookings =
        $stmt->fetchAll();

} catch (PDOException $exception) {

    $databaseError = true;
    $bookings = [];
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


function bookingStatusClass(
    string $status
): string {

    return match (
        strtolower(
            trim(
                $status
            )
        )
    ) {

        'confirmed' =>
            'status-confirmed',

        'pending' =>
            'status-pending',

        'completed' =>
            'status-completed',

        'cancelled',
        'canceled' =>
            'status-cancelled',

        default =>
            'status-none'
    };
}


function buildBookingUrl(
    int $targetPage,
    string $search,
    string $status,
    string $type
): string {

    $query = [
        'page' => $targetPage
    ];

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($status !== '') {
        $query['status'] = $status;
    }

    if ($type !== '') {
        $query['type'] = $type;
    }

    return 'bookings.php?'
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
        Bookings | Dionisio Fitness Center
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
            --blue: #69a7ff;
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

            content: "BOOKINGS";

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

            font-size: 76px;

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

            max-width: 680px;

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

        .booking-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap: 12px;

            margin-bottom: 16px;
        }


        .booking-stat {

            padding: 19px;

            background: #0f0f0f;

            border:
                1px solid
                var(--border);
        }


        .booking-stat span {

            display: block;

            margin-bottom: 12px;

            color: #626262;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: 1.3px;
        }


        .booking-stat strong {

            color: #ffffff;

            font-size: 24px;

            font-weight: 900;
        }


        .booking-stat.featured {

            background:
                linear-gradient(
                    135deg,
                    var(--red),
                    var(--red-dark)
                );

            border-color: var(--red);
        }


        .booking-stat.featured span {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.75
                );
        }


        /* =========================================================
           FILTER BAR
           ========================================================= */

        .filter-bar {

            display: grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                190px
                210px
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

        .bookings-panel {

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


        .bookings-table {

            width: 100%;

            border-collapse: collapse;
        }


        .bookings-table th {

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


        .bookings-table td {

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


        .bookings-table tbody
        tr:last-child td {

            border-bottom: 0;
        }


        .bookings-table tbody
        tr:hover {

            background: #121212;
        }


        .booking-user {

            min-width: 190px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .booking-avatar {

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


        .booking-user-copy {

            min-width: 0;

            display: flex;

            flex-direction: column;
        }


        .booking-user-copy strong {

            color: #dddddd;

            font-size: 9px;
        }


        .booking-user-copy small {

            margin-top: 4px;

            color: #565656;

            font-size: 7px;
        }


        .type-badge {

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

            letter-spacing: 0.7px;
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


        .status-confirmed {

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


        .status-completed {

            color: var(--blue);

            border-color:
                rgba(
                    105,
                    167,
                    255,
                    0.25
                );
        }


        .status-cancelled {

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

            .booking-stats {

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

                font-size: 56px;
            }


            .booking-stats {

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
            class="sidebar-link active"
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
                Bookings
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
                    BOOKING MANAGEMENT
                </span>

                <h1>
                    MANAGE
                    <em>
                        BOOKINGS.
                    </em>
                </h1>

                <p>

                    Review customer session requests,
                    booking schedules and booking status
                    from one organized admin page.

                </p>

            </div>


        </section>


        <?php if ($databaseError): ?>

            <div class="database-warning">

                Booking information could not
                be fully loaded from the database.
                Make sure the bookings table exists.

            </div>

        <?php endif; ?>


        <section class="booking-stats">


            <article class="booking-stat">

                <span>
                    TOTAL BOOKINGS
                </span>

                <strong>
                    <?= number_format(
                        $totalBookings
                    ) ?>
                </strong>

            </article>


            <article class="booking-stat">

                <span>
                    PENDING
                </span>

                <strong>
                    <?= number_format(
                        $pendingBookings
                    ) ?>
                </strong>

            </article>


            <article class="booking-stat">

                <span>
                    CONFIRMED
                </span>

                <strong>
                    <?= number_format(
                        $confirmedBookings
                    ) ?>
                </strong>

            </article>


            <article class="booking-stat featured">

                <span>
                    COMPLETED
                </span>

                <strong>
                    <?= number_format(
                        $completedBookings
                    ) ?>
                </strong>

            </article>


        </section>


        <form
            method="get"
            action="bookings.php"
            class="filter-bar"
        >


            <input
                type="search"
                name="search"
                class="field"
                placeholder="Search customer, email, type or booking ID..."
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
                name="type"
                class="field"
            >

                <option value="">
                    ALL TYPES
                </option>

                <?php foreach (
                    $allowedTypes
                    as $type
                ): ?>

                    <option
                        value="<?= e(
                            $type
                        ) ?>"
                        <?= $typeFilter === $type
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e(
                            strtoupper(
                                $type
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
                || $typeFilter !== ''
            ): ?>

                <a
                    href="bookings.php"
                    class="clear-button"
                >
                    CLEAR
                </a>

            <?php endif; ?>


        </form>


        <section class="bookings-panel">


            <div class="panel-header">


                <div>

                    <p>
                        BOOKING DATABASE
                    </p>

                    <h2>
                        CUSTOMER BOOKINGS
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


            <?php if (!empty($bookings)): ?>


                <div class="table-wrap">


                    <table class="bookings-table">


                        <thead>

                            <tr>

                                <th>
                                    BOOKING
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    TYPE
                                </th>

                                <th>
                                    DATE
                                </th>

                                <th>
                                    TIME
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $bookings
                            as $booking
                        ): ?>


                            <?php

                            $memberName =
                                trim(
                                    (string)(
                                        $booking['full_name']
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


                            $bookingDate = '—';

                            if (
                                !empty(
                                    $booking['booking_date']
                                )
                            ) {

                                $timestamp =
                                    strtotime(
                                        (string)$booking['booking_date']
                                    );

                                if ($timestamp !== false) {

                                    $bookingDate =
                                        date(
                                            'M d, Y',
                                            $timestamp
                                        );
                                }
                            }


                            $bookingTime = '—';

                            if (
                                !empty(
                                    $booking['booking_time']
                                )
                            ) {

                                $timestamp =
                                    strtotime(
                                        (string)$booking['booking_time']
                                    );

                                if ($timestamp !== false) {

                                    $bookingTime =
                                        date(
                                            'g:i A',
                                            $timestamp
                                        );
                                }
                            }

                            ?>


                            <tr>


                                <td>
                                    #<?= (int)$booking['booking_id'] ?>
                                </td>


                                <td>


                                    <div class="booking-user">


                                        <div class="booking-avatar">

                                            <?= e(
                                                $memberInitial
                                            ) ?>

                                        </div>


                                        <div class="booking-user-copy">

                                            <strong>

                                                <?= e(
                                                    $memberName !== ''
                                                        ? $memberName
                                                        : 'Unknown Customer'
                                                ) ?>

                                            </strong>

                                            <small>

                                                <?= e(
                                                    $booking['email']
                                                    ?? 'No email'
                                                ) ?>

                                            </small>

                                        </div>


                                    </div>


                                </td>


                                <td>

                                    <span class="type-badge">

                                        <?= e(
                                            strtoupper(
                                                (string)$booking['booking_type']
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= e(
                                        $bookingDate
                                    ) ?>

                                </td>


                                <td>

                                    <?= e(
                                        $bookingTime
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="status-badge
                                        <?= bookingStatusClass(
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


                                <td>

                                    <a
                                        href="view_bookings.php?id=<?= (int)$booking['booking_id'] ?>"
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

                    NO BOOKINGS FOUND

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
                                buildBookingUrl(
                                    max(
                                        1,
                                        $page - 1
                                    ),
                                    $search,
                                    $statusFilter,
                                    $typeFilter
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
                                    buildBookingUrl(
                                        $i,
                                        $search,
                                        $statusFilter,
                                        $typeFilter
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
                                buildBookingUrl(
                                    min(
                                        $totalPages,
                                        $page + 1
                                    ),
                                    $search,
                                    $statusFilter,
                                    $typeFilter
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
