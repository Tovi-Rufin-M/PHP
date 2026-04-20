<?php
// technowatch/jobs_data.php - ONLY contains the dynamic HTML content for job listings

// Includes DB Connection
include 'admin/includes/db_connect.php'; 

// --- PAGINATION SETUP ---
$jobs_per_page = 6;
// Get current page from AJAX request (default to 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $jobs_per_page;

// --- FILTER SETUP ---
$search_term = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Start building the WHERE clause and parameters
$where_clauses = ["is_published = 1"];
$params = [];
$types = "";

// Add search filter (Search title, company, or description)
if (!empty($search_term)) {
    $where_clauses[] = "(title LIKE ? OR company_name LIKE ? OR description LIKE ?)";
    $search_param = '%' . $search_term . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

// Add category filter
if (!empty($category)) {
    $where_clauses[] = "job_type = ?";
    $params[] = $category;
    $types .= "s";
}

// Construct the final WHERE clause
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- 1. COUNT TOTAL JOBS (needed for pagination calculation) ---
$count_query = "SELECT COUNT(*) AS total_jobs FROM job_postings" . $where_sql;
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_jobs = $count_result->fetch_assoc()['total_jobs'];
$total_pages = ceil($total_jobs / $jobs_per_page);
$count_stmt->close();

// --- 2. FETCH JOBS FOR THE CURRENT PAGE ---
$jobs_query = "SELECT * FROM job_postings" . $where_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";

// Prepare parameters for LIMIT and OFFSET
$limit_params = [$jobs_per_page, $offset];
$limit_types = "ii"; // 'i' for integer

// Combine existing filter parameters with LIMIT/OFFSET parameters
$final_params = array_merge($params, $limit_params);
$final_types = $types . $limit_types;

$stmt = $conn->prepare($jobs_query);
if ($stmt) {
    // Dynamically bind all parameters
    if (!empty($final_params)) {
        $stmt->bind_param($final_types, ...$final_params);
    }
    $stmt->execute();
    $jobs_result = $stmt->get_result();
} else {
    // Handle prepare error
    $jobs_result = false;
}
?>

<!-- === START: Dynamic Job Cards HTML === -->
<div class="jobs-container">
    <?php 
    if ($jobs_result && $jobs_result->num_rows > 0) {
        while ($job = $jobs_result->fetch_assoc()) {
            $tags_placeholder = "{$job['job_type']}, {$job['location']}"; 
            $summary = substr(strip_tags($job['description']), 0, 100) . '...';
            // Link to the job details page
            $detail_url = 'job_details.php?id=' . $job['job_id'];
            $salary_display = htmlspecialchars($job['salary_range'] ?? 'Negotiable');
            ?>

            <div class="job-card">
                <div class="job-header">
                    <img src="http://static.photos/office/200x200/placeholder" alt="Company Logo" class="company-logo">
                    <div>
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                        <div class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></div>
                    </div>
                </div>
                
                <div class="job-details">
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                    <span><i class="fas fa-money-bill-wave"></i> <?php echo $salary_display; ?></span> 
                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                </div>
                
                <p><?php echo htmlspecialchars($summary); ?></p>
                
                <div class="job-footer">
                    <div class="job-tags">
                        <?php 
                        $tags = explode(', ', $tags_placeholder);
                        foreach ($tags as $tag) {
                            echo "<span>" . htmlspecialchars($tag) . "</span>";
                        }
                        ?>
                    </div>
                    <!-- Link to the dedicated job details page -->
                    <a href="<?php echo $detail_url; ?>" class="apply-btn">View Details</a>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div class="alert alert-info text-center mt-5" role="alert">No active job postings match your criteria.</div>';
    }
    if (isset($jobs_result)) $jobs_result->free();
    ?>
</div>

<!-- === PAGINATION LINKS === -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php 
        // Previous Link
        if ($page > 1) {
            $prev_page = $page - 1;
            echo '<a href="#" data-page="' . $prev_page . '">&laquo; </a>';
        }

        // Numbered Links
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $page) ? 'active' : '';
            echo '<a href="#" data-page="' . $i . '" class="' . $active_class . '">' . $i . '</a>';
        }

        // Next Link
        if ($page < $total_pages) {
            $next_page = $page + 1;
            echo '<a href="#" data-page="' . $next_page . '"> &raquo;</a>';
        }
        ?>
    </div>
<?php endif; ?>
<!-- === END: Dynamic Job Cards HTML === -->

<?php 
if (isset($stmt)) $stmt->close();
$conn->close();
?>