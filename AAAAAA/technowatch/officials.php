<?php
// officials.php | Technowatch Club
header('Content-Type: text/html; charset=utf-8');
session_start();

// --- Database Connection ---
include 'admin/includes/db_connect.php';

// Fetch all officials
$query = "SELECT * FROM officials ORDER BY FIELD(category, 'HEAD', 'FACULTY', 'SECTION MAYORS'), sort_order ASC, name ASC";
$result = $conn->query($query);

// Group officials by category
$officials = [
    'HEAD' => [],
    'FACULTY' => [],
    'SECTION MAYORS' => []
];

while ($row = $result->fetch_assoc()) {
    $cat = strtoupper($row['category']);
    if (isset($officials[$cat])) {
        $officials[$cat][] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Officials & Advisers | Technowatch Club</title>

    <meta name="description" content="Meet the Department Head, Faculty Members, and Section Mayors of the Technowatch Club.">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/officials.css">

    <!-- Fonts / Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include 'header.php'; ?>

<div class="org-chart-container professional-theme">
    <h1 class="department-title">Technowatch Club Officials</h1>

    <div id="officials-content-container">
        <p>Loading officials data...</p>
    </div>

    <div class="officials-link-container">
        <a href="officers.php" class="officials-link-button">
            See Our Club Officers <i class="fas fa-users-cog"></i>
        </a>
    </div>
</div>

<!-- Modals for all officials -->
<?php foreach ($officials as $category => $members): ?>
    <?php foreach ($members as $member): ?>
        <div id="bio-<?= $member['id']; ?>" class="bio-modal" aria-hidden="true" role="dialog">
            <div class="modal-content">
                <span class="close-button" aria-label="Close biography modal">&times;</span>

                <div class="modal-profile-header">
                    <div class="modal-profile-image"
                         style="background-image:url('<?= htmlspecialchars($member['image_path']); ?>')"
                         role="img"
                         aria-label="Profile picture of <?= htmlspecialchars($member['name']); ?>">
                    </div>

                    <div class="modal-header-text">
                        <h2><?= htmlspecialchars($member['name']); ?></h2>
                        <p class="modal-role"><?= htmlspecialchars($member['role']); ?></p>
                        <?php if (!empty($member['full_title'])): ?>
                            <p class="modal-title"><?= nl2br(htmlspecialchars($member['full_title'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-body-content">
                    <?php if (!empty($member['motto'])): ?>
                        <p class="modal-motto">"<?= htmlspecialchars($member['motto']); ?>"</p>
                        <hr class="modal-divider">
                    <?php endif; ?>

                    <?php if (!empty($member['bio_content'])): ?>
                        <h3>Biography</h3>
                        <div class="modal-bio-text"><?= nl2br(htmlspecialchars($member['bio_content'])); ?></div>
                    <?php endif; ?>

                    <div class="modal-social-links">
                        <?php if (!empty($member['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($member['email']); ?>"><i class="fas fa-envelope"></i> Email</a>
                        <?php endif; ?>
                        <?php if (!empty($member['linkedin'])): ?>
                            <a href="<?= htmlspecialchars($member['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i> LinkedIn</a>
                        <?php endif; ?>
                        <?php if (!empty($member['github'])): ?>
                            <a href="<?= htmlspecialchars($member['github']); ?>" target="_blank"><i class="fab fa-github"></i> GitHub</a>
                        <?php endif; ?>
                        <?php if (!empty($member['twitter'])): ?>
                            <a href="<?= htmlspecialchars($member['twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i> Twitter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php include 'footer.php'; ?>

<!-- Fetch Dynamic Officials -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("officials-content-container");

    async function fetchOfficials() {
        try {
            const response = await fetch("officials_fetch.php");
            if (!response.ok) throw new Error("Network error");
            container.innerHTML = await response.text();
        } catch (error) {
            container.innerHTML = `<p class="error-message">Failed to load officials. Retrying...</p>`;
        }
    }

    fetchOfficials();
    setInterval(fetchOfficials, 3000);
});
</script>

<!-- Modal Handler -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function closeModal(modal) {
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    }

    document.addEventListener("click", event => {
        const target = event.target;

        if (target.classList.contains("details-button")) {
            event.preventDefault();
            openModal(target.dataset.modalTarget);
        }

        if (target.classList.contains("close-button")) {
            closeModal(target.closest(".bio-modal"));
        }

        if (target.classList.contains("bio-modal") && target.classList.contains("active")) {
            closeModal(target);
        }
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            const active = document.querySelector(".bio-modal.active");
            if (active) closeModal(active);
        }
    });
});
</script>

<script src="assets/js/script.js" defer></script>
</body>
</html>
