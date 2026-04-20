<?php
// Technowatch Club | Join Us - Main Hub
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Technowatch Club | Build Your Future</title>
    <meta name="description" content="Join TUPV's premier tech community. Code. Build. Innovate Together.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets (1)/css (1)/index.css">
    <link rel="stylesheet" href="assets (1)/css (1)/join-us.css">
    <link rel="stylesheet" href="assets (1)/css (1)/responsive.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<!-- HEADER -->
<?php include 'header.php'; ?>

<!-- HERO GALLERY -->
<section class="join-hero-gallery">
    <div class="hero-gallery-container">
        <div class="gallery-track">
            <img src="assets (1)/imgs (1)/carousel_1 (1).jpg" alt="Technowatch Awareness 2025" class="gallery-item active">
            <img src="assets (1)/imgs (1)/carousel_2 (1).jpg" alt="Mission & Vision Crafting" class="gallery-item">
            <img src="assets (1)/imgs (1)/carousel_3 (1).jpg" alt="Club Awareness Campaign" class="gallery-item">
            <img src="assets (1)/imgs (1)/carousel_4 (1).jpg" alt="IoT Home Automation Project" class="gallery-item">
            <img src="assets (1)/imgs (1)/carousel_5 (1).jpg" alt="Technowatch College" class="gallery-item">
        </div>
        
        <div class="hero-overlay-content">
            <div class="hero-text-block">
                <h1 class="hero-main-headline">
                    <span class="headline-part">JOIN THE</span>
                    <span class="headline-highlight">BUILDERS</span>
                </h1>
                <p class="hero-subtext">Code. Wire. Create. <strong>Together.</strong></p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number" data-target="150">90+</span>
                        <span class="stat-label">Active Members</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-target="12">8</span>
                        <span class="stat-label">Projects/Year</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" data-target="24">2</span>
                        <span class="stat-label">Hour Support</span>
                    </div>
                </div>
            </div>
            
            <div class="cta-group">
                <a href="how-to-apply.php" class="cta-primary">Start Application</a>
                <a href="#benefits" class="cta-secondary">Learn More</a>
            </div>
        </div>
    </div>
</section>

<!-- BENEFITS SECTION -->
<section id="benefits" class="benefits-section">
    <div class="container">
        <h2 class="section-title">Why Technowatch?</h2>
        <p class="section-subtitle">We provide the tools, you build the future.</p>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-toolbox"></i></div>
                <h3>Access Lab Gear</h3>
                <p>Use professional equipment without personal investment</p>
                <div class="benefit-tags">
                    <span>Arduino</span>
                    <span>Raspberry Pi</span>
                    <span>Sensors</span>
                </div>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Project Mentorship</h3>
                <p>Get guidance from experienced officers & alumni</p>
                <div class="benefit-tags">
                    <span>1-on-1</span>
                    <span>Code Reviews</span>
                    <span>Career Advice</span>
                </div>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-people-group"></i></div>
                <h3>Hackathon Teams</h3>
                <p>Join or form teams for national competitions</p>
                <div class="benefit-tags">
                    <span>Local</span>
                    <span>National</span>
                    <span>Prizes</span>
                </div>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-handshake"></i></div>
                <h3>Industry Network</h3>
                <p>Connect with tech companies & professionals</p>
                <div class="benefit-tags">
                    <span>Internships</span>
                    <span>Jobs</span>
                    <span>Workshops</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PROCESS PREVIEW -->
<section class="process-preview">
    <div class="container">
        <h2 class="section-title">How to Join</h2>
        <p class="section-subtitle">Three simple steps to start building.</p>
        
        <div class="process-cards">
            <div class="process-card" onclick="window.location.href='how-to-apply.php'">
                <div class="card-number">01</div>
                <img src="assets (1)/imgs (1)/carousel_1 (1).jpg" alt="Application Step">
                <h3>Fill Application</h3>
                <p>5-minute form + "Why Technowatch?"</p>
                <span class="arrow-link">Apply Now <i class="fas fa-arrow-right"></i></span>
            </div>

            <div class="process-card" onclick="window.location.href='how-to-apply.php'">
                <div class="card-number">02</div>
                <img src="assets (1)/imgs (1)/carousel_2 (1).jpg" alt="Interview Step">
                <h3>Quick Chat</h3>
                <p>15-min informal Zoom call</p>
                <span class="arrow-link">Learn More <i class="fas fa-arrow-right"></i></span>
            </div>

            <div class="process-card" onclick="window.location.href='how-to-apply.php'">
                <div class="card-number">03</div>
                <img src="assets (1)/imgs (1)/carousel_3 (1).jpg" alt="Welcome Step">
                <h3>Join Discord</h3>
                <p>Get role & start building!</p>
                <span class="arrow-link">See Details <i class="fas fa-arrow-right"></i></span>
            </div>
        </div>
    </div>
</section>

<!-- ELIGIBILITY & REQUIREMENTS PREVIEW -->
<section class="previews-section">
    <div class="container">
        <div class="preview-cards">
            <div class="preview-card eligibility" onclick="window.location.href='eligibility.php'">
                <div class="preview-icon"><i class="fas fa-user-check"></i></div>
                <h3>Who Can Join?</h3>
                <p>All majors. Any skill level. TUP-V students only.</p>
                <ul class="preview-list">
                    <li><i class="fas fa-check"></i> No experience needed</li>
                    <li><i class="fas fa-check"></i> All programs welcome</li>
                    <li><i class="fas fa-check"></i> Free membership</li>
                </ul>
                <button class="preview-btn">Check Eligibility</button>
            </div>

            <div class="preview-card requirements" onclick="window.location.href='requirements.php'">
                <div class="preview-icon"><i class="fas fa-clipboard-list"></i></div>
                <h3>What You Need</h3>
                <p>Minimal requirements. Maximum support.</p>
                <ul class="preview-list">
                    <li><i class="fas fa-id-card"></i> TUP-V Student ID</li>
                    <li><i class="fas fa-envelope"></i> School email</li>
                    <li><i class="fas fa-laptop"></i> Laptop (optional)</li>
                </ul>
                <button class="preview-btn">View Requirements</button>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section">
    <div class="container">
        <h2 class="section-title">Frequently Asked</h2>
        <p class="section-subtitle">Everything you need to know before applying.</p>
        
        <div class="faq-grid">
            <div class="faq-item">
                <button class="faq-question">
                    <span>Do I need programming experience?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>No! We welcome beginners and provide starter workshops.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    <span>Is there a membership fee?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>None. Project materials are funded by the university.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    <span>Can I join mid-semester?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes, if spots are available. We accept applications year-round.</p>
                </div>
            </div>
        </div>
        
        <div class="faq-cta">
            <a href="how-to-apply.php" class="cta-primary">Still Have Questions? Contact Us</a>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta-section">
    <div class="container">
        <h2>Ready to Build Something Amazing?</h2>
        <p>Join 150+ TUP-V students who are already coding, building, and innovating.</p>
        <div class="final-cta-buttons">
            <a href="how-to-apply.php" class="cta-primary large">
                <i class="fas fa-rocket"></i>
                Start Application
            </a>
            <a href="https://discord.gg/technowatch" class="cta-secondary large">
                <i class="fab fa-discord"></i>
                Join Discord First
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<?php include 'footer (1).php'; ?>

<!-- SCRIPTS -->
<script src="assets (1)/js (1)/script.js" defer></script>
<script src="assets (1)/js (1)/join-us.js" defer></script>

</body>
</html>