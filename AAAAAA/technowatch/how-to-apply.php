<?php
// Technowatch Club | Join Us - How to Apply
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How to Apply | Technowatch Club</title>
    <link rel="stylesheet" href="assets/css/index.css">
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
        <a href="requirements.php">Requirements</a>
        <span class="separator">/</span>
        <span class="current">How to Apply</span>
    </div>
</nav>

<!-- MINI HERO -->
<section class="mini-hero apply-hero">
    <div class="container">
        <h1>How to Apply</h1>
        <p>Takes 5 minutes. Decision within 48 hours.</p>
        <div class="progress-indicator">
            <div class="step completed"><i class="fas fa-user-check"></i></div>
            <div class="step completed"><i class="fas fa-clipboard-list"></i></div>
            <div class="step completed"><i class="fas fa-paper-plane"></i></div>
        </div>
    </div>
</section>

<!-- INTERACTIVE TIMELINE -->
<section class="timeline-section">
    <div class="container">
        <h2 class="section-title">Application Process</h2>
        
        <div class="process-timeline">
            <div class="timeline-progress">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <div class="timeline-steps">
                <div class="process-step" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Fill Out Application</h3>
                        <p>Basic info + 100-word "Why Technowatch?"</p>
                        <div class="step-details">
                            <div class="step-time">
                                <i class="fas fa-clock"></i> ~5 minutes
                            </div>
                            <div class="step-tip">
                                <i class="fas fa-lightbulb"></i> Tip: Be genuine about your interests
                            </div>
                        </div>
                    </div>
                    <div class="step-visual">
                        <img src="assets/imgs/carousel_1.jpg" alt="Application Form">
                    </div>
                </div>

                <div class="process-step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Quick Chat</h3>
                        <p>15-min informal Zoom call with an officer. No stress!</p>
                        <div class="step-details">
                            <div class="step-time">
                                <i class="fas fa-clock"></i> ~15 minutes
                            </div>
                            <div class="step-tip">
                                <i class="fas fa-lightbulb"></i> Tip: Just be yourself!
                            </div>
                        </div>
                    </div>
                    <div class="step-visual">
                        <img src="assets/imgs/carousel_2.jpg" alt="Zoom Interview">
                    </div>
                </div>

                <div class="process-step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Get Accepted</h3>
                        <p>Receive Discord invite and join your first build night.</p>
                        <div class="step-details">
                            <div class="step-time">
                                <i class="fas fa-calendar-check"></i> Within 48 hours
                            </div>
                            <div class="step-tip">
                                <i class="fas fa-lightbulb"></i> Tip: Check your email spam folder
                            </div>
                        </div>
                    </div>
                    <div class="step-visual">
                        <img src="assets/imgs/carousel_3.jpg" alt="Discord Welcome">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- APPLICATION FORM -->
<section class="application-form-section">
    <div class="container">
        <div class="form-header">
            <h2>Ready? Apply Here:</h2>
            <p>Our Google Form opens in a new tab. Takes 5 minutes.</p>
        </div>
        <div class="form-container">
            <iframe src="https://forms.google.com/technowatch-club-application" 
                    class="application-iframe" 
                    allowfullscreen>
            </iframe>
        </div>
    </div>
</section>

<!-- AFTER YOU APPLY -->
<section class="after-apply-section">
    <div class="container">
        <h2 class="section-title">After You Apply</h2>
        
        <div class="after-cards">
            <div class="after-card" data-aos="fade-up">
                <div class="after-icon"><i class="fas fa-envelope-open-text"></i></div>
                <h3>Wait for Email</h3>
                <p>We'll get back to you within 48 hours with next steps.</p>
            </div>

            <div class="after-card" data-aos="fade-up" data-aos-delay="100">
                <div class="after-icon"><i class="fab fa-discord"></i></div>
                <h3>Join Discord</h3>
                <p>Get your member role and access all channels.</p>
            </div>

            <div class="after-card" data-aos="fade-up" data-aos-delay="200">
                <div class="after-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>Attend Orientation</h3>
                <p>Meet the team and get your first project assignment.</p>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT SUPPORT -->
<section class="support-section">
    <div class="container">
        <h3>Need Help with Your Application?</h3>
        <p>Our officers are here to assist you every step of the way.</p>
        
        <div class="support-methods">
            <div class="support-method">
                <i class="fas fa-envelope"></i>
                <h4>Email Support</h4>
                <a href="mailto:technowatch.tupv@email.com">technowatch.tupv@email.com</a>
                <span>Response within 24 hours</span>
            </div>
            
            <div class="support-method">
                <i class="fab fa-discord"></i>
                <h4>Discord DM</h4>
                <a href="https://discord.gg/technowatch">Message @Officers</a>
                <span>Fastest response</span>
            </div>
            
            <div class="support-method">
                <i class="fab fa-facebook-messenger"></i>
                <h4>FB Messenger</h4>
                <a href="https://m.me/technowatchclub">@technowatchclub</a>
                <span>During office hours</span>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/script.js" defer></script>
<script src="assets/js/join-us.js" defer></script>

</body>
</html>