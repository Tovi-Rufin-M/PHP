<?php $current_page = basename($_SERVER['PHP_SELF']); ?>


<?php
$about_pages = ['missionVision.php', 'clubInfo.php'];
$admission_pages = ['eligibility.php', 'requirements.php', 'how-to-apply.php'];
$organization_pages = ['officials.php', 'officers.php'];
$updates_pages = ['eventsNews.php', 'jobposting.php', 'projects.php'];

?>

<header class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <img src="assets/imgs/logo_white.png" alt="Technowatch Logo" id="navbarLogo">
            <div class="nav-title">
                <h2>TechnoWatch</h2>
                <p>College of Computer Engineering Technology</p>
            </div>
        </div>
        <!-- <button class="menu-btn">Menu</button> -->
        
        <nav class="nav-main">
            <ul class="nav-links">
                <li><a href="index.php" class="<?php if ($current_page == 'index.php') echo 'active'; ?>">Home</a></li>

                <li class="dropdown">
                    <a href="clubInfo.php" class="<?php if (in_array($current_page, $about_pages)) echo 'active'; ?>">About</a>
                    <ul class="dropdown-menu">
                        <li><a href="missionVision.php" class="<?php if ($current_page == 'missionVision.php') echo 'active'; ?>">Mission & Vision</a></li>
                        <li><a href="clubInfo.php" class="<?php if ($current_page == 'clubInfo.php') echo 'active'; ?>">Club Info</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="join-us.php" class="<?php if (in_array($current_page, $admission_pages)) echo 'active'; ?>">Join Us</a>
                    <ul class="dropdown-menu">
                        <li><a href="eligibility.php" class="<?php if ($current_page == 'eligibility.php') echo 'active'; ?>">Eligibility</a></li>
                        <li><a href="requirements.php" class="<?php if ($current_page == 'requirements.php') echo 'active'; ?>">Requirements</a></li>
                        <li><a href="how-to-apply.php" class="<?php if ($current_page == 'how-to-apply.php') echo 'active'; ?>">How to Apply</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="<?php if (in_array($current_page, $organization_pages)) echo 'active'; ?>">Organization</a>
                    <ul class="dropdown-menu">
                        <li><a href="officials.php" class="<?php if ($current_page == 'officials.php') echo 'active'; ?>">Technowatch Officials</a></li>
                        <li><a href="officers.php" class="<?php if ($current_page == 'officers.php') echo 'active'; ?>">Technowatch Club Officers</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="<?php if (in_array($current_page, $updates_pages)) echo 'active'; ?>">Resources</a>
                    <ul class="dropdown-menu">
                        <li><a href="eventsNews.php" class="<?php if ($current_page == 'eventsNews.php') echo 'active'; ?>">Events & News</a></li>
                        <li><a href="jobposting.php" class="<?php if ($current_page == 'jobposting.php') echo 'active'; ?>">Job Postings</a></li>
                        <li><a href="projects.php" class="<?php if ($current_page == 'projects.php') echo 'active'; ?>">Projects</a></li>
                    </ul>
                </li>

                <li><a href="merch.php" class="<?php if ($current_page == 'merch.php') echo 'active'; ?>">Merchandise</a></li>
            </ul>
        </nav>
    </div>
</header>
