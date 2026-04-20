<?php
// officers_content.php - Revised for dynamic modal support

include 'admin/includes/db_connect.php'; 

$display_categories = [
    'EXECUTIVE OFFICERS' => 'CLUB EXECUTIVE OFFICERS',
    'CLUB REPRESENTATIVES' => 'CLUB REPRESENTATIVES',
    'CLUB CREATIVES' => 'CLUB CREATIVES',
];

// Fetch president
$president_query = "SELECT * FROM officers WHERE category = 'EXECUTIVE OFFICERS' AND role = 'CLUB PRESIDENT' ORDER BY sort_order ASC LIMIT 1";
$president_result = $conn->query($president_query);
$president = $president_result ? $president_result->fetch_assoc() : null;

// Fetch all other officers
$officers_data = [];
foreach (array_keys($display_categories) as $cat) {
    $exclude_president = ($cat === 'EXECUTIVE OFFICERS' && $president) ? " AND id != {$president['id']}" : "";
    $query = "SELECT * FROM officers WHERE category = ? {$exclude_president} ORDER BY sort_order ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $result = $stmt->get_result();
    $officers_data[$cat] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

// Array of all officers for modals
$all_officers_for_modals = $president ? [$president] : [];
foreach ($officers_data as $list) {
    $all_officers_for_modals = array_merge($all_officers_for_modals, $list);
}

// Helper function for social icon class
function get_icon_class($link) {
    if (strpos($link, 'linkedin') !== false) return 'fab fa-linkedin';
    if (strpos($link, 'twitter') !== false) return 'fab fa-twitter';
    return 'fas fa-link';
}
?>

<div id="officer-content-display">
    <h1 class="department-title">Technowatch Club Officers</h1>

    <?php if ($president): 
        $bio_id = 'officer-bio-' . $president['id'];
    ?>
    <div class="head-container">
        <div class="hero-card head-card club-president-gradient" data-bio-target="<?php echo $bio_id; ?>">
            <div class="text-info">
                <p class="role-tag head-tag-white"><?php echo htmlspecialchars(strtoupper($president['role'])); ?></p>
                <h2 class="hero-name head-name-white"><?php echo htmlspecialchars($president['name']); ?></h2>
                <p class="full-title"><?php echo htmlspecialchars($president['full_title']); ?></p>
                <?php if (!empty($president['motto'])): ?>
                    <p class="motto-text">"<?php echo htmlspecialchars($president['motto']); ?>"</p>
                <?php endif; ?>
                <div class="contact-links">
                    <?php if (!empty($president['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($president['email']); ?>" class="contact-icon" title="Email"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($president['linkedin'])): ?>
                        <a href="<?php echo htmlspecialchars($president['linkedin']); ?>" target="_blank" class="contact-icon" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($president['twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($president['twitter']); ?>" target="_blank" class="contact-icon" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <?php endif; ?>
                    <button class="details-button" aria-label="View biography for <?php echo htmlspecialchars($president['name']); ?>">View Bio <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            <div class="profile-image head-image" style="background-image: url('<?php echo htmlspecialchars($president['image_path']); ?>');"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($display_categories as $category_key => $section_title):
        $current_officers = $officers_data[$category_key];
        if ($category_key === 'EXECUTIVE OFFICERS' && empty($current_officers) && $president) continue;
        if (!empty($current_officers)):
    ?>
        <h3 class="staff-grid-title"><?php echo htmlspecialchars(strtoupper($section_title)); ?></h3>
        <div class="professors-grid">
            <?php foreach ($current_officers as $officer): 
                $bio_id = 'officer-bio-' . $officer['id'];
                $card_class = 'club-officer-card';
                if ($officer['category'] === 'CLUB REPRESENTATIVES') $card_class .= ' representative-card';
            ?>
                <div class="hero-card prof-card <?php echo htmlspecialchars($card_class); ?>" data-bio-target="<?php echo $bio_id; ?>">
                    <div class="profile-image prof-image" style="background-image: url('<?php echo htmlspecialchars($officer['image_path']); ?>');"></div>
                    <div class="text-info staff-text-bottom">
                        <p class="role-tag staff-role-subtle"><?php echo htmlspecialchars(strtoupper($officer['role'])); ?></p>
                        <h3 class="hero-name staff-name-white"><?php echo htmlspecialchars($officer['name']); ?></h3>
                        <p class="full-title"><?php echo htmlspecialchars($officer['full_title']); ?></p>
                        <div class="contact-links">
                            <?php if (!empty($officer['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($officer['email']); ?>" class="contact-icon" title="Email"><i class="fas fa-envelope"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($officer['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($officer['linkedin']); ?>" target="_blank" class="contact-icon" title="LinkedIn"><i class="<?php echo get_icon_class($officer['linkedin']); ?>"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($officer['twitter'])): ?>
                                <a href="<?php echo htmlspecialchars($officer['twitter']); ?>" target="_blank" class="contact-icon" title="Twitter"><i class="<?php echo get_icon_class($officer['twitter']); ?>"></i></a>
                            <?php endif; ?>
                            <button class="details-button" aria-label="View biography for <?php echo htmlspecialchars($officer['name']); ?>">View Bio <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; endforeach; ?>

    <div class="officials-link-container">
        <a href="officials.php" class="officials-link-button club-officers-style">
            See Our Club Officials & Advisers
        </a>
    </div>
</div>

<div id="officer-modals-container">
    <?php foreach ($all_officers_for_modals as $officer): 
        $bio_id = 'officer-bio-' . $officer['id'];
    ?>
        <div id="<?php echo $bio_id; ?>" class="bio-modal" aria-hidden="true" role="dialog">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <div class="modal-profile-header">
                    <div class="modal-profile-image" style="background-image: url('<?php echo htmlspecialchars($officer['image_path']); ?>');"></div>
                    <div class="modal-header-text">
                        <h2><?php echo htmlspecialchars($officer['name']); ?></h2>
                        <p class="modal-role"><?php echo htmlspecialchars($officer['role']); ?></p>
                        <p class="modal-title"><?php echo htmlspecialchars($officer['full_title']); ?></p>
                    </div>
                </div>
                <hr class="modal-divider">
                <div class="modal-body-content">
                    <?php echo nl2br(htmlspecialchars($officer['bio_content'])); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (isset($conn)) $conn->close(); ?>
