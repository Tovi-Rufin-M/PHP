<?php
// Technowatch Club | Homepage
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technowatch Club | Home</title>
</head>
<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <div class="nav-logo">
                <img src="assets/imgs/logo_dark.png" alt="Technowatch Logo" id="navbarLogo">
                <div class="nav-title">
                    <h2>TechnoWatch</h2>
                    <p>College of Computer Engineering Technology</p>
                </div>
            </div>
            <!-- Hamburger / Mobile Menu Button -->
            <button class="menu-btn">☰</button>  
            
            <!-- Navigation Links -->
            <nav class="nav-main">
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Home</a></li>

                    <!-- About TechnoWatch Dropdown -->
                    <li class="dropdown">
                    <a href="#">About</a>
                    <ul class="dropdown-menu">
                        <li><a href="mission.php">Mission & Vision</a></li>
                        <li><a href="#">Club Info</a></li>
                    </ul>
                    </li>

                    <!-- Admission Dropdown -->
                    <li class="dropdown">
                    <a href="#">Admission</a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Eligibility</a></li>
                        <li><a href="#">Requirements</a></li>
                        <li><a href="#">How to Apply</a></li>
                    </ul>
                    </li>

                    <!-- Organization Dropdown -->
                    <li class="dropdown">
                    <a href="#">Organization</a>
                    <ul class="dropdown-menu">
                        <li><a href="#">CET-Technowatch Officials</a></li>
                        <li><a href="#">Technowatch Club Officers</a></li>
                        <li><a href="#">Mayors (Every Year)</a></li>
                    </ul>
                    </li>

                    <!-- Updates Dropdown -->
                    <li class="dropdown">
                    <a href="#">Updates</a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Announcements</a></li>
                        <li><a href="#">Events & News</a></li>
                        <li><a href="#">Job Postings</a></li>
                    </ul>
                    </li>                
                    <!-- Merch / Admin / Dark Mode Dropdown (like Updates) -->
                    <li class="dropdown">
                    <a href="#">Others</a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Merch</a></li>
                        <li><a href="#">Admin Login</a></li>
                        <li>
                        <label class="darkmode-toggle">
                            <input type="checkbox" id="darkModeSwitch">
                            <span>☀️ Dark Mode Off</span>
                        </label>
                        </li>
                    </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </header>