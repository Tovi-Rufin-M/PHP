<?php
// Technowatch Club | Advisers Page - FINAL REVISION
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technowatch Club | Merchandise</title>

    <!-- Assuming these contain base styles and responsive utilities -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/officials.css">
    <link rel="stylesheet" href="assets/css/merch.css">
    
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include 'header.php'; ?>

    <section class="merchandise-section" style="background-image: url('assets/imgs/bg.png');">
        <div class="merchandise-container">
            <h2 class="merchandise-title">Technowatch Official Merchandise</h2>
            <p class="merchandise-subtitle">Gear up with the latest tech apparel and accessories from the club.</p>

            <!-- 1. TAB NAVIGATION -->
            <div class="merchandise-tabs" id="merch-tabs">
                <!-- Data attributes link the button to the content below -->
                <button class="tab-button active" data-tab="tshirts">T-SHIRTS</button>
                <button class="tab-button" data-tab="pins">PINS</button>
                <button class="tab-button" data-tab="lanyards">LANYARDS</button>
                <button class="tab-button" data-tab="caps">CAPS</button>
            </div>

            <!-- 2. TAB CONTENT - T-SHIRTS -->
            <div id="tshirts" class="tab-content active">
                <div class="product-grid">
                    <?php 
                        // Note: I'm only including 3 cards per section for brevity, 
                        // but you would place all T-Shirt items here.
                    ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <!-- Placeholder image URL is safer than a potentially missing local file -->
                            <img src="assets/imgs/tshirt.png" alt="Club T-Shirt" class="product-image">
                        </div>
                        <h3 class="product-name">Club T-Shirt (Navy)</h3>
                        <p class="product-price">$25.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>

                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="assets/imgs/tshirt.png" alt="TShirt" class="product-image">
                        </div>
                        <h3 class="product-name">Club T-Shirt (White)</h3>
                        <p class="product-price">$25.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>

                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="assets/imgs/tshirt.png" alt="TShirt" class="product-image">
                        </div>
                        <h3 class="product-name">Limited Edition Tee</h3>
                        <p class="product-price">$30.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>
                </div>
            </div>

            <!-- 3. TAB CONTENT - PINS -->
            <div id="pins" class="tab-content">
                <div class="product-grid">
                    <!-- Pin Item 1 -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="assets/imgs/niggapin.png" alt="WhitePin" class="product-image">
                        </div>
                        <h3 class="product-name">White Logo Pin</h3>
                        <p class="product-price">$5.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>
                    <!-- Pin Item 2 -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="assets/imgs/whitepin.png" alt="BlackPin" class="product-image">
                        </div>
                        <h3 class="product-name">Black Logo Pin</h3>
                        <p class="product-price">$5.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>
                    <!-- ADDED PLACEHOLDER ITEM 3 to force grid layout for Pins -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="assets/imgs/niggapin.png" alt="RedPin" class="product-image">
                        </div>
                        <h3 class="product-name">Red Logo Pin</h3>
                        <p class="product-price">$6.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>
                </div>
            </div>

            <!-- 4. TAB CONTENT - LANYARDS -->
            <div id="lanyards" class="tab-content">
                <div class="product-grid">
                    <!-- Original Item -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="https://placehold.co/400x400/1e3a5a/ffffff?text=Lanyard" alt="Lanyard" class="product-image">
                        </div>
                        <h3 class="product-name">Standard Lanyard</h3>
                        <p class="product-price">$8.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>
                    
                    <!-- ADDED PLACEHOLDER ITEM 2 to force grid layout -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="https://placehold.co/400x400/1e3a5a/ffffff?text=Lanyard+V2" alt="Lanyard V2" class="product-image">
                        </div>
                        <h3 class="product-name">Premium Lanyard</h3>
                        <p class="product-price">$12.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>

                    <!-- ADDED PLACEHOLDER ITEM 3 to force grid layout -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="https://placehold.co/400x400/1e3a5a/ffffff?text=Lanyard+Lite" alt="Lanyard Lite" class="product-image">
                        </div>
                        <h3 class="product-name">Lite Lanyard</h3>
                        <p class="product-price">$6.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>

                </div>
            </div>

            <!-- 5. TAB CONTENT - CAPS -->
            <div id="caps" class="tab-content">
                <div class="product-grid">
                    <!-- Original Item -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="https://placehold.co/400x400/000000/007bff?text=Baseball+Cap" alt="Cap" class="product-image">
                        </div>
                        <h3 class="product-name">Baseball Cap</h3>
                        <p class="product-price">$18.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>

                    <!-- ADDED PLACEHOLDER ITEM 2 to force grid layout -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="https://placehold.co/400x400/1e3a5a/ffffff?text=Snapback" alt="Snapback" class="product-image">
                        </div>
                        <h3 class="product-name">Club Snapback</h3>
                        <p class="product-price">$22.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>

                    <!-- ADDED PLACEHOLDER ITEM 3 to force grid layout -->
                    <div class="product-card">
                        <div class="product-image-wrapper">
                             <img src="https://placehold.co/400x400/007bff/000000?text=Beanie" alt="Beanie" class="product-image">
                        </div>
                        <h3 class="product-name">Winter Beanie</h3>
                        <p class="product-price">$15.00</p>
                        <button class="buy-button"><i class="fas fa-shopping-cart"></i> Buy Now</button>
                    </div>
                </div>
            </div>
            
            <!-- Replaced "View All Merchandise" with "Get Yours Now" -->
            <div class="merchandise-actions all-merch">
                <a href="/order-form.html" class="btn btn-primary">Get Yours Now</a>
            </div>

        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Function to change tab
            const changeTab = (targetId) => {
                // Deactivate all buttons and content
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Activate the selected button and content
                const activeButton = document.querySelector(`.tab-button[data-tab="${targetId}"]`);
                const activeContent = document.getElementById(targetId);

                if (activeButton) activeButton.classList.add('active');
                if (activeContent) activeContent.classList.add('active');
            };

            // Add click listeners to buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetTab = button.getAttribute('data-tab');
                    changeTab(targetTab);
                });
            });

            // Initial load: ensure the first tab is visible
            if (tabButtons.length > 0) {
                changeTab(tabButtons[0].getAttribute('data-tab'));
            }
        });
    </script>

<?php include 'footer.php'; ?>

</body>
</html>