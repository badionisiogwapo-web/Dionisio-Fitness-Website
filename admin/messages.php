<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';
require_once __DIR__ . '/../config/csrf.php';

requireAdmin();

$pageTitle = 'Messages';
$currentPage = basename($_SERVER['PHP_SELF']);

function msgE(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? null;
    $action = trim((string)($_POST['action'] ?? ''));
    $messageId = (int)($_POST['message_id'] ?? 0);

    if (!verifyCsrf(is_string($token) ? $token : null)) {

        $error = 'Your session expired. Please refresh the page and try again.';

    } elseif ($messageId <= 0) {

        $error = 'Invalid message selected.';

    } else {

        try {

            if ($action === 'mark_read') {

                $stmt = $pdo->prepare("
                    UPDATE contact_messages
                    SET status = 'Read'
                    WHERE id = ?
                ");

                $stmt->execute([$messageId]);

                $success = 'Message marked as read.';

            } elseif ($action === 'mark_unread') {

                $stmt = $pdo->prepare("
                    UPDATE contact_messages
                    SET status = 'Unread'
                    WHERE id = ?
                ");

                $stmt->execute([$messageId]);

                $success = 'Message marked as unread.';

            } elseif ($action === 'delete') {

                $stmt = $pdo->prepare("
                    DELETE FROM contact_messages
                    WHERE id = ?
                ");

                $stmt->execute([$messageId]);

                $success = 'Message deleted successfully.';

            } else {

                $error = 'Invalid action.';
            }

        } catch (PDOException $exception) {

            error_log($exception->getMessage());

            $error = 'Unable to update messages right now.';
        }
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

if (!in_array($statusFilter, ['', 'Unread', 'Read'], true)) {
    $statusFilter = '';
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR message LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter !== '') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$messages = [];
$totalMessages = 0;
$unreadMessages = 0;
$readMessages = 0;
$databaseError = false;

try {

    $totalMessages = (int)$pdo
        ->query("SELECT COUNT(*) FROM contact_messages")
        ->fetchColumn();

    $unreadMessages = (int)$pdo
        ->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'Unread'")
        ->fetchColumn();

    $readMessages = (int)$pdo
        ->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'Read'")
        ->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            email,
            message,
            status,
            created_at
        FROM contact_messages
        {$whereSql}
        ORDER BY created_at DESC
    ");

    $stmt->execute($params);
    $messages = $stmt->fetchAll();

} catch (PDOException $exception) {

    error_log($exception->getMessage());

    $databaseError = true;
    $messages = [];
}

function msgStatusClass(string $status): string
{
    return strtolower($status) === 'read'
        ? 'status-read'
        : 'status-unread';
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
    <title><?= msgE($pageTitle) ?> | Dionisio Fitness Center</title>

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
            --red: #d71920;
            --bg: #080808;
            --panel: #0f0f0f;
            --panel2: #121212;
            --line: #242424;
            --text: #f4f4f4;
            --muted: #747474;
            --green: #5fd08a;
            --yellow: #e7b94f;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Montserrat", Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 260px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #090909;
            border-right: 1px solid var(--line);
            z-index: 1000;
        }

        .sidebar-brand {
            min-height: 90px;
            display: flex;
            align-items: center;
            padding: 0 22px;
            border-bottom: 1px solid #1c1c1c;
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-link:hover,
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

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid #1c1c1c;
        }

        .sidebar-logout {
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            color: #747474;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .sidebar-logout:hover {
            color: var(--red);
            background: rgba(215,25,32,.08);
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
            padding: 0 28px;
            background: #0b0b0b;
            border-bottom: 1px solid var(--line);
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
            padding: 0 14px;
            border: 1px solid #292929;
            color: #858585;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .admin-content {
            width: min(1450px, 100%);
            margin: 0 auto;
            padding: 24px 26px 40px;
        }

        .page-header {
            min-height: 160px;
            display: flex;
            align-items: flex-end;
            padding: 30px;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #171717, #0d0d0d);
            border: 1px solid var(--line);
            border-left: 4px solid var(--red);
        }

        .page-header span {
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .page-header h1 {
            margin: 8px 0 8px;
            font-size: clamp(30px, 4vw, 46px);
            font-weight: 900;
            letter-spacing: -2px;
        }

        .page-header h1 em {
            color: var(--red);
            font-style: normal;
        }

        .page-header p {
            margin: 0;
            color: #858585;
            font-size: 11px;
            line-height: 1.7;
        }

        .message {
            margin-bottom: 14px;
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
            color: #ef7c82;
            border-color: rgba(215,25,32,.28);
            background: rgba(215,25,32,.08);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat {
            padding: 20px;
            background: var(--panel);
            border: 1px solid var(--line);
        }

        .stat span {
            display: block;
            margin-bottom: 10px;
            color: #626262;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.3px;
        }

        .stat strong {
            font-size: 25px;
            font-weight: 900;
        }

        .filter-bar {
            display: grid;
            grid-template-columns: 1fr 180px auto auto;
            gap: 9px;
            padding: 15px;
            margin-bottom: 14px;
            background: #0d0d0d;
            border: 1px solid var(--line);
        }

        .field {
            min-height: 42px;
            width: 100%;
            padding: 0 13px;
            outline: none;
            background: #111;
            border: 1px solid #292929;
            color: #fff;
            font-size: 9px;
        }

        .filter-btn,
        .clear-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
            border: 0;
            cursor: pointer;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .filter-btn {
            background: var(--red);
            color: #fff;
        }

        .clear-btn {
            background: #171717;
            border: 1px solid #292929;
            color: #888;
        }

        .messages-panel {
            background: #0d0d0d;
            border: 1px solid var(--line);
            overflow: hidden;
        }

        .panel-head {
            min-height: 66px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            border-bottom: 1px solid #222;
        }

        .panel-head p {
            margin: 0 0 4px;
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .panel-head h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 900;
        }

        .message-list {
            display: grid;
        }

        .message-card {
            display: grid;
            grid-template-columns: 230px minmax(0,1fr) 220px;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #1d1d1d;
        }

        .message-card:last-child {
            border-bottom: 0;
        }

        .sender strong {
            display: block;
            margin-bottom: 5px;
            font-size: 10px;
        }

        .sender small {
            color: #666;
            font-size: 8px;
        }

        .message-body {
            color: #aaa;
            font-size: 10px;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .message-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .status-badge {
            width: fit-content;
            min-height: 25px;
            display: inline-flex;
            align-items: center;
            padding: 0 9px;
            border: 1px solid;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: .8px;
        }

        .status-unread {
            color: var(--yellow);
            border-color: rgba(231,185,79,.28);
        }

        .status-read {
            color: var(--green);
            border-color: rgba(95,208,138,.28);
        }

        .date {
            color: #5c5c5c;
            font-size: 8px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .action-form {
            margin: 0;
        }

        .action-btn {
            min-height: 34px;
            padding: 0 10px;
            border: 1px solid #303030;
            background: #131313;
            color: #aaa;
            cursor: pointer;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: .7px;
        }

        .action-btn:hover {
            color: #fff;
            border-color: #555;
        }

        .action-btn.delete {
            color: #ef686e;
            border-color: rgba(239,104,110,.28);
        }

        .empty {
            padding: 45px 20px;
            text-align: center;
            color: #555;
            font-size: 9px;
            font-weight: 900;
        }

        @media (max-width: 900px) {
            .admin-sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .admin-main {
                margin-left: 0;
            }

            .message-card {
                grid-template-columns: 1fr;
            }

            .filter-bar,
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<aside class="admin-sidebar">

    <div class="sidebar-brand">
        <a href="index.php" class="sidebar-brand-link">
            <span class="sidebar-brand-mark">D</span>

            <span class="sidebar-brand-copy">
                <strong>DIONISIO</strong>
                <small>FITNESS CENTER</small>
            </span>
        </a>
    </div>

    <div class="sidebar-label">
        MAIN MENU
    </div>

    <nav class="sidebar-nav">

        <a href="index.php" class="sidebar-link">
            <span class="sidebar-icon">▦</span>
            <span>Dashboard</span>
        </a>

        <a href="users.php" class="sidebar-link">
            <span class="sidebar-icon">U</span>
            <span>Users</span>
        </a>

        <a href="memberships.php" class="sidebar-link">
            <span class="sidebar-icon">M</span>
            <span>Memberships</span>
        </a>

        <a href="bookings.php" class="sidebar-link">
            <span class="sidebar-icon">B</span>
            <span>Bookings</span>
        </a>

        <a href="messages.php" class="sidebar-link active">
            <span class="sidebar-icon">✉</span>
            <span>Messages</span>
        </a>

    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="sidebar-logout">
            <span>↪</span>
            LOG OUT
        </a>
    </div>

</aside>

<div class="admin-main">

    <header class="admin-topbar">

        <div class="topbar-title">
            <span>ADMIN PANEL</span>
            <strong>Messages</strong>
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

            <div>
                <span>CONTACT INBOX</span>

                <h1>
                    CUSTOMER
                    <em>MESSAGES.</em>
                </h1>

                <p>
                    Review messages submitted from the public
                    Contact Us form.
                </p>
            </div>

        </section>

        <?php if ($success !== ''): ?>
            <div class="message success">
                <?= msgE($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="message error">
                <?= msgE($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($databaseError): ?>
            <div class="message error">
                Message information could not be loaded.
                Make sure the contact_messages table exists.
            </div>
        <?php endif; ?>

        <section class="stats">

            <article class="stat">
                <span>TOTAL MESSAGES</span>
                <strong><?= number_format($totalMessages) ?></strong>
            </article>

            <article class="stat">
                <span>UNREAD</span>
                <strong><?= number_format($unreadMessages) ?></strong>
            </article>

            <article class="stat">
                <span>READ</span>
                <strong><?= number_format($readMessages) ?></strong>
            </article>

        </section>

        <form
            method="get"
            action="messages.php"
            class="filter-bar"
        >

            <input
                type="search"
                name="search"
                class="field"
                placeholder="Search name, email or message..."
                value="<?= msgE($search) ?>"
            >

            <select
                name="status"
                class="field"
            >
                <option value="">ALL STATUS</option>

                <option
                    value="Unread"
                    <?= $statusFilter === 'Unread' ? 'selected' : '' ?>
                >
                    UNREAD
                </option>

                <option
                    value="Read"
                    <?= $statusFilter === 'Read' ? 'selected' : '' ?>
                >
                    READ
                </option>
            </select>

            <button
                type="submit"
                class="filter-btn"
            >
                FILTER
            </button>

            <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a
                    href="messages.php"
                    class="clear-btn"
                >
                    CLEAR
                </a>
            <?php endif; ?>

        </form>

        <section class="messages-panel">

            <div class="panel-head">
                <div>
                    <p>MESSAGE DATABASE</p>
                    <h2>CONTACT MESSAGES</h2>
                </div>

                <div>
                    <?= number_format(count($messages)) ?> RESULT<?= count($messages) === 1 ? '' : 'S' ?>
                </div>
            </div>

            <?php if (!empty($messages)): ?>

                <div class="message-list">

                    <?php foreach ($messages as $row): ?>

                        <article class="message-card">

                            <div class="sender">
                                <strong><?= msgE($row['name']) ?></strong>
                                <small><?= msgE($row['email']) ?></small>
                            </div>

                            <div class="message-body"><?= msgE($row['message']) ?></div>

                            <div class="message-meta">

                                <span class="status-badge <?= msgStatusClass((string)$row['status']) ?>">
                                    <?= msgE(strtoupper((string)$row['status'])) ?>
                                </span>

                                <div class="date">
                                    <?php
                                    $stamp = strtotime((string)$row['created_at']);

                                    echo $stamp !== false
                                        ? msgE(date('M d, Y · g:i A', $stamp))
                                        : '—';
                                    ?>
                                </div>

                                <div class="actions">

                                    <?php if ($row['status'] === 'Unread'): ?>

                                        <form method="post" class="action-form">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= msgE(csrfToken()) ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= (int)$row['id'] ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="action"
                                                value="mark_read"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn"
                                            >
                                                MARK READ
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <form method="post" class="action-form">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= msgE(csrfToken()) ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= (int)$row['id'] ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="action"
                                                value="mark_unread"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn"
                                            >
                                                MARK UNREAD
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                    <form
                                        method="post"
                                        class="action-form"
                                        onsubmit="return confirm('Delete this message permanently?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= msgE(csrfToken()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="message_id"
                                            value="<?= (int)$row['id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn delete"
                                        >
                                            DELETE
                                        </button>
                                    </form>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty">
                    NO CONTACT MESSAGES FOUND
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
