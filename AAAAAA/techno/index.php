<?php
// Technowatch Club | Homepage
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Technowatch Club</title>
    
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">

    <!-- CSS Files -->
    <!-- FIXED: Changed all backslashes to forward slashes for cross-platform compatibility -->
    <link rel="stylesheet" href="assets/css/preloader.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- JS Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/preloader.js" defer></script>
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- PRELOADER -->
    <?php include 'preloader.php'; ?>

    <!-- HEADER -->
    <?php include 'header.php'; ?>

    <section class="hero-static-section">
        <video autoplay loop muted playsinline class="background-video">
            <source src="assets/vid/techno.webm" type="video/webm">

            <source src="assets/vid/techno.mp4" type="video/mp4">

            Your browser does not support the video tag.
        </video>
        <div class="hero-content-overlay">
            <h1 class="hero-main-text">
                CODE IT.<br>WIRE IT.<br> <span class="transparent-text">MAKE IT WORK.</span>
            </h1>
            <p class="hero-sub-text">
                Collaborate on real-world systems, share knowledge, and level up your practical tech skills.
            </p>
            <a href="join-us.php" class="hero-btn">Join the Club</a>
        </div>
    </section>

    <section class="why-choose-us-section">
        <div class="why-choose-us-container">
            <div class="why-choose-us-top-section-content">
                <div class="why-choose-us-images">
                    <video controls 
                        autoplay 
                        loop 
                        muted
                        poster="assets/imgs/carousel_1.jpg" 
                        class="why-choose-us-video">
                        
                        <source src="assets/vid/techno.mp4" type="video/mp4">

                        Your browser does not support the video tag.
                    </video>
                </div>
                <div class="why-choose-us-quote-wrapper">
                    <h1 class="why-choose-us-main-quote">
                        The best way to learn technology is to <strong>break it,</strong> <strong>fix it,</strong> and <strong>build something better</strong>.
                        Stop studying tech—start <i><strong>doing</strong></i> tech.
                    </h1>
                </div>
            </div>

            <div class="why-choose-us-bottom-section-content">
                <ul class="key-benefits-list">
                    <li>Hands-on workshops and exciting <strong>circuit troubleshooting</strong> challenges</li>
                    <li>Guidance and mentorship in <strong>embedded systems and device interfacing</strong></li>
                    <li>Opportunities to showcase your <strong>system integration</strong> and inspire others</li>
                    <li>Meet people who share your curiosity and drive for <strong>practical application</strong></li>
                </ul>

                <div class="impact-icons-grid">
                    <div class="impact-icon-item">
                        <div class="feature-icon-circle"><i class="fas fa-lightbulb"></i></div>
                        <span class="feature-label">Innovate</span>
                    </div>
                    <div class="impact-icon-item">
                        <div class="feature-icon-circle"><i class="fas fa-microchip"></i></div>
                        <span class="feature-label">Master</span>
                    </div>
                    <div class="impact-icon-item">
                        <div class="feature-icon-circle"><i class="fas fa-cogs"></i></div>
                        <span class="feature-label">Create</span>
                    </div>
                    <div class="impact-icon-item">
                        <div class="feature-icon-circle"><i class="fas fa-users"></i></div>
                        <span class="feature-label">Collaborate</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HERO CAROUSEL -->
    <section class="dynamic-hero-section">
        <div class="hero-content-wrapper">
            <div class="hero-text-area">
                <span class="pill-badge">Connect With Us</span>
                <h1 class="main-headline">WHERE HARDWARE FINDS ITS CODE.</h1>
                <p class="hero-description">
                    The Technowatch Club is your lab for <strong>applied technology</strong>.
                    Engage in deep-dive projects, <strong>system-level debugging</strong>, and share knowledge across embedded systems
                    and network protocols with a network of dedicated tech enthusiasts.
                </p>
                <div class="hero-cta-buttons">
                    <a href="join-us.php" class="btn-primary">Join the Club</a>
                    <a href="projects.php" class="btn-secondary">View Projects</a>
                </div>
            </div>

            <div class="hero-visual-area">
                <div class="hero-image-display">
                    <img src="assets/imgs/carousel_6.jpg" alt="Agricultural Technology in use" class="main-hero-image active-image">
                </div>
                <div class="hero-right-nav">
                    <button class="nav-arrow left-arrow">←</button>
                    <button class="nav-arrow right-arrow">→</button>
                </div>
                <div class="hero-dot-nav">
                    <span class="hero-dot active-dot" data-index="0"></span>
                    <span class="hero-dot" data-index="1"></span>
                    <span class="hero-dot" data-index="2"></span>
                    <span class="hero-dot" data-index="3"></span>
                </div>
                <img src="assets/imgs/carousel_7.jpg" alt="Farmer working" class="small-thumb">
            </div>
        </div>

        <section class="merchandise-section">
            <div class="merchandise-container">
                <h2 class="merchandise-title">Our Merchandise</h2>
                <div class="product-grid">
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="assets/imgs/tshirt.png" alt="TShirt" class="product-image">
                        </div>
                        <h3 class="product-name">Club T-Shirt</h3>
                        <p class="product-price">$25.00</p>
                    </div>

                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="assets/imgs/whitepin.png" alt="WhitePin" class="product-image">
                        </div>
                        <h3 class="product-name">White Logo Pin</h3>
                        <p class="product-price">$5.00</p>
                    </div>

                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="assets/imgs/niggapin.png" alt="BlackPin" class="product-image">
                        </div>
                        <h3 class="product-name">Black Logo Pin</h3>
                        <p class="product-price">$5.00</p>
                    </div>
                </div>

                <div class="merchandise-actions">
                    <a href="merch.php" class="btn btn-primary">View All Merchandise</a>
                </div>
            </div>
        </section>

        <div class="news-events-showcase">
            <h2>Latest News & Events</h2>
            <div class="news-grid">
                <div class="news-card">
                    <div class="card-header">
                        <img src="assets/imgs/carousel_8.jpg" alt="Thumbnail for Hackathon" class="news-thumbnail">
                        <span class="badge new-badge">NEW</span>
                    </div>
                    <h4>Annual Hackathon Announced!</h4>
                    <p>Prepare for 48 hours of coding and innovation. Registration opens next week.</p>
                    <a href="#" class="read-more-link">Read More &rarr;</a>
                </div>

                <div class="news-card">
                    <div class="card-header">
                        <img src="assets/imgs/carousel_9.jpg" alt="Thumbnail for Workshop" class="news-thumbnail">
                        <span class="badge live-badge">LIVE</span>
                    </div>
                    <h4>IoT Workshop Series: Week 3</h4>
                    <p>Join us this Saturday to learn about integrating sensors into farming systems.</p>
                    <a href="#" class="read-more-link">Read More &rarr;</a>
                </div>

                <div class="news-card">
                    <div class="card-header">
                        <img src="assets/imgs/carousel_10.jpg" alt="Thumbnail for Research" class="news-thumbnail">
                    </div>
                    <h4>New Research Paper Published</h4>
                    <p>TUP-V students contribute to a study on drone-based crop monitoring.</p>
                    <a href="#" class="read-more-link">Read More &rarr;</a>
                </div>
            </div>
            <div class="view-all-cta">
                <a href="eventsNews.php" class="btn-ghost-blue">View All News & Events</a>
            </div>
        </div>

        <div class="projects-showcase">
            <h2>See What We're Building</h2>
            <div class="projects-grid">
                <div class="project-card">
                    <img src="assets/imgs/carousel_11.jpg" alt="Image of Smart Sorter Robot" class="project-image">
                    <div class="project-info">
                        <h3>Smart Sorter Robot</h3>
                        <p>AI-powered machine for automated post-harvest grading.</p>
                        <a href="#" class="view-project-btn">View Project &rarr;</a>
                    </div>
                </div>

                <div class="project-card">
                    <img src="assets/imgs/carousel_3.jpg" alt="Image of Mobile App UI" class="project-image">
                    <div class="project-info">
                        <h3>AgriData Mobile App</h3>
                        <p>Real-time field data visualization and predictive analytics tool.</p>
                        <a href="#" class="view-project-btn">View Project &rarr;</a>
                    </div>
                </div>

                <div class="project-card">
                    <img src="assets/imgs/carousel_4.jpg" alt="Image of LoRa Sensor Circuit" class="project-image">
                    <div class="project-info">
                        <h3>LoRa Field Sensor Array</h3>
                        <p>Low-power, long-range wireless soil monitoring system.</p>
                        <a href="" class="view-project-btn">View Project &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>
</body>
</html>
