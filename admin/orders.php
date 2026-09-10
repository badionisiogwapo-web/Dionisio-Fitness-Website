<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

requireAdmin();

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function h(mixed $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function orderStatusClass(string $status): string
{
    return match (strtolower(trim($status))) {
        'completed', 'paid' => 'status-success',
        'processing' => 'status-processing',
        'pending' => 'status-warning',
        'cancelled', 'canceled' => 'status-danger',
        default => 'status-neutral',
    };
}

function adminOrderCsrfToken(): string
{
    if (empty($_SESSION['admin_order_csrf_token'])) {
        $_SESSION['admin_order_csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['admin_order_csrf_token'];
}

function verifyAdminOrderCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['admin_order_csrf_token'])
        && hash_equals(
            (string)$_SESSION['admin_order_csrf_token'],
            $token
        );
}

function orderDate(?string $value): string
{
    if (!$value) {
        return '—';
    }

    $time = strtotime($value);

    return $time !== false
        ? date('M d, Y h:i A', $time)
        : '—';
}


/*
|--------------------------------------------------------------------------
| PAGE STATE
|--------------------------------------------------------------------------
*/

$pageTitle = 'Orders';

$search = trim(
    (string)($_GET['search'] ?? '')
);

$statusFilter = trim(
    (string)($_GET['status'] ?? '')
);

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$perPage = 10;

$allowedStatuses = [
    'Pending',
    'Processing',
    'Completed',
    'Cancelled'
];

if (
    $statusFilter !== ''
    && !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {
    $statusFilter = '';
}

$success = '';
$error = '';
$databaseError = false;


/*
|--------------------------------------------------------------------------
| ORDER STATUS UPDATE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderId = filter_input(
        INPUT_POST,
        'order_id',
        FILTER_VALIDATE_INT
    );

    $newStatus = trim(
        (string)($_POST['status'] ?? '')
    );

    $token = $_POST['csrf_token'] ?? null;

    if (
        !verifyAdminOrderCsrf(
            is_string($token) ? $token : null
        )
    ) {

        $error =
            'Your session token is invalid. Refresh the page and try again.';

    } elseif (!$orderId || $orderId < 1) {

        $error = 'Invalid order ID.';

    } elseif (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $error = 'Invalid order status.';

    } else {

        try {

            /*
             * Completed orders are FINAL.
             * Check the current status before allowing an update.
             */
            $currentStmt = $pdo->prepare(
                "SELECT status
                 FROM orders
                 WHERE order_id = ?
                 LIMIT 1"
            );

            $currentStmt->execute([
                $orderId
            ]);

            $currentOrderStatus =
                $currentStmt->fetchColumn();

            if ($currentOrderStatus === false) {

                $error = 'Order not found.';

            } elseif (
                strcasecmp(
                    (string)$currentOrderStatus,
                    'Completed'
                ) === 0
            ) {

                $error =
                    'Completed orders are final and can no longer be updated.';

            } else {

                $stmt = $pdo->prepare(
                    "UPDATE orders
                     SET status = ?
                     WHERE order_id = ?
                       AND status <> 'Completed'"
                );

                $stmt->execute([
                    $newStatus,
                    $orderId
                ]);

                if ($stmt->rowCount() > 0) {
                    $success =
                        'Order #' .
                        (int)$orderId .
                        ' updated to ' .
                        $newStatus .
                        '.';
                } else {
                    $success =
                        'Order #' .
                        (int)$orderId .
                        ' is already set to ' .
                        $newStatus .
                    '.';
                }
            }

        } catch (PDOException $exception) {

            error_log($exception->getMessage());

            $error =
                'The order status could not be updated. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalOrders = 0;
$pendingOrders = 0;
$processingOrders = 0;
$completedOrders = 0;
$totalSales = 0.00;

try {

    $totalOrders = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM orders"
        )
        ->fetchColumn();


    $pendingOrders = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM orders
             WHERE status = 'Pending'"
        )
        ->fetchColumn();


    $processingOrders = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM orders
             WHERE status = 'Processing'"
        )
        ->fetchColumn();


    $completedOrders = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM orders
             WHERE status = 'Completed'"
        )
        ->fetchColumn();


    $totalSales = (float)$pdo
        ->query(
            "SELECT COALESCE(
                SUM(total_amount),
                0
             )
             FROM orders
             WHERE status <> 'Cancelled'"
        )
        ->fetchColumn();

} catch (PDOException $exception) {

    error_log($exception->getMessage());

    $databaseError = true;
}


