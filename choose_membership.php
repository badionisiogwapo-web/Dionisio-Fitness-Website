<?php
declare(strict_types=1);


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

requireLogin('choose_membership.php');

$userId = currentUserId();

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| MEMBERSHIP PLANS
|--------------------------------------------------------------------------
*/

$plans = [
    'Basic' => [
        'name' => 'BASIC',
        'price' => 799,
        'tagline' => 'START STRONG',
        'features' => [
            'Full gym floor access',
            'Standard equipment access',
            'Locker room access',
            'Member account access'
        ]
    ],

    'Premium' => [
        'name' => 'PREMIUM',
        'price' => 1299,
        'tagline' => 'MOST POPULAR',
        'features' => [
            'Everything in Basic',
            'Group class access',
            'Priority booking access',
            'Fitness progress support'
        ]
    ],

    'VIP' => [
        'name' => 'VIP',
        'price' => 1999,
        'tagline' => 'ALL ACCESS',
        'features' => [
            'Everything in Premium',
            'Priority gym services',
            'Premium member support',
            'Exclusive member benefits'
        ]
    ]
];


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


function membershipStatusClass(string $status): string
{
    return match (strtolower(trim($status))) {
        'active' => 'status-active',
        'pending' => 'status-pending',
        'cancelled', 'canceled', 'inactive' => 'status-cancelled',
        default => 'status-neutral'
    };
}


