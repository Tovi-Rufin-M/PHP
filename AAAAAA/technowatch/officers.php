<?php
// Technowatch Club | Club Officers Page - REVISED
header('Content-Type: text/html; charset=utf-8');

// Database connection
include 'admin/includes/db_connect.php'; 

// Note: All officer data fetching is handled dynamically via officers_content.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Officers | Technowatch Club</title>

    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/officials.css"> 
    <link rel="stylesheet" href="assets/css/officers.css">

    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>

    <!-- JS -->
    <script src="assets/js/script.js" defer></script>
</head>
<body>

<?php include 'header.php'; ?>

<div class="org-chart-container professional-theme">
    <div id="officer-data-wrapper"></div>
</div>

<?php 
if (isset($conn)) $conn->close();
include 'footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('officer-data-wrapper');
    const contentUrl = 'officers_content.php';
    const pollingInterval = 3000; // 3 seconds

    // -------------------------
    // MODAL HANDLERS
    // -------------------------
    function attachModalListeners() {
        // Open modal on card or "View Bio" click
        document.querySelectorAll('.hero-card, .details-button').forEach(el => {
            el.removeEventListener('click', openModalHandler);
            el.addEventListener('click', openModalHandler);
        });

        // Close modal on close button
        document.querySelectorAll('.bio-modal .close-button').forEach(btn => {
            btn.removeEventListener('click', closeModalHandler);
            btn.addEventListener('click', closeModalHandler);
        });
    }

    function openModalHandler(event) {
        // Avoid interfering with links inside card
        if (event.target.closest('a')) return;

        const card = event.currentTarget.closest('.hero-card');
        if (!card) return;

        const bioId = card.getAttribute('data-bio-target');
        const modal = document.getElementById(bioId);
        if (modal) {
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }
    }

    function closeModalHandler(event) {
        const modal = event.target.closest('.bio-modal');
        if (modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    // Close modal by clicking on overlay
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('bio-modal')) {
            event.target.classList.remove('active');
            event.target.setAttribute('aria-hidden', 'true');
        }
    });

    // -------------------------
    // DYNAMIC DATA FETCH
    // -------------------------
    function fetchOfficerData() {
        fetch(contentUrl)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                wrapper.innerHTML = html;
                attachModalListeners(); // Reattach listeners for new content
            })
            .catch(error => console.error('Error fetching officer data:', error));
    }

    // Initial load
    fetchOfficerData();

    // Polling interval
    setInterval(fetchOfficerData, pollingInterval);
});
</script>

</body>
</html>
