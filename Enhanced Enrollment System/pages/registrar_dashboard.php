<?php
/**
 * Registrar Dashboard
 * Provides overview metrics, a queue of department-approved students for final enrollment,
 * a master student registry table with search capabilities, a details audit modal,
 * and a live vertical chronological audit log / activity feed.
 */

require_once dirname(__DIR__) . '/php/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify Authentication and Role
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Registrar') {
    header("Location: index.php?page=login");
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffName = $_SESSION['staff_name'];

$dbClass = new Database();
$conn = $dbClass->getConnection();

// Fetch System Metrics
$metrics = ['Pending' => 0, 'Approved' => 0, 'Enrolled' => 0];
if ($conn) {
    // Pending (Dept Head Queue)
    $stmtPen = $conn->query("SELECT COUNT(*) FROM students WHERE approval_status = 'Pending'");
    $metrics['Pending'] = $stmtPen->fetchColumn();

    // Approved by Dept Head (Registrar Queue)
    $stmtApp = $conn->query("SELECT COUNT(*) FROM students WHERE approval_status = 'Approved by Dept Head'");
    $metrics['Approved'] = $stmtApp->fetchColumn();

    // Enrolled (Enrollment Complete)
    $stmtEnr = $conn->query("SELECT COUNT(*) FROM students WHERE approval_status = 'Enrolled'");
    $metrics['Enrolled'] = $stmtEnr->fetchColumn();
}

