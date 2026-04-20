<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Britto Charette</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/projects.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- JS Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
    
    </head>
<body class="dark-theme">

    <section class="signature-projects-section">
        <div class="container">
            <div class="signature-content">
                <h1 class="signature-title">Our <br> Signature Projects</h1>
                <p class="signature-description">Welcome to Britto Charette's Projects page, where we showcase our finest interior design endeavors. Our portfolio spans a diverse range of residential and commercial spaces, each crafted with meticulous attention to detail and a passion for innovation.</p>

                <div class="thumbnail-previews">
                    <div class="thumbnail-item"></div>
                    <div class="thumbnail-item"></div>
                    <div class="thumbnail-item"></div>
                </div>
            </div>

            <div class="signature-image-wrapper">
                <img src="assets/imgs/carousel_1.jpg" alt="Signature Project Interior View" class="signature-main-image">
                <a href="#" class="discover-button">
                    Discover More 
                    <svg viewBox="0 0 24 24" class="arrow-icon">
                        <path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <section class="all-projects-section">
        <div class="container">
            <h2 class="section-heading">Our Projects</h2>

            <div class="project-filters">
                <button class="filter-btn active">All</button>
                <button class="filter-btn">Family Room</button>
                <button class="filter-btn">Living Room</button>
                <button class="filter-btn">Bedroom</button>
                <button class="filter-btn">Kitchen</button>
                <button class="filter-btn">Bathroom</button>
            </div>

            <div class="project-list">
                
                <div class="project-item project-milina">
                    <div class="project-text-content">
                        <h3 class="project-name">Milina</h3>
                        <div class="project-small-image-wrapper">
                            <img src="assets/imgs/carousel_2.jpg" alt="Milina small view">
                        </div>
                        <p class="project-description">Milina is a 7,454 sqft new-construction residence in the Hamptons with spectacular—and we mean spectacular—ocean views.</p>
                    </div>
                    <div class="project-large-image-wrapper">
                        <img src="assets/imgs/carousel_3.jpg" alt="Milina living room view" class="project-main-image">
                        <a href="#" class="project-arrow-button">
                            <svg viewBox="0 0 24 24" class="arrow-icon"><path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="project-item project-indianapolis">
                    <div class="project-text-content">
                         <h3 class="project-name">Indianapolis</h3>
                    </div>
                    <div class="project-large-image-wrapper">
                        <img src="assets/imgs/carousel_4.jpg" alt="Indianapolis kitchen view" class="project-main-image">
                         <a href="#" class="project-arrow-button">
                            <svg viewBox="0 0 24 24" class="arrow-icon"><path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="project-item project-ritz-carlton">
                     <div class="project-text-content">
                        <h3 class="project-name">Ritz-Carlton <br> Residences</h3>
                        <div class="project-small-image-wrapper">
                            <img src="assets/imgs/carousel_5.jpg" alt="Ritz-Carlton small view">
                        </div>
                        <p class="project-description">A stunning new development of luxury condominiums, the Ritz-Carlton Residences blends state-of-the-art amenities with more than two acres of tropical landscape and private beach.</p>
                    </div>
                    <div class="project-large-image-wrapper">
                        <img src="assets/imgs/carousel_6.jpg" alt="Ritz-Carlton bedroom view" class="project-main-image">
                        <a href="#" class="project-arrow-button">
                            <svg viewBox="0 0 24 24" class="arrow-icon"><path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="pagination">
                <button class="pagination-arrow prev-arrow">&lt;</button>
                <button class="page-number">3</button>
                <button class="page-number active">4</button>
                <button class="page-number">5</button>
                <button class="page-number">6</button>
                <button class="page-number">7</button>
                <button class="pagination-arrow next-arrow">&gt;</button>
            </div>

        </div>
    </section>
    <?php include 'footer.php'; ?>

</body>

</html>