<?php
// Technowatch Club | Advisers Page - FINAL REVISION
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technowatch Club | Club Officials</title>

    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/officials.css"> 
    
<script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>


    <div class="org-chart-container professional-theme">
        <div class="head-container">
            <div class="hero-card head-card head-gradient">
                
                <div class="text-info">
                    <p class="role-tag head-tag-white">DEPARTMENT HEAD</p>
                    <h2 class="hero-name head-name-white">JOHN PAULO N.</h2>
                    <p class="motto-text">A driving force for innovation.</p>
                </div>
                
                <div class="profile-image head-image" 
                    style="background-image: url('assets/officials/jpn.jpg');">
                </div>
            </div>
        </div>

        <h3 class="staff-grid-title">VISIONARY LEADERS</h3>
        <div class="professors-grid">
            
            <div class="hero-card prof-card light-dark-card">
                <div class="profile-image prof-image" 
                    style="background-image: url('assets/officials/josh.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">MANAGER</p>
                    <h3 class="hero-name staff-name-white">JOSHCULLEN S.</h3>
                </div>
            </div>
            
            <div class="hero-card prof-card light-dark-card">
                <div class="profile-image prof-image" 
                    style="background-image: url('assets/officials/fjs.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">MANAGER</p>
                    <h3 class="hero-name staff-name-white">FELIP JHON S.</h3>
                </div>
            </div>

            <div class="hero-card prof-card light-dark-card">
                <div class="profile-image prof-image" 
                    style="background-image: url('assets/officials/jah.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">MANAGER</p>
                    <h3 class="hero-name staff-name-white">JUSTIN D.</h3>
                </div>
            </div>

            <div class="hero-card prof-card light-dark-card">
                <div class="profile-image prof-image" 
                    style="background-image: url('assets/officials/stell.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">DEVELOPER</p>
                    <h3 class="hero-name staff-name-white">STELL A.</h3>
                </div>
            </div>

            <div class="hero-card prof-card light-dark-card">
                <div class="profile-image prof-image" 
                    style="background-image: url('assets/officials/download (1).jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">DEVELOPER</p>
                    <h3 class="hero-name staff-name-white">PURPLE CAR</h3>
                </div>
            </div>
        </div>

        <h3 class="staff-grid-title">MAYORS</h3>
        <div class="mayors-grid">
            <div class="hero-card mayor-card">
                <div class="profile-image mayor-image" 
                    style="background-image: url('assets/officials/fjs.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">S09 MAYOR</p>
                    <h3 class="hero-name staff-name-white">ALTHEA M.</h3>
                </div>
            </div>

            <div class="hero-card mayor-card">
                <div class="profile-image mayor-image" 
                    style="background-image: url('assets/officials/stell.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">T09 MAYOR</p>
                    <h3 class="hero-name staff-name-white">ENZO R.</h3>
                </div>
            </div>

            <div class="hero-card mayor-card">
                <div class="profile-image mayor-image" 
                    style="background-image: url('assets/officials/jah.jpg');">
                </div>
                <div class="text-info staff-text-bottom">
                    <p class="role-tag staff-role-subtle">F09 MAYOR</p>
                    <h3 class="hero-name staff-name-white">LAYA C.</h3>
                </div>
            </div>
        </div>

        <div class="officials-link-container">
            <a href="officers.php" class="officials-link-button">
                See Our Club Officers
            </a>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>