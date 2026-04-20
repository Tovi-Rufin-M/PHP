<?php
// Technowatch Club | All Officials and Officers (Final Restructured & Enhanced)
header('Content-Type: text/html; charset=utf-8');

// Include Database Connection
include 'admin/includes/db_connect.php';

// FIX: Check for database connection error
if (!isset($conn) || $conn->connect_error) {
    $error_message = isset($conn) ? $conn->connect_error : "Database connection object not initialized.";
    die("<h1 style='color:red;'>Database Connection Failed!</h1>" . $error_message);
}

// Configuration Arrays
$sections_official_map = [
    'DEPT_HEAD' => 'Department Head',
    'FACULTY' => 'Faculty Members & Advisers',
    'MAYOR' => 'Mayor',
];

$official_section_slugs = ['DEPT_HEAD', 'FACULTY', 'MAYOR'];

$categories_club_map = [
    'EXECUTIVE' => 'Executive Committee',
    'REPRESENTATIVES' => 'Club Representatives',
    'CREATIVES' => 'Club Creatives',
];

// Fetch Officials/Staff Data
$officials_data_grouped = [];
$sql_officials = "SELECT full_name, role, section, quote, image_path, email
                   FROM officials_staff
                   WHERE is_active = 1
                   ORDER BY FIELD(section, 'DEPT_HEAD', 'FACULTY', 'MAYOR'), sort_order ASC";
$result_officials = $conn->query($sql_officials);

if ($result_officials) {
    while ($row = $result_officials->fetch_assoc()) {
        $group_slug = $row['section'];
        if (!isset($officials_data_grouped[$group_slug])) {
            $officials_data_grouped[$group_slug] = [];
        }
        $officials_data_grouped[$group_slug][] = $row;
    }
}

// Fetch Club Officers Data
$officers_data_grouped = [];
$sql_officers = "SELECT full_name, position, category, short_bio, email, image_path
                  FROM officers_club
                  WHERE is_active = 1
                  ORDER BY FIELD(category, 'EXECUTIVE', 'REPRESENTATIVES', 'CREATIVES'), sort_order ASC";
$result_officers = $conn->query($sql_officers);

if ($result_officers) {
    while ($row = $result_officers->fetch_assoc()) {
        $group_slug = $row['category'];
        if (!isset($officers_data_grouped[$group_slug])) {
            $officers_data_grouped[$group_slug] = [];
        }
        $row['role'] = $row['position'];
        $row['quote'] = $row['short_bio'];
        $officers_data_grouped[$group_slug][] = $row;
    }
}

$conn->close();

// Banner image paths
$officials_banner_image = 'assets/imgs/officials.png';
$officers_banner_image = 'assets/imgs/officers.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officials & Officers | Technowatch Club</title>
    <link rel="icon" type="image/png" href="assets/imgs/logo_white.png">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/organization.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // These match the fixed about.php and align with your CSS variables
                        'tech-dark-bg': '#0d121c', 
                        'tech-card-bg': '#1a2230',
                        'primary-cyan': '#00c6ff', 
                        'primary-hover': '#00a0e6',
                        'text-subtle': '#9aa5b5',
                    },
                    boxShadow: {
                        'subtle-dark': '0 5px 15px rgba(0, 0, 0, 0.6)',
                        'cyan-glow': '0 0 10px rgba(0, 198, 255, 0.5)',
                    },
                    // We remove fontFamily to rely on the Inter font loaded via the link tag
                },
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="assets/js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>

</head>
<body class="bg-tech-dark-bg text-[var(--color-text)]"> <?php include 'header.php'; ?>

<div class="banner-section" style="background-image: url('<?php echo htmlspecialchars($officials_banner_image); ?>');">
    <div class="banner-content">
        <div class="banner-title">Technowatch Club Officials</div>
        <div class="banner-subtitle">The Guiding Leadership</div>
    </div>
</div>

