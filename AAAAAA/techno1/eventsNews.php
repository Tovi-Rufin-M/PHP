<?php
// technowatch/eventsNews.php

// 1. We only need session_start() for potential user features, but not DB connection or queries, 
// as those are now in events_data.php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events & News | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">
    
    <link rel="stylesheet" href="assets/css/eventsNews.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
    <script src="assets/js/script.js"></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="events-section">
        <div class="section-header">
            <h1>Events</h1>
            <p>Discover what's happening in our tech community</p>
        </div>

        <div id="dynamic-events-news-container">
            <p style="text-align: center;">Loading latest updates...</p>
        </div>
        
    </section>

    <?php include 'footer.php'; ?>

    <script>
        $(document).ready(function() {
            // Function to load content via AJAX
            function loadEventsNews() {
                $.ajax({
                    url: 'events_data.php', // Fetches content from the dynamic file
                    type: 'GET',
                    success: function(data) {
                        // Replace the content inside the target container
                        $('#dynamic-events-news-container').html(data);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading events and news:", error);
                        $('#dynamic-events-news-container').html('<p style="color: red; text-align: center;">Failed to load updates. Please refresh the page.</p>');
                    }
                });
            }

            // 1. Load the content immediately on page load
            loadEventsNews(); 

            // 2. Set the interval to refresh the content every 3 seconds (3000 milliseconds)
            setInterval(loadEventsNews, 3000); 
        });
    </script>

</body>
</html>