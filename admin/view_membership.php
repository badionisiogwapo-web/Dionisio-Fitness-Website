<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';
require_once __DIR__ . '/../config/csrf.php';

requireAdmin();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function membershipStatusClass(string $status): string
{
    return match (strtolower(trim($status))) {
        'active' => 'status-active',
        'pending' => 'status-pending',
        'inactive', 'cancelled', 'canceled' => 'status-cancelled',
        default => 'status-neutral',
    };
}

function prettyDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $time = strtotime($date);

    return $time !== false
        ? date('M d, Y', $time)
        : '—';
}

$membershipId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$membershipId || $membershipId < 1) {
    http_response_code(400);
    exit('Invalid membership ID.');
}

$success = '';
$error = '';

/*
|--------------------------------------------------------------------------
| LOAD MEMBERSHIP
|--------------------------------------------------------------------------
*/

$membership = null;

$loadMembership = function () use ($pdo, $membershipId): ?array {
    $stmt = $pdo->prepare(
        "SELECT
            m.id,
            m.user_id,
            m.plan,
            m.status,
            m.start_date,
            u.full_name,
            u.email,
            u.phone,
            u.created_at AS user_created_at
         FROM memberships m
         LEFT JOIN users u
            ON u.id = m.user_id
         WHERE m.id = ?
         LIMIT 1"
    );

    $stmt->execute([$membershipId]);

    $row = $stmt->fetch();

    return $row ?: null;
};

try {
    $membership = $loadMembership();
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Membership information could not be loaded.');
}

if (!$membership) {
    http_response_code(404);
    exit('Membership request not found.');
}

/*
|--------------------------------------------------------------------------
| APPROVE / REJECT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = trim((string)($_POST['action'] ?? ''));
    $token = $_POST['csrf_token'] ?? null;

    if (!verifyCsrf(is_string($token) ? $token : null)) {

        $error = 'Your session token is invalid. Please refresh the page and try again.';

    } elseif (!in_array($action, ['approve', 'reject'], true)) {

        $error = 'Invalid membership action.';

    } elseif (strcasecmp((string)$membership['status'], 'Pending') !== 0) {

        $error = 'Only pending membership requests can be approved or rejected.';

    } else {

        try {

            $pdo->beginTransaction();

            /*
             * Lock the selected membership so two admin requests
             * cannot process the same Pending row at the same time.
             */
            $lockStmt = $pdo->prepare(
                "SELECT id, user_id, status
                 FROM memberships
                 WHERE id = ?
                 FOR UPDATE"
            );

            $lockStmt->execute([$membershipId]);

            $lockedMembership = $lockStmt->fetch();

            if (!$lockedMembership) {
                throw new RuntimeException(
                    'The membership request no longer exists.'
                );
            }

            if (
                strcasecmp(
                    (string)$lockedMembership['status'],
                    'Pending'
                ) !== 0
            ) {
                throw new RuntimeException(
                    'This membership request has already been processed.'
                );
            }

            $userId = (int)$lockedMembership['user_id'];

            if ($action === 'approve') {

                /*
                 * A user should only have one Active membership.
                 * Any previous Active membership becomes Inactive.
                 */
                $deactivateStmt = $pdo->prepare(
                    "UPDATE memberships
                     SET status = 'Inactive'
                     WHERE user_id = ?
                       AND status = 'Active'
                       AND id <> ?"
                );

                $deactivateStmt->execute([
                    $userId,
                    $membershipId
                ]);

                /*
                 * Approval activates the selected request and sets
                 * the real membership start date to today.
                 */
                $approveStmt = $pdo->prepare(
                    "UPDATE memberships
                     SET
                        status = 'Active',
                        start_date = CURDATE()
                     WHERE id = ?
                       AND user_id = ?
                       AND status = 'Pending'"
                );

                $approveStmt->execute([
                    $membershipId,
                    $userId
                ]);

                if ($approveStmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The membership could not be approved.'
                    );
                }

                $success = 'Membership request approved successfully.';

            } else {

                /*
                 * Rejecting the Pending request does not touch any
                 * existing Active membership the customer may have.
                 */
                $rejectStmt = $pdo->prepare(
                    "UPDATE memberships
                     SET status = 'Cancelled'
                     WHERE id = ?
                       AND user_id = ?
                       AND status = 'Pending'"
                );

                $rejectStmt->execute([
                    $membershipId,
                    $userId
                ]);

                if ($rejectStmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'The membership request could not be rejected.'
                    );
                }

                $success = 'Membership request rejected.';
            }

            $pdo->commit();

            $membership = $loadMembership();

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The membership request could not be processed. Please try again.';

            if ($exception instanceof PDOException) {
                error_log($exception->getMessage());
            }
        }
    }
}

