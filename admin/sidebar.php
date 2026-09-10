<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$adminName = $_SESSION['admin_username']
    ?? 'System Administrator';
?>

<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-brand">

        <a href="index.php" class="brand" aria-label="Dionisio Fitness Center home">
      
        <span class="brand-text">
            <strong></strong>
             <img src="assets/navbar-brand.png" alt="Dionisio Fitness Center">
        </span>
    </a>


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
            href="memberships.php"
            class="sidebar-link
            <?= in_array(
                $currentPage,
                [
                    'memberships.php',
                    'view_membership.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >
            <span class="sidebar-icon">M</span>
            <span>Memberships</span>
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


        <a
            href="messages.php"
            class="sidebar-link
            <?= $currentPage === 'messages.php'
                ? 'active'
                : '' ?>"
        >
            <span class="sidebar-icon">✉</span>

            <span>
                Messages
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