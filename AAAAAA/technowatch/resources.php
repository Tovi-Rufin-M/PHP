<?php
// Technowatch Club | Resources
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources | Technowatch Club</title>

    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <link rel="stylesheet" href="assets/css/resources.css">
    
 

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
    <script src="assets\js\script.js" defer></script>
    <script src="assets\js\resources.js" defer></script> <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="resources-hero-section" style="background-image: url('assets/imgs/bg.png');">
        <div class="hero-content-overlay">
            <h1 class="hero-main-text">
                TECHNOWATCH <br> <span class="transparent-text">RESOURCES</span>
            </h1>
            <p class="hero-sub-text">
                Your central hub for projects, career opportunities, and club events.
            </p>
        </div>
    </section>

    <section class="resources-hub-section">
        <div class="hub-container">

            <div class="hub-category-block">
                <div class="block-header">
                    <h2><i class="fas fa-calendar-alt"></i>Events & News</h2>
                    <p>See what's happening and join our next workshop or hackathon.</p>
                    </div>
                
                <div class="preview-grid">
                    <div class="timeline-item featured">
                        <div class="timeline-image">
                            <img src="http://static.photos/technology/640x360/1" alt="Hackathon">
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">June 30, 2023</div>
                            <div class="event-badge">FEATURED</div>
                            <h3>Annual Technowatch Hackathon</h3>
                            <p>48-hour coding marathon with prizes and industry judges. Open to all CET students.</p>
                            <div class="event-meta">
                                <span><i class="fas fa-map-marker-alt"></i> CET Building, Room 205</span>
                                <span><i class="fas fa-clock"></i> 9:00 AM - 5:00 PM</span>
                            </div>
                            <a href="#" class="register-btn">Register Now</a>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-image">
                            <img src="assets/imgs/arduino.png" alt="IoT Workshop">
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">July 5-8, 2023</div>
                            <h3>IoT Workshop Series</h3>
                            <p>Learn to build connected devices with hands-on projects using Arduino and Raspberry Pi.</p>
                            <div class="event-meta">
                                <span><i class="fas fa-map-marker-alt"></i> CET Lab</span>
                                <span><i class="fas fa-clock"></i> 2:00 PM - 4:00 PM</span>
                            </div>
                            <a href="#" class="register-btn">Learn More</a>
                        </div>
                    </div>
                </div>
                
                <div class="category-footer-button">
                    <a href="eventsNews.php" class="view-all-btn">View All Events & News <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <div class="hub-category-block">
                <div class="block-header">
                    <h2><i class="fas fa-briefcase"></i> Career Opportunities</h2>
                    <p>Find your next tech job or internship through our network.</p>
                    </div>
                
                <div class="jobs-preview-grid">
                    <div class="job-card featured">
                        <div class="job-badge">FEATURED</div>
                        <div class="job-header">
                            <img src="http://static.photos/office/200x200/1" alt="Company Logo" class="company-logo">
                            <div>
                                <h3>Software Engineer Intern</h3>
                                <div class="company-name">Tech Solutions Inc.</div>
                            </div>
                        </div>
                        <div class="job-details">
                            <span><i class="fas fa-map-marker-alt"></i> Manila (Hybrid)</span>
                            <span><i class="fas fa-money-bill-wave"></i> ₱15,000/month</span>
                        </div>
                        <div class="job-footer">
                            <div class="job-tags">
                                <span>Python</span>
                                <span>Django</span>
                                <span>Web</span>
                            </div>
                            <a href="#" class="apply-btn">Apply Now</a>
                        </div>
                    </div>

                    <div class="job-card">
                        <div class="job-header">
                            <img src="http://static.photos/office/200x200/2" alt="Company Logo" class="company-logo">
                            <div>
                                <h3>IoT Technician</h3>
                                <div class="company-name">Smart Devices Co.</div>
                            </div>
                        </div>
                        <div class="job-details">
                            <span><i class="fas fa-map-marker-alt"></i> Cebu City</span>
                            <span><i class="fas fa-clock"></i> Full-time</span>
                        </div>
                        <div class="job-footer">
                            <div class="job-tags">
                                <span>Arduino</span>
                                <span>Embedded Systems</span>
                            </div>
                            <a href="#" class="apply-btn">Apply Now</a>
                        </div>
                    </div>
                </div>
                
                <div class="category-footer-button">
                    <a href="jobposting.php" class="view-all-btn">View All Jobs <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <div class="hub-category-block">
                <div class="block-header">
                    <h2><i class="fas fa-cogs"></i> Our Projects</h2>
                    <p>Explore the innovative projects our team has worked on.</p>
                    </div>
                
                <div class="projects-showcase-grid">
                    <div class="project-card">
                        <img src="assets/imgs/carousel_7.jpg" alt="AI Chatbot Assistant" class="project-image">
                        <div class="project-info">
                            <h3>AI Chatbot Assistant</h3>
                            <p>An intelligent chatbot that automates customer support using natural language processing.</p>
                            <div class="project-meta">
                                <span><i class="fas fa-microchip"></i> AI/NLP</span>
                            </div>
                            <a href="#" class="view-project-btn">View Project &rarr;</a>
                        </div>
                    </div>

                    <div class="project-card">
                        <img src="assets/imgs/carousel_5.jpg" alt="Smart Campus System" class="project-image">
                        <div class="project-info">
                            <h3>Smart Campus System</h3>
                            <p>A platform integrating IoT devices for efficient school management and monitoring.</p>
                             <div class="project-meta">
                                <span><i class="fas fa-wifi"></i> IoT</span>
                            </div>
                            <a href="#" class="view-project-btn">View Project &rarr;</a>
                        </div>
                    </div>

                    <div class="project-card">
                        <img src="assets/imgs/carousel_4.jpg" alt="E-Commerce Dashboard" class="project-image">
                        <div class="project-info">
                            <h3>E-Commerce Dashboard</h3>
                            <p>A responsive admin panel for managing products, orders, and analytics.</p>
                             <div class="project-meta">
                                <span><i class="fas fa-chart-bar"></i> Web App</span>
                            </div>
                            <a href="#" class="view-project-btn">View Project &rarr;</a>
                        </div>
                    </div>
                </div>
                
                <div class="category-footer-button">
                    <a href="projects.php" class="view-all-btn">View All Projects <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>