<div class="sidebar-header">
    <h2>Technowatch Admin</h2>
</div>
<ul class="nav-list">
    <li>
        <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <li>
        <a href="manage_jobs.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_jobs.php') ? 'active' : ''; ?>">
            <i class="fas fa-briefcase"></i>
            <span>Manage Jobs</span>
        </a>
    </li>
    <li>
        <a href="manage_events_news.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_events_news.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Manage Events & News</span>
        </a>
    </li>
    <li>
        <a href="manage_merch.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_merch.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-store"></i>
            <span>Manage Merchandise</span>
        </a>
    </li>
    <li>
        <a href="manage_officers.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_officers.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-store"></i>
            <span>Manage Officers</span>
        </a>
    </li>
    <li>
        <a href="manage_officials.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_officials.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Manage Officials</span>
        </a>
    </li>
    <li>
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </li>
</ul>

