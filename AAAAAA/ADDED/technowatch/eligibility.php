<?php
// Technowatch Club | Join Us - Eligibility
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligibility | Technowatch Club</title>
    <link rel="stylesheet" href="assets (1)/css (1)/index.css">
    <link rel="stylesheet" href="assets (1)/css (1)/join-us.css">
    <link rel="stylesheet" href="assets (1)/css (1)/responsive.css">
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include 'header.php'; ?>

<!-- BREADCRUMB -->
<nav class="breadcrumb">
    <div class="container">
        <a href="join-us.php">Join Us</a>
        <span class="separator">/</span>
        <span class="current">Eligibility</span>
    </div>
</nav>

<!-- MINI HERO -->
<section class="mini-hero eligibility-hero">
    <div class="container">
        <h1>Who Can Join?</h1>
        <p>We built this club for you—the curious, the builder, the problem-solver.</p>
        <div class="progress-indicator">
            <div class="step completed"><i class="fas fa-user-check"></i></div>
            <div class="step active"><i class="fas fa-clipboard-list"></i></div>
            <div class="step"><i class="fas fa-paper-plane"></i></div>
        </div>
    </div>
</section>

<!-- ELIGIBILITY CRITERIA -->
<section class="eligibility-detail">
    <div class="container">
        <div class="eligibility-grid">
            <div class="eligibility-card" data-aos="fade-up">
                <div class="eligibility-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>All Majors Welcome</h3>
                <p>CS, Engineering, Business, Art—if you're curious, you're in.</p>
                <div class="eligibility-check">
                    <i class="fas fa-check-circle"></i>
                    <span>No restriction</span>
                </div>
            </div>

            <div class="eligibility-card" data-aos="fade-up" data-aos-delay="100">
                <div class="eligibility-icon"><i class="fas fa-gauge-simple-low"></i></div>
                <h3>Any Skill Level</h3>
                <p>Beginner-friendly. We value curiosity over experience.</p>
                <div class="eligibility-check">
                    <i class="fas fa-check-circle"></i>
                    <span>Start from zero</span>
                </div>
            </div>

            <div class="eligibility-card" data-aos="fade-up" data-aos-delay="200">
                <div class="eligibility-icon"><i class="fas fa-id-badge"></i></div>
                <h3>TUP-V Students Only</h3>
                <p>Currently enrolled at Technological University of the Philippines Visayas.</p>
                <div class="eligibility-check">
                    <i class="fas fa-check-circle"></i>
                    <span>Valid ID required</span>
                </div>
            </div>
        </div>

        <!-- INCLUSIVITY MESSAGE -->
        <div class="inclusivity-quote" data-aos="fade-up">
            <h2>
                No experience? <strong>Perfect.</strong> We started there too. <br>
                Stop waiting—<em>start building</em>.
            </h2>
        </div>

        <!-- CTAs -->
        <div class="section-ctas">
            <a href="requirements.php" class="cta-primary">See Requirements</a>
            <a href="https://discord.gg/technowatch" class="cta-secondary">Join Info Session</a>
        </div>
    </div>
</section>

<!-- RELATED INFO -->
<section class="related-info">
    <div class="container">
        <h3>Not sure if you're eligible?</h3>
        <p>Contact us directly and we'll help you figure it out.</p>
        <div class="contact-quick">
            <a href="mailto:technowatch.tupv@email.com" class="contact-chip">
                <i class="fas fa-envelope"></i> Email Officers
            </a>
            <a href="https://m.me/technowatchclub" class="contact-chip">
                <i class="fab fa-facebook"></i> FB Message
            </a>
        </div>
    </div>
</section>

<?php include 'footer (1).php'; ?>
<script src="assets (1)/js (1)/script (1).js" defer></script>
<script src="assets (1)/js (1)/join-us.js" defer></script>

</body>
</html>