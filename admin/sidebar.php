<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$adminName = $_SESSION['admin_username']
    ?? 'System Administrator';
?>

<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-brand">

        <a href="index.php" class="sidebar-logo">
            <span class="sidebar-logo-mark">D</span>

            <span class="sidebar-logo-text">
                <strong>DIONISIO</strong>
                <small>FITNESS CENTER</small>
            </span>
        </a>

    </div>


    <div class="sidebar-section-label">
        MAIN MENU
    </div>


    <nav class="sidebar-nav">

        <a
            href="index.php"
            class="sidebar-link
            <?= $currentPage === 'index.php'
                ? 'active'
                : '' ?>"
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
            class="sidebar-link
            <?= in_array(
                $currentPage,
                [
                    'users.php',
                    'user_details.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >
            <span class="sidebar-icon">
                ♙
            </span>

            <span>
                Users
            </span>
        </a>


        <a
            href="bookings.php"
            class="sidebar-link
            <?= $currentPage === 'bookings.php'
                ? 'active'
                : '' ?>"
        >
            <span class="sidebar-icon">
                ◆
            </span>

            <span>
                Bookings
            </span>
        </a>


        <a
            href="orders.php"
            class="sidebar-link
            <?= in_array(
                $currentPage,
                [
                    'orders.php',
                    'order_details.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >
            <span class="sidebar-icon">
                ▤
            </span>

            <span>
                Orders
            </span>
        </a>

    </nav>


    <div class="sidebar-bottom">

        <div class="sidebar-admin">

            <div class="sidebar-admin-avatar">

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            (string)$adminName,
                            0,
                            1
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>


            <div class="sidebar-admin-info">

                <span>
                    ADMINISTRATOR
                </span>

                <strong>

                    <?= htmlspecialchars(
                        (string)$adminName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

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


<button
    type="button"
    class="sidebar-mobile-toggle"
    id="sidebarToggle"
    aria-label="Toggle admin navigation"
>
    ☰
</button>


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
                    'sidebar-open'
                );

            }
        );

    }
);
</script>