function membershipDate(?string $date): string
{
    if (!$date) {
        return 'Not started yet';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('M d, Y', $timestamp);
}


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$userStmt = $pdo->prepare(
    'SELECT
        id,
        full_name,
        email
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$userStmt->execute([$userId]);

$user = $userStmt->fetch();

if (!$user) {
    session_destroy();

    header('Location: login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| MEMBERSHIP LOADER
|--------------------------------------------------------------------------
|
| An Active membership and a Pending membership are intentionally loaded
| separately. This allows an existing Active plan to stay untouched while
| a member requests a change to another plan.
|
*/

function loadMembershipState(PDO $pdo, int $userId): array
{
    $active = null;
    $pending = null;
    $latest = null;

    $activeStmt = $pdo->prepare(
        "SELECT
            id,
            user_id,
            plan,
            status,
            start_date
         FROM memberships
         WHERE user_id = ?
           AND status = 'Active'
         ORDER BY id DESC
         LIMIT 1"
    );

    $activeStmt->execute([$userId]);
    $active = $activeStmt->fetch() ?: null;


    $pendingStmt = $pdo->prepare(
        "SELECT
            id,
            user_id,
            plan,
            status,
            start_date
         FROM memberships
         WHERE user_id = ?
           AND status = 'Pending'
         ORDER BY id DESC
         LIMIT 1"
    );

    $pendingStmt->execute([$userId]);
    $pending = $pendingStmt->fetch() ?: null;


    $latestStmt = $pdo->prepare(
        'SELECT
            id,
            user_id,
            plan,
            status,
            start_date
         FROM memberships
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 1'
    );

    $latestStmt->execute([$userId]);
    $latest = $latestStmt->fetch() ?: null;


    return [
        'active' => $active,
        'pending' => $pending,
        'latest' => $latest
    ];
}


try {

    $membershipState =
        loadMembershipState(
            $pdo,
            $userId
        );

} catch (PDOException $exception) {

    $membershipState = [
        'active' => null,
        'pending' => null,
        'latest' => null
    ];

    $error =
        'Membership information could not be loaded. Make sure the memberships table exists.';
}


/*
|--------------------------------------------------------------------------
| HANDLE MEMBERSHIP ACTIONS
|--------------------------------------------------------------------------
|
| Supported actions:
|
| choose  = create a new Pending membership or update an existing Pending
| cancel  = cancel only a Pending membership request
|
| IMPORTANT:
| An Active membership is never overwritten from this customer page.
|
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $error === ''
) {

    if (
        !verifyCsrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {

        $error =
            'Your session expired. Please refresh the page and try again.';

    } else {

        $action =
            trim(
                (string)(
                    $_POST['action']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | CHOOSE / CHANGE PLAN
        |--------------------------------------------------------------------------
        */

        if ($action === 'choose') {

            $selectedPlan =
                trim(
                    (string)(
                        $_POST['plan']
                        ?? ''
                    )
                );


            if (
                $selectedPlan === ''
                || !array_key_exists(
                    $selectedPlan,
                    $plans
                )
            ) {

                $error =
                    'Please choose a valid membership plan.';

            } else {

                try {

                    $pdo->beginTransaction();


                    /*
                     * Lock all Active/Pending rows for this user while
                     * processing the request.
                     */

                    $lockStmt = $pdo->prepare(
                        "SELECT
                            id,
                            plan,
                            status,
                            start_date
                         FROM memberships
                         WHERE user_id = ?
                           AND status IN ('Active', 'Pending')
                         ORDER BY id DESC
                         FOR UPDATE"
                    );

                    $lockStmt->execute([$userId]);

                    $rows =
                        $lockStmt->fetchAll();


                    $activeMembership = null;
                    $pendingMembership = null;


                    foreach ($rows as $row) {

                        if (
                            $activeMembership === null
                            && strcasecmp(
                                (string)$row['status'],
                                'Active'
                            ) === 0
                        ) {

                            $activeMembership = $row;
                        }


                        if (
                            $pendingMembership === null
                            && strcasecmp(
                                (string)$row['status'],
                                'Pending'
                            ) === 0
                        ) {

                            $pendingMembership = $row;
                        }
                    }


                    /*
                     * Same as the already-pending request.
                     */

                    if (
                        $pendingMembership
                        && strcasecmp(
                            (string)$pendingMembership['plan'],
                            $selectedPlan
                        ) === 0
                    ) {

                        throw new RuntimeException(
                            'You already have a pending request for the '
                            . $plans[$selectedPlan]['name']
                            . ' plan.'
                        );
                    }


                    /*
                     * No pending request exists and the selected plan
                     * is already the user's active plan.
                     */

                    if (
                        !$pendingMembership
                        && $activeMembership
                        && strcasecmp(
                            (string)$activeMembership['plan'],
                            $selectedPlan
                        ) === 0
                    ) {

                        throw new RuntimeException(
                            'The '
                            . $plans[$selectedPlan]['name']
                            . ' plan is already your active membership.'
                        );
                    }


                    /*
                     * If a pending request already exists, update only
                     * that Pending row. Never modify the Active row.
                     */

                    if ($pendingMembership) {

                        /*
                         * If the member has an Active membership and
                         * chooses that same Active plan again, canceling
                         * the pending change is the correct action.
                         */

                        if (
                            $activeMembership
                            && strcasecmp(
                                (string)$activeMembership['plan'],
                                $selectedPlan
                            ) === 0
                        ) {

                            throw new RuntimeException(
                                'That is already your active plan. Use CANCEL REQUEST below if you want to keep your current membership.'
                            );
                        }


                        $updateStmt = $pdo->prepare(
    "UPDATE memberships
     SET
        plan = ?,
        status = 'Pending',
        start_date = CURDATE()
     WHERE id = ?
       AND user_id = ?
       AND status = 'Pending'"
);

                        $updateStmt->execute([
                            $selectedPlan,
                            (int)$pendingMembership['id'],
                            $userId
                        ]);


                        if ($updateStmt->rowCount() < 1) {

                            throw new RuntimeException(
                                'Your membership request could not be updated. Please try again.'
                            );
                        }


                        $success =
                            'Your pending membership request has been changed to '
                            . $plans[$selectedPlan]['name']
                            . '.';

                    } else {

                        /*
                         * Create a brand-new Pending request.
                         * If an Active membership exists, it stays Active.
                         */

                       $insertStmt = $pdo->prepare(
    "INSERT INTO memberships
        (
            user_id,
            plan,
            status,
            start_date
        )
     VALUES
        (
            ?,
            ?,
            'Pending',
            CURDATE()
        )"
);

                        $insertStmt->execute([
                            $userId,
                            $selectedPlan
                        ]);


                        if ($activeMembership) {

                            $success =
                                'Your request to change from '
                                . strtoupper(
                                    (string)$activeMembership['plan']
                                )
                                . ' to '
                                . $plans[$selectedPlan]['name']
                                . ' has been submitted and is pending approval. Your current plan remains active until the request is approved.';

                        } else {

                            $success =
                                'Your '
                                . $plans[$selectedPlan]['name']
                                . ' membership request has been submitted successfully and is pending approval.';
                        }
                    }


                    $pdo->commit();

                } catch (RuntimeException $exception) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error =
                        $exception->getMessage();

                } catch (PDOException $exception) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error =
                        'Your membership request could not be saved. Please try again.';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CANCEL PENDING REQUEST
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'cancel') {

            $membershipId =
                filter_var(
                    $_POST['membership_id']
                    ?? null,
                    FILTER_VALIDATE_INT
                );


            if (
                !$membershipId
                || $membershipId < 1
            ) {

                $error =
                    'Invalid membership request.';

            } else {

                try {

                    $cancelStmt = $pdo->prepare(
                        "UPDATE memberships
                         SET status = 'Cancelled'
                         WHERE id = ?
                           AND user_id = ?
                           AND status = 'Pending'"
                    );

                    $cancelStmt->execute([
                        $membershipId,
                        $userId
                    ]);


                    if ($cancelStmt->rowCount() < 1) {

                        $error =
                            'That request is no longer pending or could not be cancelled.';

                    } else {

                        $success =
                            'Your pending membership request has been cancelled.';
                    }

                } catch (PDOException $exception) {

                    $error =
                        'Your membership request could not be cancelled. Please try again.';
                }
            }
        }


        else {

            $error =
                'Invalid membership action.';
        }


        /*
        |--------------------------------------------------------------------------
        | REFRESH DISPLAY
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            try {

                $membershipState =
                    loadMembershipState(
                        $pdo,
                        $userId
                    );

            } catch (PDOException $exception) {

                $error =
                    'Your request was saved, but the updated membership information could not be reloaded.';
            }
        }
    }
}


$activeMembership =
    $membershipState['active'];

$pendingMembership =
    $membershipState['pending'];

$latestMembership =
    $membershipState['latest'];

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
        Choose Membership | Dionisio Fitness Center
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
            --red-hover: #ef252d;
            --bg: #080808;
            --panel: #101010;
            --panel-2: #0c0c0c;
            --border: #242424;
            --text: #f4f4f4;
            --muted: #7c7c7c;
            --green: #5fd08a;
            --yellow: #e7b94f;
            --danger: #ef686e;
        }


        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(215, 25, 32, 0.09),
                    transparent 34%
                ),
                var(--bg);
            color: var(--text);
            font-family:
                "Montserrat",
                Arial,
                sans-serif;
        }


        button,
        input {
            font: inherit;
        }


        .page {
            width:
                min(
                    1220px,
                    calc(100% - 32px)
                );
            margin: 0 auto;
            padding: 26px 0 50px;
        }


        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 30px;
        }


        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            text-decoration: none;
        }


        .brand-mark {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            background: var(--red);
            font-size: 23px;
            font-weight: 900;
        }


        .brand-copy {
            display: flex;
            flex-direction: column;
        }


        .brand-copy strong {
            font-size: 13px;
            font-weight: 900;
        }


        .brand-copy small {
            margin-top: 3px;
            color: #676767;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }


        .top-actions {
            display: flex;
            gap: 8px;
        }


        .top-link {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border: 1px solid #2b2b2b;
            color: #838383;
            text-decoration: none;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1px;
        }


        .top-link:hover {
            color: #ffffff;
            border-color: #484848;
        }


        .hero {
            position: relative;
            overflow: hidden;
            padding: 42px 36px;
            margin-bottom: 16px;
            background:
                linear-gradient(
                    135deg,
                    #181818,
                    #0c0c0c
                );
            border: 1px solid var(--border);
            border-left: 4px solid var(--red);
        }


        .hero::after {
            content: "DFC";
            position: absolute;
            top: -28px;
            right: 20px;
            color: rgba(255, 255, 255, 0.025);
            font-size: 130px;
            font-weight: 900;
            letter-spacing: -8px;
            pointer-events: none;
        }


        .eyebrow {
            margin: 0;
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 2.4px;
        }


        .hero h1 {
            position: relative;
            z-index: 1;
            margin: 10px 0 0;
            max-width: 760px;
            font-size:
                clamp(
                    34px,
                    6vw,
                    62px
                );
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -3px;
        }


        .hero h1 span {
            color: var(--red);
        }


        .hero p {
            position: relative;
            z-index: 1;
            max-width: 650px;
            margin: 18px 0 0;
            color: #858585;
            font-size: 10px;
            line-height: 1.8;
        }


        .alert {
            margin-bottom: 16px;
            padding: 15px 17px;
            border: 1px solid;
            font-size: 10px;
            line-height: 1.7;
        }


        .alert-error {
            background: rgba(215, 25, 32, 0.07);
            border-color: rgba(215, 25, 32, 0.3);
            color: #ef858a;
        }


        .alert-success {
            background: rgba(95, 208, 138, 0.06);
            border-color: rgba(95, 208, 138, 0.27);
            color: var(--green);
        }


        .membership-state-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            gap: 14px;
            margin-bottom: 22px;
        }


        .state-card {
            padding: 20px;
            background: var(--panel);
            border: 1px solid var(--border);
        }


        .state-card > span {
            display: block;
            margin-bottom: 8px;
            color: #5e5e5e;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }


        .state-main {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }


        .state-plan {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
        }


        .state-date {
            margin: 10px 0 0;
            color: #777777;
            font-size: 8px;
            line-height: 1.7;
        }


        .status {
            flex: 0 0 auto;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #303030;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 0.8px;
        }


        .status-active {
            color: var(--green);
            border-color: rgba(95, 208, 138, 0.27);
        }


        .status-pending {
            color: var(--yellow);
            border-color: rgba(231, 185, 79, 0.27);
        }


        .status-cancelled {
            color: var(--danger);
            border-color: rgba(239, 104, 110, 0.27);
        }


        .status-neutral {
            color: #888888;
        }


        .pending-actions {
            margin-top: 15px;
        }


        .cancel-button {
            min-height: 37px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 13px;
            border: 1px solid rgba(239, 104, 110, 0.32);
            background: transparent;
            color: var(--danger);
            cursor: pointer;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 0.9px;
        }


        .cancel-button:hover {
            background: rgba(239, 104, 110, 0.08);
        }


        .empty-state-card {
            color: #6d6d6d;
            font-size: 9px;
            line-height: 1.7;
        }


        .section-heading {
            margin: 27px 0 14px;
        }


        .section-heading span {
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 2px;
        }


        .section-heading h2 {
            margin: 6px 0 0;
            font-size: 24px;
            font-weight: 900;
        }


        .plans-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );
            gap: 14px;
        }


        .plan-card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 430px;
            padding: 25px;
            background: var(--panel);
            border: 1px solid var(--border);
            transition:
                transform 0.2s ease,
                border-color 0.2s ease;
        }


        .plan-card:hover {
            transform: translateY(-3px);
            border-color: #444444;
        }


        .plan-card.recommended {
            background:
                linear-gradient(
                    160deg,
                    #191919,
                    #0d0d0d
                );
            border-color: rgba(215, 25, 32, 0.65);
        }


        .plan-card.current-active {
            border-color: rgba(95, 208, 138, 0.35);
        }


        .plan-card.current-pending {
            border-color: rgba(231, 185, 79, 0.35);
        }


        .corner-label {
            position: absolute;
            top: 0;
            right: 0;
            padding: 8px 10px;
            background: var(--red);
            color: #ffffff;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 1px;
        }


        .corner-label.active {
            background: #1f7240;
        }


        .corner-label.pending {
            background: #80651c;
        }


        .plan-tag {
            color: var(--red);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.6px;
        }


        .plan-card h3 {
            margin: 14px 0 0;
            font-size: 28px;
            font-weight: 900;
        }


        .price {
            display: flex;
            align-items: flex-end;
            gap: 7px;
            margin-top: 20px;
        }


        .price strong {
            font-size: 35px;
            font-weight: 900;
            letter-spacing: -1.5px;
        }


        .price span {
            padding-bottom: 5px;
            color: #666666;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: 0.8px;
        }


        .divider {
            height: 1px;
            margin: 22px 0;
            background: #242424;
        }


        .features {
            display: flex;
            flex-direction: column;
            gap: 13px;
            margin: 0 0 24px;
            padding: 0;
            list-style: none;
        }


        .features li {
            position: relative;
            padding-left: 18px;
            color: #929292;
            font-size: 9px;
            line-height: 1.55;
        }


        .features li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--red);
            font-weight: 900;
        }


        .plan-form {
            margin-top: auto;
        }


        .choose-button {
            width: 100%;
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: var(--red);
            color: #ffffff;
            cursor: pointer;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1px;
        }


        .choose-button:hover {
            background: var(--red-hover);
        }


        .choose-button.disabled {
            background: #232323;
            color: #666666;
            cursor: not-allowed;
        }


        .note {
            margin-top: 18px;
            padding: 17px;
            background: #0d0d0d;
            border: 1px solid #202020;
            color: #696969;
            font-size: 8px;
            line-height: 1.8;
        }


        .confirm-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.78);
        }


        .confirm-overlay.open {
            display: flex;
        }


        .confirm-box {
            width: min(440px, 100%);
            padding: 26px;
            background: #101010;
            border: 1px solid #292929;
            border-top: 3px solid var(--red);
        }


        .confirm-box h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
        }


        .confirm-box h3 span {
            color: var(--red);
        }


        .confirm-box p {
            margin: 14px 0 0;
            color: #858585;
            font-size: 9px;
            line-height: 1.8;
        }


        .confirm-actions {
            display: flex;
            gap: 9px;
            margin-top: 22px;
        }


        .confirm-actions button {
            flex: 1;
            min-height: 42px;
            cursor: pointer;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0.8px;
        }


        .confirm-no {
            border: 1px solid #343434;
            background: transparent;
            color: #8a8a8a;
        }


        .confirm-yes {
            border: 1px solid var(--red);
            background: var(--red);
            color: #ffffff;
        }


        @media (max-width: 850px) {

            .plans-grid,
            .membership-state-grid {
                grid-template-columns: 1fr;
            }


            .plan-card {
                min-height: 0;
            }

        }


        @media (max-width: 620px) {

            .page {
                width: min(100% - 20px, 1220px);
                padding-top: 16px;
            }


            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }


            .top-actions {
                width: 100%;
            }


            .top-link {
                flex: 1;
            }


            .hero {
                padding: 28px 22px;
            }


            .hero::after {
                font-size: 80px;
            }


            .state-main {
                flex-direction: column;
            }


            .confirm-actions {
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<main class="page">


    <header class="topbar">


        <a
            href="index.php"
            class="brand"
        >

            <span class="brand-mark">
                D
            </span>

            <span class="brand-copy">

                <strong>
                    DIONISIO
                </strong>

                <small>
                    FITNESS CENTER
                </small>

            </span>

        </a>


        <div class="top-actions">

            <a
                href="account.php"
                class="top-link"
            >
                MY ACCOUNT
            </a>

            <a
                href="profile.php"
                class="top-link"
            >
                PROFILE
            </a>

        </div>


    </header>


    <section class="hero">

        <p class="eyebrow">
            DIONISIO MEMBERSHIP
        </p>

        <h1>
            CHOOSE YOUR
            <span>
                MEMBERSHIP.
            </span>
        </h1>

        <p>

            Choose your preferred fitness membership.
            New requests are submitted as Pending for
            administrator approval. If you already have
            an Active membership, it remains active while
            a plan-change request is being reviewed.

        </p>

    </section>


    <?php if ($error !== ''): ?>

        <div class="alert alert-error">

            <?= h($error) ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?= h($success) ?>

        </div>

    <?php endif; ?>


    <section class="membership-state-grid">


        <article class="state-card">

            <span>
                ACTIVE MEMBERSHIP
            </span>


            <?php if ($activeMembership): ?>


                <div class="state-main">

                    <div>

                        <h2 class="state-plan">

                            <?= h(
                                strtoupper(
                                    (string)$activeMembership['plan']
                                )
                            ) ?>

                        </h2>

                        <p class="state-date">

                            Started:
                            <?= h(
                                membershipDate(
                                    $activeMembership['start_date']
                                )
                            ) ?>

                        </p>

                    </div>


                    <span class="status status-active">
                        ACTIVE
                    </span>

                </div>


            <?php else: ?>


                <div class="empty-state-card">

                    You do not currently have an Active
                    membership plan.

                </div>


            <?php endif; ?>


        </article>


        <article class="state-card">

            <span>
                PENDING REQUEST
            </span>


            <?php if ($pendingMembership): ?>


                <div class="state-main">

                    <div>

                        <h2 class="state-plan">

                            <?= h(
                                strtoupper(
                                    (string)$pendingMembership['plan']
                                )
                            ) ?>

                        </h2>

                        <p class="state-date">

                            Waiting for administrator approval.

                        </p>

                    </div>


                    <span class="status status-pending">
                        PENDING
                    </span>

                </div>


                <div class="pending-actions">


                    <form
                        method="POST"
                        class="confirm-form"
                        data-confirm-title="CANCEL REQUEST?"
                        data-confirm-message="Are you sure you want to cancel your pending membership request?"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= h(csrfToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="cancel"
                        >

                        <input
                            type="hidden"
                            name="membership_id"
                            value="<?= (int)$pendingMembership['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="cancel-button"
                        >
                            CANCEL REQUEST
                        </button>

                    </form>


                </div>


            <?php else: ?>


                <div class="empty-state-card">

                    You have no membership request waiting
                    for approval.

                </div>


            <?php endif; ?>


        </article>


    </section>


    <div class="section-heading">

        <span>
            AVAILABLE PLANS
        </span>

        <h2>
            SELECT YOUR PLAN
        </h2>

    </div>


    <section class="plans-grid">


        <?php foreach ($plans as $planKey => $plan): ?>


            <?php

            $isActivePlan =
                $activeMembership
                && strcasecmp(
                    (string)$activeMembership['plan'],
                    $planKey
                ) === 0;

            $isPendingPlan =
                $pendingMembership
                && strcasecmp(
                    (string)$pendingMembership['plan'],
                    $planKey
                ) === 0;

            $isPremium =
                $planKey === 'Premium';

            $disabled =
                $isPendingPlan
                || (
                    $isActivePlan
                    && !$pendingMembership
                );

            $cardClass = '';

            if ($isActivePlan) {
                $cardClass = 'current-active';
            } elseif ($isPendingPlan) {
                $cardClass = 'current-pending';
            } elseif ($isPremium) {
                $cardClass = 'recommended';
            }

            ?>


            <article
                class="plan-card <?= h($cardClass) ?>"
            >


                <?php if ($isActivePlan): ?>

                    <span class="corner-label active">
                        ACTIVE PLAN
                    </span>

                <?php elseif ($isPendingPlan): ?>

                    <span class="corner-label pending">
                        PENDING
                    </span>

                <?php elseif ($isPremium): ?>

                    <span class="corner-label">
                        MOST POPULAR
                    </span>

                <?php endif; ?>


                <span class="plan-tag">

                    <?= h($plan['tagline']) ?>

                </span>


                <h3>

                    <?= h($plan['name']) ?>

                </h3>


                <div class="price">

                    <strong>

                        ₱<?= number_format(
                            (float)$plan['price'],
                            0
                        ) ?>

                    </strong>

                    <span>
                        / MONTH
                    </span>

                </div>


                <div class="divider"></div>


                <ul class="features">


                    <?php foreach ($plan['features'] as $feature): ?>

                        <li>
                            <?= h($feature) ?>
                        </li>

                    <?php endforeach; ?>


                </ul>


                <form
                    method="POST"
                    class="plan-form confirm-form"
                    data-confirm-title="CONFIRM MEMBERSHIP?"
                    data-confirm-message="Submit this membership selection for administrator approval?"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h(csrfToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="choose"
                    >

                    <input
                        type="hidden"
                        name="plan"
                        value="<?= h($planKey) ?>"
                    >


                    <button
                        type="submit"
                        class="choose-button <?= $disabled ? 'disabled' : '' ?>"
                        <?= $disabled ? 'disabled' : '' ?>
                    >


                        <?php if ($isPendingPlan): ?>

                            REQUEST PENDING

                        <?php elseif (
                            $isActivePlan
                            && !$pendingMembership
                        ): ?>

                            CURRENT ACTIVE PLAN

                        <?php elseif ($pendingMembership): ?>

                            CHANGE REQUEST TO
                            <?= h($plan['name']) ?>

                        <?php elseif ($activeMembership): ?>

                            REQUEST CHANGE TO
                            <?= h($plan['name']) ?>

                        <?php else: ?>

                            CHOOSE
                            <?= h($plan['name']) ?>

                        <?php endif; ?>


                    </button>


                </form>


            </article>


        <?php endforeach; ?>


    </section>


    <div class="note">

        <strong>How it works:</strong>
        selecting a plan creates a Pending request.
        The administrator must approve it before it becomes
        Active. If you already have an Active plan, that plan
        stays Active until the new request is approved.
        You may change or cancel a Pending request before approval.

    </div>


</main>


<div
    class="confirm-overlay"
    id="confirmOverlay"
    aria-hidden="true"
>

    <div class="confirm-box">

        <h3 id="confirmTitle">
            CONFIRM <span>ACTION?</span>
        </h3>

        <p id="confirmMessage">
            Are you sure?
        </p>

        <div class="confirm-actions">

            <button
                type="button"
                class="confirm-no"
                id="confirmNo"
            >
                NO / CANCEL
            </button>

            <button
                type="button"
                class="confirm-yes"
                id="confirmYes"
            >
                YES, CONTINUE
            </button>

        </div>

    </div>

</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const overlay =
            document.getElementById(
                "confirmOverlay"
            );

        const title =
            document.getElementById(
                "confirmTitle"
            );

        const message =
            document.getElementById(
                "confirmMessage"
            );

        const noButton =
            document.getElementById(
                "confirmNo"
            );

        const yesButton =
            document.getElementById(
                "confirmYes"
            );

        let pendingForm = null;


        document
            .querySelectorAll(
                ".confirm-form"
            )
            .forEach(
                function (form) {

                    form.addEventListener(
                        "submit",
                        function (event) {

                            if (
                                form.dataset.confirmed
                                === "yes"
                            ) {
                                return;
                            }

                            event.preventDefault();

                            pendingForm = form;

                            title.textContent =
                                form.dataset
                                    .confirmTitle
                                || "CONFIRM ACTION?";

                            message.textContent =
                                form.dataset
                                    .confirmMessage
                                || "Are you sure?";

                            overlay.classList.add(
                                "open"
                            );

                            overlay.setAttribute(
                                "aria-hidden",
                                "false"
                            );
                        }
                    );
                }
            );


        function closeConfirm() {

            overlay.classList.remove(
                "open"
            );

            overlay.setAttribute(
                "aria-hidden",
                "true"
            );

            pendingForm = null;
        }


        noButton.addEventListener(
            "click",
            closeConfirm
        );


        overlay.addEventListener(
            "click",
            function (event) {

                if (event.target === overlay) {
                    closeConfirm();
                }
            }
        );


        yesButton.addEventListener(
            "click",
            function () {

                if (!pendingForm) {
                    return;
                }

                pendingForm.dataset.confirmed =
                    "yes";

                pendingForm.submit();
            }
        );

    }
);

</script>


</body>

</html>
