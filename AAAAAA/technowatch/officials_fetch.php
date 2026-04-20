<?php
// officials_fetch.php
header('Content-Type: text/html; charset=utf-8');

include 'admin/includes/db_connect.php'; 

$query = "SELECT * FROM officials ORDER BY 
            FIELD(category, 'HEAD', 'FACULTY', 'SECTION MAYORS'), 
            sort_order ASC, name ASC";

$result = $conn->query($query);

$officials_by_category = [
    'HEAD' => [],
    'FACULTY' => [],
    'SECTION MAYORS' => [],
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category = strtoupper($row['category']);
        if (isset($officials_by_category[$category])) {
            $officials_by_category[$category][] = $row;
        }
    }
}
$conn->close();

?>

<?php if (!empty($officials_by_category['HEAD'])): 
$head = $officials_by_category['HEAD'][0]; ?>
<div class="head-container">
    <div class="hero-card head-card head-gradient">
        <div class="text-info">
            <p class="role-tag head-tag-white"><?= htmlspecialchars($head['role']); ?></p>
            <h2 class="hero-name head-name-white"><?= htmlspecialchars($head['name']); ?></h2>
            <p class="full-title"><?= nl2br(htmlspecialchars($head['full_title'])); ?></p>
            <p class="motto-text">"<?= htmlspecialchars($head['motto']); ?>"</p>
            
            <div class="contact-links">
                <?php if (!empty($head['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($head['email']); ?>" class="contact-icon"><i class="fas fa-envelope"></i></a>
                <?php endif; ?>
                <?php if (!empty($head['linkedin'])): ?>
                    <a href="<?= htmlspecialchars($head['linkedin']); ?>" target="_blank" class="contact-icon"><i class="fab fa-linkedin"></i></a>
                <?php endif; ?>

                <!-- FIXED: working trigger -->
                <button class="details-button" data-modal-target="bio-<?= $head['id']; ?>">
                    View Bio <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div class="profile-image head-image" 
            style="background-image: url('<?= htmlspecialchars($head['image_path']); ?>');">
        </div>
    </div>
</div>
<?php endif; ?>


<h3 class="staff-grid-title">FACULTY MEMBERS</h3>
<div class="professors-grid">

<?php if (!empty($officials_by_category['FACULTY'])):
foreach ($officials_by_category['FACULTY'] as $official): ?>

<div class="hero-card prof-card light-dark-card">
    <div class="profile-image prof-image" 
        style="background-image: url('<?= htmlspecialchars($official['image_path']); ?>');">
    </div>
    <div class="text-info staff-text-bottom">
        <p class="role-tag staff-role-subtle"><?= htmlspecialchars($official['role']); ?></p>
        <h3 class="hero-name staff-name-white"><?= htmlspecialchars($official['name']); ?></h3>

        

        <!-- FIXED trigger button -->
        <button class="details-button" data-modal-target="bio-<?= $official['id']; ?>">View Bio</button>
    </div>
</div>

<?php endforeach; endif; ?>
</div>


<h3 class="staff-grid-title">MAYORS (CLASS REPRESENTATIVES)</h3>
<div class="mayors-grid">

<?php if (!empty($officials_by_category['SECTION MAYORS'])):
foreach ($officials_by_category['SECTION MAYORS'] as $official): ?>

<div class="hero-card mayor-card">
    <div class="profile-image mayor-image" 
        style="background-image: url('<?= htmlspecialchars($official['image_path']); ?>');">
    </div>
    <div class="text-info staff-text-bottom">
        <p class="role-tag staff-role-subtle"><?= htmlspecialchars($official['role']); ?></p>
        <h3 class="hero-name staff-name-white"><?= htmlspecialchars($official['name']); ?></h3>
    </div>

    <!-- FIXED trigger button -->
    <button class="details-button" data-modal-target="bio-<?= $official['id']; ?>">View Bio</button>
</div>

<?php endforeach; endif; ?>
</div>
