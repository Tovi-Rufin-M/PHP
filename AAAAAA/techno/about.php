<?php
// Technowatch Club | About Us Page (Pure PHP/CSS)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/about.css">
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body class="bg-body-dark text-[var(--color-text)]"> 

<?php include 'header.php'; ?>

<main>
    <section class="about-hero-section club-hero-bg">
        <div class="hero-content">
            <img src="assets/imgs/logo_white.png" alt="Technowatch Club Logo" class="hero-logo">
            <h1 class="hero-title gradient-text-main">Welcome to Technowatch Club</h1>
            <p class="hero-subtitle">
                <strong class="font-bold italic text-white">Technowatch Club</strong> is a vibrant community dedicated to exploring the latest advancements in technology. We provide a platform for individuals to share knowledge, collaborate on projects, and develop their skills across all aspects of technology—from UX/UI design and programming languages to software and hardware innovation.
            </p>
            <a href="#mission-vision" class="hero-cta-button">Learn Our Values <i class="fas fa-arrow-down"></i></a>
        </div>
    </section>

    <section id="mission-vision" class="mission-vision-section">
        <div class="section-container">
            <header class="section-header">
                <h2>Our Purpose: Vision, Mission, & Core Values</h2>
                <p>The foundation of our community and our direction for the future.</p>
                <div class="section-separator"></div>
            </header>

            <div class="grid-2-cols">
                <div class="vision-card">
                    <h3><i class="fas fa-eye"></i> Our Vision</h3>
                    <p>
                        The <strong>TechnoWatch Club</strong> aims to be the center of <strong>quality computer education</strong> — leading in <strong>technological innovation</strong> and fostering expertise that solves <strong>real-world problems</strong> while promoting moral awareness and global responsibility.
                    </p>
                </div>

                <div class="mission-card">
                    <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
                    <p>
                        We are a community that explores the latest trends in technology. We create a platform for learners to share knowledge, collaborate, and grow their skills—from <strong>UI/UX design</strong> and <strong>programming</strong> to <strong>software</strong> and <strong>hardware innovation</strong>. Our mission is to inspire curiosity, creativity, and collaboration in every member.
                    </p>
                </div>
            </div>

            <div class="core-values-box">
                <h3><i class="fas fa-handshake"></i> Core Values: P.R.O.T.E.C.H.</h3>
                <ul class="core-values-list">
                    <li><strong>P – Progress:</strong> We strive for continuous learning and innovation.</li>
                    <li><strong>R – Responsibility:</strong> We use our knowledge to create positive change.</li>
                    <li><strong>O – Openness:</strong> We embrace diverse ideas and inclusive collaboration.</li>
                    <li><strong>T – Teamwork:</strong> We grow together through shared effort and respect.</li>
                    <li><strong>E – Excellence:</strong> We uphold high standards in everything we do.</li>
                    <li><strong>C – Creativity:</strong> We think differently to build meaningful solutions.</li>
                    <li><strong>H – Holistic Development:</strong> We nurture both skill and character.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="next-step-section">
        <div class="section-container">
            <header class="section-header">
                <h2>Take the Next Step</h2>
                <p>Ready to join the community? Explore your options below.</p>
                <div class="section-separator"></div>
            </header>

            <div class="grid-3-cols">
                <div class="next-step-card">
                    <h3><i class="fas fa-user-plus"></i> How to Apply</h3>
                    <p>Learn the simple steps to become a member of Technowatch Club and start your journey with us.</p>
                    <a href="how-to-apply.php">Apply Now</a>
                </div>

                <div class="next-step-card">
                    <h3><i class="fas fa-users"></i> Meet the Officers</h3>
                    <p>Get to know the dedicated individuals leading the club and making every project possible.</p>
                    <a href="officers.php">View Officers</a>
                </div>

                <div class="next-step-card">
                    <h3><i class="fas fa-puzzle-piece"></i> Join the Club</h3>
                    <p>Become part of a passionate community that thrives on creativity, technology, and collaboration.</p>
                    <a href="join-us.php">Join Us</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
