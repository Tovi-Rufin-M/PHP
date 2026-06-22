<?php
/**
 * Department Head Dashboard
 * Provides overview metrics, a program-filtered pending approvals queue, 
 * search capabilities, detailed student schedules/retakes modal, and approval/rejection workflows.
 */

require_once dirname(__DIR__) . '/php/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify Authentication and Role
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Dept Head') {
    header("Location: index.php?page=login");
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffName = $_SESSION['staff_name'];
$programCode = $_SESSION['program_code'];

$dbClass = new Database();
$conn = $dbClass->getConnection();

$message = null;
$messageType = 'info';

// Fetch Program Details
$programName = "Unknown Department";
if ($conn) {
    $stmtProg = $conn->prepare("SELECT program_name FROM programs WHERE program_code = ? LIMIT 1");
    $stmtProg->execute([$programCode]);
    $programName = $stmtProg->fetchColumn() ?: $programCode;
}

// Fetch Metrics for this Head's Program
$metrics = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
if ($conn) {
    // Pending
    $stmtPen = $conn->prepare("SELECT COUNT(*) FROM students WHERE program_code = ? AND approval_status = 'Pending'");
    $stmtPen->execute([$programCode]);
    $metrics['Pending'] = $stmtPen->fetchColumn();

    // Approved (Approved by Dept Head or Enrolled)
    $stmtApp = $conn->prepare("SELECT COUNT(*) FROM students WHERE program_code = ? AND approval_status IN ('Approved by Dept Head', 'Enrolled')");
    $stmtApp->execute([$programCode]);
    $metrics['Approved'] = $stmtApp->fetchColumn();

    // Rejected
    $stmtRej = $conn->prepare("SELECT COUNT(*) FROM students WHERE program_code = ? AND approval_status = 'Rejected'");
    $stmtRej->execute([$programCode]);
    $metrics['Rejected'] = $stmtRej->fetchColumn();
}

