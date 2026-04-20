<?php
// Technowatch Club | Join Us - Requirements
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requirements | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">
    
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/join-us.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include 'header.php'; ?>

<!-- BREADCRUMB -->
<nav class="breadcrumb">
    <div class="container">
        <a href="join-us.php">Join Us</a>
        <span class="separator">/</span>
        <a href="eligibility.php">Eligibility</a>
        <span class="separator">/</span>
        <span class="current">Requirements</span>
        <span class="separator">/</span>
        <a href="how-to-apply.php">How to Apply</a>
    </div>
</nav>

<!-- MINI HERO -->
<section class="mini-hero requirements-hero">
    <div class="container">
        <h1>What You Need</h1>
        <p>Minimal requirements. Maximum opportunity.</p>
        <div class="progress-indicator">
            <div class="step completed"><i class="fas fa-user-check"></i></div>
            <div class="step completed"><i class="fas fa-clipboard-list"></i></div>
            <div class="step active"><i class="fas fa-paper-plane"></i></div>
        </div>
    </div>
</section>

<!-- REQUIREMENTS TOGGLE -->
<section class="requirements-detail">
    <div class="container">
        <div class="requirements-toggle initialized">
            <div class="toggle-header">
                <h2>Application Requirements</h2>
                <button class="toggle-btn active" id="reqToggle">
                    <span>Hide Requirements</span>
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div class="requirements-content show" id="reqContent">
                <div class="req-columns">
                    <div class="req-column required">
                        <h4><i class="fas fa-star"></i> Required</h4>
                        <ul>
                            <li><i class="fas fa-id-card"></i> Valid TUP-V Student ID number</li>
                            <li><i class="fas fa-envelope"></i> Active @tupv.edu.ph email address</li>
                            <li><i class="fab fa-discord"></i> Discord account (for our community server)</li>
                            <li><i class="fas fa-heart"></i> Curiosity and willingness to learn (free!)</li>
                        </ul>
                    </div>
                    <div class="req-column optional">
                        <h4><i class="fas fa-plus-circle"></i> Optional Add-ons</h4>
                        <ul>
                            <li><i class="fas fa-laptop"></i> Your own laptop (lab PCs available)</li>
                            <li><i class="fas fa-code"></i> Basic coding interest (we'll teach you)</li>
                            <li><i class="fa-solid fa-calendar"></i> Weekend availability for builds</li>
                            <li><i class="fab fa-github"></i> GitHub profile (nice to have)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="requirements-note" data-aos="fade-up">
                    <i class="fas fa-info-circle"></i>
                    <p>We <strong>don't need</strong> transcripts, resumes, or recommendation letters—just your genuine enthusiasm to build!</p>
                </div>
            </div>
        </div>

        <!-- GOOD NEWS BOX -->
        <div class="good-news-box" data-aos="zoom-in">
            <div class="news-icon"><i class="fas fa-gift"></i></div>
            <div class="news-content">
                <h3>Good News!</h3>
                <p>All project materials, workshop resources, and mentorship are <strong>100% free</strong> for members.</p>
            </div>
        </div>

        <!-- CTAs -->
        <div class="section-ctas">
            <a href="how-to-apply.php" class="cta-primary">How to Apply</a>
            <a href="eligibility.php" class="cta-secondary">Back to Eligibility</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/script.js" defer></script>
<script src="assets/js/join-us.js" defer></script>

</body>
</html>