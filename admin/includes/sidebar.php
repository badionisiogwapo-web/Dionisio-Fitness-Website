<aside class="admin-sidebar">

    <div class="admin-brand">

        <div class="admin-brand-mark">
            D
        </div>

        <div class="admin-brand-text">

            <strong>DIONISIO</strong>

            <span>FITNESS CENTER</span>

        </div>

    </div>

    <div class="sidebar-label">
        MAIN MENU
    </div>

    <nav class="admin-nav">

        <a
            href="index.php"
            class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>"
        >
            <span>▦</span>
            Dashboard
        </a>

        <a
            href="users.php"
            class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>"
        >
            <span>♙</span>
            Users
        </a>

        <a
            href="memberships.php"
            class="<?php echo $currentPage === 'memberships.php' ? 'active' : ''; ?>"
        >
            <span>◆</span>
            Memberships
        </a>

        <a
            href="orders.php"
            class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>"
        >
            <span>▤</span>
            Orders
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="admin-user">

            <div class="admin-user-avatar">
                A
            </div>

            <div>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION['admin_username'] ?? 'Admin'
                    );
                    ?>
                </strong>

                <span>Administrator</span>

            </div>

        </div>

        <a
            href="logout.php"
            class="sidebar-logout"
            data-confirm="Are you sure you want to log out?"
        >
            LOG OUT
        </a>

    </div>

</aside>

<main class="admin-main">

    <header class="admin-topbar">

        <button
            class="mobile-menu-btn"
            id="mobileMenuBtn"
        >
            ☰
        </button>

        <div>

            <p class="topbar-label">
                DIONISIO FITNESS CENTER
            </p>

            <h1>
                <?php echo $pageTitle ?? 'Dashboard'; ?>
            </h1>

        </div>

        <a
            href="../index.php"
            target="_blank"
            class="view-site-btn"
        >
            VIEW WEBSITE ↗
        </a>

    </header>

    <div class="admin-content"></div>