<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';
require_once __DIR__ . '/../config/csrf.php';

requireAdmin();

$pageTitle = 'View Booking | Dionisio Fitness Center';

function vbH(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookingId <= 0) {
    header('Location: bookings.php');
    exit;
}

$success = '';
$error = '';

$allowedTransitions = [
    'Pending' => ['Confirmed', 'Cancelled'],
    'Confirmed' => ['Completed', 'Cancelled'],
    'Completed' => [],
    'Cancelled' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? null;
    $newStatus = trim((string)($_POST['status'] ?? ''));

    if (!verifyCsrf(is_string($token) ? $token : null)) {

        $error = 'Your session expired. Please refresh and try again.';

    } else {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT status
                FROM bookings
                WHERE booking_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$bookingId]);

            $current = $stmt->fetch();

            if (!$current) {

                $pdo->rollBack();
                $error = 'Booking not found.';

            } else {

                $currentStatus = (string)$current['status'];
                $validNextStatuses = $allowedTransitions[$currentStatus] ?? [];

                if (!in_array($newStatus, $validNextStatuses, true)) {

                    $pdo->rollBack();
                    $error = 'That status change is not allowed.';

                } else {

                    $update = $pdo->prepare("
                        UPDATE bookings
                        SET status = ?
                        WHERE booking_id = ?
                    ");

                    $update->execute([
                        $newStatus,
                        $bookingId
                    ]);

                    $pdo->commit();

                    $success =
                        'Booking status updated to ' .
                        $newStatus .
                        '.';
                }
            }

        } catch (PDOException $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($exception->getMessage());

            $error =
                'The booking could not be updated. Please try again.';
        }
    }
}

$booking = null;

try {

    $stmt = $pdo->prepare("
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
        INNER JOIN users u
            ON u.id = b.user_id
        WHERE b.booking_id = ?
        LIMIT 1
    ");

    $stmt->execute([$bookingId]);

    $booking = $stmt->fetch();

} catch (PDOException $exception) {

    error_log($exception->getMessage());

    $error =
        'Unable to load the booking details.';
}

if (!$booking && $error === '') {
    http_response_code(404);
    $error = 'Booking not found.';
}

function vbStatusClass(string $status): string
{
    return match (strtolower(trim($status))) {
        'confirmed' => 'confirmed',
        'completed' => 'completed',
        'cancelled', 'canceled' => 'cancelled',
        default => 'pending',
    };
}

function vbFormatDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $stamp = strtotime($date);

    return $stamp !== false
        ? date('F d, Y', $stamp)
        : $date;
}

function vbFormatTime(?string $time): string
{
    if (!$time) {
        return '—';
    }

    $stamp = strtotime($time);

    return $stamp !== false
        ? date('g:i A', $stamp)
        : $time;
}

function vbInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }

    return $initials !== '' ? $initials : 'U';
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
    <title><?= vbH($pageTitle) ?></title>

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
        * {
            box-sizing: border-box;
        }

        :root {
            --bg: #080808;
            --panel: #0f0f0f;
            --panel-2: #131313;
            --line: #242424;
            --text: #f1f1f1;
            --muted: #747474;
            --red: #d71920;
            --red-light: #ef222a;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Montserrat', Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        .admin-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 245px minmax(0, 1fr);
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: #0b0b0b;
            border-right: 1px solid #202020;
            padding: 27px 18px;
        }

        .brand {
            padding: 0 10px 26px;
            border-bottom: 1px solid #222;
            margin-bottom: 24px;
        }

        .brand strong {
            display: block;
            font-size: 19px;
            font-weight: 900;
            letter-spacing: -.6px;
        }

        .brand span {
            display: block;
            margin-top: 3px;
            color: var(--red);
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .nav-label {
            margin: 0 10px 9px;
            color: #4c4c4c;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }

        .sidebar-link {
            min-height: 46px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px;
            margin-bottom: 4px;
            border: 1px solid transparent;
            color: #858585;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .4px;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: #fff;
            background: #111;
            border-color: #262626;
        }

        .sidebar-link.active {
            border-left: 2px solid var(--red);
        }

        .sidebar-icon {
            width: 27px;
            height: 27px;
            border: 1px solid #292929;
            display: grid;
            place-items: center;
            font-size: 8px;
            font-weight: 900;
        }

        .sidebar-bottom {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 24px;
        }

        .logout-link {
            min-height: 44px;
            border: 1px solid #262626;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #767676;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .logout-link:hover {
            color: #fff;
            border-color: var(--red);
        }

        .main {
            min-width: 0;
        }

        .topbar {
            min-height: 78px;
            border-bottom: 1px solid #202020;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 38px;
        }

        .topbar-left span {
            display: block;
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.5px;
            margin-bottom: 5px;
        }

        .topbar-left h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
        }

        .back-link {
            min-height: 39px;
            padding: 0 15px;
            border: 1px solid #2b2b2b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #a0a0a0;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .8px;
        }

        .back-link:hover {
            color: #fff;
            border-color: #555;
        }

        .content {
            padding: 34px 38px 60px;
        }

        .message {
            max-width: 1120px;
            margin: 0 auto 18px;
            padding: 14px 16px;
            border: 1px solid;
            font-size: 10px;
            line-height: 1.6;
        }

        .message.success {
            color: #71d495;
            border-color: rgba(95,208,138,.28);
            background: rgba(95,208,138,.06);
        }

        .message.error {
            color: #ed7c82;
            border-color: rgba(215,25,32,.30);
            background: rgba(215,25,32,.08);
        }

        .booking-grid {
            width: min(1120px, 100%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
            gap: 22px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
        }

        .panel-head {
            padding: 21px 24px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .panel-head span {
            display: block;
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
        }

        .panel-head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 0 10px;
            border: 1px solid;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .status.pending {
            color: #e7b94f;
            border-color: rgba(231,185,79,.28);
        }

        .status.confirmed {
            color: #5fd08a;
            border-color: rgba(95,208,138,.28);
        }

        .status.completed {
            color: #69a7ff;
            border-color: rgba(105,167,255,.28);
        }

        .status.cancelled {
            color: #ef686e;
            border-color: rgba(239,104,110,.28);
        }

        .details {
            padding: 8px 24px 22px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #1f1f1f;
        }

        .detail-row:last-child {
            border-bottom: 0;
        }

        .detail-label {
            color: #5c5c5c;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.2px;
        }

        .detail-value {
            color: #e7e7e7;
            font-size: 11px;
            line-height: 1.7;
            word-break: break-word;
        }

        .customer-box {
            padding: 24px;
        }

        .customer-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            display: grid;
            place-items: center;
            background: #171717;
            border: 1px solid #2a2a2a;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
        }

        .customer-header strong {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 900;
        }

        .customer-header span {
            color: #666;
            font-size: 9px;
        }

        .customer-line {
            padding: 14px 0;
            border-top: 1px solid #1f1f1f;
        }

        .customer-line small {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1.1px;
        }

        .customer-line div {
            color: #d8d8d8;
            font-size: 10px;
            line-height: 1.5;
        }

        .actions-panel {
            margin-top: 22px;
            padding: 22px;
        }

        .actions-panel h3 {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 900;
        }

        .actions-panel p {
            margin: 0 0 18px;
            color: #656565;
            font-size: 9px;
            line-height: 1.7;
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 9px;
        }

        .action-form {
            margin: 0;
        }

        .action-btn {
            width: 100%;
            min-height: 45px;
            border: 1px solid;
            background: transparent;
            cursor: pointer;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .action-btn.confirm {
            color: #67d98e;
            border-color: rgba(95,208,138,.32);
            background: rgba(95,208,138,.05);
        }

        .action-btn.complete {
            color: #75afff;
            border-color: rgba(105,167,255,.32);
            background: rgba(105,167,255,.05);
        }

        .action-btn.cancel {
            color: #ef686e;
            border-color: rgba(239,104,110,.32);
            background: rgba(239,104,110,.05);
        }

        .action-btn:hover {
            filter: brightness(1.25);
        }

        .no-actions {
            min-height: 55px;
            border: 1px solid #232323;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5c5c5c;
            font-size: 9px;
            text-align: center;
            padding: 12px;
        }

        @media (max-width: 900px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
                border-right: 0;
                border-bottom: 1px solid #202020;
            }

            .sidebar-bottom {
                position: static;
                margin-top: 18px;
            }

            .booking-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 24px 16px 45px;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 7px;
            }
        }
    </style>
</head>

<body>

<div class="admin-shell">

    <aside class="sidebar">

        <div class="brand">
            <strong>DIONISIO</strong>
            <span>ADMIN PANEL</span>
        </div>

        <div class="nav-label">
            MANAGEMENT
        </div>

        <a
            href="index.php"
            class="sidebar-link"
        >
            <span class="sidebar-icon">D</span>
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
            class="sidebar-link active"
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

        <div class="sidebar-bottom">
            <a
                href="logout.php"
                class="logout-link"
            >
                LOG OUT
            </a>
        </div>

    </aside>

    <main class="main">

        <header class="topbar">

            <div class="topbar-left">
                <span>BOOKING MANAGEMENT</span>
                <h1>
                    BOOKING #<?= $bookingId ?>
                </h1>
            </div>

            <a
                href="bookings.php"
                class="back-link"
            >
                ← BACK TO BOOKINGS
            </a>

        </header>

        <div class="content">

            <?php if ($success !== ''): ?>

                <div class="message success">
                    <?= vbH($success) ?>
                </div>

            <?php endif; ?>

            <?php if ($error !== ''): ?>

                <div class="message error">
                    <?= vbH($error) ?>
                </div>

            <?php endif; ?>

            <?php if ($booking): ?>

                <div class="booking-grid">

                    <section class="panel">

                        <div class="panel-head">

                            <div>
                                <span>BOOKING DETAILS</span>
                                <h2>
                                    <?= vbH($booking['booking_type']) ?>
                                </h2>
                            </div>

                            <span
                                class="status
                                <?= vbH(
                                    vbStatusClass(
                                        (string)$booking['status']
                                    )
                                ) ?>"
                            >
                                <?= vbH(
                                    strtoupper(
                                        (string)$booking['status']
                                    )
                                ) ?>
                            </span>

                        </div>

                        <div class="details">

                            <div class="detail-row">
                                <div class="detail-label">
                                    BOOKING ID
                                </div>
                                <div class="detail-value">
                                    #<?= (int)$booking['booking_id'] ?>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    SESSION TYPE
                                </div>
                                <div class="detail-value">
                                    <?= vbH($booking['booking_type']) ?>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    DATE
                                </div>
                                <div class="detail-value">
                                    <?= vbH(
                                        vbFormatDate(
                                            (string)$booking['booking_date']
                                        )
                                    ) ?>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    TIME
                                </div>
                                <div class="detail-value">
                                    <?= vbH(
                                        vbFormatTime(
                                            (string)$booking['booking_time']
                                        )
                                    ) ?>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    STATUS
                                </div>
                                <div class="detail-value">
                                    <?= vbH($booking['status']) ?>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    NOTES
                                </div>
                                <div class="detail-value">
                                    <?= !empty($booking['notes'])
                                        ? nl2br(vbH($booking['notes']))
                                        : 'No notes provided.' ?>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    REQUESTED
                                </div>
                                <div class="detail-value">
                                    <?= vbH(
                                        !empty($booking['created_at'])
                                            ? date(
                                                'F d, Y · g:i A',
                                                strtotime(
                                                    (string)$booking['created_at']
                                                )
                                            )
                                            : '—'
                                    ) ?>
                                </div>
                            </div>

                        </div>

                    </section>

                    <aside>

                        <section class="panel">

                            <div class="panel-head">
                                <div>
                                    <span>CUSTOMER</span>
                                    <h2>MEMBER DETAILS</h2>
                                </div>
                            </div>

                            <div class="customer-box">

                                <div class="customer-header">

                                    <div class="avatar">
                                        <?= vbH(
                                            vbInitials(
                                                (string)$booking['full_name']
                                            )
                                        ) ?>
                                    </div>

                                    <div>
                                        <strong>
                                            <?= vbH($booking['full_name']) ?>
                                        </strong>

                                        <span>
                                            USER #<?= (int)$booking['user_id'] ?>
                                        </span>
                                    </div>

                                </div>

                                <div class="customer-line">
                                    <small>EMAIL</small>
                                    <div>
                                        <?= vbH($booking['email']) ?>
                                    </div>
                                </div>

                                <div class="customer-line">
                                    <small>PHONE</small>
                                    <div>
                                        <?= !empty($booking['phone'])
                                            ? vbH($booking['phone'])
                                            : 'Not provided' ?>
                                    </div>
                                </div>

                            </div>

                        </section>

                        <section class="panel actions-panel">

                            <h3>
                                MANAGE BOOKING
                            </h3>

                            <p>
                                Change the booking status based on
                                the current stage of the request.
                            </p>

                            <div class="action-grid">

                                <?php
                                $currentStatus =
                                    (string)$booking['status'];
                                ?>

                                <?php if ($currentStatus === 'Pending'): ?>

                                    <form
                                        method="post"
                                        action="view_bookings.php?id=<?= $bookingId ?>"
                                        class="action-form"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= vbH(csrfToken()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="status"
                                            value="Confirmed"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn confirm"
                                        >
                                            CONFIRM BOOKING
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        action="view_bookings.php?id=<?= $bookingId ?>"
                                        class="action-form"
                                        onsubmit="return confirm('Cancel this booking?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= vbH(csrfToken()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="status"
                                            value="Cancelled"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn cancel"
                                        >
                                            CANCEL BOOKING
                                        </button>
                                    </form>

                                <?php elseif ($currentStatus === 'Confirmed'): ?>

                                    <form
                                        method="post"
                                        action="view_bookings.php?id=<?= $bookingId ?>"
                                        class="action-form"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= vbH(csrfToken()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="status"
                                            value="Completed"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn complete"
                                        >
                                            MARK AS COMPLETED
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        action="view_bookings.php?id=<?= $bookingId ?>"
                                        class="action-form"
                                        onsubmit="return confirm('Cancel this booking?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= vbH(csrfToken()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="status"
                                            value="Cancelled"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn cancel"
                                        >
                                            CANCEL BOOKING
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <div class="no-actions">
                                        No further actions are available
                                        for this booking.
                                    </div>

                                <?php endif; ?>

                            </div>

                        </section>

                    </aside>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>