$adminNameRaw = (string)(
    $_SESSION['admin_username']
    ?? 'System Administrator'
);

$memberName = trim(
    (string)($membership['full_name'] ?? '')
);

if ($memberName === '') {
    $memberName = 'Unknown Member';
}

$memberInitial = strtoupper(
    substr($memberName, 0, 1)
);

$status = (string)($membership['status'] ?? '');
$isPending = strcasecmp($status, 'Pending') === 0;

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
        Membership #<?= (int)$membership['id'] ?>
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
            --border: #242424;
            --border-soft: #1c1c1c;
            --text: #f5f5f5;
            --muted: #777777;
            --green: #5fd08a;
            --yellow: #e7b94f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: "Montserrat", Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        button {
            font: inherit;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1000;
            width: 260px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
        }

        .sidebar-brand {
            min-height: 90px;
            display: flex;
            align-items: center;
            padding: 0 22px;
            border-bottom: 1px solid var(--border-soft);
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
        }

        .sidebar-brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            background: var(--red);
            color: #fff;
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
            padding: 27px 22px 10px;
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
            color: #777;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-link:hover {
            background: #111;
            color: #fff;
        }

        .sidebar-link.active {
            background: #151515;
            color: #fff;
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

        .sidebar-link.active .sidebar-icon {
            color: var(--red);
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid var(--border-soft);
        }

        .sidebar-admin {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px;
            margin-bottom: 9px;
            background: #101010;
            border: 1px solid #1f1f1f;
        }

        .sidebar-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(215, 25, 32, 0.14);
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
            background: rgba(215, 25, 32, 0.08);
        }

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
            border-bottom: 1px solid var(--border);
        }

        .topbar-title span {
            display: block;
            margin-bottom: 4px;
            color: #555;
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
            border: 1px solid #292929;
            color: #858585;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .topbar-link:hover {
            color: #fff;
            border-color: #3b3b3b;
        }

        .admin-content {
            width: min(1250px, 100%);
            margin: 0 auto;
            padding: 24px 26px 32px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: #777;
            text-decoration: none;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .back-link:hover {
            color: #fff;
        }

        .page-header {
            position: relative;
            min-height: 175px;
            display: flex;
            align-items: flex-end;
            padding: 30px;
            margin-bottom: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, #171717, #0d0d0d);
            border: 1px solid var(--border);
            border-left: 4px solid var(--red);
        }

        .page-header::after {
            content: "MEMBERSHIP";
            position: absolute;
            top: -13px;
            right: 18px;
            color: rgba(255,255,255,0.02);
            font-size: 72px;
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
            margin: 8px 0 0;
            font-size: clamp(30px, 4vw, 46px);
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
            margin: 13px 0 0;
            color: #858585;
            font-size: 11px;
            line-height: 1.7;
        }

        .message {
            margin-bottom: 14px;
            padding: 14px 16px;
            border: 1px solid;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.6;
        }

        .message-success {
            color: var(--green);
            background: rgba(95, 208, 138, 0.06);
            border-color: rgba(95, 208, 138, 0.25);
        }

        .message-error {
            color: #ef858a;
            background: rgba(215, 25, 32, 0.08);
            border-color: rgba(215, 25, 32, 0.28);
        }

        .details-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(330px, 0.9fr);
            gap: 16px;
        }

        .panel {
            background: #0d0d0d;
            border: 1px solid var(--border);
        }

        .panel-header {
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 0 21px;
            border-bottom: 1px solid #222;
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

        .member-hero {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px;
            border-bottom: 1px solid #1d1d1d;
        }

        .member-avatar {
            width: 56px;
            height: 56px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(215, 25, 32, 0.13);
            color: #ef555c;
            font-size: 18px;
            font-weight: 900;
        }

        .member-hero h3 {
            margin: 0 0 5px;
            font-size: 16px;
            font-weight: 900;
        }

        .member-hero p {
            margin: 0;
            color: #656565;
            font-size: 9px;
        }

        .detail-list {
            padding: 5px 22px 18px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr);
            gap: 16px;
            padding: 15px 0;
            border-bottom: 1px solid #1c1c1c;
        }

        .detail-row:last-child {
            border-bottom: 0;
        }

        .detail-label {
            color: #555;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .detail-value {
            color: #ddd;
            font-size: 10px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .plan-box {
            padding: 24px;
        }

        .plan-name {
            margin-bottom: 8px;
            color: #fff;
            font-size: 29px;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .plan-id {
            margin-bottom: 20px;
            color: #555;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border: 1px solid #2a2a2a;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0.9px;
        }

        .status-active {
            color: var(--green);
            border-color: rgba(95, 208, 138, 0.25);
            background: rgba(95, 208, 138, 0.05);
        }

        .status-pending {
            color: var(--yellow);
            border-color: rgba(231, 185, 79, 0.25);
            background: rgba(231, 185, 79, 0.05);
        }

        .status-cancelled {
            color: #ef686e;
            border-color: rgba(239, 104, 110, 0.25);
            background: rgba(239, 104, 110, 0.04);
        }

        .status-neutral {
            color: #aaa;
        }

        .action-box {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #222;
        }

        .action-box > span {
            display: block;
            margin-bottom: 12px;
            color: #555;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.4px;
        }

        .action-form {
            display: grid;
            gap: 9px;
        }

        .action-button {
            width: 100%;
            min-height: 46px;
            border: 0;
            cursor: pointer;
            color: #fff;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .approve-button {
            background: var(--red);
        }

        .approve-button:hover {
            background: #ef222a;
        }

        .reject-button {
            background: #171717;
            border: 1px solid #333;
            color: #aaa;
        }

        .reject-button:hover {
            color: #fff;
            border-color: #555;
        }

        .processed-note {
            padding: 15px;
            background: #111;
            border: 1px solid #252525;
            color: #777;
            font-size: 9px;
            line-height: 1.6;
        }

        .mobile-bar {
            display: none;
        }

        .mobile-toggle {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            background: #111;
            border: 1px solid #303030;
            color: #fff;
            cursor: pointer;
        }

        @media (max-width: 1000px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }

            .admin-sidebar.open {
                transform: translateX(0);
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
                border-bottom: 1px solid var(--border);
            }

            .admin-content {
                padding: 18px 14px 28px;
            }
        }

        @media (max-width: 600px) {

            .page-header {
                min-height: 0;
                padding: 23px;
            }

            .page-header::after {
                font-size: 48px;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 6px;
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
            class="sidebar-link active"
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
            class="sidebar-link"
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
                        substr($adminNameRaw, 0, 1)
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
                Membership Request
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

        <a
            href="memberships.php"
            class="back-link"
        >
            ← BACK TO MEMBERSHIPS
        </a>

        <section class="page-header">

            <div class="page-header-copy">

                <div class="page-header-line"></div>

                <span>
                    MEMBERSHIP REQUEST
                </span>

                <h1>
                    REVIEW
                    <em>
                        REQUEST.
                    </em>
                </h1>

                <p>
                    Review the customer's selected plan and
                    approve or reject a pending membership request.
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

        <div class="details-grid">

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <p>
                            CUSTOMER
                        </p>

                        <h2>
                            MEMBER INFORMATION
                        </h2>

                    </div>

                </div>

                <div class="member-hero">

                    <div class="member-avatar">
                        <?= h($memberInitial) ?>
                    </div>

                    <div>

                        <h3>
                            <?= h($memberName) ?>
                        </h3>

                        <p>
                            <?= h(
                                $membership['email']
                                ?? 'No email'
                            ) ?>
                        </p>

                    </div>

                </div>

                <div class="detail-list">

                    <div class="detail-row">

                        <div class="detail-label">
                            USER ID
                        </div>

                        <div class="detail-value">
                            #<?= (int)$membership['user_id'] ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            FULL NAME
                        </div>

                        <div class="detail-value">
                            <?= h($memberName) ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            EMAIL
                        </div>

                        <div class="detail-value">
                            <?= h(
                                $membership['email']
                                ?? '—'
                            ) ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            PHONE
                        </div>

                        <div class="detail-value">
                            <?= !empty($membership['phone'])
                                ? h($membership['phone'])
                                : '—' ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            ACCOUNT CREATED
                        </div>

                        <div class="detail-value">
                            <?= h(
                                prettyDate(
                                    isset($membership['user_created_at'])
                                        ? (string)$membership['user_created_at']
                                        : null
                                )
                            ) ?>
                        </div>

                    </div>

                </div>

            </section>

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <p>
                            REQUEST
                        </p>

                        <h2>
                            MEMBERSHIP DETAILS
                        </h2>

                    </div>

                </div>

                <div class="plan-box">

                    <div class="plan-name">
                        <?= h(
                            strtoupper(
                                (string)$membership['plan']
                            )
                        ) ?>
                    </div>

                    <div class="plan-id">
                        MEMBERSHIP #<?= (int)$membership['id'] ?>
                    </div>

                    <span
                        class="status-badge
                        <?= h(
                            membershipStatusClass(
                                $status
                            )
                        ) ?>"
                    >
                        <?= h(strtoupper($status)) ?>
                    </span>

                    <div class="detail-list" style="padding:18px 0 0;">

                        <div class="detail-row">

                            <div class="detail-label">
                                PLAN
                            </div>

                            <div class="detail-value">
                                <?= h($membership['plan']) ?>
                            </div>

                        </div>

                        <div class="detail-row">

                            <div class="detail-label">
                                STATUS
                            </div>

                            <div class="detail-value">
                                <?= h($status) ?>
                            </div>

                        </div>

                        <div class="detail-row">

                            <div class="detail-label">
                                START DATE
                            </div>

                            <div class="detail-value">
                                <?= h(
                                    prettyDate(
                                        isset($membership['start_date'])
                                            ? (string)$membership['start_date']
                                            : null
                                    )
                                ) ?>
                            </div>

                        </div>

                    </div>

                    <div class="action-box">

                        <?php if ($isPending): ?>

                            <span>
                                ADMIN ACTION
                            </span>

                            <div class="action-form">

                                <form
                                    method="post"
                                    action="view_membership.php?id=<?= (int)$membership['id'] ?>"
                                    onsubmit="return confirm('Approve this membership request?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(csrfToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >

                                    <button
                                        type="submit"
                                        class="action-button approve-button"
                                    >
                                        APPROVE MEMBERSHIP
                                    </button>

                                </form>

                                <form
                                    method="post"
                                    action="view_membership.php?id=<?= (int)$membership['id'] ?>"
                                    onsubmit="return confirm('Reject this membership request?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(csrfToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject"
                                    >

                                    <button
                                        type="submit"
                                        class="action-button reject-button"
                                    >
                                        REJECT REQUEST
                                    </button>

                                </form>

                            </div>

                        <?php else: ?>

                            <div class="processed-note">

                                This membership request has already
                                been processed. Only Pending requests
                                can be approved or rejected.

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </section>

        </div>

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
                    sidebar.classList.contains('open') &&
                    !sidebar.contains(event.target) &&
                    !toggle.contains(event.target)
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