<div class="container">

    <?php foreach ($official_section_slugs as $group_slug):
        if (!isset($officials_data_grouped[$group_slug])) continue;
        $group_name = $sections_official_map[$group_slug];
        $members = $officials_data_grouped[$group_slug];
    ?>

        <?php if ($group_slug === 'DEPT_HEAD' && !empty($members)):
            $head = $members[0];
        ?>
            <h3 class="section-title"><?php echo htmlspecialchars($group_name); ?></h3>

            <div class="flex-center-x">
                <div class="hero-card head-card">
                    <div class="text-info left">
                        <p class="role-title left"><?php echo strtoupper(htmlspecialchars($head['role'])); ?></p>
                        <h2 class="person-name"><?php echo htmlspecialchars($head['full_name']); ?></h2>
                        <p class="quote">"<?php echo htmlspecialchars($head['quote']); ?>"</p>
                        <?php if (!empty($head['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($head['email']); ?>" class="email-link">
                                <i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($head['email']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="profile-image-base profile-image-head"
                        style="background-image: url('<?php echo htmlspecialchars($head['image_path']); ?>');">
                    </div>
                </div>
            </div>

        <?php elseif ($group_slug === 'FACULTY' && !empty($members)): ?>
            <h3 class="subsection-title"><?php echo htmlspecialchars($group_name); ?></h3>
            <div class="grid-container large-cols">
                <?php foreach ($members as $member): ?>
                    <div class="officer-card">
                        <div class="profile-image-base profile-image-prof"
                            style="background-image: url('<?php echo htmlspecialchars($member['image_path']); ?>');"></div>
                        <div class="text-info">
                            <p class="role"><?php echo strtoupper(htmlspecialchars($member['role'])); ?></p>
                            <h3 class="name"><?php echo htmlspecialchars($member['full_name']); ?></h3>
                            <?php if (!empty($member['quote'])): ?>
                                <p class="small-quote">"<?php echo htmlspecialchars($member['quote']); ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($group_slug === 'MAYOR' && !empty($members)): ?>
            <h3 class="subsection-title">Mayors (Class Representatives)</h3>
            <div class="grid-container grid-max-w-2xl">
                <?php foreach ($members as $member): ?>
                    <div class="officer-card">
                        <div class="profile-image-base profile-image-prof"
                            style="background-image: url('<?php echo htmlspecialchars($member['image_path']); ?>');"></div>
                        <div class="text-info">
                            <p class="role"><?php echo strtoupper(htmlspecialchars($member['role'])); ?></p>
                            <h3 class="name"><?php echo htmlspecialchars($member['full_name']); ?></h3>
                            <?php if (!empty($member['quote'])): ?>
                                <p class="small-quote">"<?php echo htmlspecialchars($member['quote']); ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="glowing-separator"></div>

</div>

<div class="banner-section" style="background-image: url('<?php echo htmlspecialchars($officers_banner_image); ?>');">
    <div class="banner-content">
        <div class="banner-title">Technowatch Club Officers</div>
        <div class="banner-subtitle">The Club's Core Team</div>
    </div>
</div>

<div class="container">

    <?php foreach (array_keys($categories_club_map) as $group_slug):
        if (!isset($officers_data_grouped[$group_slug])) continue;
        $group_name = $categories_club_map[$group_slug];
        $members = $officers_data_grouped[$group_slug];

        $president = null;
        if ($group_slug === 'EXECUTIVE') {
            foreach ($members as $key => $member) {
                if (strtolower($member['role']) === 'club president') {
                    $president = $member;
                    unset($members[$key]);
                    $members = array_values($members);
                    break;
                }
            }
        }
    ?>

        <?php if ($group_slug === 'EXECUTIVE' && $president): ?>
            <h3 class="section-title">Club President</h3>
            <div class="flex-center-x">
                <div class="hero-card president-card president">
                    <div class="text-info right">
                        <p class="role-title right"><?php echo strtoupper(htmlspecialchars($president['role'])); ?></p>
                        <h2 class="person-name"><?php echo htmlspecialchars($president['full_name']); ?></h2>
                        <p class="quote">"<?php echo htmlspecialchars($president['quote']); ?>"</p>
                        <?php if (!empty($president['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($president['email']); ?>" class="email-link">
                                <i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($president['email']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="profile-image-base profile-image-head"
                        style="background-image: url('<?php echo htmlspecialchars($president['image_path']); ?>');">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($members)): ?>
            <h3 class="subsection-title"><?php echo htmlspecialchars($group_name); ?></h3>
            <div class="grid-container <?php echo (in_array($group_slug, ['EXECUTIVE', 'REPRESENTATIVES'])) ? 'large-cols' : 'grid-max-w-4xl'; ?>">
                <?php foreach ($members as $member): ?>
                    <div class="officer-card">
                        <div class="profile-image-base profile-image-prof"
                            style="background-image: url('<?php echo htmlspecialchars($member['image_path']); ?>');"></div>
                        <div class="text-info">
                            <p class="role"><?php echo strtoupper(htmlspecialchars($member['role'])); ?></p>
                            <h3 class="name"><?php echo htmlspecialchars($member['full_name']); ?></h3>
                            <?php if (!empty($member['quote'])): ?>
                                <p class="small-quote">"<?php echo htmlspecialchars($member['quote']); ?>"</p>
                            <?php endif; ?>
                            <?php if (!empty($member['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="email-link-sm">
                                    <i class="fas fa-envelope mr-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endforeach; ?>

</div>

<?php include 'footer.php'; ?>
</body>
</html>