/*
|--------------------------------------------------------------------------
| SEARCH / FILTER
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($search !== '') {

    $where[] =
        "(
            CAST(o.order_id AS CHAR) LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
        )";

    $term = '%' . $search . '%';

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter !== '') {

    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql =
        ' WHERE ' .
        implode(
            ' AND ',
            $where
        );
}


/*
|--------------------------------------------------------------------------
| COUNT FILTERED ORDERS
|--------------------------------------------------------------------------
*/

$totalFiltered = 0;

try {

    $countSql =
        "SELECT COUNT(*)
         FROM orders o
         LEFT JOIN users u
            ON u.id = o.user_id" .
        $whereSql;

    $countStmt = $pdo->prepare(
        $countSql
    );

    $countStmt->execute(
        $params
    );

    $totalFiltered =
        (int)$countStmt->fetchColumn();

} catch (PDOException $exception) {

    error_log($exception->getMessage());

    $databaseError = true;
}

$totalPages = max(
    1,
    (int)ceil(
        $totalFiltered / $perPage
    )
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| LOAD ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];

try {

    $sql =
        "SELECT
            o.order_id,
            o.user_id,
            o.total_amount,
            o.status,
            o.order_date,
            u.full_name,
            u.email,
            (
                SELECT COALESCE(
                    SUM(oi.quantity),
                    0
                )
                FROM order_items oi
                WHERE oi.order_id = o.order_id
            ) AS item_count
         FROM orders o
         LEFT JOIN users u
            ON u.id = o.user_id" .
        $whereSql .
        " ORDER BY
            o.order_date DESC,
            o.order_id DESC
          LIMIT {$perPage}
          OFFSET {$offset}";

    $stmt = $pdo->prepare(
        $sql
    );

    $stmt->execute(
        $params
    );

    $orders = $stmt->fetchAll();

} catch (PDOException $exception) {

    error_log($exception->getMessage());

    /*
     * Fallback if order_items is not present yet.
     * The page can still manage orders.
     */
    try {

        $sql =
            "SELECT
                o.order_id,
                o.user_id,
                o.total_amount,
                o.status,
                o.order_date,
                u.full_name,
                u.email,
                0 AS item_count
             FROM orders o
             LEFT JOIN users u
                ON u.id = o.user_id" .
            $whereSql .
            " ORDER BY
                o.order_date DESC,
                o.order_id DESC
              LIMIT {$perPage}
              OFFSET {$offset}";

        $stmt = $pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        $orders = $stmt->fetchAll();

    } catch (PDOException $fallbackException) {

        error_log(
            $fallbackException->getMessage()
        );

        $databaseError = true;
        $orders = [];
    }
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
        <?= h($pageTitle) ?>
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

        /* SIDEBAR */

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
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
            padding: 0 22px;
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
            padding: 0 12px;
        }

        .sidebar-link {
            position: relative;
            min-height: 48px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 0 14px;
            color: #777777;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
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
            padding: 0 12px;
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

        /* MAIN */

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
            padding: 0 28px;
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
            padding: 0 14px;
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
            width: min(1500px, 100%);
            margin: 0 auto;
            padding:
                24px
                26px
                32px;
        }

        /* HEADER */

        .page-header {
            position: relative;
            min-height: 175px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 28px;
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
            content: "ORDERS";
            position: absolute;
            top: -24px;
            right: 20px;
            color:
                rgba(
                    255,
                    255,
                    255,
                    0.025
                );
            font-size: 108px;
            font-weight: 900;
            letter-spacing: -7px;
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
            margin: 8px 0 0;
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

        .page-header-copy h1 em {
            color: var(--red);
            font-style: normal;
        }

        .page-header-copy p {
            max-width: 680px;
            margin: 13px 0 0;
            color: #858585;
            font-size: 11px;
            line-height: 1.7;
        }

        /* MESSAGES */

        .message {
            margin-bottom: 14px;
            padding: 14px 16px;
            border: 1px solid;
            font-size: 10px;
            font-weight: 700;
        }

        .message-success {
            color: var(--green);
            background:
                rgba(
                    95,
                    208,
                    138,
                    0.06
                );
            border-color:
                rgba(
                    95,
                    208,
                    138,
                    0.25
                );
        }

        .message-error {
            color: #ef858a;
            background:
                rgba(
                    215,
                    25,
                    32,
                    0.08
                );
            border-color:
                rgba(
                    215,
                    25,
                    32,
                    0.28
                );
        }

        /* STATS */

        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    5,
                    minmax(
                        0,
                        1fr
                    )
                );
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            min-height: 118px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 18px;
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

        .stat-label {
            color: #686868;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.2px;
        }

        .featured
        .stat-label {
            color:
                rgba(
                    255,
                    255,
                    255,
                    0.8
                );
        }

        .stat-value {
            margin-top: 16px;
            font-size: 26px;
            font-weight: 900;
        }

        /* FILTERS */

        .filter-panel {
            margin-bottom: 16px;
            padding: 18px;
            background: #0d0d0d;
            border:
                1px solid
                var(--border);
        }

        .filter-form {
            display: grid;
            grid-template-columns:
                minmax(
                    260px,
                    1fr
                )
                200px
                auto
                auto;
            gap: 10px;
            align-items: center;
        }

        .filter-input,
        .filter-select {
            min-height: 44px;
            width: 100%;
            padding: 0 13px;
            background: #101010;
            border:
                1px solid
                #292929;
            color: #dddddd;
            outline: none;
            font-size: 9px;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: var(--red);
        }

        .filter-button {
            min-height: 44px;
            padding: 0 18px;
            border: 0;
            background: var(--red);
            color: #ffffff;
            cursor: pointer;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .filter-button:hover {
            background: #ef222a;
        }

        .clear-button {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 18px;
            border:
                1px solid
                #2b2b2b;
            color: #777777;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .clear-button:hover {
            color: #ffffff;
            border-color: #454545;
        }

        /* TABLE */

        .panel {
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
            padding: 0 21px;
            border-bottom:
                1px solid
                #222222;
        }

        .panel-header p {
            margin: 0 0 4px;
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
            color: #5d5d5d;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        .orders-table th {
            padding: 13px 16px;
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
            padding: 14px 16px;
            color: #9c9c9c;
            font-size: 9px;
            border-bottom:
                1px solid
                #1b1b1b;
            vertical-align: middle;
        }

        .orders-table tbody
        tr:hover {
            background: #121212;
        }

        .order-number {
            color: #ffffff;
            font-weight: 900;
        }

        .customer-cell strong {
            display: block;
            margin-bottom: 4px;
            color: #dddddd;
            font-size: 9px;
        }

        .customer-cell small {
            color: #5c5c5c;
            font-size: 8px;
        }

        .total-cell {
            color: #ffffff;
            font-weight: 900;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: 0 9px;
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

        .status-processing {
            color: var(--blue);
            border-color:
                rgba(
                    105,
                    167,
                    255,
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

        .status-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .status-select {
            min-height: 34px;
            width: 115px;
            padding: 0 8px;
            background: #111111;
            border:
                1px solid
                #2a2a2a;
            color: #aaaaaa;
            font-size: 8px;
            outline: none;
        }

        .status-select:focus {
            border-color: var(--red);
        }

        .status-select:disabled,
        .update-button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .update-button:disabled {
            background: #242424;
            color: #777777;
        }

        .update-button {
            min-height: 34px;
            padding: 0 10px;
            border: 0;
            background: var(--red);
            color: #ffffff;
            cursor: pointer;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 0.7px;
        }

        .update-button:hover {
            background: #ef222a;
        }

        .empty-state {
            padding: 48px 20px;
            color: #555555;
            text-align: center;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        /* PAGINATION */

        .pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
            padding: 16px 18px;
            border-top:
                1px solid
                #1f1f1f;
        }

        .page-link {
            min-width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
            border:
                1px solid
                #292929;
            color: #777777;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
        }

        .page-link:hover,
        .page-link.active {
            color: #ffffff;
            border-color: var(--red);
            background:
                rgba(
                    215,
                    25,
                    32,
                    0.1
                );
        }

        .footer {
            margin-top: 22px;
            padding: 18px 0 0;
            border-top:
                1px solid
                #1c1c1c;
            color: #4f4f4f;
            font-size: 8px;
            text-align: center;
        }

        /* MOBILE */

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

        @media (max-width: 1180px) {

            .stats-grid {
                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }

            .filter-form {
                grid-template-columns:
                    1fr
                    180px;
            }
        }

        @media (max-width: 900px) {

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
                justify-content: space-between;
                padding: 10px 14px;
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

        @media (max-width: 640px) {

            .page-header {
                min-height: 0;
                padding: 23px;
            }

            .stats-grid,
            .filter-form {
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
            <span class="sidebar-icon">▦</span>
            <span>Dashboard</span>
        </a>

        <a
            href="users.php"
            class="sidebar-link"
        >
            <span class="sidebar-icon">U</span>
            <span>Users</span>
        </a>

        <a
            href="memberships.php"
            class="sidebar-link"
        >
            <span class="sidebar-icon">M</span>
            <span>Memberships</span>
        </a>

        <a
            href="bookings.php"
            class="sidebar-link"
        >
            <span class="sidebar-icon">B</span>
            <span>Bookings</span>
        </a>

        <a
            href="orders.php"
            class="sidebar-link active"
        >
            <span class="sidebar-icon">O</span>
            <span>Orders</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="sidebar-admin">

            <div class="sidebar-avatar">
                <?= h(
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
                    <?= h($adminNameRaw) ?>
                </strong>

            </div>

        </div>

        <a
            href="logout.php"
            class="sidebar-logout"
        >
            <span>↪</span>
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
                Orders
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
                    STORE MANAGEMENT
                </span>

                <h1>
                    MANAGE
                    <em>
                        ORDERS.
                    </em>
                </h1>

                <p>
                    Review customer purchases, track order
                    progress and update each order from
                    Pending to Processing, Completed or Cancelled.
                </p>

            </div>

        </section>

        <?php if ($success !== ''): ?>

            <div class="message message-success">
                <?= h($success) ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="message message-error">
                <?= h($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($databaseError): ?>

            <div class="message message-error">
                Some order information could not be loaded.
                Make sure your orders table exists and your
                database connection is correct.
            </div>

        <?php endif; ?>

        <section class="stats-grid">

            <article class="stat-card">

                <span class="stat-label">
                    TOTAL ORDERS
                </span>

                <strong class="stat-value">
                    <?= number_format($totalOrders) ?>
                </strong>

            </article>

            <article class="stat-card">

                <span class="stat-label">
                    PENDING
                </span>

                <strong class="stat-value">
                    <?= number_format($pendingOrders) ?>
                </strong>

            </article>

            <article class="stat-card">

                <span class="stat-label">
                    PROCESSING
                </span>

                <strong class="stat-value">
                    <?= number_format($processingOrders) ?>
                </strong>

            </article>

            <article class="stat-card">

                <span class="stat-label">
                    COMPLETED
                </span>

                <strong class="stat-value">
                    <?= number_format($completedOrders) ?>
                </strong>

            </article>

            <article class="stat-card featured">

                <span class="stat-label">
                    TOTAL SALES
                </span>

                <strong class="stat-value">
                    ₱<?= number_format(
                        $totalSales,
                        2
                    ) ?>
                </strong>

            </article>

        </section>

        <section class="filter-panel">

            <form
                method="get"
                action="orders.php"
                class="filter-form"
            >

                <input
                    type="text"
                    name="search"
                    class="filter-input"
                    value="<?= h($search) ?>"
                    placeholder="Search order ID, customer name or email..."
                >

                <select
                    name="status"
                    class="filter-select"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach (
                        $allowedStatuses
                        as $allowedStatus
                    ): ?>

                        <option
                            value="<?= h($allowedStatus) ?>"
                            <?= $statusFilter === $allowedStatus
                                ? 'selected'
                                : '' ?>
                        >
                            <?= h($allowedStatus) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button
                    type="submit"
                    class="filter-button"
                >
                    FILTER
                </button>

                <a
                    href="orders.php"
                    class="clear-button"
                >
                    CLEAR
                </a>

            </form>

        </section>

        <section class="panel">

            <div class="panel-header">

                <div>

                    <p>
                        ORDER DATABASE
                    </p>

                    <h2>
                        CUSTOMER ORDERS
                    </h2>

                </div>

                <div class="panel-count">

                    <?= number_format(
                        $totalFiltered
                    ) ?>

                    RESULT<?= $totalFiltered === 1
                        ? ''
                        : 'S' ?>

                </div>

            </div>

            <?php if (!empty($orders)): ?>

                <div class="table-wrap">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>
                                    ORDER
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    ITEMS
                                </th>

                                <th>
                                    TOTAL
                                </th>

                                <th>
                                    DATE
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    UPDATE STATUS
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach (
                            $orders
                            as $order
                        ): ?>

                            <?php

                            $currentStatus =
                                (string)(
                                    $order['status']
                                    ?? ''
                                );

                            ?>

                            <tr>

                                <td>

                                    <span class="order-number">
                                        #<?= (int)$order['order_id'] ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="customer-cell">

                                        <strong>
                                            <?= h(
                                                $order['full_name']
                                                ?? 'Unknown Customer'
                                            ) ?>
                                        </strong>

                                        <small>
                                            <?= h(
                                                $order['email']
                                                ?? 'No email'
                                            ) ?>
                                        </small>

                                    </div>

                                </td>

                                <td>
                                    <?= number_format(
                                        (int)$order['item_count']
                                    ) ?>
                                </td>

                                <td>

                                    <span class="total-cell">

                                        ₱<?= number_format(
                                            (float)$order['total_amount'],
                                            2
                                        ) ?>

                                    </span>

                                </td>

                                <td>
                                    <?= h(
                                        orderDate(
                                            isset($order['order_date'])
                                                ? (string)$order['order_date']
                                                : null
                                        )
                                    ) ?>
                                </td>

                                <td>

                                    <span
                                        class="status-badge
                                        <?= h(
                                            orderStatusClass(
                                                $currentStatus
                                            )
                                        ) ?>"
                                    >
                                        <?= h(
                                            strtoupper(
                                                $currentStatus
                                            )
                                        ) ?>
                                    </span>

                                </td>

                                <td>

                                    <?php if ($currentStatus === 'Completed'): ?>

                                        <div class="status-form">

                                            <select
                                                class="status-select"
                                                disabled
                                                aria-label="Completed order status"
                                            >
                                                <option selected>
                                                    Completed
                                                </option>
                                            </select>

                                            <button
                                                type="button"
                                                class="update-button"
                                                disabled
                                                title="Completed orders are final"
                                            >
                                                LOCKED
                                            </button>

                                        </div>

                                    <?php else: ?>

                                        <form
                                            method="post"
                                            action="orders.php?<?= h(
                                                http_build_query([
                                                    'search' => $search,
                                                    'status' => $statusFilter,
                                                    'page' => $page
                                                ])
                                            ) ?>"
                                            class="status-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= h(
                                                    adminOrderCsrfToken()
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?= (int)$order['order_id'] ?>"
                                            >

                                            <select
                                                name="status"
                                                class="status-select"
                                            >

                                                <?php foreach (
                                                    $allowedStatuses
                                                    as $allowedStatus
                                                ): ?>

                                                    <option
                                                        value="<?= h($allowedStatus) ?>"
                                                        <?= $currentStatus === $allowedStatus
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        <?= h($allowedStatus) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                            <button
                                                type="submit"
                                                class="update-button"
                                            >
                                                UPDATE
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <?php

                        $baseQuery = [
                            'search' => $search,
                            'status' => $statusFilter
                        ];

                        ?>

                        <?php if ($page > 1): ?>

                            <a
                                class="page-link"
                                href="orders.php?<?= h(
                                    http_build_query(
                                        $baseQuery + [
                                            'page' => $page - 1
                                        ]
                                    )
                                ) ?>"
                            >
                                ←
                            </a>

                        <?php endif; ?>

                        <?php

                        $startPage = max(
                            1,
                            $page - 2
                        );

                        $endPage = min(
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
                                class="page-link
                                <?= $i === $page
                                    ? 'active'
                                    : '' ?>"
                                href="orders.php?<?= h(
                                    http_build_query(
                                        $baseQuery + [
                                            'page' => $i
                                        ]
                                    )
                                ) ?>"
                            >
                                <?= $i ?>
                            </a>

                        <?php endfor; ?>

                        <?php if (
                            $page < $totalPages
                        ): ?>

                            <a
                                class="page-link"
                                href="orders.php?<?= h(
                                    http_build_query(
                                        $baseQuery + [
                                            'page' => $page + 1
                                        ]
                                    )
                                ) ?>"
                            >
                                →
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php else: ?>

                <div class="empty-state">
                    NO ORDERS FOUND
                </div>

            <?php endif; ?>

        </section>

        <footer class="footer">

            © <?= date('Y') ?>
            Dionisio Fitness Center.
            Admin Orders.

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
                    window.innerWidth <= 900
                    && sidebar.classList.contains(
                        'open'
                    )
                    && !sidebar.contains(
                        event.target
                    )
                    && !toggle.contains(
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
