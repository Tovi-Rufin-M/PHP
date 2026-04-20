<?php 
// FILE: techno/merch.php


// 1. --- DATABASE CONNECTION ---
// Path from techno/merch.php to techno/admin/includes/db
require_once 'admin/includes/db_connect.php'; 

// Define ALL possible merchandise categories for the filter buttons
$all_categories = ['T-Shirts', 'Lanyards', 'Pins', 'Others'];

// Define the Google Form Link
$google_form_link = "https://docs.google.com/forms/d/e/1FAIpQLSctwqm8bkV9t89R7HTjt86hH7sPmCRF5XeTuwceffdbAPBqgw/viewform"; 

// Initialize default variables
$merch_items = [];

if ($conn->connect_error) {
    // Gracefully handle connection failure
    // Log error if necessary
} else {
    // --- 2. FETCH ALL AVAILABLE MERCHANDISE ---
    $sql = "SELECT merch_id, name, price, stock, image_url, description, category FROM merch WHERE stock > 0 ORDER BY merch_id DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $merch_items = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchandise | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">

    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/merch.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>

<body class="merch-body">

    <?php include 'header.php'; ?>

    <div class="container">
        <header class="merch-header">
            <h1>
                🔥 Official Technowatch Merch
            </h1>
            <p>Grab your gear before it's gone!</p>
        </header>
        
        <div class="filter-container" id="category-filters">
            <button class="filter-btn filter-btn-active" data-filter="all">
                <i class="fas fa-grip-horizontal mr-2"></i> All Items
            </button>
            <?php 
            $icon_map = [
                'T-Shirts' => 'fas fa-tshirt',
                'Lanyards' => 'fas fa-key',
                'Pins' => 'fas fa-thumbtack', 
                'Others' => 'fas fa-box-open' 
            ];
            
            foreach ($all_categories as $cat): 
                $icon = $icon_map[$cat] ?? 'fas fa-shopping-bag';
            ?>
            <button class="filter-btn filter-btn-default" data-filter="<?= htmlspecialchars($cat) ?>">
                <i class="<?= $icon; ?> mr-2"></i> <?= htmlspecialchars($cat) ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($merch_items)): ?>
            <div class="merch-list" id="merch-list">
                <?php foreach ($merch_items as $item): ?>
                    <div class="merch-card" data-category="<?= htmlspecialchars($item['category']) ?>">
                        
                        <div class="merch-image-container">
                            <?php 
                            $image_src = $item['image_url'] ? htmlspecialchars($item['image_url']) : 'path/to/placeholder/image.jpg';
                            ?>
                            <img 
                                src="<?= $image_src ?>" 
                                alt="<?= htmlspecialchars($item['name']) ?>" 
                                class="merch-image"
                            >
                        </div>
                        
                        <div class="merch-content">
                            <h2 class="merch-title">
                                <?= htmlspecialchars($item['name']) ?>
                            </h2>
                            <p class="merch-description">
                                <?= htmlspecialchars($item['description'] ?? 'No description provided.') ?>
                            </p>
                            
                            <div class="price-stock-row">
                                <span class="merch-price">
                                    ₱<?= number_format($item['price'], 2) ?>
                                </span>
                                <?php 
                                    $stock = (int)$item['stock'];
                                    $stock_text = "In Stock";
                                    $stock_class = "in-stock";
                                    
                                    if ($stock < 20 && $stock > 0) {
                                        $stock_text = "Low Stock! ($stock left)";
                                        $stock_class = "low-stock";
                                    } else if ($stock <= 0) {
                                        $stock_text = "Sold Out";
                                        $stock_class = "sold-out";
                                    }
                                ?>
                                <span class="stock-status <?= $stock_class ?>">
                                    <?= $stock_text ?>
                                </span>
                            </div>
                            
                            <a 
                                href="<?= $google_form_link ?>"
                                target="_blank"
                                class="order-link <?= ($stock <= 0) ? 'disabled' : '' ?>"
                                <?= ($stock <= 0) ? 'aria-disabled="true"' : '' ?>
                            >
                                <?php if ($stock > 0): ?>
                                    <i class="fas fa-shopping-bag mr-2"></i> Order Now
                                <?php else: ?>
                                    <span class="sold-out-btn">
                                        <i class="fas fa-times-circle mr-2"></i> Sold Out
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="no-merch-message" class="no-merch-message" style="display: none;">
                <p>
                    <i class="fas fa-search-minus mr-2"></i> No Merch Found in this Category
                </p>
                <p>Try selecting 'All Items' or a different filter.</p>
            </div>
            
            <?php else: ?>
            <div class="no-merch-message">
                <p>
                    <i class="far fa-sad-tear mr-2"></i> Sorry, we are currently sold out of all merchandise!
                </p>
                <p>Check back soon for new items.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const merchCards = document.querySelectorAll('.merch-card');
            const noMerchMessage = document.getElementById('no-merch-message'); 

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filterCategory = this.getAttribute('data-filter');
                    let visibleCount = 0; 

                    // 1. Manage Active State of Buttons (Swapping custom classes)
                    filterButtons.forEach(btn => {
                        btn.classList.remove('filter-btn-active');
                        btn.classList.add('filter-btn-default');
                    });
                    
                    this.classList.add('filter-btn-active');
                    this.classList.remove('filter-btn-default');

                    // 2. Filter the merchandise cards and count visible ones
                    merchCards.forEach(card => {
                        const cardCategory = card.getAttribute('data-category');
                        
                        if (filterCategory === 'all' || cardCategory === filterCategory) {
                            card.style.display = 'block';
                            visibleCount++; 
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // 3. Display "No Merch" message if no cards are visible
                    if (visibleCount === 0) {
                        noMerchMessage.style.display = 'block';
                    } else {
                        noMerchMessage.style.display = 'none';
                    }
                });
            });
            
            // Set 'All Items' button to active on initial load
            document.querySelector('.filter-btn[data-filter="all"]').click();
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>