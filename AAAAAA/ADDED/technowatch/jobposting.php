<?php
// Technowatch Club | Homepage
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technowatch Club | Job Postings</title>
      <!-- CSS Files -->
  <link rel="stylesheet" href="assets (1)\css (1)\jobpostings.css">
  <link rel="stylesheet" href="assets (1)\css (1)\index.css">
  <link rel="stylesheet" href="assets (1)\css (1)\responsive.css">

  <!-- JS Files -->
  <script src="assets (1)\js (1)\script (1).js" defer></script>
  <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="jobs-section">
        <div class="section-header">
            <h1>Career Opportunities</h1>
            <p>Find your next tech job or internship through our network</p>
            <div class="job-search">
                <input type="text" placeholder="Search jobs...">
                <button><i class="fas fa-search"></i></button>
                <select>
                    <option>All Categories</option>
                    <option>Internship</option>
                    <option>Full-time</option>
                    <option>Part-time</option>
                    <option>Remote</option>
                </select>
            </div>
        </div>

        <div class="jobs-container">
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
                    <span><i class="fas fa-clock"></i> Summer Internship</span>
                </div>
                <p>Looking for CET students with Python and Django experience to join our development team.</p>
                <div class="job-footer">
                    <div class="job-tags">
                        <span>Python</span>
                        <span>Django</span>
                        <span>Web Development</span>
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
                    <span><i class="fas fa-money-bill-wave"></i> ₱25,000/month</span>
                    <span><i class="fas fa-clock"></i> Full-time</span>
                </div>
                <p>Entry-level position for recent graduates with experience in embedded systems.</p>
                <div class="job-footer">
                    <div class="job-tags">
                        <span>Arduino</span>
                        <span>Raspberry Pi</span>
                        <span>Embedded Systems</span>
                    </div>
                    <a href="#" class="apply-btn">Apply Now</a>
                </div>
            </div>

            <div class="job-card">
                <div class="job-header">
                    <img src="http://static.photos/office/200x200/3" alt="Company Logo" class="company-logo">
                    <div>
                        <h3>Network Support Specialist</h3>
                        <div class="company-name">DataNet Philippines</div>
                    </div>
                </div>
                <div class="job-details">
                    <span><i class="fas fa-map-marker-alt"></i> Davao (Remote)</span>
                    <span><i class="fas fa-money-bill-wave"></i> ₱20,000/month</span>
                    <span><i class="fas fa-clock"></i> Part-time</span>
                </div>
                <p>Provide technical support for network infrastructure. Flexible hours for students.</p>
                <div class="job-footer">
                    <div class="job-tags">
                        <span>Networking</span>
                        <span>Cisco</span>
                        <span>TCP/IP</span>
                    </div>
                    <a href="#" class="apply-btn">Apply Now</a>
                </div>
            </div>

            <div class="job-card">
                <div class="job-header">
                    <img src="http://static.photos/office/200x200/4" alt="Company Logo" class="company-logo">
                    <div>
                        <h3>Junior Web Developer</h3>
                        <div class="company-name">Digital Creations</div>
                    </div>
                </div>
                <div class="job-details">
                    <span><i class="fas fa-map-marker-alt"></i> Iloilo City</span>
                    <span><i class="fas fa-money-bill-wave"></i> ₱18,000/month</span>
                    <span><i class="fas fa-clock"></i> Full-time</span>
                </div>
                <p>Front-end development position requiring HTML, CSS, and JavaScript skills.</p>
                <div class="job-footer">
                    <div class="job-tags">
                        <span>HTML/CSS</span>
                        <span>JavaScript</span>
                        <span>React</span>
                    </div>
                    <a href="#" class="apply-btn">Apply Now</a>
                </div>
            </div>

            <div class="job-card urgent">
                <div class="job-badge">URGENT</div>
                <div class="job-header">
                    <img src="http://static.photos/office/200x200/5" alt="Company Logo" class="company-logo">
                    <div>
                        <h3>Cybersecurity Trainee</h3>
                        <div class="company-name">SecureNet PH</div>
                    </div>
                </div>
                <div class="job-details">
                    <span><i class="fas fa-map-marker-alt"></i> Makati City</span>
                    <span><i class="fas fa-money-bill-wave"></i> ₱22,000/month</span>
                    <span><i class="fas fa-clock"></i> Full-time</span>
                </div>
                <p>Training program for graduates interested in cybersecurity careers. Certifications provided.</p>
                <div class="job-footer">
                    <div class="job-tags">
                        <span>Security</span>
                        <span>Ethical Hacking</span>
                        <span>Certification</span>
                    </div>
                    <a href="#" class="apply-btn">Apply Now</a>
                </div>
            </div>
        </div>

        <div class="pagination">
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#"><i class="fas fa-chevron-right"></i></a>
        </div>
    </section>

    <?php include 'footer (1).php'; ?>