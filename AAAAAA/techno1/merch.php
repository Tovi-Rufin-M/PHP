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
    // EDITED: Changed 'stock > 0' to 'stock >= 0' to include out-of-stock items.
    // NOTE: We fetch ALL data here, and use JavaScript to handle pagination and filtering.
    $sql = "SELECT merch_id, name, price, stock, image_url, description, category FROM merch WHERE stock >= 0 ORDER BY merch_id DESC";
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
    <link rel="stylesheet" href="assets/css/footer.css">
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
            <div class="merch-banner-content">
                <h1 class="merch-banner-title">
                    <strong>TECHNOWATCH CLUB GEAR</strong>
                </h1>
                <p class="merch-banner-tagline">
                    Grab your <strong>Official Merchandise</strong> before it's gone!
                </p>
                <a 
                    href="<?= $google_form_link ?>" 
                    target="_blank" 
                    class="banner-order-btn"
                >
                    <i class="fas fa-shopping-cart"></i> Browse & Order Now
                </a>
            </div>
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
                <?php 
                $card_index = 0; 
                $cards_per_page = 4; // Define cards per page for initial card naming
                foreach ($merch_items as $item): 
                    // Calculate page number for card
                    $page_number = floor($card_index / $cards_per_page) + 1;
                ?>
                    <div 
                        class="merch-card page-<?= $page_number ?>" 
                        data-category="<?= htmlspecialchars($item['category']) ?>" 
                        data-page="<?= $page_number ?>"
                        style="display: none;"
                    >
                        
                        <div class="merch-image-container">
                            <?php 
                            $image_src = $item['image_url'] ? htmlspecialchars($item['image_url']) : 'path/to/placeholder/image.jpg';
                            $full_image_src = $image_src; 
                            ?>
                            <img 
                                src="<?= $image_src ?>" 
                                alt="<?= htmlspecialchars($item['name']) ?>" 
                                class="merch-image hover:cursor-zoom-in"
                                data-full-src="<?= $full_image_src ?>" 
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
                <?php 
                $card_index++;
                endforeach; 
                ?>
            </div>
            
            <div id="pagination-container" class="pagination-container">
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
    
    <div id="image-modal" class="image-modal" style="display: none;">
        <span class="close-btn">&times;</span>
        <img class="modal-content" id="modal-image">
        <div id="caption" class="modal-caption"></div>
    </div>

    <style>
        /* Existing modal styles... */
        .image-modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            padding-top: 60px; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgb(0,0,0); 
            background-color: rgba(0,0,0,0.9); 
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
        }

        .modal-caption {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            text-align: center;
            color: #ccc;
            padding: 10px 0;
            height: 150px;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        @media only screen and (max-width: 700px){
            .modal-content {
                width: 100%;
            }
        }
        
        .hover\:cursor-zoom-in:hover {
            cursor: zoom-in;
        }
        /* END: Existing modal styles */


        /* --- NEW PAGINATION STYLES --- */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 40px;
            margin-bottom: 20px;
            gap: 10px;
        }

        .page-btn {
            background-color: transparent;
            border: 1px solid var(--color-text-subtle);
            color: var(--color-text-base);
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
            font-weight: 600;
        }

        .page-btn:hover:not(.active) {
            background-color: var(--color-bg-light);
            border-color: var(--color-primary);
        }

        .page-btn.active {
            background-color: var(--color-primary);
            color: var(--color-bg-dark); /* Ensure high contrast text */
            border-color: var(--color-primary);
            cursor: default;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const merchCards = Array.from(document.querySelectorAll('.merch-card'));
            const noMerchMessage = document.getElementById('no-merch-message'); 
            const paginationContainer = document.getElementById('pagination-container');
            const cardsPerPage = 4; // Define the card limit per page

            // --- 1. PAGINATION LOGIC ---

            /**
             * Hides all cards, then shows only the cards for the specified page number 
             * and scrolls to the top of the merch list.
             */
            function displayPage(pageNumber, category) {
                const merchList = document.getElementById('merch-list');
                
                // Hide all cards initially
                merchCards.forEach(card => card.style.display = 'none');
                
                // Get all cards that match the current filter category
                const filteredCards = merchCards.filter(card => 
                    category === 'all' || card.getAttribute('data-category') === category
                );

                // Calculate start and end index for the current page
                const startIndex = (pageNumber - 1) * cardsPerPage;
                const endIndex = startIndex + cardsPerPage;

                // Display the relevant cards
                for (let i = startIndex; i < endIndex && i < filteredCards.length; i++) {
                    filteredCards[i].style.display = 'block';
                }

                // Scroll to the top of the merchandise list for better UX
                merchList.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }


            /**
             * Generates the pagination buttons based on the number of filtered items.
             */
            function setupPagination(filteredCards, category) {
                paginationContainer.innerHTML = ''; // Clear existing buttons
                
                const totalCards = filteredCards.length;
                const totalPages = Math.ceil(totalCards / cardsPerPage);

                if (totalPages <= 1) {
                    paginationContainer.style.display = 'none';
                    return;
                }
                
                paginationContainer.style.display = 'flex';

                // Create and append buttons for each page
                for (let i = 1; i <= totalPages; i++) {
                    const button = document.createElement('button');
                    button.classList.add('page-btn');
                    button.textContent = i;
                    button.setAttribute('data-page', i);
                    
                    if (i === 1) {
                        button.classList.add('active'); // Page 1 is active by default
                    }

                    button.addEventListener('click', function() {
                        const pageNumber = parseInt(this.getAttribute('data-page'));
                        
                        // Update active state
                        document.querySelectorAll('.page-btn').forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Display the correct cards
                        displayPage(pageNumber, category);
                    });

                    paginationContainer.appendChild(button);
                }
            }
            
            // --- 2. FILTER LOGIC (UPDATED) ---

            function applyFilter(filterCategory) {
                // 1. Get filtered cards (this time, we use a count for the 'No Merch' message)
                const filteredCards = merchCards.filter(card => 
                    filterCategory === 'all' || card.getAttribute('data-category') === filterCategory
                );
                
                const visibleCount = filteredCards.length;

                // 2. Setup Pagination and display the first page
                setupPagination(filteredCards, filterCategory);
                if (visibleCount > 0) {
                     // Display the first page of the new filter
                    displayPage(1, filterCategory); 
                    noMerchMessage.style.display = 'none';
                } else {
                    // Hide all cards if count is 0
                    merchCards.forEach(card => card.style.display = 'none');
                    paginationContainer.style.display = 'none';
                    noMerchMessage.style.display = 'block';
                }
            }


            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filterCategory = this.getAttribute('data-filter');

                    // 1. Manage Active State of Buttons
                    filterButtons.forEach(btn => {
                        btn.classList.remove('filter-btn-active');
                        btn.classList.add('filter-btn-default');
                    });
                    this.classList.add('filter-btn-active');
                    this.classList.remove('filter-btn-default');

                    // 2. Apply the new filter and pagination logic
                    applyFilter(filterCategory);
                });
            });
            
            // Set 'All Items' button to active and run filter/pagination on initial load
            document.querySelector('.filter-btn[data-filter="all"]').click();

            // --- 3. IMAGE ZOOM LOGIC (Kept intact) ---
            const modal = document.getElementById("image-modal");
            const modalImg = document.getElementById("modal-image");
            const captionText = document.getElementById("caption");
            const closeBtn = document.querySelector(".close-btn");
            const merchImages = document.querySelectorAll(".merch-image");

            merchImages.forEach(img => {
                img.addEventListener('click', function() {
                    modal.style.display = "block";
                    modalImg.src = this.getAttribute('data-full-src');
                    captionText.innerHTML = this.alt; 
                });
            });

            closeBtn.onclick = function() { 
                modal.style.display = "none";
            }
            
            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>