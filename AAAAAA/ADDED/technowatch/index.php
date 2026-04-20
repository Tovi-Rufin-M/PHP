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

  <!-- CSS Files -->
  <link rel="stylesheet" href="assets\css\preloader.css">
  <link rel="stylesheet" href="assets\css\style.css">
  <link rel="stylesheet" href="assets\css\mobile.css">
  <link rel="stylesheet" href="assets\css\darkmode.css">

  <!-- JS Files -->
  <script src="assets/js/preloader.js" defer></script>
  <script src="assets/js/script.js" defer></script>
  <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- PRELOADER -->
    <?php include 'preloader.php'; ?>

    <!-- PRELOADER -->
    <?php include 'header.php'; ?>

  <section class="hero-static-section" style="background-image: url('assets/imgs/to9.jpg');">
        <div class="hero-content-overlay">
            <h1 class="hero-main-text">BECOME THE MEMBER,<br>YOU WERE <span class="transparent-text">BORN TO BE.</span></h1>
            <p class="hero-sub-text">Cutting-edge courses and hands-on projects in a supportive, tech-focused environment.</p>
            <a href="#" class="hero-btn">Join the Club</a>
        </div>
    </section>

  <section class="why-choose-us-section">
    <div class="why-choose-us-container">

        <div class="why-choose-us-top-section-content"> <div class="why-choose-us-images">
                <img src="assets/imgs/carousel_1.jpg" alt="Agricultural Field View" class="why-choose-us-image-item">
                <img src="assets/imgs/carousel_2.jpg" alt="Drone View of Fields" class="why-choose-us-image-item">
            </div>
            <div class="why-choose-us-quote-wrapper"> <h1 class="why-choose-us-main-quote">"With technology advancing at lightspeed, knowing how to build is paramount. Here, you don't just learn; you create."<strong>labor-intensive characteristics</strong>.</h1>
            </div>
        </div>
        
        <div class="why-choose-us-bottom-section-content"> <ul class="key-benefits-list">
                <li>Access to exclusive workshops and seminars</li>
                <li>Networking opportunities with industry professionals</li>
                <li>Mentorship from experienced club members</li>
                <li>Showcase your projects and gain recognition</li>
            </ul>

            <div class="impact-icons-grid"> 
                <div class="impact-icon-item">
                    <div class="feature-icon-circle"><i class="fas fa-lightbulb"></i></div>
                    <span class="feature-label">Innovate</span>
                </div>
                <div class="impact-icon-item">
                    <div class="feature-icon-circle"><i class="fas fa-graduation-cap"></i></div>
                    <span class="feature-label">Educate</span>
                </div>
                <div class="impact-icon-item">
                    <div class="feature-icon-circle"><i class="fas fa-tools"></i></div>
                    <span class="feature-label">Build</span>
                </div>
                <div class="impact-icon-item">
                    <div class="feature-icon-circle"><i class="fas fa-handshake"></i></div>
                    <span class="feature-label">Connect</span>
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

            <h1 class="main-headline">Where Tech Meets the Field</h1>

            <p class="hero-description">Discover Technowatch Club, your community for driving agricultural advancement through technology. From smart farming to sustainable solutions, engage in lively discussions, hands-on learning, and knowledge sharing with a network of dedicated tech enthusiasts.</p>

            <div class="hero-cta-buttons">
                <a href="#" class="btn-primary">Join the Club</a>
                <a href="#" class="btn-secondary">View Projects</a>
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
                <a href="/merchandise-page.html" class="btn btn-primary">View All Merchandise</a>
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
            <a href="news-and-events.php" class="btn-ghost-blue">View All News & Events</a>
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
                    <a href="#" class="view-project-btn">View Project &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

    <?php include 'footer.php'; ?>

</body>
</html>