// Fetch All Students (Master List)
$students = [];
if ($conn) {
    $stmtStudents = $conn->query("
        SELECT s.*, p.program_name,
               (SELECT timestamp FROM audit_logs WHERE student_id = s.student_id AND action = 'Enrollment Submission' ORDER BY timestamp DESC LIMIT 1) as submitted_at
        FROM students s
        LEFT JOIN programs p ON s.program_code = p.program_code
        ORDER BY FIELD(s.approval_status, 'Approved by Dept Head', 'Pending', 'Rejected', 'Enrolled'), s.name
    ");
    $students = $stmtStudents->fetchAll();
}

// Fetch Recent Audit Logs (Activity Feed)
$logs = [];
if ($conn) {
    $stmtLogs = $conn->query("
        SELECT al.*, s.name as student_name, st.name as staff_name, st.role as staff_role
        FROM audit_logs al
        LEFT JOIN students s ON al.student_id = s.student_id
        LEFT JOIN staff st ON al.staff_id = st.staff_id
        ORDER BY al.timestamp DESC
        LIMIT 25
    ");
    $logs = $stmtLogs->fetchAll();
}

// Fetch all Shifting Requests for the Registrar
$shiftingRequests = [];
if ($conn) {
    try {
        $stmtShift = $conn->query("
            SELECT r.*, s.name as student_name,
                   p1.program_name as current_program_name,
                   p2.program_name as target_program_name
            FROM shifting_requests r
            JOIN students s ON r.student_id = s.student_id
            JOIN programs p1 ON r.current_program_code = p1.program_code
            JOIN programs p2 ON r.target_program_code = p2.program_code
            ORDER BY FIELD(r.status, 'Approved by Dept Head', 'Pending Dept Head', 'Approved', 'Rejected'), r.created_at DESC
        ");
        $shiftingRequests = $stmtShift->fetchAll();
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - Enhanced Enrollment System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
        <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">

</head>
<body>

    <div class="dashboard-container">
        
        <!-- Header -->
        <header class="dashboard-header">
            <div class="dashboard-profile">
                <div class="profile-avatar">RG</div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($staffName); ?></h1>
                    <p>University Registrar Office — <strong>Melchora Aquino Registry Board</strong></p>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php?logout=1" class="btn btn-danger logout-btn">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </header>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-details">
                    <p>Awaiting Head Approval</p>
                    <h2><?php echo $metrics['Pending']; ?></h2>
                </div>
                <div class="metric-icon pending">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-details">
                    <p>Verified (Registrar Queue)</p>
                    <h2><?php echo $metrics['Approved']; ?></h2>
                </div>
                <div class="metric-icon approved">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-details">
                    <p>Officially Enrolled</p>
                    <h2><?php echo $metrics['Enrolled']; ?></h2>
                </div>
                <div class="metric-icon enrolled">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>
        </div>

        <!-- Dashboard Layout Grid -->
        <div class="dashboard-grid">
                       <!-- Left Workspace Wrapper -->
            <div class="left-workspace-wrapper" style="flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.5rem;">
                
                <!-- Tabs Header -->
                <div class="tabs-header" style="display: flex; gap: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--color-card-border); padding-bottom: 0.5rem;">
                    <button class="tab-btn active" onclick="switchRegistrarTab('registry')" id="tab-registry-btn" style="background: none; border: none; font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: #ffffff; cursor: pointer; padding: 0.5rem 1rem; border-bottom: 3px solid var(--color-primary); transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-users" style="color: var(--color-primary);"></i> Student Registry
                    </button>
                    <button class="tab-btn" onclick="switchRegistrarTab('shifting')" id="tab-shifting-btn" style="background: none; border: none; font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: #94a3b8; cursor: pointer; padding: 0.5rem 1rem; border-bottom: 3px solid transparent; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i> Shifting Approvals (<?php echo count($shiftingRequests); ?>)
                    </button>
                </div>

                <!-- Tab 1: Registry -->
                <div id="registry-tab-content">
                    <!-- Master Workspace Box -->
                    <div class="workspace-box glass-card">
                        <div class="search-filter-row">
                            <div class="workspace-title">
                                <h2>Master Student Registry</h2>
                                <p>Enroll verified files and manage enrollment history across all programs</p>
                            </div>
         
                            <div class="filter-controls">
                                <div class="search-input-wrapper">
                                    <input type="text" id="registry-search" class="form-input" oninput="filterRegistryTable()" placeholder="Search ID, name, or program...">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
         
                                <div class="filter-wrapper">
                                    <select id="registry-filter" onchange="filterRegistryTable()" class="form-select no-icon filter-select">
                                        <option value="All">All Files</option>
                                        <option value="Approved by Dept Head" selected>Verified by Head</option>
                                        <option value="Pending">Pending Head Approval</option>
                                        <option value="Enrolled">Officially Enrolled</option>
                                        <option value="Rejected">Rejected / Returned</option>
                                    </select>
                                </div>
                            </div>
                        </div>
         
                        <!-- Table -->
                        <div class="table-container">
                            <table id="registry-table" class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Program Code</th>
                                        <th>Cohort Section</th>
                                        <th>Term</th>
                                        <th>Submitted Date</th>
                                        <th>Status</th>
                                        <th style="text-align: right; padding-right: 1.5rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr class="empty-row">
                                            <td colspan="8" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No student records found in the registry.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $stud): ?>
                                            <tr class="student-row-item" 
                                                data-id="<?php echo htmlspecialchars(strtolower($stud['student_id'])); ?>" 
                                                data-name="<?php echo htmlspecialchars(strtolower($stud['name'])); ?>" 
                                                data-course="<?php echo htmlspecialchars(strtolower($stud['program_code'])); ?>"
                                                data-status="<?php echo htmlspecialchars($stud['approval_status']); ?>">
                                                
                                                <td class="student-id"><?php echo htmlspecialchars($stud['student_id']); ?></td>
                                                <td class="student-name"><?php echo htmlspecialchars($stud['name']); ?></td>
                                                <td><?php echo htmlspecialchars($stud['program_code']); ?></td>
                                                <td><?php echo htmlspecialchars($stud['section']); ?></td>
                                                <td><?php echo htmlspecialchars($stud['current_term']); ?></td>
                                                <td style="color: var(--color-text-muted);">
                                                    <?php echo $stud['submitted_at'] ? date('M d, Y h:i A', strtotime($stud['submitted_at'])) : '—'; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $badgeClass = 'badge-warning';
                                                        $statusLabel = 'Pending Head';
                                                        if ($stud['approval_status'] === 'Approved by Dept Head') {
                                                            $badgeClass = 'badge-info';
                                                            $statusLabel = 'Verified by Head';
                                                        } elseif ($stud['approval_status'] === 'Enrolled') {
                                                            $badgeClass = 'badge-success';
                                                            $statusLabel = 'Enrolled';
                                                        } elseif ($stud['approval_status'] === 'Rejected') {
                                                            $badgeClass = 'badge-danger';
                                                            $statusLabel = 'Returned';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                                </td>
                                                <td style="text-align: right; padding-right: 1.5rem;">
                                                    <div class="actions-cell" style="justify-content: flex-end;">
                                                        <button onclick="openDetailsModal('<?php echo htmlspecialchars($stud['student_id']); ?>')" class="btn-action view-btn" title="View details & selections"><i class="fa-solid fa-file-invoice"></i></button>
                                                        <?php if ($stud['approval_status'] === 'Approved by Dept Head'): ?>
                                                            <button onclick="quickEnroll('<?php echo htmlspecialchars($stud['student_id']); ?>')" class="btn-action approve-btn" title="Enroll officially"><i class="fa-solid fa-circle-check"></i></button>
                                                            <button onclick="openRejectDialog('<?php echo htmlspecialchars($stud['student_id']); ?>')" class="btn-action reject-btn" title="Return to student"><i class="fa-solid fa-circle-xmark"></i></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Shifting Queue -->
                <div id="shifting-tab-content" style="display: none;">
                    <div class="workspace-box glass-card">
                        <div class="search-filter-row">
                            <div class="workspace-title">
                                <h2>Shifting Approvals Queue</h2>
                                <p>Process and finalize student shifting requests approved by department heads</p>
                            </div>

                            <div class="filter-controls">
                                <div class="search-input-wrapper">
                                    <input type="text" id="registrar-shifting-search" class="form-input" oninput="filterRegistrarShiftingTable()" placeholder="Search ID, name, or program...">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>

                                <div class="filter-wrapper">
                                    <select id="registrar-shifting-filter" onchange="filterRegistrarShiftingTable()" class="form-select no-icon filter-select">
                                        <option value="All">All Statuses</option>
                                        <option value="Approved by Dept Head" selected>Pending Finalization</option>
                                        <option value="Approved">Fully Shifted</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="registrar-shifting-table" class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Target Major</th>
                                        <th>Assigned Section</th>
                                        <th>Submitted Date</th>
                                        <th>Status</th>
                                        <th style="text-align: right; padding-right: 1.5rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($shiftingRequests)): ?>
                                        <tr class="empty-reg-shifting-row">
                                            <td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No shifting records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($shiftingRequests as $r): ?>
                                            <tr class="reg-shifting-row-item" 
                                                data-id="<?php echo htmlspecialchars(strtolower($r['student_id'])); ?>" 
                                                data-name="<?php echo htmlspecialchars(strtolower($r['student_name'])); ?>" 
                                                data-status="<?php echo htmlspecialchars($r['status']); ?>"
                                                data-target="<?php echo htmlspecialchars(strtolower($r['target_program_code'])); ?>">
                                                
                                                <td class="student-id"><?php echo htmlspecialchars($r['student_id']); ?></td>
                                                <td class="student-name"><?php echo htmlspecialchars($r['student_name']); ?></td>
                                                <td style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($r['target_program_code']); ?></td>
                                                <td style="font-weight: 600; color: var(--color-primary);"><?php echo htmlspecialchars($r['target_section'] ?: '—'); ?></td>
                                                <td style="color: var(--color-text-muted);">
                                                    <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $badge = 'badge-warning';
                                                        $statusLabel = 'Pending Review';
                                                        if ($r['status'] === 'Approved by Dept Head') {
                                                            $badge = 'badge-info';
                                                            $statusLabel = 'Awaiting Finalization';
                                                        } elseif ($r['status'] === 'Approved') {
                                                            $badge = 'badge-success';
                                                            $statusLabel = 'Fully Shifted';
                                                        } elseif ($r['status'] === 'Rejected') {
                                                            $badge = 'badge-danger';
                                                            $statusLabel = 'Rejected';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $badge; ?>"><?php echo $statusLabel; ?></span>
                                                </td>
                                                <td style="text-align: right; padding-right: 1.5rem;">
                                                    <div class="actions-cell" style="justify-content: flex-end;">
                                                        <button onclick="openRegistrarShiftingModal(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="btn-action view-btn" title="View details and test answers"><i class="fa-solid fa-file-invoice"></i></button>
                                                        <?php if ($r['status'] === 'Approved by Dept Head'): ?>
                                                            <button onclick="confirmFinalShift(<?php echo htmlspecialchars($r['id']); ?>, '<?php echo htmlspecialchars($r['student_name']); ?>', '<?php echo htmlspecialchars($r['target_program_code']); ?>', '<?php echo htmlspecialchars($r['target_section']); ?>')" class="btn-action approve-btn" title="Finalize Shift"><i class="fa-solid fa-check-double"></i></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Activity Feed Panel -->
            <div class="sidebar-panel">
                <h3><i class="fa-solid fa-chart-line"></i> Activity Feed Log</h3>
                
                <div class="activity-timeline">
                    <?php if (empty($logs)): ?>
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-align: center; padding: 1rem 0;">No system logs available.</div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                                // Determine specific log action styling
                                $logClass = 'log-Default';
                                if ($log['action'] === 'Enrolled') $logClass = 'log-Enrolled';
                                elseif ($log['action'] === 'Rejection') $logClass = 'log-Rejection';
                                elseif (strpos($log['action'], 'Submission') !== false) $logClass = 'log-Enrollment';
                            ?>
                            <div class="activity-item <?php echo $logClass; ?>">
                                <div class="activity-meta">
                                    <span style="font-weight: 700; color: var(--accent-primary);"><?php echo htmlspecialchars($log['student_id']); ?></span>
                                    <span class="activity-time"><?php echo date('M d, h:i A', strtotime($log['timestamp'])); ?></span>
                                </div>
                                <div class="activity-text"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="activity-details"><?php echo htmlspecialchars($log['details']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

    <!-- Registrar Shifting Review Modal -->
    <div class="modal-overlay" id="reg-shifting-modal">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-shield"></i> Shifting Request Audit</h3>
                <button class="modal-close" onclick="closeRegistrarShiftingModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 1.5rem; text-align: left;">
                
                <!-- Student Info Card -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--color-card-border); padding: 1rem 1.25rem; border-radius: 8px; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Student ID: <strong style="color: #ffffff;" id="reg-shift-modal-student-id"></strong></div>
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Name: <strong style="color: #ffffff;" id="reg-shift-modal-student-name"></strong></div>
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Shifting Path: <strong style="color: #ffffff;" id="reg-shift-modal-path"></strong></div>
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Assigned Section: <strong style="color: var(--color-primary);" id="reg-shift-modal-section"></strong></div>
                </div>

                <!-- Academic Deficiencies -->
                <div>
                    <h4 style="font-family: var(--font-display); font-weight: 700; color: #ffffff; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: var(--color-danger);"></i> Deficiencies Check
                    </h4>
                    <div id="reg-shift-modal-deficiencies-list" style="font-size: 0.85rem; color: var(--color-text-muted); background: rgba(255, 255, 255, 0.01); border: 1px solid var(--color-card-border); border-radius: 6px; padding: 0.75rem 1rem;">
                        Loading...
                    </div>
                </div>

                <!-- Eligibility Aptitude Answers -->
                <div>
                    <h4 style="font-family: var(--font-display); font-weight: 700; color: #ffffff; font-size: 0.95rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
                        <i class="fa-solid fa-file-signature" style="color: var(--color-primary);"></i> Aptitude Test Answers
                    </h4>
                    <div id="reg-shift-modal-answers-container" style="display: flex; flex-direction: column; gap: 1rem; max-height: 250px; overflow-y: auto; padding-right: 0.5rem;">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <!-- Action Form Controls -->
                <div id="reg-shifting-action-controls" style="border-top: 1px solid var(--color-card-border); padding-top: 1rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeRegistrarShiftingModal()" style="padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600;">Close</button>
                    <button type="button" class="btn btn-primary" id="btn-reg-shift-approve-submit" onclick="submitRegistrarFinalShift()" style="padding: 0.6rem 2rem; border-radius: 8px; font-weight: 600;">Finalize Program Shifting</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal-overlay" id="details-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-id-card"></i> Student Enrollment Selections Review</h3>
                <button class="modal-close" onclick="closeDetailsModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <!-- Academic Profile Card -->
                <div class="detail-card">
                    <h4><i class="fa-solid fa-graduation-cap"></i> Student Profile</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Name</label>
                            <span id="det-name">Jose Rizal</span>
                        </div>
                        <div class="detail-item">
                            <label>Student ID</label>
                            <span id="det-id">TUPV-00-0001</span>
                        </div>
                        <div class="detail-item">
                            <label>Department Major</label>
                            <span id="det-program">BET-00-V</span>
                        </div>
                        <div class="detail-item" style="margin-top: 1rem;">
                            <label>Cohort Section</label>
                            <span id="det-section">Section A</span>
                        </div>
                        <div class="detail-item" style="margin-top: 1rem;">
                            <label>Completed Term</label>
                            <span id="det-term">First Term</span>
                        </div>
                        <div class="detail-item" style="margin-top: 1rem;">
                            <label>Approval Status</label>
                            <span id="det-status" class="badge badge-warning">Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Selections List -->
                <div class="detail-card">
                    <h4><i class="fa-solid fa-list-check" style="color: var(--color-primary);"></i> Selected Subjects</h4>
                    <div class="table-container">
                        <table class="data-table" style="width: 100%;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--color-card-border);">
                                    <th style="padding: 0.6rem 0.5rem;">Subject Code & Description</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: center;">Units</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: right;">Selection Type</th>
                                </tr>
                            </thead>
                            <tbody id="det-selections-body">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Process logs -->
                <div class="detail-card" id="audit-log-card">
                    <h4><i class="fa-solid fa-clock-rotate-left" style="color: var(--color-warning);"></i> Process Log</h4>
                    <div style="font-size: 0.85rem; color: var(--color-text-muted); display: flex; flex-direction: column; gap: 0.6rem;" id="det-logs">
                        <!-- Dynamic list -->
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="modal-footer-actions">
                <!-- Dynamic approve/reject buttons -->
            </div>
        </div>
    </div>

    <!-- Rejection Remarks Modal -->
    <div class="modal-overlay" id="reject-modal">
        <div class="modal-box" style="max-width: 450px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-circle-exclamation" style="color: var(--color-danger);"></i> Rejection Remarks</h3>
                <button class="modal-close" onclick="closeRejectDialog()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <p style="font-size: 0.88rem; color: var(--color-text-muted);">Please specify why you are returning this registration. Remarks will be displayed to the student.</p>
                <textarea id="reject-reason" class="feedback-textarea" placeholder="Remarks details..."></textarea>
            </div>
            <div class="modal-footer">
                <button onclick="closeRejectDialog()" class="btn btn-secondary btn-modal btn-modal-cancel">Cancel</button>
                <button onclick="submitRejection()" class="btn btn-danger btn-modal btn-modal-reject">Return Form</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i id="toast-icon" class="fa-solid"></i>
        <div>
            <strong id="toast-title" style="display: block; font-size: 0.9rem; color: #ffffff;">Notification</strong>
            <span id="toast-msg" style="font-size: 0.8rem; color: var(--text-muted);">Details here</span>
        </div>
    </div>

    <script>
        let currentAuditingStudentId = '';

        function showToast(title, message, type = 'info') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const titleEl = document.getElementById('toast-title');
            const msgEl = document.getElementById('toast-msg');

            titleEl.textContent = title;
            msgEl.textContent = message;
            
            toast.className = 'toast';
            icon.className = 'fa-solid';

            if (type === 'success') {
                toast.classList.add('toast-success');
                icon.classList.add('fa-circle-check');
                icon.style.color = 'var(--color-success)';
            } else if (type === 'error') {
                toast.classList.add('toast-error');
                icon.classList.add('fa-circle-xmark');
                icon.style.color = 'var(--color-danger)';
            } else {
                icon.classList.add('fa-circle-info');
                icon.style.color = 'var(--color-info)';
            }

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Table filters
        function filterRegistryTable() {
            const searchVal = document.getElementById('registry-search').value.toLowerCase().trim();
            const filterVal = document.getElementById('registry-filter').value;
            const rows = document.querySelectorAll('.student-row-item');

            let visibleCount = 0;
            rows.forEach(row => {
                const id = row.getAttribute('data-id');
                const name = row.getAttribute('data-name');
                const program = row.getAttribute('data-program');
                const status = row.getAttribute('data-status');

                const matchesSearch = id.includes(searchVal) || name.includes(searchVal) || program.includes(searchVal);
                const matchesFilter = filterVal === 'All' || status === filterVal;

                if (matchesSearch && matchesFilter) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle empty rows
            const emptyRow = document.querySelector('.empty-row');
            if (visibleCount === 0) {
                if (!emptyRow) {
                    const newEmpty = document.createElement('tr');
                    newEmpty.className = 'empty-row';
                    newEmpty.innerHTML = `<td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No matching student records found.</td>`;
                    document.querySelector('#registry-table tbody').appendChild(newEmpty);
                } else {
                    emptyRow.style.display = 'table-row';
                }
            } else if (emptyRow) {
                emptyRow.style.display = 'none';
            }
        }

        // Fetch student details and open modal
        async function openDetailsModal(studentId) {
            currentAuditingStudentId = studentId;
            try {
                const response = await fetch(`php/api/get_student_details.php?student_id=${encodeURIComponent(studentId)}`, {
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();

                if (!result.success) {
                    showToast('Failed to load profile', result.message, 'error');
                    return;
                }

                const s = result.student;
                document.getElementById('det-name').textContent = s.name;
                document.getElementById('det-id').textContent = s.student_id;
                document.getElementById('det-program').textContent = `${s.program_code} — ${s.program_name}`;
                document.getElementById('det-section').textContent = s.section;
                document.getElementById('det-term').textContent = s.current_term;
                
                const statusBadge = document.getElementById('det-status');
                statusBadge.textContent = s.approval_status;
                statusBadge.className = 'badge';
                if (s.approval_status === 'Pending') statusBadge.classList.add('badge-warning');
                else if (s.approval_status === 'Approved by Dept Head') {
                    statusBadge.classList.add('badge-info');
                    statusBadge.textContent = 'Verified by Head';
                }
                else if (s.approval_status === 'Enrolled') statusBadge.classList.add('badge-success');
                else if (s.approval_status === 'Rejected') statusBadge.classList.add('badge-danger');

                // Render Selections
                const tbody = document.getElementById('det-selections-body');
                tbody.innerHTML = '';

                if (result.selections.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No subject selections recorded.</td></tr>`;
                } else {
                    let totalUnits = 0;
                    result.selections.forEach(sel => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid rgba(255,255,255,0.02)';
                        tr.style.height = '3.5rem';
                        
                        let badgeHtml = '';
                        if (sel.status === 'Regular') {
                            totalUnits += parseInt(sel.units, 10);
                            badgeHtml = `<span class="type-badge regular">Regular Class</span>`;
                        } else if (sel.status === 'Dropped') {
                            badgeHtml = `<span class="type-badge dropped">Dropped</span>`;
                        } else {
                            totalUnits += parseInt(sel.units, 10);
                            badgeHtml = `
                                <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                    <span class="type-badge retake">Retake</span>
                                    <span style="font-size: 0.7rem; color: var(--color-text-muted); margin-top: 0.2rem;">${sel.retake_method}</span>
                                </div>
                            `;
                        }

                        tr.innerHTML = `
                            <td style="padding: 0.5rem;">
                                <div style="font-weight: 600; color: #ffffff;">${sel.subject_code}</div>
                                <div style="font-size: 0.8rem; color: var(--color-text-muted);">${sel.description}</div>
                            </td>
                            <td style="padding: 0.5rem; text-align: center; font-weight: 600; color: var(--color-primary);">${sel.units}</td>
                            <td style="padding: 0.5rem; text-align: right;">${badgeHtml}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Add total row
                    const totalTr = document.createElement('tr');
                    totalTr.innerHTML = `
                        <td style="padding: 1rem 0.5rem; font-weight: 700; color: #ffffff;">Total Semestral Units</td>
                        <td style="padding: 1rem 0.5rem; text-align: center; font-weight: 800; color: var(--color-secondary); font-size: 1.1rem;">${totalUnits}</td>
                        <td></td>
                    `;
                    tbody.appendChild(totalTr);
                }

                // Render Logs
                const logCard = document.getElementById('audit-log-card');
                const logList = document.getElementById('det-logs');
                logList.innerHTML = '';
                
                if (result.logs.length === 0) {
                    logCard.style.display = 'none';
                } else {
                    logCard.style.display = 'block';
                    result.logs.forEach(log => {
                        const div = document.createElement('div');
                        div.style.padding = '0.5rem';
                        div.style.background = 'rgba(0,0,0,0.15)';
                        div.style.borderRadius = '8px';
                        
                        const logTime = new Date(log.timestamp).toLocaleString();
                        const staffInfo = log.staff_name ? `by ${log.staff_name} (${log.staff_role})` : '';
                        div.innerHTML = `
                            <div style="display: flex; justify-content: space-between; font-weight: 600; color: #ffffff;">
                                <span>${log.action} ${staffInfo}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">${logTime}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">${log.details}</div>
                        `;
                        logList.appendChild(div);
                    });
                }

                // Configure action buttons in footer
                const footer = document.getElementById('modal-footer-actions');
                footer.innerHTML = `<button onclick="closeDetailsModal()" class="btn btn-secondary btn-modal btn-modal-cancel">Close</button>`;

                if (s.approval_status === 'Approved by Dept Head') {
                    footer.innerHTML += `
                        <button onclick="modalReject()" class="btn btn-danger btn-modal btn-modal-reject"><i class="fa-solid fa-xmark"></i> Return Registration</button>
                        <button onclick="modalEnroll()" class="btn btn-success btn-modal btn-modal-approve"><i class="fa-solid fa-user-plus"></i> Finalize Enrollment</button>
                    `;
                }

                document.getElementById('details-modal').classList.add('active');

            } catch (err) {
                console.error(err);
                showToast('API Error', 'Failed to communicate with details endpoint.', 'error');
            }
        }

        function closeDetailsModal() {
            document.getElementById('details-modal').classList.remove('active');
        }

        // Action flows
        async function quickEnroll(studentId) {
            if (!confirm(`Are you sure you want to finalize enrollment for student ${studentId}?`)) {
                return;
            }

            try {
                const response = await fetch('php/api/enroll.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ student_id: studentId }),
                    credentials: 'include',
                    cache: 'no-store'
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Student Enrolled', result.message, 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Enrollment Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach the enrollment api.', 'error');
            }
        }

        function modalEnroll() {
            closeDetailsModal();
            quickEnroll(currentAuditingStudentId);
        }

        // Rejection Remarks Dialog
        let rejectionStudentTarget = '';

        function openRejectDialog(studentId) {
            rejectionStudentTarget = studentId;
            document.getElementById('reject-reason').value = '';
            document.getElementById('reject-modal').classList.add('active');
        }

        function closeRejectDialog() {
            document.getElementById('reject-modal').classList.remove('active');
        }

        function modalReject() {
            closeDetailsModal();
            openRejectDialog(currentAuditingStudentId);
        }

        async function submitRejection() {
            const reason = document.getElementById('reject-reason').value.trim();
            if (!reason) {
                alert('Please provide remarks explaining why the profile is returned.');
                return;
            }

            try {
                const response = await fetch('php/api/reject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: rejectionStudentTarget,
                        reason: reason
                    }),
                    credentials: 'include',
                    cache: 'no-store'
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Returned', result.message, 'success');
                    closeRejectDialog();
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Rejection Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach rejection api.', 'error');
            }
        }


        // Tab selection controller for Registrar
        function switchRegistrarTab(tab) {
            const registryTab = document.getElementById('registry-tab-content');
            const shiftingTab = document.getElementById('shifting-tab-content');
            const registryBtn = document.getElementById('tab-registry-btn');
            const shiftingBtn = document.getElementById('tab-shifting-btn');

            if (tab === 'registry') {
                registryTab.style.display = 'block';
                shiftingTab.style.display = 'none';
                registryBtn.classList.add('active');
                shiftingBtn.classList.remove('active');
                registryBtn.style.borderBottomColor = 'var(--color-primary)';
                registryBtn.style.color = '#ffffff';
                shiftingBtn.style.borderBottomColor = 'transparent';
                shiftingBtn.style.color = '#94a3b8';
            } else {
                registryTab.style.display = 'none';
                shiftingTab.style.display = 'block';
                registryBtn.classList.remove('active');
                shiftingBtn.classList.add('active');
                shiftingBtn.style.borderBottomColor = 'var(--color-primary)';
                shiftingBtn.style.color = '#ffffff';
                registryBtn.style.borderBottomColor = 'transparent';
                registryBtn.style.color = '#94a3b8';
                filterRegistrarShiftingTable();
            }
        }

        // Search & Status filters for Shifting approvals
        function filterRegistrarShiftingTable() {
            const searchVal = document.getElementById('registrar-shifting-search').value.toLowerCase().trim();
            const filterVal = document.getElementById('registrar-shifting-filter').value;
            const rows = document.querySelectorAll('.reg-shifting-row-item');

            let visibleCount = 0;
            rows.forEach(row => {
                const id = row.getAttribute('data-id');
                const name = row.getAttribute('data-name');
                const status = row.getAttribute('data-status');
                const target = row.getAttribute('data-target');

                const matchesSearch = id.includes(searchVal) || name.includes(searchVal) || target.includes(searchVal);
                const matchesFilter = (filterVal === 'All') || (status === filterVal);

                if (matchesSearch && matchesFilter) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle empty search results inside shifting table
            const tbody = document.querySelector('#registrar-shifting-table tbody');
            let emptyRow = tbody.querySelector('.empty-reg-shifting-row');
            
            if (visibleCount === 0) {
                if (!emptyRow) {
                    const newEmpty = document.createElement('tr');
                    newEmpty.className = 'empty-reg-shifting-row';
                    newEmpty.innerHTML = `<td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No matching shifting records found.</td>`;
                    tbody.appendChild(newEmpty);
                } else {
                    emptyRow.style.display = 'table-row';
                }
            } else {
                if (emptyRow) {
                    emptyRow.style.display = 'none';
                }
            }
        }

        // Shifting Review Modal Logic
        let activeRegistrarShiftingRequest = null;

        async function openRegistrarShiftingModal(req) {
            activeRegistrarShiftingRequest = req;
            document.getElementById('reg-shift-modal-student-id').textContent = req.student_id;
            document.getElementById('reg-shift-modal-student-name').textContent = req.student_name;
            document.getElementById('reg-shift-modal-path').textContent = `${req.current_program_code} → ${req.target_program_code}`;
            document.getElementById('reg-shift-modal-section').textContent = req.target_section || 'Not Assigned';
            
            // Show details & action box based on status
            const actionControls = document.getElementById('reg-shifting-action-controls');
            const approveBtn = document.getElementById('btn-reg-shift-approve-submit');
            
            if (req.status !== 'Approved by Dept Head') {
                approveBtn.style.display = 'none';
            } else {
                approveBtn.style.display = 'inline-block';
            }

            // Fetch deficiencies list from API (reusing the secure endpoint)
            const defContainer = document.getElementById('reg-shift-modal-deficiencies-list');
            defContainer.innerHTML = 'Loading deficiencies...';
            try {
                const response = await fetch(`php/api/get_shifting_deficiencies.php?student_id=${req.student_id}`);
                const result = await response.json();
                if (result.success && result.deficiencies.length > 0) {
                    let list = '<ul style="margin: 0; padding-left: 1.2rem; color: #fca5a5;">';
                    result.deficiencies.forEach(d => {
                        list += `<li><strong>${d.subject_code}</strong>: ${d.description} (Grade: ${d.grade})</li>`;
                    });
                    list += '</ul>';
                    defContainer.innerHTML = list;
                } else {
                    defContainer.innerHTML = '<span style="color: var(--accent-secondary); font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Clean academic record - No deficiencies.</span>';
                }
            } catch (err) {
                defContainer.innerHTML = '<span style="color: #ef4444;">Error loading deficiencies history.</span>';
            }

            // Populate aptitude test answers
            const answersContainer = document.getElementById('reg-shift-modal-answers-container');
            answersContainer.innerHTML = '';
            
            try {
                const answers = JSON.parse(req.eligibility_answers);
                answers.forEach((ans, index) => {
                    const item = document.createElement('div');
                    item.style.background = 'rgba(255,255,255,0.01)';
                    item.style.border = '1px solid var(--color-card-border)';
                    item.style.borderRadius = '8px';
                    item.style.padding = '0.75rem 1rem';

                    const qLabel = document.createElement('div');
                    qLabel.style.fontWeight = '600';
                    qLabel.style.color = '#ffffff';
                    qLabel.style.fontSize = '0.85rem';
                    qLabel.style.marginBottom = '0.3rem';
                    qLabel.innerHTML = `Q${index + 1}. ${ans.question}`;

                    const aVal = document.createElement('div');
                    aVal.style.fontSize = '0.9rem';
                    if (ans.type === 'scale') {
                        aVal.style.color = 'var(--color-primary)';
                        aVal.style.fontWeight = '700';
                        aVal.innerHTML = `Rating: ${ans.answer} / 5 <span style="font-weight: 400; color: var(--color-text-muted); font-size: 0.8rem;">(${'★'.repeat(ans.answer)}${'☆'.repeat(5 - ans.answer)})</span>`;
                    } else {
                        aVal.style.color = 'var(--color-text-muted)';
                        aVal.style.fontStyle = 'italic';
                        aVal.textContent = ans.answer || 'No response provided.';
                    }

                    item.appendChild(qLabel);
                    item.appendChild(aVal);
                    answersContainer.appendChild(item);
                });
            } catch (err) {
                answersContainer.innerHTML = '<div style="color: #ef4444; font-size: 0.85rem;">Error parsing aptitude test responses.</div>';
            }

            // Open modal
            document.getElementById('reg-shifting-modal').classList.add('active');
        }

        function closeRegistrarShiftingModal() {
            document.getElementById('reg-shifting-modal').classList.remove('active');
            activeRegistrarShiftingRequest = null;
        }

        async function submitRegistrarFinalShift() {
            if (!activeRegistrarShiftingRequest) return;
            closeRegistrarShiftingModal();
            
            await confirmFinalShift(
                activeRegistrarShiftingRequest.id,
                activeRegistrarShiftingRequest.student_name,
                activeRegistrarShiftingRequest.target_program_code,
                activeRegistrarShiftingRequest.target_section
            );
        }

        async function confirmFinalShift(reqId, studentName, targetProg, targetSect) {
            if (!confirm(`Are you sure you want to officially shift ${studentName} to ${targetProg} - ${targetSect}? This will reset their academic term to second-year first-term.`)) {
                return;
            }

            try {
                const response = await fetch('php/api/approve_shifting_registrar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: reqId })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('Shifting Finalized', result.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    showToast('Finalization Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to submit shifting finalization API.', 'error');
            }
        }

        // Initial setup on load
        window.addEventListener('DOMContentLoaded', () => {
            filterRegistryTable();
        });
    </script>
</body>
</html>
