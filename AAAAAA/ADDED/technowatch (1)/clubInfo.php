<?php
// Technowatch Club | Homepage
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technowatch Club | Club Info </title>

  <!-- CSS Files -->
  <link rel="stylesheet" href="assets/css/index.css">
  <link rel="stylesheet" href="assets/css/clubInfo.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/darkmode.css">

  <!-- JS Files -->
  <script src="assets/js/script.js" defer></script>
  <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <!-- HEADER -->
    <?php include 'header.php'; ?>

    <section class="club-info">
        <div class="club-info-container">
            <div class="club-intro">
            <img src="assets/imgs/logo_white.png" alt="Technowatch Club Logo" class="club-logo">
            <p>
                <strong><em>Technowatch Club</em></strong> is a community dedicated to exploring the latest advancements in technology. 
                We provide a platform for individuals to share their knowledge, collaborate on projects, and develop their skills. 
                Our community is passionate about all aspects of technology, from UX/UI design to programming languages, 
                software and system development up to hardware design.
            </p>
            </div>

            <div class="club-cards">
                <div class="club-card">
                    <h3>How to Apply</h3>
                    <p>Learn the simple steps to become a member of Technowatch Club and start your journey with us.</p>
                    <a href="/apply.php" class="btn">Apply Now</a>
                </div>

                <div class="club-card">
                    <h3>Meet the Officers</h3>
                    <p>Get to know the dedicated individuals leading the club and making every project possible.</p>
                    <a href="officers.php" class="btn">View Officers</a>
                </div>

                <div class="club-card">
                    <h3>Join the Club</h3>
                    <p>Become part of a passionate community that thrives on creativity, technology, and collaboration.</p>
                    <a href="/join.php" class="btn">Join Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

</body>
</html>