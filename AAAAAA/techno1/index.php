<?php
// Technowatch Club | Homepage
header('Content-Type: text/html; charset=utf-8');

// 1. DATABASE CONNECTION
// NOTE: Assuming db_connect.php is located in the admin/includes/ directory
include 'admin/includes/db_connect.php';

// 2. DATA FETCHING QUERIES

// =========================================================================
// MODIFIED: MERCHANDISE QUERIES (One from each category, not sold out/out of stock)
// ASSUMPTION: 'merch' table has 'category' (e.g., 't-shirt', 'lanyard', 'pin') and 'stock_quantity' columns.
// =========================================================================
$merch_categories = ['T-Shirts', 'Lanyards', 'Pins'];
$merch_items = [];

foreach ($merch_categories as $category) {
    // Selects the latest (highest ID) item for the category that has stock
    $query = "SELECT merch_id, name, price, image_url FROM merch WHERE category = ? AND stock > 0 ORDER BY merch_id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $merch_items[] = $result->fetch_assoc();
        }
        $stmt->close();
    }
}


// =========================================================================
// MODIFIED: NEWS & EVENTS QUERIES (2 Events, 2 News, prioritizing featured/pinned)
// ASSUMPTION: 'events_news' table now has an 'is_featured' column.
// =========================================================================
$featured_events = [];
$featured_news = [];

// Query 1: Get up to 2 Events (Prioritize featured, then latest date)
$event_query = "SELECT item_id, title, type, summary, image_path, event_date FROM events_news WHERE is_published = 1 AND type = 'event' ORDER BY is_featured DESC, event_date DESC LIMIT 2";
$event_result = $conn->query($event_query);
if ($event_result) {
    while ($row = $event_result->fetch_assoc()) {
        $featured_events[] = $row;
    }
}

// Query 2: Get up to 2 News items (Prioritize featured, then latest date)
$news_query = "SELECT item_id, title, type, summary, image_path, event_date FROM events_news WHERE is_published = 1 AND type = 'news' ORDER BY is_featured DESC, event_date DESC LIMIT 2";
$news_result = $conn->query($news_query);
if ($news_result) {
    while ($row = $news_result->fetch_assoc()) {
        $featured_news[] = $row;
    }
}

// Combine for display (Events first, then News, for a total of max 4)
$combined_events_news = array_merge($featured_events, $featured_news); 


// 3. PROJECTS QUERY (No changes requested, remains 3 featured items)
$projects_query = "SELECT project_id, title, short_description, image_path FROM projects WHERE tag = 'FEATURED' ORDER BY project_id DESC LIMIT 3";
$projects_result = $conn->query($projects_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Technowatch Club</title>
    
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">

    <link rel="stylesheet" href="assets/css/preloader.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/preloader.js" defer></script>
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'preloader.php'; ?>

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
                <h2 class="merchandise-title">Featured Merchandise</h2>
                <div class="product-grid">
                    <?php if (!empty($merch_items)): ?>
                        <?php foreach ($merch_items as $item): ?>
                            <div class="product-card">
                                <div class="product-image-wrapper">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'assets/imgs/placeholder_merch.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="product-image">
                                </div>
                                <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="product-price">₱<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center w-full" style="padding: 20px;">No featured merchandise currently available in stock.</p>
                    <?php endif; ?>
                </div>

                <div class="merchandise-actions">
                    <a href="merch.php" class="btn btn-primary">View All Merchandise</a>
                </div>
            </div>
        </section>

        <div class="news-events-showcase">
            <h2>Latest News & Events</h2>
            <div class="news-grid">
                <?php if (!empty($combined_events_news)): ?>
                    <?php foreach ($combined_events_news as $item): 
                        // Determine the badge/label based on type and featuring
                        $badge_class = $item['type'] === 'event' ? 'live-badge' : 'new-badge'; 
                        $badge_text = strtoupper($item['type']);
                        
                        $description = htmlspecialchars($item['summary']);
                        $title = htmlspecialchars($item['title']); 
                    ?>
                        <div class="news-card">
                            <div class="card-header">
                                <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'assets/imgs/placeholder.jpg'); ?>" 
                                     alt="Thumbnail for <?php echo $title; ?>" 
                                     class="news-thumbnail">
                                <?php if($badge_text): ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo $title; ?></h4>
                            <p><?php echo $description; ?></p>
                            <a href="event_details.php?id=<?php echo $item['item_id']; ?>" class="read-more-link">Read More &rarr;</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center w-full" style="padding: 20px;">No recent news or events to display.</p>
                <?php endif; ?>
            </div>
            <div class="view-all-cta">
                <a href="eventsNews.php" class="btn-ghost-blue">View All News & Events</a>
            </div>
        </div>

        <div class="projects-showcase">
            <h2>See What We're Building</h2>
            <div class="projects-grid">
                <?php if ($projects_result && $projects_result->num_rows > 0): ?>
                    <?php while ($item = $projects_result->fetch_assoc()): ?>
                        <div class="project-card">
                            <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'assets/imgs/placeholder_project.jpg'); ?>" 
                                 alt="Image of <?php echo htmlspecialchars($item['title']); ?>" 
                                 class="project-image">
                            <div class="project-info">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['short_description']); ?></p>
                                <a href="projects.php?id=<?php echo $item['project_id']; ?>" class="view-project-btn">View Project &rarr;</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                     <p class="text-center w-full" style="padding: 20px;">No featured projects to display.</p>
                <?php endif; ?>
            </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>