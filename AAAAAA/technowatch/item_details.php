<?php
// technowatch/item_details.php - Displays the full content of an Event or News item

session_start();
include 'admin/includes/db_connect.php'; // Use the established connection path

// 1. Get the Item ID from the URL
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id === 0) {
    // Redirect if no ID is provided
    header('Location: eventsNews.php');
    exit;
}

// 2. Fetch the specific item from the database
$stmt = $conn->prepare("SELECT * FROM events_news WHERE item_id = ? AND is_published = 1");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Item not found or not published
    $title = "Item Not Found";
    $content = "<p>The requested event or news item could not be found or is no longer available.</p>";
    $item_data = null;
} else {
    $item_data = $result->fetch_assoc();
    $title = $item_data['title'];
    $content = $item_data['content'];
}

$stmt->close();
$conn->close();

// Helper variable for layout
$item_type = $item_data['type'] ?? 'item'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Technowatch Club</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Basic styling for the detail view, adjust as needed */
        .details-container { max-width: 900px; margin: 50px auto; padding: 20px; }
        .details-image { width: 100%; height: auto; margin-bottom: 20px; border-radius: 8px; }
        .details-meta { color: #666; margin-bottom: 20px; font-size: 0.9em; }
        .details-content p { line-height: 1.6; margin-bottom: 1em; }
        .back-link { display: inline-block; margin-top: 30px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="details-section">
        <div class="details-container">
            
            <h1><?php echo htmlspecialchars($title); ?></h1>

            <?php if ($item_data): ?>
                
                <div class="details-meta">
                    Type: **<?php echo ucfirst($item_type); ?>** | 
                    Posted: **<?php echo date('F j, Y', strtotime($item_data['created_at'])); ?>**
                    <?php if ($item_data['event_date']): ?>
                        | Date of Event: **<?php echo date('F j, Y', strtotime($item_data['event_date'])); ?>**
                    <?php endif; ?>
                </div>

                <?php if ($item_data['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($item_data['image_path']); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="details-image">
                <?php endif; ?>
                
                <div class="details-content">
                    <p><?php echo nl2br(htmlspecialchars($content)); ?></p>
                </div>
                
            <?php else: ?>
                <div class="alert alert-danger"><?php echo $content; ?></div>
            <?php endif; ?>
            
            <a href="eventsNews.php" class="back-link">← Back to Events & News</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>