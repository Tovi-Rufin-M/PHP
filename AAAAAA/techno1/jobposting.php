<?php
// Technowatch Club | Job Postings
header('Content-Type: text/html; charset=utf-8');
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/jobpostings.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- JQUERY MUST BE LOADED FOR AJAX -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="jobs-section">
        <div class="section-header">
            <h1>Career Opportunities</h1>
            <p>Find your next tech job or internship through our network</p>
            
            <div class="job-search-wrapper">
                <!-- Restored HTML structure for styling and added form for JS submission -->
                <form id="job-filter-form" onsubmit="return false;" class="job-search">
                    <input type="text" 
                        placeholder="Search jobs..." 
                        name="search" 
                        id="search-input" 
                        value="">
                        
                    <button type="submit"><i class="fas fa-search"></i></button>
                    
                    <select name="category" id="category-select">
                        <option value="">All Categories</option>
                        <option value="Internship">Internship</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- AJAX TARGET CONTAINER -->
        <div id="dynamic-jobs-content">
            <p style="text-align: center;">Loading job listings...</p>
        </div>
        
    </section>

    <?php include 'footer.php'; ?>

    <script>
        $(document).ready(function() {
            // Function to load content via AJAX
            function loadJobPostings(isFilter = false, page = 1) {
                let searchVal = $('#search-input').val();
                let categoryVal = $('#category-select').val();
                
                // Build the data object to send via GET
                let data = {
                    search: searchVal,
                    category: categoryVal,
                    page: page // Pass the current page number
                };

                // Add a temporary loading state
                if(isFilter) {
                    $('#dynamic-jobs-content').html('<p style="text-align: center; padding: 50px 0;">Loading results...</p>');
                }

                $.ajax({
                    url: 'jobs_data.php', 
                    type: 'GET',
                    data: data, // Send the search, category, and page filters
                    success: function(html) {
                        $('#dynamic-jobs-content').html(html);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading job postings:", error);
                        $('#dynamic-jobs-content').html('<p style="color: red; text-align: center;">Failed to load jobs. Please refresh the page.</p>');
                    }
                });
                
                // If a filter or pagination was manually triggered, restart the auto-refresh interval
                if (isFilter) {
                    clearInterval(window.jobInterval);
                    // The auto-refresh should run without changing the page/filters, 
                    // so we call loadJobPostings(false) to maintain state.
                    window.jobInterval = setInterval(() => loadJobPostings(false, page), 3000); 
                }
            }

            // --- Event Listeners ---

            // 1. Intercept form submission (Search button click)
            $('#job-filter-form').on('submit', function(e) {
                e.preventDefault(); 
                loadJobPostings(true, 1); // Reset to page 1 when filtering
            });

            // 2. Category change listener
            $('#category-select').on('change', function() {
                loadJobPostings(true, 1); // Reset to page 1 when filtering
            });

            // 3. Delegation for Pagination clicks
            // Since pagination links are loaded dynamically, we must use delegation on the static parent (#dynamic-jobs-content)
            $('#dynamic-jobs-content').on('click', '.pagination a', function(e) {
                e.preventDefault();
                let newPage = $(this).data('page');
                // Use the page number from the data attribute and mark it as a manual filter/load
                loadJobPostings(true, newPage); 
            });


            // --- Initial Load and Auto-Refresh ---
            
            // Initial load on page load
            loadJobPostings(); 

            // Set the interval to refresh the content every 3 seconds (3000 milliseconds)
            // It will check the current page and filters on each refresh.
            window.jobInterval = setInterval(() => loadJobPostings(false, 1), 3000); 
        });
    </script>

</body>
</html>