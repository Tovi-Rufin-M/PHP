<?php
// technowatch/events_data.php

// Use the existing connection file
// NOTE: We MUST ensure db_connect.php or admin/includes/db_connect.php is available. 
// Assuming you corrected the path in eventsNews.php to 'admin/includes/db_connect.php',
// we'll use the same relative path here since this file is also in the root.
include 'admin/includes/db_connect.php'; 

// --- 1. Fetch Events ---
$events_query = "SELECT * FROM events_news 
                 WHERE is_published = 1 AND type = 'event' 
                 ORDER BY event_date DESC";
$events_result = $conn->query($events_query);

// --- 2. Fetch News ---
$news_query = "SELECT * FROM events_news 
               WHERE is_published = 1 AND type = 'news' 
               ORDER BY created_at DESC LIMIT 6";
$news_result = $conn->query($news_query);

// --- START: Events Timeline HTML ---
echo '<div class="events-timeline">';

if ($events_result->num_rows > 0) {
    while ($event = $events_result->fetch_assoc()) {
        $is_past = strtotime($event['event_date']) < time();
        $class = $is_past ? 'past-event' : '';
        $action_link = 'item_details.php?id=' . $event['item_id'];
        $action_text = $is_past ? 'View Details' : 'Register Now'; 
        ?>
        <div class="timeline-item <?php echo $class; ?>">
            <div class="timeline-image">
                <img src="<?php echo htmlspecialchars($event['image_path'] ?? 'assets/imgs/default.png'); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
            </div>
            <div class="timeline-content">
                <div class="timeline-date"><?php echo date('F j, Y', strtotime($event['event_date'])); ?></div>
                <?php if (!$is_past): ?>
                    <div class="event-badge">UPCOMING</div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p><?php echo htmlspecialchars($event['summary']); ?></p>
                <div class="event-meta">
                    <span>
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($event['location'] ?? 'Online'); ?>
                    </span>
                    <span>
                        <i class="fas fa-clock"></i> 
                        <?php 
                            if ($event['event_time']) {
                                echo date('g:i A', strtotime($event['event_time'])); 
                            } else {
                                echo 'All Day'; 
                            }
                        ?>
                    </span>
                </div>
                <a href="<?php echo $action_link; ?>" class="register-btn"><?php echo $action_text; ?></a>
            </div>
        </div>
        <?php
    }
} else {
    echo '<p class="text-center p-5">No upcoming events scheduled right now.</p>';
}

if (isset($events_result)) $events_result->free();
echo '</div>'; // Close events-timeline

// --- START: News Grid HTML ---
// New line
echo '<h2 class="news-heading-center">Latest Tech-News</h2>';
echo '<div class="news-grid">';

if ($news_result->num_rows > 0) {
    while ($news = $news_result->fetch_assoc()) {
        $time_diff = time() - strtotime($news['created_at']);
        $days_ago = floor($time_diff / (60 * 60 * 24));
        $time_label = ($days_ago == 0) 
            ? 'Today' 
            : (($days_ago == 1) ? '1 day ago' : $days_ago . ' days ago');

        $category_label = ucfirst($news['type']);
        ?>
        
        <div class="news-card">
            <div class="news-category"><?php echo $category_label; ?></div>
            <h3><?php echo htmlspecialchars($news['title']); ?></h3>

            <?php if (!empty($news['location'])): ?>
                <div class="news-meta-location">
                    <span>
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($news['location']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <p><?php echo htmlspecialchars($news['summary']); ?></p>

            <div class="news-footer">
                <span><?php echo $time_label; ?></span>
                <a href="item_details.php?id=<?php echo $news['item_id']; ?>">Read More</a>
            </div>
        </div>

        <?php
    }
} else {
    echo '<p class="text-center p-3">No news updates available.</p>';
}
if (isset($news_result)) $news_result->free();
echo '</div>'; // Close news-grid
// --- END: News Grid HTML ---

$conn->close();
?>