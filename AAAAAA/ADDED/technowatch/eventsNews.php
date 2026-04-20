<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technowatch Club | Events & News</title>
    <link rel="stylesheet" href="assets (1)\css (1)\eventsNews.css">
    <link rel="stylesheet" href="assets (1)\css (1)\index.css">
    <link rel="stylesheet" href="assets (1)\css (1)\responsive.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
    <script src="assets (1)\js (1)\script (1).js"></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="events-section">
        <div class="section-header">
            <h1>Events</h1>
            <p>Discover what's happening in our tech community</p>
        </div>

        <div class="events-timeline">
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
                    <img src="assets (1)\imgs (1)\arduino.png" alt="Hackathon">
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

            <div class="timeline-item">
                <div class="timeline-image">
                    <img src="assets (1)\imgs (1)\speaker.png" alt="Hackathon">
                </div>

                <div class="timeline-content">
                    <div class="timeline-date">July 12, 2023</div>
                    <h3>Guest Speaker: AI in Industry</h3>
                    <p>Join us for a talk by Google Engineer Maria Santos on real-world AI applications.</p>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> University Auditorium</span>
                        <span><i class="fas fa-clock"></i> 10:00 AM - 12:00 PM</span>
                    </div>
                    <a href="#" class="register-btn">RSVP</a>
                </div>
            </div>

            <div class="timeline-item past-event">
                <div class="timeline-image">
                    <img src="http://static.photos/technology/640x360/2" alt="Project Showcase">
                </div>
                <div class="timeline-content">
                    <div class="timeline-date">June 8, 2023</div>
                    <div class="event-badge">PAST EVENT</div>
                    <h3>Project Showcase Day</h3>
                    <p>See what our members have been working on this semester with live demos.</p>
                    <a href="#" class="view-gallery-btn">View Gallery</a>
                </div>
            </div>
        </div>

        <div class="news-section">
            <h2>Latest Tech-News</h2>
            <div class="news-grid">
                <div class="news-card">
                    <div class="news-category">Industry</div>
                    <h3>New Chip Breakthrough at TUP Labs</h3>
                    <p>Our researchers have developed a more efficient processor architecture.</p>
                    <div class="news-footer">
                        <span>2 days ago</span>
                        <a href="#">Read More</a>
                    </div>
                </div>
                <div class="news-card">
                    <div class="news-category">Achievements</div>
                    <h3>Technowatch Team Wins National Competition</h3>
                    <p>Our robotics team placed first in the National Engineering Challenge.</p>
                    <div class="news-footer">
                        <span>1 week ago</span>
                        <a href="#">Read More</a>
                    </div>
                </div>
                <div class="news-card">
                    <div class="news-category">Partnership</div>
                    <h3>New Industry Partnership Announced</h3>
                    <p>Technowatch partners with local tech company for internship program.</p>
                    <div class="news-footer">
                        <span>2 weeks ago</span>
                        <a href="#">Read More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer (1).php'; ?>

</body>
</html>