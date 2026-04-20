<?php
// Technowatch Club | Homepage
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission & Vision | Technowatch Club</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/missionVision.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- JS Files -->
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <!-- HEADER -->
    <?php include 'header.php'; ?>

    <section class="mission">
        <div class="mission-container">
            <header class="mission-header">
            <h1>Vision, Mission, Core Values</h1>
            <div class="glow-divider"></div>
            </header>

            <div class="mission-content">
                <div class="mission-section fade-in" style="--delay: 0s;">
                    <h2>Vision</h2>
                    <p>
                    The <strong>TechnoWatch Club</strong> aims to be the center of 
                    <strong>quality computer education</strong> — leading in 
                    <strong>technological innovation</strong> and fostering expertise that solves 
                    <strong>real-world problems</strong> while promoting moral awareness and 
                    global responsibility.
                    </p>
                </div>

                <div class="mission-section fade-in" style="--delay: 0.2s;">
                    <h2>Mission</h2>
                    <p>
                    We are a community that explores the latest trends in technology. 
                    We create a platform for learners to share knowledge, collaborate, 
                    and grow their skills — from <strong>UI/UX design</strong> and 
                    <strong>programming</strong> to <strong>software</strong> and 
                    <strong>hardware innovation</strong>. Our mission is to inspire 
                    curiosity, creativity, and collaboration in every member.
                    </p>
                </div>

                <div class="mission-section fade-in" style="--delay: 0.4s;">
                    <h2>Core Values</h2>
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
        </div>
    </section>


    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

</body>
</html>