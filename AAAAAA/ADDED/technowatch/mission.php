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
  <link rel="stylesheet" href="assets\css\mission.css">
  <link rel="stylesheet" href="assets\css\style.css">
  <link rel="stylesheet" href="assets\css\mobile.css">
  <link rel="stylesheet" href="assets\css\darkmode.css">

  <!-- JS Files -->
  <script src="assets/js/script.js" defer></script>
  <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<!-- PRELOADER -->
    <?php include 'header.php'; ?>

    <section class="mission">
        <div class="content active">
            <div class="paragraph">
                <h1>VISION, MISSION, CORE VALUES</h1>
                <hr style="width: 80%; margin: 20px auto; border: 1px solid #ffffff61;">
                <h2>VISION</h2>
                <p>The TechnoWatch will be the center of quality computer education that will take lead in the innovation of computer
                    <br>technology and building of an expertise that will solve real-world problems while embracing the moral awareness and
                    <br>responsiveness of an individual to the society and the global scenario</p>
                <hr style="width: 80%; margin: 20px auto; border: 1px solid #ffffff61;">
                <h2>MISSION</h2>
                <p>TechnoWatch club is a community dedicated to exploring the latest advancements in technology.
                    <br>We provide a platform for individuals to share their knowledge collaborate on projects and develop their skill.
                    <br>Our community is a passionate about all aspects of technology. From UI/UX design to programming languages.
                <br>Software and system development up to hardware design.</p>
                <hr style="width: 80%; margin: 20px auto; border: 1px solid #ffffff61;">
                <h2>CORE VALUES</h2>
                <ul class="core-values-list">
                    <li>
                        <strong>P - Progress:</strong> We are committed to continuous learning, skill development, and embracing the latest advancements in technology to stay at the forefront of innovation.
                    </li>
                    <li>
                        <strong>R - Responsibility:</strong> We act with moral awareness and responsiveness, applying our expertise to solve real-world problems and contribute positively to society and the global community.
                    </li>
                    <li>
                        <strong>O - Openness:</strong> We foster a welcoming and inclusive environment where knowledge is freely shared and diverse ideas are encouraged.
                    </li>
                    <li>
                        <strong>T - Teamwork:</strong> We believe in the power of collaboration, actively working together on projects, sharing insights, and supporting each other's growth.
                    </li>
                    <li>
                        <strong>E - Excellence:</strong> We strive for high standards in all our endeavors, from UI/UX design and programming to software development and hardware design, building expertise that makes a tangible impact.
                    </li>
                    <li>
                        <strong>C - Creativity:</strong> We encourage innovative thinking and the imaginative application of computer technology to build unique and effective solutions.
                    </li>
                    <li>
                        <strong>H - Holistic Development:</strong> We aim to develop well-rounded individuals who not only possess technical mastery but also embrace ethical considerations and social consciousness.
                    </li>
                </ul>
                <br>
                <hr style="width: 80%; margin: 20px auto; border: 1px solid #fffcfc61;">
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>