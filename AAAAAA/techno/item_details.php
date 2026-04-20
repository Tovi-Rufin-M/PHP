<?php
// technowatch/item_details.php - Displays the full content of an Event or News item

session_start();
// Use the established connection path
include 'admin/includes/db_connect.php';

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

    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/eventsNews.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* Basic styling for the detail view, adjust as needed */
        .details-container { max-width: 900px; margin: 50px auto; padding: 20px; }
        .details-meta { color: #666; margin-bottom: 20px; font-size: 0.9em; }
        .details-content p { line-height: 1.6; margin-bottom: 1em; }
        .back-link { display: inline-block; margin-top: 30px; }

        /* -------------------------------------- */
        /* RULES FOR SIDE-BY-SIDE LAYOUT (Flexbox) */
        /* -------------------------------------- */

        .media-content-wrapper {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .details-image-container {
            flex: 0 0 40%;
            max-width: 300px;
            border-radius: 8px;
            overflow: hidden;
        }

        .details-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .details-content {
            flex: 1;
        }

        @media (max-width: 768px) {
            .media-content-wrapper {
                flex-direction: column;
            }
            .details-image-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="details-section">
        <div class="details-container">

            <h1><?php echo htmlspecialchars($title); ?></h1>

            <?php if ($item_data): ?>

                <div class="details-meta">
                    Type: <strong><?php echo ucfirst($item_type); ?></strong> |
                    Posted: <strong><?php echo date('F j, Y', strtotime($item_data['created_at'])); ?></strong>

                    <?php if ($item_data['type'] == 'event'): ?>

                        <?php if ($item_data['event_date']): ?>
                            | Date of Event: <strong><?php echo date('F j, Y', strtotime($item_data['event_date'])); ?></strong>
                        <?php endif; ?>

                        <?php if ($item_data['event_time']): ?>
                            | Time: <strong><?php echo date('g:i A', strtotime($item_data['event_time'])); ?></strong>
                        <?php endif; ?>

                        <?php if ($item_data['location']): ?>
                            | Location: <strong><?php echo htmlspecialchars($item_data['location']); ?></strong>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

                <div class="media-content-wrapper">
                    <?php if ($item_data['image_path']): ?>
                        <div class="details-image-container">
                            <img src="<?php echo htmlspecialchars($item_data['image_path']); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="details-image">
                        </div>
                    <?php endif; ?>

                    <div class="details-content">
                        <p><?php echo nl2br(htmlspecialchars($content)); ?></p>
                    </div>
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
