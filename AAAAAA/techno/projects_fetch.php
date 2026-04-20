<?php
// projects_fetch.php

header('Content-Type: text/html; charset=utf-8');

// NOTE: Adjust the path if necessary for your database connection
include 'admin/includes/db_connect.php'; 

// --- Configuration based on officials_fetch ---
// This is the absolute path from the root that your public page needs.
$base_image_path = '../assets/imgs/uploads/'; 

// Helper function to sanitize the category string for the data-category attribute
function sanitizeCategoriesForFilter($categories) {
    if (empty($categories)) return '';
    // Removes non-alphanumeric, replaces hyphens with spaces (for easy filtering)
    return strtolower(trim(preg_replace('/[^a-z0-9\s-]/i', '', str_replace('-', ' ', $categories))));
}

// Helper function to sanitize the display tag
function formatTag($tag) {
    // Converts TAG-NAME to Tag Name
    return ucwords(strtolower(str_replace('-', ' ', $tag)));
}

// --- Fetch Projects Data ---
$query = "SELECT 
            project_id, 
            title, 
            short_description, 
            full_description,
            categories, 
            tag, 
            image_path, 
            features, 
            case_study_link 
          FROM projects 
          WHERE tag != 'ARCHIVED' 
          ORDER BY sort_order ASC, project_id DESC";
$result = $conn->query($query);

$output_html = '';

if ($result && $result->num_rows > 0) {
    while ($project = $result->fetch_assoc()) {
        $project_link = !empty($project['case_study_link']) ? htmlspecialchars($project['case_study_link']) : '#';
        
        // Combines categories (e.g., 'AI-ROBOTICS') and tag (e.g., 'FEATURED') into a single lowercase filter string
        $filter_categories = sanitizeCategoriesForFilter($project['categories'] . ' ' . $project['tag']);
        
        $display_tag = formatTag($project['tag']);
        
        // --- IMAGE PATH FIX ---
        if (empty($project['image_path'])) {
            // Use a generic placeholder path if no image is defined
            $image_src = 'assets/imgs/placeholder.png'; 
        } else {
            // Assuming image_path stores the filename (e.g., 'robot_arm.jpg' OR a relative path like 'assets/imgs/uploads/robot_arm.jpg').
            // The logic here is conservative: it assumes the DB column might contain a full path,
            // but strips it to ensure only the filename is appended to the $base_image_path.
            // NOTE: Since manage_projects.php saves the full relative path, this basename() logic might need 
            // adjustment depending on how clean your image_path column is. For typical setups, we assume 
            // the DB value is the full relative path from the site root (e.g., 'assets/imgs/uploads/file.jpg').
            // Let's modify it to respect the path stored in the DB if it looks like a path.
            
            // Reverting to the simpler logic based on the admin script output structure:
            // Since the image_path in the DB (from manage_projects.php) is like 'assets/imgs/uploads/filename.jpg',
            // and $base_image_path is '../assets/imgs/uploads/', we need to handle this discrepancy.
            
            // The simplest fix for deployment consistency is to use the full path stored in the DB, 
            // which starts relative to the site root (e.g., 'assets/imgs/uploads/file.jpg').
            // Since this script is called from the site root, we only need to use the stored path.
            
            $image_src = '../' . htmlspecialchars($project['image_path']);
            // NOTE: If this script is *not* in a subdirectory like 'admin/', remove the '../' prefix.
            // Given the original script structure:
            // include 'admin/includes/db_connect.php'; 
            // $base_image_path = '../assets/imgs/uploads/'; 
            // We assume this script is called from the site root. Let's use the DB path directly 
            // if the admin script saves the relative path from the root.
            
            // Assuming the image_path column contains the path relative to the site root, like 'assets/imgs/uploads/robot_arm.jpg'
            // and this fetch script is being run from the site root, we can simplify:
             $image_src = htmlspecialchars($project['image_path']); 
            
            // To be safe based on the original structure ($base_image_path = '../assets/imgs/uploads/'), 
            // which suggests $base_image_path is relative to the calling script, 
            // let's stick to the original conservative method but ensuring the path works:
            $clean_path = basename($project['image_path']);
            $image_src = $base_image_path . htmlspecialchars($clean_path);
            
            // NOTE: If you save the full path in the DB, you MUST adjust this path logic.
            // If DB stores: 'assets/imgs/uploads/file.jpg'
            // And this file runs from site root: $image_src should be 'assets/imgs/uploads/file.jpg'
            // And $base_image_path should be empty or just '/'.
            
            // Let's assume the DB path is the full relative path from the site root (e.g., 'assets/imgs/uploads/filename.jpg').
            // This is the cleanest way.
            $image_src = htmlspecialchars($project['image_path']);
        }
        // ----------------------
        
        // Build the HTML for a single project card
        $output_html .= '
            <a href="' . $project_link . '" class="project-card" 
                data-id="' . htmlspecialchars($project['project_id']) . '"
                data-category="' . $filter_categories . '"
                data-title="' . htmlspecialchars($project['title']) . '"
                data-tag="' . $display_tag . '"
                data-image="' . $image_src . '"
                data-description="' . htmlspecialchars($project['short_description']) . '"
                data-full-description="' . htmlspecialchars($project['full_description']) . '"
                data-features="' . htmlspecialchars($project['features']) . '"
                data-case-study-link="' . $project_link . '"
                target="_blank">
                
                <div class="image-container">
                    <img src="' . $image_src . '" alt="' . htmlspecialchars($project['title']) . '" class="project-image">
                </div>
                <div class="project-info">
                    <span class="project-tag">' . $display_tag . '</span>
                    <h3>' . htmlspecialchars($project['title']) . '</h3>
                    <p>' . htmlspecialchars($project['short_description']) . '</p>
                    <span class="view-details">View Details <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>';
    }
} else {
    $output_html = '<p class="no-projects-message">No projects are currently available for display.</p>';
}

$conn->close();

// Output the generated HTML directly
echo $output_html;
?>