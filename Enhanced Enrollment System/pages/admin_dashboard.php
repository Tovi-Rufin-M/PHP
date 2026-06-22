<?php
/**
 * System Administrator Dashboard
 * Provides full CRUD operations for Students and Staff accounts,
 * complete with search, filter, inline validation, and modal interfaces.
 */

require_once dirname(__DIR__) . '/php/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify Authentication and Role
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'Admin') {
    header("Location: index.php?page=login");
    exit;
}

$staffId = $_SESSION['staff_id'];
$staffName = $_SESSION['staff_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - Enhanced Enrollment System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    

        <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">

    <style>
        /* Checklist style for grades in student modal */
        .grade-history-divider {
            margin: 2rem 0 1.25rem 0;
            border-top: 1px solid var(--color-card-border);
            padding-top: 1.5rem;
        }

        .grade-history-divider h3 {
            font-family: var(--font-display);
            font-size: var(--font-size-h3);
            font-weight: 700;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ffffff;
        }

        .grade-history-divider h3 i {
            color: var(--color-secondary);
        }

        .grade-history-divider p {
            font-size: var(--font-size-small);
            color: var(--color-text-muted);
            margin-bottom: 1rem;
        }

        .subject-checklist-container {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid var(--color-card-border);
            border-radius: 12px;
            background: rgba(17, 24, 39, 0.3);
            padding: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .subject-checklist-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background var(--transition-fast);
        }

        .subject-checklist-row:last-child {
            border-bottom: none;
        }

        .subject-checklist-row:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .subject-info {
            display: flex;
            flex-direction: column;
            max-width: 60%;
        }

        .subject-code {
            font-weight: 700;
            font-size: var(--font-size-small);
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .subject-units {
            font-size: var(--font-size-small);
            background: rgba(14, 165, 233, 0.15);
            color: var(--color-primary);
            padding: 0.05rem 0.3rem;
            border-radius: 4px;
            border: 1px solid rgba(14, 165, 233, 0.2);
            display: inline-block;
        }

        .subject-desc {
            font-size: var(--font-size-small);
            color: var(--color-text-muted);
            margin-top: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .subject-actions {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        /* Grade Toggles */
        .grade-pill {
            cursor: pointer;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: var(--font-size-small);
            font-weight: 600;
            border: 1px solid var(--color-card-border);
            color: var(--color-text-muted);
            background: rgba(255, 255, 255, 0.01);
            transition: all var(--transition-fast);
        }

        .grade-pill:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .grade-pill.selected-not_taken {
            border-color: var(--color-text-muted);
            background: rgba(203, 213, 225, 0.1);
            color: #ffffff;
        }

        .grade-pill.selected-passed {
            border-color: var(--color-secondary);
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }

        .grade-pill.selected-failed {
            border-color: var(--color-danger);
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        .actions-cell {
            display: flex;
            gap: 0.4rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid var(--color-card-border);
            padding-top: 1.25rem;
        }

        /* Modal Checklist Responsive Layout */
        @media (max-width: 576px) {
            .subject-checklist-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
                padding: 1rem 0.8rem;
            }

            .subject-info {
                max-width: 100%;
            }

            .subject-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .grade-pill {
                flex-grow: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <!-- Header -->
        <header class="dashboard-header">
            <div class="dashboard-profile">
                <div class="profile-avatar">AD</div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($staffName); ?></h1>
                    <p>System Administrator Portal — <strong>ADMIN BOARD</strong></p>
                </div>
            </div>
            <div class="header-actions">
                <button type="button" onclick="runDatabaseSeeder()" class="btn btn-success" id="btn-seed-db">
                    <i class="fa-solid fa-database"></i>
                    <span>Update Database</span>
                </button>
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
                    <p>Total Registered Students</p>
                    <h2 id="metric-students-val">0</h2>
                </div>
                <div class="metric-icon students">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-details">
                    <p>Active Staff Members</p>
                    <h2 id="metric-staff-val">0</h2>
                </div>
                <div class="metric-icon staff">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-details">
                    <p>Academic Program Majors</p>
                    <h2 id="metric-programs-val">0</h2>
                </div>
                <div class="metric-icon programs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>
        </div>

        <!-- Master CRUD Workspace Box -->
        <div class="workspace-box glass-card">
            
            <!-- Segments Tabs -->
            <div class="tabs-header">
                <button type="button" class="tab-btn active" id="tab-btn-students" onclick="switchTab('students')">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>Manage Students</span>
                </button>
                <button type="button" class="tab-btn" id="tab-btn-staff" onclick="switchTab('staff')">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Manage Staff</span>
                </button>
            </div>

            <!-- TAB: STUDENTS -->
            <div class="tab-content active" id="tab-content-students">
                <div class="search-filter-row">
                    <div class="search-input-wrapper">
                        <input type="text" id="student-search" class="form-input" oninput="filterStudents()" placeholder="Search ID, name or section...">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <div class="filter-controls">
                        <div class="filter-wrapper">
                            <select id="student-program-filter" onchange="filterStudents()" class="form-select no-icon filter-select">
                                <option value="All">All Program Majors</option>
                                <!-- Dynamic programs options -->
                            </select>
                        </div>
                        
                        <div class="filter-wrapper">
                            <select id="student-status-filter" onchange="filterStudents()" class="form-select no-icon filter-select">
                                <option value="All">All Statuses</option>
                                <option value="Pending">Pending Review</option>
                                <option value="Approved by Dept Head">Approved by Head</option>
                                <option value="Enrolled">Officially Enrolled</option>
                                <option value="Rejected">Rejected / Returned</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-secondary btn-add" onclick="fetchSystemUsers()" style="background: rgba(255, 255, 255, 0.05); border-color: var(--color-card-border); margin-right: 0.5rem;" title="Refresh Database Data">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Refresh</span>
                        </button>
                        <button type="button" id="btn-batch-delete-students" class="btn btn-danger btn-add" onclick="batchDeleteStudentsTrigger()" style="display: none; background: var(--color-danger); border-color: rgba(239, 68, 68, 0.3); margin-right: 0.5rem;">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Delete Selected</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-add" onclick="openStudentModal('create')">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Add Student</span>
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table id="students-table" class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center; padding-left: 1.5rem;"><input type="checkbox" id="select-all-students" onclick="toggleSelectAllStudents(this)" style="cursor: pointer; width: 16px; height: 16px;"></th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Program Code</th>
                                <th>Cohort Section</th>
                                <th>Completed Term</th>
                                <th>Status</th>
                                <th style="text-align: right; padding-right: 1.5rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="students-tbody">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: STAFF -->
            <div class="tab-content" id="tab-content-staff">
                <div class="search-filter-row">
                    <div class="search-input-wrapper">
                        <input type="text" id="staff-search" class="form-input" oninput="filterStaff()" placeholder="Search ID, name or role...">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <div class="filter-controls">
                        <div class="filter-wrapper">
                            <select id="staff-role-filter" onchange="filterStaff()" class="form-select no-icon filter-select">
                                <option value="All">All Roles</option>
                                <option value="Dept Head">Department Head</option>
                                <option value="Registrar">Registrar</option>
                                <option value="Admin">System Administrator</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-secondary btn-add" onclick="fetchSystemUsers()" style="background: rgba(255, 255, 255, 0.05); border-color: var(--color-card-border); margin-right: 0.5rem;" title="Refresh Database Data">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Refresh</span>
                        </button>
                        <button type="button" id="btn-batch-delete-staff" class="btn btn-danger btn-add" onclick="batchDeleteStaffTrigger()" style="display: none; background: var(--color-danger); border-color: rgba(239, 68, 68, 0.3); margin-right: 0.5rem;">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Delete Selected</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-add" onclick="openStaffModal('create')">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Add Staff Account</span>
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table id="staff-table" class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center; padding-left: 1.5rem;"><input type="checkbox" id="select-all-staff" onclick="toggleSelectAllStaff(this)" style="cursor: pointer; width: 16px; height: 16px;"></th>
                                <th>Staff ID</th>
                                <th>Staff Name</th>
                                <th>Office Role</th>
                                <th>Program Code (For Dept Heads)</th>
                                <th style="text-align: right; padding-right: 1.5rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="staff-tbody">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- STUDENT MODAL (CREATE / EDIT) -->
    <div class="modal-overlay" id="student-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="student-modal-title"><i class="fa-solid fa-user-graduate"></i> Create Student Profile</h3>
                <button class="modal-close" onclick="closeStudentModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="student-form" onsubmit="saveStudent(event)">
                <input type="hidden" id="student-mode" value="create">
                <input type="hidden" id="student-orig-id" value="">
                
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="student-form-id">Student ID</label>
                            <div class="input-wrapper">
                                <input type="text" id="student-form-id" class="form-input" placeholder="TUPV-00-0000" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-name">Full Name</label>
                            <div class="input-wrapper">
                                <input type="text" id="student-form-name" class="form-input" placeholder="Full name..." required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-password">Password (Optional on Edit)</label>
                            <div class="input-wrapper">
                                <input type="password" id="student-form-password" class="form-input" placeholder="Defaults to password123 if empty">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-birthday">Date of Birth</label>
                            <div class="input-wrapper">
                                <input type="date" id="student-form-birthday" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-program">Program Major</label>
                            <div class="input-wrapper select-wrapper">
                                <select id="student-form-program" class="form-select" required>
                                    <!-- Dynamic programs -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-section">Cohort Section</label>
                            <div class="input-wrapper select-wrapper">
                                <select id="student-form-section" class="form-select" required>
                                    <option value="Section A">Section A</option>
                                    <option value="Section B">Section B</option>
                                    <option value="Section C">Section C</option>
                                    <option value="Section D">Section D</option>
                                    <option value="Section E">Section E</option>
                                    <option value="Section F">Section F</option>
                                    <option value="Section G">Section G</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-term">Completed Enrollment Term</label>
                            <div class="input-wrapper select-wrapper">
                                <select id="student-form-term" class="form-select" required>
                                    <option value="First Term">First Term (Completed)</option>
                                    <option value="Second Term">Second Term (Completed)</option>
                                    <option value="Third Term">Third Term (Completed)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student-form-status">Approval Status</label>
                            <div class="input-wrapper select-wrapper">
                                <select id="student-form-status" class="form-select" required>
                                    <option value="Pending">Pending Review</option>
                                    <option value="Approved by Dept Head">Approved by Dept Head</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Enrolled">Enrolled</option>
                                </select>
                            </div>
                        </div>

                        <!-- Academic Subject History Section -->
                        <div class="form-group full-width" style="margin-top: 1.5rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                            <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.3rem;">
                                <i class="fa-solid fa-file-invoice" style="color: var(--color-secondary);"></i> Academic Subject History (Transcript)
                            </h3>
                            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1rem;">Configure historical grades. Failed subjects will block enrollment in subsequent courses requiring prerequisites.</p>
                            
                            <div class="search-row" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                                <div class="search-input-wrapper" style="position: relative; flex-grow: 1;">
                                    <input type="text" id="modal-subject-search" class="form-input" oninput="filterModalSubjects()" placeholder="Search subjects by code or description..." style="padding-left: 2.4rem !important;">
                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--color-text-dim); font-size: 0.9rem; pointer-events: none;"></i>
                                </div>
                            </div>
                            
                            <div class="subject-checklist-container" id="modal-subjects-list">
                                <!-- Injected dynamically via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeStudentModal()" class="btn btn-secondary btn-modal btn-modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modal btn-modal-save" id="student-submit-btn">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- STAFF MODAL (CREATE / EDIT) -->
    <div class="modal-overlay" id="staff-modal">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="staff-modal-title"><i class="fa-solid fa-user-shield"></i> Create Staff Profile</h3>
                <button class="modal-close" onclick="closeStaffModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="staff-form" onsubmit="saveStaff(event)">
                <input type="hidden" id="staff-mode" value="create">
                <input type="hidden" id="staff-orig-id" value="">
                
                <div class="modal-body">
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <div class="form-group">
                            <label class="form-label" for="staff-form-id">Staff ID</label>
                            <div class="input-wrapper">
                                <input type="text" id="staff-form-id" class="form-input" placeholder="DEPT-XX, REG-XX, or ADMIN-XX" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="staff-form-name">Full Name</label>
                            <div class="input-wrapper">
                                <input type="text" id="staff-form-name" class="form-input" placeholder="Full name..." required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="staff-form-password">Password (Optional on Edit)</label>
                            <div class="input-wrapper">
                                <input type="password" id="staff-form-password" class="form-input" placeholder="Defaults to password123 if empty">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="staff-form-role">Office Role</label>
                            <div class="input-wrapper select-wrapper">
                                <select id="staff-form-role" class="form-select" onchange="toggleStaffProgramField()" required>
                                    <option value="Dept Head">Department Head</option>
                                    <option value="Registrar">Registrar</option>
                                    <option value="Admin">System Administrator</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="staff-program-group">
                            <label class="form-label" for="staff-form-program">Program Code (Academic Major)</label>
                            <div class="input-wrapper select-wrapper">
                                <select id="staff-form-program" class="form-select">
                                    <option value="">No Program Assigned</option>
                                    <!-- Dynamic programs -->
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeStaffModal()" class="btn btn-secondary btn-modal btn-modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modal btn-modal-save" id="staff-submit-btn">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- BATCH ACTION CONFIRMATION MODAL -->
    <div class="modal-overlay" id="confirm-batch-modal">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--color-card-border);">
                <h3><i class="fa-solid fa-triangle-exclamation" style="color: var(--color-danger); margin-right: 0.5rem;"></i> Confirm Batch Action</h3>
                <button class="modal-close" onclick="closeConfirmBatchModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem 0;">
                <p id="confirm-batch-text" style="margin-bottom: 1rem; font-weight: 500; font-size: 0.95rem;"></p>
                <div style="max-height: 200px; overflow-y: auto; background: rgba(0, 0, 0, 0.2); border: 1px solid var(--color-card-border); border-radius: 8px; padding: 0.75rem;" id="confirm-batch-list-container">
                    <ul id="confirm-batch-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                        <!-- List of selected items -->
                    </ul>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--color-card-border); padding-top: 1rem;">
                <button type="button" onclick="closeConfirmBatchModal()" class="btn btn-secondary btn-modal btn-modal-cancel">Cancel</button>
                <button type="button" class="btn btn-danger btn-modal" id="confirm-batch-btn" style="background: var(--color-danger); border-color: rgba(239, 68, 68, 0.3);">Confirm Delete</button>
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
        // Global variables holding fetched DB records
        let studentsList = [];
        let staffList = [];
        let programsList = [];
        let subjectsList = [];
        let curriculumsList = [];

        // Batch Action State variables
        let activeBatchAction = null;
        let activeBatchItems = [];

        // Selection & Batch Delete Logic
        function toggleSelectAllStudents(source) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr && tr.style.display !== 'none') {
                    cb.checked = source.checked;
                }
            });
            updateStudentSelectionState();
        }

        function updateStudentSelectionState() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            let checkedCount = 0;
            let visibleCount = 0;
            checkboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr && tr.style.display !== 'none') {
                    visibleCount++;
                    if (cb.checked) checkedCount++;
                }
            });

            const selectAll = document.getElementById('select-all-students');
            if (selectAll) {
                selectAll.checked = (visibleCount > 0 && checkedCount === visibleCount);
                selectAll.indeterminate = (checkedCount > 0 && checkedCount < visibleCount);
            }

            const deleteBtn = document.getElementById('btn-batch-delete-students');
            if (deleteBtn) {
                deleteBtn.style.display = checkedCount > 0 ? 'inline-flex' : 'none';
            }
        }

        function toggleSelectAllStaff(source) {
            const checkboxes = document.querySelectorAll('.staff-checkbox');
            checkboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr && tr.style.display !== 'none') {
                    cb.checked = source.checked;
                }
            });
            updateStaffSelectionState();
        }

        function updateStaffSelectionState() {
            const checkboxes = document.querySelectorAll('.staff-checkbox');
            let checkedCount = 0;
            let visibleCount = 0;
            checkboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr && tr.style.display !== 'none') {
                    visibleCount++;
                    if (cb.checked) checkedCount++;
                }
            });

            const selectAll = document.getElementById('select-all-staff');
            if (selectAll) {
                selectAll.checked = (visibleCount > 0 && checkedCount === visibleCount);
                selectAll.indeterminate = (checkedCount > 0 && checkedCount < visibleCount);
            }

            const deleteBtn = document.getElementById('btn-batch-delete-staff');
            if (deleteBtn) {
                deleteBtn.style.display = checkedCount > 0 ? 'inline-flex' : 'none';
            }
        }

        function closeConfirmBatchModal() {
            document.getElementById('confirm-batch-modal').classList.remove('active');
            activeBatchAction = null;
            activeBatchItems = [];
        }

        function batchDeleteStudentsTrigger() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            activeBatchItems = [];
            checkboxes.forEach(cb => {
                activeBatchItems.push({
                    id: cb.getAttribute('data-id'),
                    name: cb.getAttribute('data-name')
                });
            });

            if (activeBatchItems.length === 0) return;

            activeBatchAction = 'students';
            
            document.getElementById('confirm-batch-text').innerHTML = `⚠️ WARNING: Are you sure you want to permanently delete the following <strong>${activeBatchItems.length}</strong> student(s)?<br><small style="color: var(--color-danger);">This will also cascade delete all their selected schedules, history records, and audit logs.</small>`;
            
            const listEl = document.getElementById('confirm-batch-list');
            listEl.innerHTML = '';
            activeBatchItems.forEach(item => {
                const li = document.createElement('li');
                li.style.cssText = 'display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.35rem 0.6rem; background: rgba(255, 255, 255, 0.03); border-radius: 6px;';
                li.innerHTML = `<span>${item.name}</span><strong style="font-family: monospace;">${item.id.toUpperCase()}</strong>`;
                listEl.appendChild(li);
            });

            document.getElementById('confirm-batch-btn').textContent = 'Confirm Delete';
            document.getElementById('confirm-batch-btn').onclick = executeBatchDelete;
            document.getElementById('confirm-batch-modal').classList.add('active');
        }

        function batchDeleteStaffTrigger() {
            const checkboxes = document.querySelectorAll('.staff-checkbox:checked');
            activeBatchItems = [];
            const currentStaffId = '<?php echo $staffId; ?>'.toLowerCase();
            let containsSelf = false;

            checkboxes.forEach(cb => {
                const id = cb.getAttribute('data-id').toLowerCase();
                if (id === currentStaffId) {
                    containsSelf = true;
                } else {
                    activeBatchItems.push({
                        id: cb.getAttribute('data-id'),
                        name: cb.getAttribute('data-name')
                    });
                }
            });

            if (containsSelf) {
                showToast('Security Alert', 'Your own active administrator account was automatically excluded from the batch deletion list.', 'error');
            }

            if (activeBatchItems.length === 0) return;

            activeBatchAction = 'staff';
            
            document.getElementById('confirm-batch-text').innerHTML = `⚠️ WARNING: Are you sure you want to permanently delete the following <strong>${activeBatchItems.length}</strong> staff account(s)?`;
            
            const listEl = document.getElementById('confirm-batch-list');
            listEl.innerHTML = '';
            activeBatchItems.forEach(item => {
                const li = document.createElement('li');
                li.style.cssText = 'display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.35rem 0.6rem; background: rgba(255, 255, 255, 0.03); border-radius: 6px;';
                li.innerHTML = `<span>${item.name}</span><strong style="font-family: monospace;">${item.id.toUpperCase()}</strong>`;
                listEl.appendChild(li);
            });

            document.getElementById('confirm-batch-btn').textContent = 'Confirm Delete';
            document.getElementById('confirm-batch-btn').onclick = executeBatchDelete;
            document.getElementById('confirm-batch-modal').classList.add('active');
        }

        async function executeBatchDelete() {
            const btn = document.getElementById('confirm-batch-btn');
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            const endpoint = activeBatchAction === 'students' ? 'php/api/admin/delete_student.php' : 'php/api/admin/delete_staff.php';
            const idKey = activeBatchAction === 'students' ? 'student_id' : 'staff_id';

            try {
                const promises = activeBatchItems.map(item => {
                    const body = {};
                    body[idKey] = item.id;
                    return fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body),
                        credentials: 'include',
                        cache: 'no-store'
                    }).then(res => res.json());
                });

                const results = await Promise.all(promises);
                const failures = results.filter(r => !r.success);

                if (failures.length === 0) {
                    showToast('Batch Action Completed', `Successfully deleted ${activeBatchItems.length} profile(s).`, 'success');
                } else {
                    showToast('Batch Action Partial Success', `Successfully deleted ${activeBatchItems.length - failures.length} profile(s). ${failures.length} failed.`, 'warning');
                }

                // Reset selection checkboxes
                if (activeBatchAction === 'students') {
                    const selectAll = document.getElementById('select-all-students');
                    if (selectAll) selectAll.checked = false;
                } else {
                    const selectAll = document.getElementById('select-all-staff');
                    if (selectAll) selectAll.checked = false;
                }

                fetchSystemUsers(); // Refresh data
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'An error occurred during batch deletion.', 'error');
            } finally {
                btn.disabled = false;
                closeConfirmBatchModal();
            }
        }

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

        // Tab Switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            document.getElementById(`tab-btn-${tabName}`).classList.add('active');
            document.getElementById(`tab-content-${tabName}`).classList.add('active');
        }

        // Load data on page load
        async function fetchSystemUsers() {
            try {
                const response = await fetch('php/api/admin/get_users.php', {
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();

                if (result.success) {
                    studentsList = result.students;
                    staffList = result.staff;
                    programsList = result.programs;
                    subjectsList = result.subjects || [];
                    curriculumsList = result.curriculums || [];

                    // Update Metrics Values
                    document.getElementById('metric-students-val').textContent = studentsList.length;
                    document.getElementById('metric-staff-val').textContent = staffList.length;
                    document.getElementById('metric-programs-val').textContent = programsList.length;

                    // Populate Dropdowns & Filter selects
                    populateDropdowns();

                    // Render Tables
                    renderStudentsTable();
                    renderStaffTable();

                    // Initialize Subject List inside the student modal
                    renderModalSubjects();
                } else {
                    showToast('Data Load Failed', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to retrieve administrative users data.', 'error');
            }
        }

        // Populate program dropdowns dynamically
        function populateDropdowns() {
            const studentProgFilter = document.getElementById('student-program-filter');
            const studentFormProg = document.getElementById('student-form-program');
            const staffFormProg = document.getElementById('staff-form-program');

            // Store selected values to restore them
            const prevFilter = studentProgFilter.value;
            
            studentProgFilter.innerHTML = '<option value="All">All Program Majors</option>';
            studentFormProg.innerHTML = '';
            staffFormProg.innerHTML = '<option value="">No Program Assigned (Registrar/Admin)</option>';

            programsList.forEach(prog => {
                const optText = `${prog.program_code} — ${prog.program_name}`;
                
                // Add to Filter
                const opt1 = document.createElement('option');
                opt1.value = prog.program_code;
                opt1.textContent = optText;
                studentProgFilter.appendChild(opt1);

                // Add to Student Form
                const opt2 = document.createElement('option');
                opt2.value = prog.program_code;
                opt2.textContent = optText;
                studentFormProg.appendChild(opt2);

                // Add to Staff Form
                const opt3 = document.createElement('option');
                opt3.value = prog.program_code;
                opt3.textContent = optText;
                staffFormProg.appendChild(opt3);
            });

            // Restore selection
            if (prevFilter) studentProgFilter.value = prevFilter;
        }

        // Student Table render
        function renderStudentsTable() {
            const tbody = document.getElementById('students-tbody');
            tbody.innerHTML = '';

            const selectAll = document.getElementById('select-all-students');
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            const deleteBtn = document.getElementById('btn-batch-delete-students');
            if (deleteBtn) deleteBtn.style.display = 'none';

            if (studentsList.length === 0) {
                tbody.innerHTML = `<tr class="empty-row"><td colspan="7">No students registered in the system database.</td></tr>`;
                return;
            }

            studentsList.forEach(s => {
                const tr = document.createElement('tr');
                tr.className = 'student-row-item';
                tr.setAttribute('data-id', s.student_id.toLowerCase());
                tr.setAttribute('data-name', s.name.toLowerCase());
                tr.setAttribute('data-program', s.program_code);
                tr.setAttribute('data-status', s.approval_status);

                let badgeClass = 'badge-warning';
                if (s.approval_status === 'Approved by Dept Head') badgeClass = 'badge-info';
                else if (s.approval_status === 'Enrolled') badgeClass = 'badge-success';
                else if (s.approval_status === 'Rejected') badgeClass = 'badge-danger';

                tr.innerHTML = `
                    <td style="text-align: center; padding-left: 1.5rem;"><input type="checkbox" class="student-checkbox" data-id="${s.student_id}" data-name="${s.name}" onchange="updateStudentSelectionState()" style="cursor: pointer; width: 16px; height: 16px;"></td>
                    <td class="item-id">${s.student_id}</td>
                    <td class="item-name">${s.name}</td>
                    <td><strong>${s.program_code}</strong></td>
                    <td>${s.section}</td>
                    <td>${s.current_term}</td>
                    <td><span class="badge ${badgeClass}">${s.approval_status}</span></td>
                    <td style="text-align: right; padding-right: 1.5rem;">
                        <div class="actions-cell" style="justify-content: flex-end;">
                            <button onclick="editStudentTrigger('${s.student_id}')" class="btn-action edit-btn" title="Edit Student Profile"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button onclick="deleteStudentTrigger('${s.student_id}', '${s.name}')" class="btn-action delete-btn" title="Delete Student"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Staff Table render
        function renderStaffTable() {
            const tbody = document.getElementById('staff-tbody');
            tbody.innerHTML = '';

            const selectAll = document.getElementById('select-all-staff');
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            const deleteBtn = document.getElementById('btn-batch-delete-staff');
            if (deleteBtn) deleteBtn.style.display = 'none';

            if (staffList.length === 0) {
                tbody.innerHTML = `<tr class="empty-row"><td colspan="5">No staff accounts registered.</td></tr>`;
                return;
            }

            staffList.forEach(st => {
                const tr = document.createElement('tr');
                tr.className = 'staff-row-item';
                tr.setAttribute('data-id', st.staff_id.toLowerCase());
                tr.setAttribute('data-name', st.name.toLowerCase());
                tr.setAttribute('data-role', st.role);

                let badgeClass = 'badge-info';
                if (st.role === 'Registrar') badgeClass = 'badge-primary';
                else if (st.role === 'Admin') badgeClass = 'badge-success';

                tr.innerHTML = `
                    <td style="text-align: center; padding-left: 1.5rem;"><input type="checkbox" class="staff-checkbox" data-id="${st.staff_id}" data-name="${st.name}" onchange="updateStaffSelectionState()" style="cursor: pointer; width: 16px; height: 16px;"></td>
                    <td class="item-id">${st.staff_id}</td>
                    <td class="item-name">${st.name}</td>
                    <td><span class="badge ${badgeClass}">${st.role}</span></td>
                    <td><strong>${st.program_code ? st.program_code : '—'}</strong></td>
                    <td style="text-align: right; padding-right: 1.5rem;">
                        <div class="actions-cell" style="justify-content: flex-end;">
                            <button onclick="editStaffTrigger('${st.staff_id}')" class="btn-action edit-btn" title="Edit Staff Account"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button onclick="deleteStaffTrigger('${st.staff_id}', '${st.name}')" class="btn-action delete-btn" title="Delete Staff Account"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Client-side Filters: Students
        function filterStudents() {
            const query = document.getElementById('student-search').value.toLowerCase().trim();
            const program = document.getElementById('student-program-filter').value;
            const status = document.getElementById('student-status-filter').value;
            const rows = document.querySelectorAll('.student-row-item');

            const selectAll = document.getElementById('select-all-students');
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateStudentSelectionState();

            rows.forEach(row => {
                const rowId = row.getAttribute('data-id');
                const rowName = row.getAttribute('data-name');
                const rowProgram = row.getAttribute('data-program');
                const rowStatus = row.getAttribute('data-status');

                const matchesSearch = rowId.includes(query) || rowName.includes(query);
                const matchesProgram = program === 'All' || rowProgram === program;
                const matchesStatus = status === 'All' || rowStatus === status;

                if (matchesSearch && matchesProgram && matchesStatus) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Client-side Filters: Staff
        function filterStaff() {
            const query = document.getElementById('staff-search').value.toLowerCase().trim();
            const role = document.getElementById('staff-role-filter').value;
            const rows = document.querySelectorAll('.staff-row-item');

            const selectAll = document.getElementById('select-all-staff');
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            const checkboxes = document.querySelectorAll('.staff-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateStaffSelectionState();

            rows.forEach(row => {
                const rowId = row.getAttribute('data-id');
                const rowName = row.getAttribute('data-name');
                const rowRole = row.getAttribute('data-role');

                const matchesSearch = rowId.includes(query) || rowName.includes(query);
                const matchesRole = role === 'All' || rowRole === role;

                if (matchesSearch && matchesRole) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Render Modal Subjects checklist
        function renderModalSubjects() {
            const listContainer = document.getElementById('modal-subjects-list');
            if (!listContainer) return;
            listContainer.innerHTML = '';
            
            // Get selected program and completed term
            const selectedProg = document.getElementById('student-form-program').value;
            const completedTerm = document.getElementById('student-form-term').value;

            const termOrder = {
                'First Term': 1,
                'Second Term': 2,
                'Third Term': 3
            };

            const completedTermWeight = termOrder[completedTerm] || 3;
            
            // Filter curriculums:
            // 1. If Common First Year (BET-00-V) is selected, show only BET-00-V up to completedTermWeight (which is 1st Year).
            // 2. If a specific Major (e.g. BET-04-V) is selected, show ALL BET-00-V (1st Year) subjects AND major subjects (which represent 2nd Year) up to completedTermWeight.
            const programCurriculums = curriculumsList.filter(c => {
                const termWeight = termOrder[c.term] || 99;
                if (selectedProg === 'BET-00-V') {
                    return c.program_code === 'BET-00-V' && termWeight <= completedTermWeight;
                } else {
                    const isCommonFirstYear = (c.program_code === 'BET-00-V');
                    const isMajorSecondYear = (c.program_code === selectedProg);
                    return isCommonFirstYear || (isMajorSecondYear && termWeight <= completedTermWeight);
                }
            });

            const subjectMeta = {}; // maps subject_code -> { term, year_level }
            programCurriculums.forEach(c => {
                subjectMeta[c.subject_code] = {
                    term: c.term,
                    year_level: parseInt(c.year_level, 10) || (c.program_code === 'BET-00-V' ? 1 : 2)
                };
            });
            
            const filteredSubjects = subjectsList.filter(s => s.subject_code in subjectMeta);

            if (filteredSubjects.length === 0) {
                listContainer.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 1rem;">No subjects mapped to the selected program curriculum.</div>';
                return;
            }

            // Sort by year level, then by term order, then by subject code
            filteredSubjects.sort((a, b) => {
                const metaA = subjectMeta[a.subject_code];
                const metaB = subjectMeta[b.subject_code];
                
                if (metaA.year_level !== metaB.year_level) {
                    return metaA.year_level - metaB.year_level;
                }
                
                const orderA = termOrder[metaA.term] || 99;
                const orderB = termOrder[metaB.term] || 99;
                
                if (orderA !== orderB) {
                    return orderA - orderB;
                }
                return a.subject_code.localeCompare(b.subject_code);
            });

            let currentHeader = '';

            filteredSubjects.forEach(subj => {
                const meta = subjectMeta[subj.subject_code];
                const yearLabel = meta.year_level === 1 ? 'First Year' : 'Second Year';
                const headerText = `${yearLabel} - ${meta.term}`;
                
                // Add header if year/term changes
                if (headerText !== currentHeader) {
                    currentHeader = headerText;
                    const header = document.createElement('div');
                    header.style.cssText = 'padding: 0.5rem 0.8rem; background: rgba(255, 255, 255, 0.05); font-weight: 700; font-size: 0.75rem; color: var(--color-primary); border-radius: 8px; margin: 0.6rem 0.2rem 0.3rem 0.2rem; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid var(--color-primary); text-align: left;';
                    header.textContent = headerText;
                    listContainer.appendChild(header);
                }

                const row = document.createElement('div');
                row.className = 'subject-checklist-row modal-subject-row';
                row.setAttribute('data-code', subj.subject_code.toLowerCase());
                row.setAttribute('data-desc', subj.description.toLowerCase());
                row.innerHTML = `
                    <div class="subject-info">
                        <span class="subject-code">${subj.subject_code}</span>
                        <span class="subject-units">${subj.units} Units</span>
                        <div class="subject-desc">${subj.description}</div>
                    </div>
                    <div class="subject-actions">
                        <input type="hidden" id="modal-grade-input-${subj.subject_code}" value="not_taken">
                        <span class="grade-pill selected-not_taken" id="modal-pill-nt-${subj.subject_code}" onclick="selectModalGrade('${subj.subject_code}', 'not_taken')">
                            Not Taken
                        </span>
                        <span class="grade-pill" id="modal-pill-p-${subj.subject_code}" onclick="selectModalGrade('${subj.subject_code}', 'passed')">
                            Passed
                        </span>
                        <span class="grade-pill" id="modal-pill-f-${subj.subject_code}" onclick="selectModalGrade('${subj.subject_code}', 'failed')">
                            Failed
                        </span>
                    </div>
                `;
                listContainer.appendChild(row);
            });
        }

        // Filter modal subjects list by query
        function filterModalSubjects() {
            const query = document.getElementById('modal-subject-search').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.modal-subject-row');
            
            rows.forEach(row => {
                const code = row.getAttribute('data-code');
                const desc = row.getAttribute('data-desc');
                
                if (code.includes(query) || desc.includes(query)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Change modal grade selections
        function selectModalGrade(subCode, val) {
            const inputEl = document.getElementById(`modal-grade-input-${subCode}`);
            if (!inputEl) return;
            inputEl.value = val;
            
            const ntPill = document.getElementById(`modal-pill-nt-${subCode}`);
            const pPill = document.getElementById(`modal-pill-p-${subCode}`);
            const fPill = document.getElementById(`modal-pill-f-${subCode}`);
            
            if (ntPill) ntPill.className = 'grade-pill';
            if (pPill) pPill.className = 'grade-pill';
            if (fPill) fPill.className = 'grade-pill';
            
            if (val === 'not_taken' && ntPill) {
                ntPill.classList.add('selected-not_taken');
            } else if (val === 'passed' && pPill) {
                pPill.classList.add('selected-passed');
            } else if (val === 'failed' && fPill) {
                fPill.classList.add('selected-failed');
            }
        }

        // Student Modal Control
        function openStudentModal(mode) {
            document.getElementById('student-form').reset();
            document.getElementById('student-mode').value = mode;
            document.getElementById('student-form-id').disabled = false;
            document.getElementById('modal-subject-search').value = '';
            
            // Build the dynamic subjects list
            renderModalSubjects();
            
            // Reset modal grades UI to default "not_taken"
            subjectsList.forEach(subj => {
                selectModalGrade(subj.subject_code, 'not_taken');
            });
            filterModalSubjects(); // Reset visibility

            if (mode === 'create') {
                document.getElementById('student-modal-title').innerHTML = `<i class="fa-solid fa-user-plus"></i> Create Student Profile`;
                document.getElementById('student-submit-btn').textContent = 'Create Profile';
                document.getElementById('student-form-id').value = generateCandidateStudentId();
            }
            document.getElementById('student-modal').classList.add('active');
        }

        function closeStudentModal() {
            document.getElementById('student-modal').classList.remove('active');
        }

        function generateCandidateStudentId() {
            // Check max and auto prefill
            let maxNum = 0;
            studentsList.forEach(s => {
                if (s.student_id.startsWith('TUPV-00-')) {
                    const num = parseInt(s.student_id.substring(8), 10);
                    if (num > maxNum) maxNum = num;
                }
            });
            return `TUPV-00-${String(maxNum + 1).padStart(4, '0')}`;
        }

        async function editStudentTrigger(studentId) {
            const s = studentsList.find(item => item.student_id === studentId);
            if (!s) return;

            openStudentModal('edit');
            document.getElementById('student-modal-title').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> Edit Student Profile`;
            document.getElementById('student-submit-btn').textContent = 'Update Profile';

            document.getElementById('student-orig-id').value = s.student_id;
            document.getElementById('student-form-id').value = s.student_id;
            document.getElementById('student-form-id').disabled = true; // Protect ID on edit
            document.getElementById('student-form-name').value = s.name;
            document.getElementById('student-form-birthday').value = s.birthday;
            document.getElementById('student-form-program').value = s.program_code;
            document.getElementById('student-form-section').value = s.section;
            document.getElementById('student-form-term').value = s.current_term;
            document.getElementById('student-form-status').value = s.approval_status;

            // Re-render subjects allowed under this specific student's program major
            renderModalSubjects();

            // Fetch current subject history transcript
            try {
                const response = await fetch(`php/api/admin/get_student_history.php?student_id=${encodeURIComponent(studentId)}`, {
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();
                if (result.success && result.history) {
                    result.history.forEach(hist => {
                        const dbStatus = hist.status.toLowerCase(); // 'passed' or 'failed'
                        selectModalGrade(hist.subject_code, dbStatus);
                    });
                }
            } catch (err) {
                console.error("Error loading student academic history: ", err);
            }
        }

        async function saveStudent(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('student-submit-btn');
            submitBtn.disabled = true;

            // Gather academic history selections (handle filtered elements safely)
            const grades = {};
            subjectsList.forEach(subj => {
                const el = document.getElementById(`modal-grade-input-${subj.subject_code}`);
                const gradeVal = el ? el.value : 'not_taken';
                grades[subj.subject_code] = gradeVal;
            });

            const payload = {
                mode: document.getElementById('student-mode').value,
                original_student_id: document.getElementById('student-orig-id').value,
                student_id: document.getElementById('student-form-id').value.trim(),
                name: document.getElementById('student-form-name').value.trim(),
                password: document.getElementById('student-form-password').value,
                birthday: document.getElementById('student-form-birthday').value,
                program_code: document.getElementById('student-form-program').value,
                section: document.getElementById('student-form-section').value,
                current_term: document.getElementById('student-form-term').value,
                approval_status: document.getElementById('student-form-status').value,
                grades: grades
            };

            try {
                const response = await fetch('php/api/admin/save_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Success', result.message, 'success');
                    closeStudentModal();
                    fetchSystemUsers(); // Refresh dataset
                } else {
                    showToast('Error saving profile', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach student endpoint.', 'error');
            } finally {
                submitBtn.disabled = false;
            }
        }

        async function deleteStudentTrigger(studentId, name) {
            if (!confirm(`⚠️ WARNING: Are you sure you want to permanently delete student '${name}' (${studentId})?\n\nThis will also cascade delete all their selected schedules, history records, and audit logs.`)) {
                return;
            }

            try {
                const response = await fetch('php/api/admin/delete_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id: studentId }),
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Student Deleted', result.message, 'success');
                    fetchSystemUsers(); // Refresh list
                } else {
                    showToast('Deletion Failed', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach delete endpoint.', 'error');
            }
        }

        // Staff Modal Controls
        function openStaffModal(mode) {
            document.getElementById('staff-form').reset();
            document.getElementById('staff-mode').value = mode;
            document.getElementById('staff-form-id').disabled = false;

            if (mode === 'create') {
                document.getElementById('staff-modal-title').innerHTML = `<i class="fa-solid fa-user-plus"></i> Create Staff Account`;
                document.getElementById('staff-submit-btn').textContent = 'Create Account';
            }
            toggleStaffProgramField();
            document.getElementById('staff-modal').classList.add('active');
        }

        function closeStaffModal() {
            document.getElementById('staff-modal').classList.remove('active');
        }

        function toggleStaffProgramField() {
            const role = document.getElementById('staff-form-role').value;
            const programGroup = document.getElementById('staff-program-group');
            const programSelect = document.getElementById('staff-form-program');

            if (role === 'Dept Head') {
                programGroup.style.display = 'flex';
                programSelect.required = true;
            } else {
                programGroup.style.display = 'none';
                programSelect.required = false;
                programSelect.value = '';
            }
        }

        function editStaffTrigger(staffId) {
            const st = staffList.find(item => item.staff_id === staffId);
            if (!st) return;

            openStaffModal('edit');
            document.getElementById('staff-modal-title').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> Edit Staff Account`;
            document.getElementById('staff-submit-btn').textContent = 'Update Account';

            document.getElementById('staff-orig-id').value = st.staff_id;
            document.getElementById('staff-form-id').value = st.staff_id;
            document.getElementById('staff-form-name').value = st.name;
            document.getElementById('staff-form-role').value = st.role;
            toggleStaffProgramField();
            if (st.role === 'Dept Head') {
                document.getElementById('staff-form-program').value = st.program_code;
            }
        }

        async function saveStaff(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('staff-submit-btn');
            submitBtn.disabled = true;

            const payload = {
                mode: document.getElementById('staff-mode').value,
                original_staff_id: document.getElementById('staff-orig-id').value,
                staff_id: document.getElementById('staff-form-id').value.trim(),
                name: document.getElementById('staff-form-name').value.trim(),
                password: document.getElementById('staff-form-password').value,
                role: document.getElementById('staff-form-role').value,
                program_code: document.getElementById('staff-form-program').value
            };

            try {
                const response = await fetch('php/api/admin/save_staff.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Success', result.message, 'success');
                    closeStaffModal();
                    fetchSystemUsers(); // Refresh dataset
                } else {
                    showToast('Error saving staff account', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach staff endpoint.', 'error');
            } finally {
                submitBtn.disabled = false;
            }
        }

        async function deleteStaffTrigger(staffId, name) {
            // Check self deletion
            if (staffId === '<?php echo $staffId; ?>') {
                showToast('Security Alert', 'You cannot delete your own active administrator account.', 'error');
                return;
            }

            if (!confirm(`Are you sure you want to permanently delete staff account '${name}' (${staffId})?`)) {
                return;
            }

            try {
                const response = await fetch('php/api/admin/delete_staff.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ staff_id: staffId }),
                    credentials: 'include',
                    cache: 'no-store'
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Account Deleted', result.message, 'success');
                    fetchSystemUsers(); // Refresh list
                } else {
                    showToast('Deletion Failed', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach delete endpoint.', 'error');
            }
        }

        async function runDatabaseSeeder() {
            if (!confirm("Are you sure you want to restore the database to its default seed data?\n\nThis will reset all student registrations, schedules, and history records to their initial testing states.")) {
                return;
            }
            
            const btn = document.getElementById('btn-seed-db');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Updating...</span>';
            
            try {
                const response = await fetch('database/seed.php', {
                    credentials: 'include',
                    cache: 'no-store'
                });
                const text = await response.text();
                
                if (response.ok && text.includes('successfully')) {
                    showToast('Database Updated', 'Database seed data restored successfully.', 'success');
                    fetchSystemUsers(); // Reload lists
                } else {
                    showToast('Update Failed', 'Failed to run database seeder.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to reach database seeder.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        // Initialize on load
        window.addEventListener('DOMContentLoaded', () => {
            fetchSystemUsers();

            // Re-render student transcript subjects list dynamically when the program or term changes
            document.getElementById('student-form-program').addEventListener('change', renderModalSubjects);
            document.getElementById('student-form-term').addEventListener('change', renderModalSubjects);
        });
    </script>
</body>
</html>
