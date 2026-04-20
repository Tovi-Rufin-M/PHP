<?php
// Technowatch Club | Merchandise Page - DYNAMICALLY LOADED VIA AJAX
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchandise | Technowatch Club</title>

    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/merch.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>

<body>

<?php include 'header.php'; ?>

<section class="merchandise-section" style="background-image: url('assets/imgs/bg.png');">
    <div class="merchandise-container">
        <h2 class="merchandise-title">Technowatch Official Merchandise</h2>
        <p class="merchandise-subtitle">Gear up with the latest tech apparel and accessories from the club.</p>

        <div id="merch-content-wrapper">
            <p>Loading merchandise...</p>
        </div>

        <div class="merchandise-actions all-merch">
            <a href="https://docs.google.com/forms/d/e/1FAIpQLSctwqm8bkV9t89R7HTjt86hH7sPmCRF5XeTuwceffdbAPBqgw/viewform" class="btn btn-primary">Get Yours Now</a>
        </div>
    </div>
</section>

<script>
    let currentActiveTabId = 'tshirts';

    const changeTab = (targetId) => {
        if (!targetId) return;

        const tabButtons = document.querySelectorAll('#merch-tabs .tab-button');
        const tabContents = document.querySelectorAll('#merch-content-wrapper .tab-content');

        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        const activeButton = document.querySelector(`.tab-button[data-tab="${targetId}"]`);
        const activeContent = document.getElementById(targetId);

        if (activeButton) activeButton.classList.add('active');
        if (activeContent) activeContent.classList.add('active');

        currentActiveTabId = targetId;
    };

    const attachTabListeners = () => {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.getAttribute('data-tab');
                changeTab(targetTab);
            });
        });
    };

    const fetchMerchandise = async () => {
        try {
            // *** REVISED: Change the fetch URL to the dedicated fetch file ***
            const response = await fetch('merch_fetch.php'); 
            const data = await response.json();

            if (data.success) {
                const wrapper = document.getElementById('merch-content-wrapper');
                const tempContent = document.createElement('div');
                tempContent.innerHTML = data.html;

                let targetTab = data.first_active_tab;

                if (tempContent.querySelector(`.tab-button[data-tab="${currentActiveTabId}"]`)) {
                    targetTab = currentActiveTabId;
                }

                wrapper.innerHTML = data.html;
                attachTabListeners();
                changeTab(targetTab);
            }
        } catch (error) {
            console.error("Error fetching merchandise:", error);
            // Optionally, update the wrapper to show an error message
            // document.getElementById('merch-content-wrapper').innerHTML = '<p>Failed to load merchandise. Please try again later.</p>';
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Initial load
        fetchMerchandise(); 
        // Load every 3 seconds without refresh
        setInterval(fetchMerchandise, 3000);
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>