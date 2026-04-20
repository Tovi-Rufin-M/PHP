<?php
// Technowatch Club | Club Officers Page
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technowatch Club | Club Officers</title>

    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/officials.css"> 
    <link rel="stylesheet" href="assets/css/officers.css">
    
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include 'header.php'; ?>


<div class="org-chart-container professional-theme">
    
    <div class="head-container">
        <div class="hero-card head-card club-president-gradient"> 
            
            <div class="text-info">
                <p class="role-tag head-tag-white">CLUB PRESIDENT</p> 
                <h2 class="hero-name head-name-white">JUAN DELA CRUZ</h2>
                <p class="motto-text">Leading the future, together.</p>
            </div>
            
            <div class="profile-image head-image" 
                 style="background-image: url('assets/officials/josh.jpg');">
            </div>
        </div>
    </div>

    <h3 class="staff-grid-title">CLUB EXECUTIVE COMMITTEE</h3> 
    <div class="professors-grid">
        
        <div class="hero-card prof-card club-officer-card"> 
            <div class="profile-image prof-image" 
                 style="background-image: url('assets/officials/fjs.jpg');">
            </div>
            <div class="text-info staff-text-bottom">
                <p class="role-tag staff-role-subtle">VICE PRESIDENT</p>
                <h3 class="hero-name staff-name-white">MARIA S.</h3>
            </div>
        </div>
        
        <div class="hero-card prof-card club-officer-card"> 
            <div class="profile-image prof-image" 
                 style="background-image: url('assets/officials/jah.jpg');">
            </div>
            <div class="text-info staff-text-bottom">
                <p class="role-tag staff-role-subtle">SECRETARY</p>
                <h3 class="hero-name staff-name-white">PEDRO M.</h3>
            </div>
        </div>
        
        <div class="hero-card prof-card club-officer-card"> 
            <div class="profile-image prof-image" 
                 style="background-image: url('assets/officials/stell.jpg');">
            </div>
            <div class="text-info staff-text-bottom">
                <p class="role-tag staff-role-subtle">TREASURER</p>
                <h3 class="hero-name staff-name-white">SARA L.</h3>
            </div>
        </div>
        <div class="hero-card prof-card light-dark-card">
            <div class="profile-image prof-image" 
                style="background-image: url('assets/officials/jpn.jpg');">
            </div>
            <div class="text-info staff-text-bottom">
                <p class="role-tag staff-role-subtle">ANALYST</p>
                <h3 class="hero-name staff-name-white">PAULO V.</h3>
            </div>
        </div> 
    </div>
    <div class="officials-link-container">
        <a href="officials.php" class="officials-link-button club-officers-style">
            See Our Club Officials & Advisers
        </a>
    </div>
</div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>