// Fetch Students in Department
$students = [];
if ($conn) {
    try {
        $stmtStudents = $conn->prepare("
            SELECT s.*, 
                   (SELECT timestamp FROM audit_logs WHERE student_id = s.student_id AND action = 'Enrollment Submission' ORDER BY timestamp DESC LIMIT 1) as submitted_at
            FROM students s
            WHERE s.program_code = :program_code
            ORDER BY FIELD(s.approval_status, 'Pending', 'Rejected', 'Approved by Dept Head', 'Enrolled'), s.name
        ");
        $stmtStudents->execute([':program_code' => $programCode]);
        $students = $stmtStudents->fetchAll();
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

// Fetch all Shifting Requests for this Department Head's Program
$shiftingRequests = [];
if ($conn) {
    try {
        $stmtShift = $conn->prepare("
            SELECT r.*, s.name as student_name, s.section as current_section,
                   (SELECT COUNT(*) FROM student_subject_history WHERE student_id = r.student_id AND status = 'Failed') as failed_count
            FROM shifting_requests r
            JOIN students s ON r.student_id = s.student_id
            WHERE r.target_program_code = :target_program
            ORDER BY FIELD(r.status, 'Pending Dept Head', 'Approved by Dept Head', 'Approved', 'Rejected'), r.created_at DESC
        ");
        $stmtShift->execute([':target_program' => $programCode]);
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
    <title>Department Head Dashboard - Enhanced Enrollment System</title>
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
                <div class="profile-avatar">DH</div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($staffName); ?></h1>
                    <p>Department Head — <strong><?php echo htmlspecialchars($programCode); ?> (<?php echo htmlspecialchars($programName); ?>)</strong></p>
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
                    <p>Pending Review</p>
                    <h2 id="metric-pending-val"><?php echo $metrics['Pending']; ?></h2>
                </div>
                <div class="metric-icon pending">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-details">
                    <p>Total Approved</p>
                    <h2 id="metric-approved-val"><?php echo $metrics['Approved']; ?></h2>
                </div>
                <div class="metric-icon approved">
                    <i class="fa-solid fa-square-check"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-details">
                    <p>Returned / Rejected</p>
                    <h2 id="metric-rejected-val"><?php echo $metrics['Rejected']; ?></h2>
                </div>
                <div class="metric-icon rejected">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
            </div>
        </div>

        <!-- Tabs Header -->
        <div class="tabs-header" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-card-border); padding-bottom: 0.5rem;">
            <button class="tab-btn active" onclick="switchDashboardTab('enrollment')" id="tab-enrollment-btn" style="background: none; border: none; font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: #ffffff; cursor: pointer; padding: 0.5rem 1rem; border-bottom: 3px solid var(--color-primary); transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-list-check" style="color: var(--color-primary);"></i> Enrollment Queue (<?php echo count($students); ?>)
            </button>
            <button class="tab-btn" onclick="switchDashboardTab('shifting')" id="tab-shifting-btn" style="background: none; border: none; font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: #94a3b8; cursor: pointer; padding: 0.5rem 1rem; border-bottom: 3px solid transparent; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-arrow-right-arrow-left"></i> Shifting Requests (<?php echo count($shiftingRequests); ?>)
            </button>
        </div>

        <div id="enrollment-queue-tab">
            <!-- Pending Queue Card -->
            <div class="queue-container glass-card">
                <div class="search-filter-row">
                    <div class="queue-title">
                        <h2>Student Registration Queue</h2>
                        <p>Manage and audit enrollment records inside your program department</p>
                    </div>

                    <div class="filter-controls">
                        <div class="search-input-wrapper">
                            <input type="text" id="queue-search" class="form-input" oninput="filterQueueTable()" placeholder="Search student ID or name...">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>

                        <div class="filter-wrapper">
                            <select id="queue-filter" onchange="filterQueueTable()" class="form-select no-icon filter-select">
                                <option value="All">All Statuses</option>
                                <option value="Pending" selected>Pending Review</option>
                                <option value="Approved by Dept Head">Approved by Head</option>
                                <option value="Enrolled">Officially Enrolled</option>
                                <option value="Rejected">Returned / Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Student Table -->
                <div class="table-container">
                    <table id="queue-table" class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Cohort Section</th>
                                <th>Current Term</th>
                                <th>Submitted Date</th>
                                <th>Approval Status</th>
                                <th style="text-align: right; padding-right: 1.5rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr class="empty-row">
                                    <td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No student records found in your program.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $stud): ?>
                                    <tr class="student-row-item" 
                                        data-id="<?php echo htmlspecialchars(strtolower($stud['student_id'])); ?>" 
                                        data-name="<?php echo htmlspecialchars(strtolower($stud['name'])); ?>" 
                                        data-status="<?php echo htmlspecialchars($stud['approval_status']); ?>">
                                        
                                        <td class="student-id"><?php echo htmlspecialchars($stud['student_id']); ?></td>
                                        <td class="student-name"><?php echo htmlspecialchars($stud['name']); ?></td>
                                        <td><?php echo htmlspecialchars($stud['section']); ?></td>
                                        <td><?php echo htmlspecialchars($stud['current_term']); ?></td>
                                        <td style="color: var(--color-text-muted);">
                                            <?php echo $stud['submitted_at'] ? date('M d, Y h:i A', strtotime($stud['submitted_at'])) : '—'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $badgeClass = 'badge-warning';
                                                $statusLabel = 'Pending Review';
                                                if ($stud['approval_status'] === 'Approved by Dept Head') {
                                                    $badgeClass = 'badge-info';
                                                    $statusLabel = 'Approved by Head';
                                                } elseif ($stud['approval_status'] === 'Enrolled') {
                                                    $badgeClass = 'badge-success';
                                                } elseif ($stud['approval_status'] === 'Rejected') {
                                                    $badgeClass = 'badge-danger';
                                                    $statusLabel = 'Returned';
                                                }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                        </td>
                                        <td style="text-align: right; padding-right: 1.5rem;">
                                            <div class="actions-cell" style="justify-content: flex-end;">
                                                <button onclick="openDetailsModal('<?php echo htmlspecialchars($stud['student_id']); ?>')" class="btn-action view-btn" title="View details & curriculum files"><i class="fa-solid fa-file-invoice"></i></button>
                                                <?php if ($stud['approval_status'] === 'Pending'): ?>
                                                    <button onclick="quickApprove('<?php echo htmlspecialchars($stud['student_id']); ?>')" class="btn-action approve-btn" title="Approve registration"><i class="fa-solid fa-check"></i></button>
                                                    <button onclick="openRejectDialog('<?php echo htmlspecialchars($stud['student_id']); ?>')" class="btn-action reject-btn" title="Return to student"><i class="fa-solid fa-xmark"></i></button>
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

        <div id="shifting-queue-tab" style="display: none;">
            <!-- Shifting Queue Card -->
            <div class="queue-container glass-card">
                <div class="search-filter-row">
                    <div class="queue-title">
                        <h2>Pending Shifting Requests</h2>
                        <p>Evaluate first-year students requesting to shift into your program department</p>
                    </div>

                    <div class="filter-controls">
                        <div class="search-input-wrapper">
                            <input type="text" id="shifting-search" class="form-input" oninput="filterShiftingTable()" placeholder="Search student ID or name...">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>

                        <div class="filter-wrapper">
                            <select id="shifting-filter" onchange="filterShiftingTable()" class="form-select no-icon filter-select">
                                <option value="All">All Statuses</option>
                                <option value="Pending Dept Head" selected>Pending Review</option>
                                <option value="Approved by Dept Head">Approved by Head</option>
                                <option value="Approved">Fully Shifted</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shifting Table -->
                <div class="table-container">
                    <table id="shifting-table" class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Current Section</th>
                                <th>Deficiencies</th>
                                <th>Submitted Date</th>
                                <th>Status</th>
                                <th style="text-align: right; padding-right: 1.5rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shiftingRequests)): ?>
                                <tr class="empty-shifting-row">
                                    <td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No shifting requests found targeting your department.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shiftingRequests as $req): ?>
                                    <tr class="shifting-row-item" 
                                        data-id="<?php echo htmlspecialchars(strtolower($req['student_id'])); ?>" 
                                        data-name="<?php echo htmlspecialchars(strtolower($req['student_name'])); ?>" 
                                        data-status="<?php echo htmlspecialchars($req['status']); ?>">
                                        
                                        <td class="student-id"><?php echo htmlspecialchars($req['student_id']); ?></td>
                                        <td class="student-name"><?php echo htmlspecialchars($req['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['current_section']); ?></td>
                                        <td>
                                            <?php if ($req['failed_count'] > 0): ?>
                                                <span class="badge badge-danger" style="font-weight: 700; font-size: 0.75rem;"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $req['failed_count']; ?> Failed</span>
                                            <?php else: ?>
                                                <span class="badge badge-success" style="font-size: 0.75rem;"><i class="fa-solid fa-check"></i> Clean</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: var(--color-text-muted);">
                                            <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $badgeC = 'badge-warning';
                                                $lbl = 'Pending Review';
                                                if ($req['status'] === 'Approved by Dept Head') {
                                                    $badgeC = 'badge-info';
                                                    $lbl = 'Dept Approved';
                                                } elseif ($req['status'] === 'Approved') {
                                                    $badgeC = 'badge-success';
                                                    $lbl = 'Fully Shifted';
                                                } elseif ($req['status'] === 'Rejected') {
                                                    $badgeC = 'badge-danger';
                                                    $lbl = 'Rejected';
                                                }
                                            ?>
                                            <span class="badge <?php echo $badgeC; ?>"><?php echo $lbl; ?></span>
                                        </td>
                                        <td style="text-align: right; padding-right: 1.5rem;">
                                            <div class="actions-cell" style="justify-content: flex-end;">
                                                <button onclick="openShiftingModal(<?php echo htmlspecialchars(json_encode($req)); ?>)" class="btn-action view-btn" style="background: rgba(14, 165, 233, 0.15); border-color: rgba(14, 165, 233, 0.2); color: var(--color-primary); padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem;" title="Screen student & assign section"><i class="fa-solid fa-user-gear"></i> Screen Student</button>
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

    <!-- Shifting Review Modal -->
    <div class="modal-overlay" id="shifting-modal">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-shield"></i> Shifting Screening & Section Assignment</h3>
                <button class="modal-close" onclick="closeShiftingModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 1.5rem; text-align: left;">
                
                <!-- Student Info Card -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--color-card-border); padding: 1rem 1.25rem; border-radius: 8px; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Applicant ID: <strong style="color: #ffffff;" id="shift-modal-student-id"></strong></div>
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Name: <strong style="color: #ffffff;" id="shift-modal-student-name"></strong></div>
                    <div style="font-size: 0.85rem; color: var(--color-text-muted);">Current Program & Section: <strong style="color: #ffffff;" id="shift-modal-current-section"></strong></div>
                </div>

                <!-- Academic Deficiencies -->
                <div>
                    <h4 style="font-family: var(--font-display); font-weight: 700; color: #ffffff; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: var(--color-danger);"></i> Deficiencies Check
                    </h4>
                    <div id="shift-modal-deficiencies-list" style="font-size: 0.85rem; color: var(--color-text-muted); background: rgba(255, 255, 255, 0.01); border: 1px solid var(--color-card-border); border-radius: 6px; padding: 0.75rem 1rem;">
                        Loading deficiencies...
                    </div>
                </div>

                <!-- Eligibility Aptitude Answers -->
                <div>
                    <h4 style="font-family: var(--font-display); font-weight: 700; color: #ffffff; font-size: 0.95rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
                        <i class="fa-solid fa-file-signature" style="color: var(--color-primary);"></i> Aptitude Test Answers
                    </h4>
                    <div id="shift-modal-answers-container" style="display: flex; flex-direction: column; gap: 1rem; max-height: 250px; overflow-y: auto; padding-right: 0.5rem;">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <!-- Action Form Controls -->
                <div id="shifting-action-controls" style="border-top: 1px solid var(--color-card-border); padding-top: 1rem; display: flex; flex-direction: column; gap: 1.25rem;">
                    
                    <!-- Section Assignment Dropdown -->
                    <div>
                        <label for="shift-assign-section" style="display: block; font-weight: 600; color: #ffffff; margin-bottom: 0.5rem; font-size: 0.9rem;">Assign Second Year Section</label>
                        <select id="shift-assign-section" class="form-select" style="background: rgba(15,23,42,0.8); cursor: pointer; width: 100%; height: 42px; padding: 0.5rem 1rem; border: 1px solid var(--color-card-border); border-radius: 8px; color: #ffffff;">
                            <option value="" disabled selected>-- Select Target Section --</option>
                            <option value="Section A">Section A</option>
                            <option value="Section B">Section B</option>
                            <option value="Section C">Section C</option>
                            <option value="Section D">Section D</option>
                            <option value="Section E">Section E</option>
                            <option value="Section F">Section F</option>
                            <option value="Section G">Section G</option>
                        </select>
                    </div>

                    <!-- Rejection Reason Input -->
                    <div id="shifting-rejection-reason-box" style="display: none;">
                        <label for="shift-rejection-reason" style="display: block; font-weight: 600; color: #ef4444; margin-bottom: 0.5rem; font-size: 0.9rem;">Reason for Rejection</label>
                        <textarea id="shift-rejection-reason" class="form-input" style="height: 70px; resize: none; background: rgba(15,23,42,0.8); width: 100%; border: 1px solid var(--color-card-border); border-radius: 8px; padding: 0.5rem; color: #ffffff;" placeholder="Provide a reason for turning down this shifting request..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeShiftingModal()" style="padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600;">Cancel</button>
                        <button type="button" class="btn btn-secondary" id="btn-shift-reject-toggle" onclick="toggleShiftingRejectionBox()" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600;">Reject Shift</button>
                        <button type="button" class="btn btn-primary" id="btn-shift-approve-submit" onclick="submitShiftingApproval()" style="padding: 0.6rem 2rem; border-radius: 8px; font-weight: 600;">Approve & Assign</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal-overlay" id="details-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-id-card"></i> Student Selections Audit</h3>
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
                    <h4><i class="fa-solid fa-list-check" style="color: var(--color-primary);"></i> Selections Summary</h4>
                    <div class="table-container">
                        <table class="data-table" style="width: 100%;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--color-card-border);">
                                    <th style="padding: 0.6rem 0.5rem;">Subject Code & Description</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: center;">Units</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: right;">Selection Type / Status</th>
                                </tr>
                            </thead>
                            <tbody id="det-selections-body">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- History Log Card -->
                <div class="detail-card" id="audit-log-card">
                    <h4><i class="fa-solid fa-clock-rotate-left" style="color: var(--color-warning);"></i> Student Process Log</h4>
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
                <p style="font-size: 0.88rem; color: var(--color-text-muted);">Please specify why you are returning this enrollment registration profile. Remarks will be shown to the student.</p>
                <textarea id="reject-reason" class="feedback-textarea" placeholder="Example: Schedule conflict in BET-00-V, please drop regular Math and enroll in Tutorial class."></textarea>
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
        function filterQueueTable() {
            const searchVal = document.getElementById('queue-search').value.toLowerCase().trim();
            const filterVal = document.getElementById('queue-filter').value;
            const rows = document.querySelectorAll('.student-row-item');

            let visibleCount = 0;
            rows.forEach(row => {
                const id = row.getAttribute('data-id');
                const name = row.getAttribute('data-name');
                const status = row.getAttribute('data-status');

                const matchesSearch = id.includes(searchVal) || name.includes(searchVal);
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
                    document.querySelector('#queue-table tbody').appendChild(newEmpty);
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
                    statusBadge.textContent = 'Approved by Head';
                }
                else if (s.approval_status === 'Enrolled') statusBadge.classList.add('badge-success');
                else if (s.approval_status === 'Rejected') statusBadge.classList.add('badge-danger');

                // Render Selections
                const tbody = document.getElementById('det-selections-body');
                tbody.innerHTML = '';

                if (result.selections.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--color-text-muted); padding: 1.5rem;">No selections made by student.</td></tr>`;
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
                        <td style="padding: 1rem 0.5rem; font-weight: 700; color: #ffffff;">Total Load Semestral Units</td>
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
                                <span style="font-size: 0.75rem; color: var(--color-text-muted);">${logTime}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 0.2rem;">${log.details}</div>
                        `;
                        logList.appendChild(div);
                    });
                }

                // Configure action buttons in footer
                const footer = document.getElementById('modal-footer-actions');
                footer.innerHTML = `<button onclick="closeDetailsModal()" class="btn btn-secondary btn-modal btn-modal-cancel">Close</button>`;

                if (s.approval_status === 'Pending') {
                    footer.innerHTML += `
                        <button onclick="modalReject()" class="btn btn-danger btn-modal btn-modal-reject"><i class="fa-solid fa-xmark"></i> Return to Student</button>
                        <button onclick="modalApprove()" class="btn btn-success btn-modal btn-modal-approve"><i class="fa-solid fa-check"></i> Approve Registration</button>
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
        async function quickApprove(studentId) {
            if (!confirm(`Are you sure you want to approve registration for student ${studentId}?`)) {
                return;
            }

            try {
                const response = await fetch('php/api/approve.php', {
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
                    showToast('Approved', result.message, 'success');
                    
                    // Reload counts & table in 1 sec
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Approval Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach the approval api.', 'error');
            }
        }

        function modalApprove() {
            closeDetailsModal();
            quickApprove(currentAuditingStudentId);
        }

        // Rejection remarks handling
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
                alert('Please provide a reason or remarks for returning the profile.');
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
                    showToast('Registration Returned', result.message, 'success');
                    closeRejectDialog();
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Rejection Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach the rejection api.', 'error');
            }
        }

        // Tab selection controller for Dashboard
        function switchDashboardTab(tab) {
            const enrollTab = document.getElementById('enrollment-queue-tab');
            const shiftTab = document.getElementById('shifting-queue-tab');
            const enrollBtn = document.getElementById('tab-enrollment-btn');
            const shiftBtn = document.getElementById('tab-shifting-btn');

            if (tab === 'enrollment') {
                enrollTab.style.display = 'block';
                shiftTab.style.display = 'none';
                enrollBtn.classList.add('active');
                shiftBtn.classList.remove('active');
                enrollBtn.style.borderBottomColor = 'var(--color-primary)';
                enrollBtn.style.color = '#ffffff';
                shiftBtn.style.borderBottomColor = 'transparent';
                shiftBtn.style.color = '#94a3b8';
            } else {
                enrollTab.style.display = 'none';
                shiftTab.style.display = 'block';
                enrollBtn.classList.remove('active');
                shiftBtn.classList.add('active');
                shiftBtn.style.borderBottomColor = 'var(--color-primary)';
                shiftBtn.style.color = '#ffffff';
                enrollBtn.style.borderBottomColor = 'transparent';
                enrollBtn.style.color = '#94a3b8';
                filterShiftingTable();
            }
        }

        // Search & Status filters for Shifting requests
        function filterShiftingTable() {
            const searchVal = document.getElementById('shifting-search').value.toLowerCase().trim();
            const filterVal = document.getElementById('shifting-filter').value;
            const rows = document.querySelectorAll('.shifting-row-item');

            let visibleCount = 0;
            rows.forEach(row => {
                const id = row.getAttribute('data-id');
                const name = row.getAttribute('data-name');
                const status = row.getAttribute('data-status');

                const matchesSearch = id.includes(searchVal) || name.includes(searchVal);
                const matchesFilter = (filterVal === 'All') || (status === filterVal);

                if (matchesSearch && matchesFilter) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle empty search results inside shifting requests table
            const tbody = document.querySelector('#shifting-table tbody');
            let emptyRow = tbody.querySelector('.empty-shifting-row');
            
            if (visibleCount === 0) {
                if (!emptyRow) {
                    const newEmpty = document.createElement('tr');
                    newEmpty.className = 'empty-shifting-row';
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
        let activeShiftingRequest = null;

        async function openShiftingModal(req) {
            activeShiftingRequest = req;
            document.getElementById('shift-modal-student-id').textContent = req.student_id;
            document.getElementById('shift-modal-student-name').textContent = req.student_name;
            document.getElementById('shift-modal-current-section').textContent = `${req.current_program_code} - ${req.current_section}`;
            
            // Clear inputs
            document.getElementById('shift-assign-section').value = '';
            document.getElementById('shift-rejection-reason').value = '';
            document.getElementById('shifting-rejection-reason-box').style.display = 'none';
            document.getElementById('btn-shift-reject-toggle').style.display = 'inline-block';
            document.getElementById('btn-shift-approve-submit').style.display = 'inline-block';

            // Show details & action box based on status
            const actionControls = document.getElementById('shifting-action-controls');
            if (req.status !== 'Pending Dept Head') {
                actionControls.style.display = 'none';
            } else {
                actionControls.style.display = 'flex';
            }

            // Fetch deficiencies list from API
            const defContainer = document.getElementById('shift-modal-deficiencies-list');
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
            const answersContainer = document.getElementById('shift-modal-answers-container');
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
            document.getElementById('shifting-modal').classList.add('active');
        }

        function closeShiftingModal() {
            document.getElementById('shifting-modal').classList.remove('active');
            activeShiftingRequest = null;
        }

        function toggleShiftingRejectionBox() {
            const box = document.getElementById('shifting-rejection-reason-box');
            const approveBtn = document.getElementById('btn-shift-approve-submit');
            const rejectBtn = document.getElementById('btn-shift-reject-toggle');

            if (box.style.display === 'none') {
                box.style.display = 'block';
                approveBtn.style.display = 'none';
                rejectBtn.textContent = 'Confirm Rejection';
                rejectBtn.style.background = 'var(--color-danger)';
                rejectBtn.style.borderColor = 'var(--color-danger)';
                rejectBtn.style.color = '#ffffff';
                rejectBtn.onclick = submitShiftingRejection;
            } else {
                box.style.display = 'none';
                approveBtn.style.display = 'inline-block';
                rejectBtn.textContent = 'Reject Shift';
                rejectBtn.style.background = 'rgba(239, 68, 68, 0.15)';
                rejectBtn.style.borderColor = 'rgba(239, 68, 68, 0.2)';
                rejectBtn.style.color = '#fca5a5';
                rejectBtn.onclick = toggleShiftingRejectionBox;
            }
        }

        async function submitShiftingApproval() {
            const section = document.getElementById('shift-assign-section').value;
            if (!section) {
                alert('Please assign a second year section for the student.');
                return;
            }

            if (!confirm(`Are you sure you want to approve the shifting request for student ${activeShiftingRequest.student_id} and assign them to ${section}?`)) {
                return;
            }

            try {
                const response = await fetch('php/api/approve_shifting_dept.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        request_id: activeShiftingRequest.id,
                        target_section: section
                    })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Approval Success', result.message, 'success');
                    closeShiftingModal();
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    showToast('Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to submit shifting approval.', 'error');
            }
        }

        async function submitShiftingRejection() {
            const reason = document.getElementById('shift-rejection-reason').value.trim();
            if (!reason) {
                alert('Please specify the reason for rejecting the shifting request.');
                return;
            }

            if (!confirm(`Are you sure you want to reject the shifting request for student ${activeShiftingRequest.student_id}?`)) {
                return;
            }

            try {
                const response = await fetch('php/api/reject_shifting_dept.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        request_id: activeShiftingRequest.id,
                        rejection_reason: reason
                    })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Rejection Success', result.message, 'success');
                    closeShiftingModal();
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    showToast('Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to submit shifting rejection.', 'error');
            }
        }

        // Initial setup on load
        window.addEventListener('DOMContentLoaded', () => {
            filterQueueTable();
            filterShiftingTable();
        });
    </script>
</body>
</html>
