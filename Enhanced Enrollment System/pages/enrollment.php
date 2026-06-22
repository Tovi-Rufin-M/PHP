<?php
/**
 * Student Enrollment Step-by-Step Portal
 * Provides enrollment instructions, schedule previews, failed subject retake workflows,
 * conflict validators, and a live auto-updating summary.
 */

require_once dirname(__DIR__) . '/php/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

// Check Session Authentication
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$studentId = $_SESSION['student_id'];
$dbClass = new Database();
$conn = $dbClass->getConnection();

$student = null;
$schedule = [];
$failedSubjects = [];
$otherSchedules = [];
$prereqMap = [];
$disabledSubjects = [];
$upcomingTerm = "";
$otherSchedulesJson = json_encode([]);
$totalCoreUnits = 0;
$maxUnitsAllowed = 24;

if ($conn) {
    try {
        // 1. Fetch Student Profile
        $stmt = $conn->prepare("
            SELECT s.*, p.program_name 
            FROM students s
            LEFT JOIN programs p ON s.program_code = p.program_code
            WHERE s.student_id = :student_id
            LIMIT 1
        ");
        $stmt->execute([':student_id' => $studentId]);
        $student = $stmt->fetch();

        if ($student) {
            $currentTerm = $student['current_term'];
            $programCode = $student['program_code'];
            $section = $student['section'];

            // Check if student has submitted selections
            $stmtCount = $conn->prepare("SELECT COUNT(*) FROM student_enrollment_selections WHERE student_id = :student_id");
            $stmtCount->execute([':student_id' => $studentId]);
            $hasSubmitted = $stmtCount->fetchColumn() > 0;

            // Fetch selections if submitted
            $submittedSelections = [];
            if ($hasSubmitted) {
                $stmtSelections = $conn->prepare("
                    SELECT es.*, s.description, s.units 
                    FROM student_enrollment_selections es
                    JOIN subjects s ON es.subject_code = s.subject_code
                    WHERE es.student_id = :student_id
                ");
                $stmtSelections->execute([':student_id' => $studentId]);
                $submittedSelections = $stmtSelections->fetchAll();
            }

            // Fetch latest rejection reason if rejected
            $rejectionReason = "";
            if ($student['approval_status'] === 'Rejected') {
                $stmtReject = $conn->prepare("
                    SELECT details FROM audit_logs 
                    WHERE student_id = :student_id AND action = 'Rejection' 
                    ORDER BY timestamp DESC LIMIT 1
                ");
                $stmtReject->execute([':student_id' => $studentId]);
                $rejectionReason = $stmtReject->fetchColumn();
            }

            // Determine Upcoming Term
            if ($currentTerm === "First Term") {
                $upcomingTerm = "Second Term";
            } elseif ($currentTerm === "Second Term") {
                $upcomingTerm = "Third Term";
            } else {
                $upcomingTerm = "Summer Term";
            }

            // 2. Fetch Next Term Schedule (Skip if BET-00-V on Third Term)
            $isCommonFirstYearThirdTerm = ($programCode === "BET-00-V" && $currentTerm === "Third Term");
            if (!$isCommonFirstYearThirdTerm) {
                $stmtSched = $conn->prepare("
                    SELECT ss.*, s.description, s.units
                    FROM section_schedules ss
                    JOIN subjects s ON ss.subject_code = s.subject_code
                    WHERE ss.program_code = :program_code 
                      AND ss.section_name = :section
                      AND ss.term = :term
                    ORDER BY ss.day_of_week, ss.start_time
                ");
                $stmtSched->execute([
                    ':program_code' => $programCode,
                    ':section' => $section,
                    ':term' => $upcomingTerm
                ]);
                $schedule = $stmtSched->fetchAll();
            }

            // 3. Fetch Failed Subjects (Grade >= 3.0)
            $stmtFailed = $conn->prepare("
                SELECT h.*, s.description, s.units, s.is_tutorial
                FROM student_subject_history h
                JOIN subjects s ON h.subject_code = s.subject_code
                WHERE h.student_id = :student_id AND h.status = 'Failed'
            ");
            $stmtFailed->execute([':student_id' => $studentId]);
            $failedSubjects = $stmtFailed->fetchAll();

            // 4. Fetch All Subject Prerequisites
            $stmtPrereqs = $conn->prepare("SELECT subject_code, prerequisite_code FROM subject_prerequisites");
            $stmtPrereqs->execute();
            $prereqs = $stmtPrereqs->fetchAll();

            foreach ($prereqs as $p) {
                $prereqMap[$p['subject_code']][] = $p['prerequisite_code'];
            }

            // 5. Determine Disabled Subjects in the Next-Term Schedule (due to failed prerequisites)
            $failedCodes = array_column($failedSubjects, 'subject_code');
            foreach ($schedule as $s) {
                $code = $s['subject_code'];
                if (isset($prereqMap[$code])) {
                    foreach ($prereqMap[$code] as $prereq) {
                        if (in_array($prereq, $failedCodes)) {
                            $disabledSubjects[$code] = $prereq;
                        }
                    }
                }
            }

            // Calculate total core units for next term (excluding disabled ones)
            $totalCoreUnits = 0;
            $uniqueCoreCodes = [];
            foreach ($schedule as $s) {
                $code = $s['subject_code'];
                if (!isset($disabledSubjects[$code]) && !in_array($code, $uniqueCoreCodes)) {
                    $uniqueCoreCodes[] = $code;
                    $totalCoreUnits += intval($s['units']);
                }
            }

            // Calculate total regular cohort load units for next term (including disabled ones)
            $totalRegularUnits = 0;
            $uniqueRegularCodes = [];
            foreach ($schedule as $s) {
                $code = $s['subject_code'];
                if (!in_array($code, $uniqueRegularCodes)) {
                    $uniqueRegularCodes[] = $code;
                    $totalRegularUnits += intval($s['units']);
                }
            }
            if ($currentTerm === "Third Term") {
                $maxUnitsAllowed = 9;
            } else {
                $maxUnitsAllowed = $totalRegularUnits > 0 ? $totalRegularUnits : 24;
            }

            // 6. Fetch Sit-In schedules (schedules of failed subjects offered in OTHER programs)
            if (!empty($failedCodes)) {
                $placeholders = implode(',', array_fill(0, count($failedCodes), '?'));
                $sqlOther = "
                    SELECT ss.*, s.description, s.units, p.program_name
                    FROM section_schedules ss
                    JOIN subjects s ON ss.subject_code = s.subject_code
                    JOIN programs p ON ss.program_code = p.program_code
                    WHERE ss.subject_code IN ($placeholders)
                      AND ss.program_code != ?
                      AND ss.term = ?
                    ORDER BY ss.program_code, ss.section_name, ss.day_of_week, ss.start_time
                ";
                $stmtOther = $conn->prepare($sqlOther);
                
                $params = array_merge($failedCodes, [$programCode, $upcomingTerm]);
                $stmtOther->execute($params);
                $otherSchedules = $stmtOther->fetchAll();
            }
            $otherSchedulesJson = json_encode($otherSchedules);
        }
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
    <title>Student Enrollment - Step-by-Step Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">

    <style>
        /* Enrollment page-specific variables (aliases to global design tokens) */
        :root {
            --bg-color: var(--color-bg);
            --card-bg: var(--color-card-bg);
            --card-border: var(--color-card-border);
            --accent-primary: var(--color-primary);
            --accent-primary-glow: var(--color-primary-glow);
            --accent-secondary: var(--color-secondary);
            --accent-warning: var(--color-warning);
            --text-main: var(--color-text-main);
            --text-muted: var(--color-text-muted);
        }

        /* Enrollment-specific body overrides */
        body {
            background-image: 
                radial-gradient(at 10% 20%, rgba(14, 165, 233, 0.1) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            padding-bottom: 5rem;
        }

        /* Logo + branding (page-specific) */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            font-size: clamp(1.6rem, 5vw, 2.2rem);
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 10px var(--accent-primary-glow));
        }

        .logo-text h1 {
            font-family: var(--font-display);
            font-size: clamp(1.2rem, 4vw, 1.6rem);
            font-weight: 800;
        }

        .logo-text p {
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Step indicator styling (page-specific) */
        .steps-progress-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid var(--card-border);
            padding: 1rem 2rem;
            border-radius: 16px;
            backdrop-filter: blur(12px);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            box-shadow: var(--shadow-sm);
        }

        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-muted);
            font-family: var(--font-display);
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.5;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-indicator.active {
            opacity: 1;
            color: #ffffff;
            font-weight: 600;
        }

        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-indicator.active .step-number {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 0 12px var(--accent-primary-glow);
        }

        .step-line {
            height: 2px;
            background: var(--card-border);
            width: 40px;
            border-radius: 1px;
            opacity: 0.3;
        }

        /* Glassmorphism Panel Container */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem;
            backdrop-filter: blur(12px);
            margin-bottom: 2rem;
            animation: fadeIn 0.4s ease forwards;
        }

        .panel-title {
            font-family: var(--font-display);
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 0.8rem;
        }

        .panel-title i {
            color: var(--accent-primary);
        }

        /* Step 1 Instructions Styles */
        .instruction-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .instruction-item {
            display: flex;
            gap: 1.2rem;
            align-items: flex-start;
            padding: 1.25rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
        }

        .instruction-item:hover {
            background: rgba(255, 255, 255, 0.035);
            border-color: rgba(14, 165, 233, 0.15);
        }

        .instruction-item i.num-badge {
            background: rgba(14, 165, 233, 0.1);
            color: var(--accent-primary);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: clamp(0.95rem, 2vw, 1.1rem);
            font-style: normal;
            flex-shrink: 0;
        }

        .instruction-text h3 {
            font-size: clamp(0.9rem, 2vw, 1.05rem);
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .instruction-text p {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: var(--text-muted);
            line-height: 1.5;
        }

        .rule-card {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.15);
            display: flex;
            gap: 1rem;
        }

        .rule-card i {
            color: var(--accent-secondary);
            font-size: clamp(1.15rem, 3vw, 1.5rem);
            margin-top: 0.1rem;
        }

        .rule-card h4 {
            color: #ffffff;
            font-size: clamp(0.85rem, 2vw, 1rem);
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .rule-card p {
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Step 2 Schedule View Columns Grid */
        /* schedule-week-grid layout now in css/style.css */

        .day-column {
            background: rgba(17, 24, 39, 0.4);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem;
            min-height: 520px;
            height: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        /* day-column responsive rules now in css/style.css */

        .day-column:hover {
            border-color: rgba(59, 130, 246, 0.2);
            background: rgba(17, 24, 39, 0.55);
        }

        .day-header {
            font-family: var(--font-display);
            font-size: clamp(0.95rem, 2.5vw, 1.15rem);
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .day-sessions {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            flex-grow: 1;
        }

        .class-card {
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1rem;
            text-align: left;
            transition: all 0.25s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: relative;
            gap: 0.4rem;
        }

        .class-card:hover:not(.disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -5px rgba(14, 165, 233, 0.3);
            border-color: var(--accent-primary);
            background: rgba(30, 41, 59, 0.65);
        }

        .class-card.lab {
            background: rgba(16, 185, 129, 0.04);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .class-card.lab:hover:not(.disabled) {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.08);
            box-shadow: 0 8px 20px -5px rgba(16, 185, 129, 0.3);
        }

        /* Disabled Card (Failed Prerequisite) */
        .class-card.disabled {
            opacity: 0.35;
            filter: grayscale(0.85);
            background: rgba(239, 68, 68, 0.03) !important;
            border-color: rgba(239, 68, 68, 0.25) !important;
            cursor: not-allowed;
        }

        .prereq-badge {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #fca5a5 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            font-size: clamp(0.55rem, 1.2vw, 0.58rem) !important;
            padding: 0.15rem 0.35rem;
            border-radius: 4px;
            font-weight: 700;
            margin-top: 0.25rem;
            display: inline-block;
            text-transform: uppercase;
        }

        .class-header {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            font-weight: 700;
            font-size: clamp(0.75rem, 1.8vw, 0.85rem);
            color: #ffffff;
            margin-bottom: 0.2rem;
            gap: 0.5rem;
        }

        .class-header span.tag {
            font-size: clamp(0.55rem, 1.2vw, 0.6rem);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tag.lec {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .tag.lab-type {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .class-desc {
            font-size: clamp(0.7rem, 1.5vw, 0.75rem);
            color: var(--text-muted);
            margin-top: 0.2rem;
            margin-bottom: 0.5rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left;
            line-height: 1.4;
        }

        .class-footer {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            font-size: clamp(0.7rem, 1.5vw, 0.75rem);
            color: var(--text-muted);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 0.5rem;
            margin-top: auto;
            width: 100%;
        }

        .class-footer i {
            margin-right: 0.2rem;
        }

        .class-time {
            font-weight: 600;
            color: #ffffff;
        }

        .vacant-card {
            background: rgba(255, 255, 255, 0.015);
            border: 1px dashed rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 1.25rem 0.8rem;
            text-align: center;
            font-size: clamp(0.65rem, 1.5vw, 0.7rem);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .vacant-card:hover {
            border-color: rgba(59, 130, 246, 0.2);
            background: rgba(59, 130, 246, 0.03);
            color: #ffffff;
        }

        .vacant-card i {
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            opacity: 0.6;
        }

        /* Failed Subjects Table Styles */
        .failed-section-header {
            font-family: var(--font-display);
            font-size: clamp(1rem, 3vw, 1.2rem);
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .failed-table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.01);
            margin-bottom: 2.5rem;
        }

        table.failed-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        table.failed-table th {
            background: rgba(255, 255, 255, 0.03);
            color: #ffffff;
            font-weight: 600;
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.failed-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            color: var(--text-muted);
        }

        table.failed-table tr:hover td {
            background: rgba(255,255,255,0.01);
            color: #ffffff;
        }

        .retake-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: var(--text-muted);
            font-weight: 500;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
        }

        .retake-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent-primary);
        }

        /* Enrollment Summary Section */
        .summary-card {
            background: rgba(14, 165, 233, 0.04);
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 3rem;
        }

        .summary-header {
            font-family: var(--font-display);
            font-size: clamp(0.95rem, 2.5vw, 1.15rem);
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid rgba(14, 165, 233, 0.15);
            padding-bottom: 0.5rem;
        }

        .summary-items {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
        }

        .summary-row.regular {
            border-left: 3px solid var(--accent-primary);
        }

        .summary-row.retake {
            border-left: 3px solid var(--accent-secondary);
            background: rgba(16, 185, 129, 0.03);
            border-color: rgba(16, 185, 129, 0.1) rgba(16, 185, 129, 0.1) rgba(16, 185, 129, 0.1) var(--accent-secondary);
        }

        .summary-actions {
            display: flex;
            gap: 0.5rem;
        }

        .summary-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: clamp(0.7rem, 1.8vw, 0.75rem);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .summary-btn:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.1);
        }

        .summary-btn.remove-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.25);
        }

        .empty-summary {
            color: var(--text-muted);
            font-style: italic;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            padding: 1rem 0;
            text-align: center;
        }

        /* Multi-step Display Toggle */
        .step-panel {
            display: none;
        }

        .step-panel.active {
            display: block;
        }

        /* Bottom Navigation Bar Styles */
        /* .navigation-row - now in css/style.css */

        .nav-btn {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.95rem 1.8rem;
            border-radius: 12px;
            font-family: var(--font-display);
            font-size: clamp(0.85rem, 2vw, 1rem);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.07);
            color: #ffffff;
            border-color: rgba(255,255,255,0.2);
        }

        .nav-btn:focus-visible {
            outline: 3px solid var(--accent-primary);
            outline-offset: 2px;
        }

        .nav-btn.primary-btn {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border: none;
            color: #ffffff;
            box-shadow: 0 4px 12px var(--accent-primary-glow);
        }

        .nav-btn.primary-btn:hover {
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
            filter: brightness(1.1);
        }

        /* Popup Modal Dialog Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 17, 32, 0.85);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            width: 100%;
            max-width: 500px;
            background: #151f32;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        .modal-header {
            font-family: var(--font-display);
            font-size: clamp(1rem, 3vw, 1.25rem);
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .modal-header i {
            color: var(--accent-primary);
        }

        .modal-desc {
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }

        .modal-option-row {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .option-card {
            border: 1px solid var(--card-border);
            background: rgba(255,255,255,0.015);
            border-radius: 10px;
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .option-card:hover:not(.disabled) {
            border-color: var(--accent-primary);
            background: rgba(14, 165, 233, 0.05);
        }

        .option-card.selected {
            border-color: var(--accent-secondary);
            background: rgba(16, 185, 129, 0.05);
        }

        .option-card.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: rgba(255,255,255,0.005);
        }

        .option-radio {
            margin-top: 0.2rem;
            width: 18px;
            height: 18px;
            accent-color: var(--accent-secondary);
        }

        .option-info h5 {
            font-size: clamp(0.8rem, 2.5vw, 0.95rem);
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .option-info p {
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            color: var(--text-muted);
        }

        .modal-controls {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .modal-select-wrapper {
            display: none;
            flex-direction: column;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .modal-select-wrapper.active {
            display: flex;
        }

        .modal-select-wrapper label {
            font-size: clamp(0.7rem, 1.8vw, 0.75rem);
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .modal-select {
            background: #1e293b;
            border: 1px solid var(--card-border);
            color: #ffffff;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            outline: none;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            width: 100%;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 0.8rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 1rem;
        }

        .modal-btn {
            background: none;
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.04);
        }

        .modal-btn.save-btn {
            background: var(--accent-secondary);
            border: none;
            color: #ffffff;
        }

        .modal-btn.save-btn:hover {
            background: #059669;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Alert styling helper */
        .toast-notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: #1e293b;
            border: 1px solid var(--accent-primary);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 10;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .toast-notification.active {
            transform: translateX(0);
        }

        .toast-notification.success {
            border-color: var(--accent-secondary);
        }

        .toast-notification.error {
            border-color: #ef4444;
        }
    </style>
</head>
<body>

    <header class="enrollment-header">
        <div class="logo-container">
            <i class="fa-solid fa-graduation-cap logo-icon"></i>
            <div class="logo-text">
                <h1>Student Portal</h1>
                <p>Enhanced Enrollment System</p>
            </div>
        </div>
        
        <?php if ($student): ?>
            <div class="student-chip">
                <span>Student ID: <strong><?php echo htmlspecialchars($student['student_id']); ?></strong></span>
                <span class="chip-divider">|</span>
                <span>Name: <strong><?php echo htmlspecialchars($student['name']); ?></strong></span>
                <span class="chip-divider">|</span>
                <span>Cohort: <strong><?php echo htmlspecialchars($student['program_code'] . ' - ' . $student['section']); ?></strong></span>
            </div>
        <?php endif; ?>
    </header>

    <main>
        <?php if (!$student): ?>
            <!-- Steps Indicator Progress Bar -->
            <nav class="steps-progress-bar" aria-label="Enrollment Progress">
                <div class="step-indicator active" id="indicator-1">
                    <div class="step-number">1</div>
                    <span>Step 1: Instructions</span>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" id="indicator-2">
                    <div class="step-number">2</div>
                    <span>Step 2: Schedule & Selections</span>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" id="indicator-3">
                    <div class="step-number">3</div>
                    <span>Step 3: Verification & Submit</span>
                </div>
            </nav>
            <div class="panel">
                <div class="panel-title">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Profile Load Error</span>
                </div>
                <p>Unable to retrieve student profile. Please log out and log in again.</p>
                <div class="navigation-row">
                    <a href="index.php?logout=1" class="btn btn-secondary nav-btn"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        <?php elseif ($hasSubmitted && $student['approval_status'] !== 'Rejected'): ?>
            <!-- Locked Status Dashboard -->
            <div class="panel locked-status-panel" style="animation: fadeIn 0.5s ease forwards;">
                <div class="panel-title locked-panel-title">
                    <?php if ($student['approval_status'] === 'Pending'): ?>
                        <i class="fa-solid fa-hourglass-half status-icon" style="color: var(--color-warning);"></i>
                        <span class="status-text">Enrollment Review in Progress</span>
                    <?php elseif ($student['approval_status'] === 'Approved by Dept Head'): ?>
                        <i class="fa-solid fa-square-poll-horizontal status-icon" style="color: var(--color-primary);"></i>
                        <span class="status-text">Department Approved</span>
                    <?php elseif ($student['approval_status'] === 'Enrolled'): ?>
                        <i class="fa-solid fa-circle-check status-icon" style="color: var(--color-secondary);"></i>
                        <span class="status-text">Officially Enrolled</span>
                    <?php endif; ?>
                </div>

                <!-- Horizontal Status Flow Tracker -->
                <div class="status-tracker">
                    <!-- Step 1: Submitted -->
                    <div class="status-step">
                        <div class="status-circle" style="background: var(--color-secondary); color: #ffffff; border: 2px solid var(--color-secondary); box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <span class="status-step-label" style="color: #ffffff;">Submitted</span>
                    </div>

                    <div class="status-connector" style="background: <?php echo ($student['approval_status'] !== 'Pending') ? 'var(--color-secondary)' : 'var(--color-card-border)'; ?>;"></div>

                    <!-- Step 2: Department Head -->
                    <div class="status-step">
                        <?php if ($student['approval_status'] === 'Pending'): ?>
                            <div class="status-circle" style="background: rgba(245, 158, 11, 0.1); color: var(--color-warning); border: 2px solid var(--color-warning); animation: pulseGlow 1.5s infinite;">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            </div>
                            <span class="status-step-label" style="color: var(--color-warning);">Dept Head Review</span>
                        <?php else: ?>
                            <div class="status-circle" style="background: var(--color-secondary); color: #ffffff; border: 2px solid var(--color-secondary); box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="status-step-label" style="color: #ffffff;">Dept Head Approved</span>
                        <?php endif; ?>
                    </div>

                    <div class="status-connector" style="background: <?php echo ($student['approval_status'] === 'Enrolled') ? 'var(--color-secondary)' : 'var(--color-card-border)'; ?>;"></div>

                    <!-- Step 3: Registrar -->
                    <div class="status-step">
                        <?php if ($student['approval_status'] === 'Enrolled'): ?>
                            <div class="status-circle" style="background: var(--color-secondary); color: #ffffff; border: 2px solid var(--color-secondary); box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="status-step-label" style="color: #ffffff;">Enrolled</span>
                        <?php elseif ($student['approval_status'] === 'Approved by Dept Head'): ?>
                            <div class="status-circle" style="background: rgba(14, 165, 233, 0.1); color: var(--color-primary); border: 2px solid var(--color-primary); animation: pulseGlowBlue 1.5s infinite;">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            </div>
                            <span class="status-step-label" style="color: var(--color-primary);">Registrar Verification</span>
                        <?php else: ?>
                            <div class="status-circle" style="background: rgba(255, 255, 255, 0.05); color: var(--color-text-muted); border: 2px solid var(--color-card-border);">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <span class="status-step-label" style="color: var(--color-text-muted);">Registrar Verification</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Selections List -->
                <div class="selections-card">
                    <h3 class="selections-card-title">
                        <i class="fa-solid fa-list-check" style="color: var(--color-primary);"></i>
                        <span>Submitted Subject Selections</span>
                    </h3>

                    <div class="failed-table-container">
                        <table class="selections-table">
                            <thead>
                                <tr>
                                    <th>Subject Code & Description</th>
                                    <th style="text-align: center;">Units</th>
                                    <th style="text-align: right;">Selection Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submittedSelections as $sel): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($sel['subject_code']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?php echo htmlspecialchars($sel['description']); ?></div>
                                        </td>
                                        <td style="text-align: center; font-weight: 600; color: var(--color-primary);">
                                            <?php echo htmlspecialchars($sel['units']); ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if ($sel['status'] === 'Regular'): ?>
                                                <span class="type-badge regular">Regular Cohort</span>
                                            <?php elseif ($sel['status'] === 'Dropped'): ?>
                                                <span class="type-badge dropped">Dropped Class</span>
                                            <?php else: ?>
                                                <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                                    <span class="type-badge retake">Retake Class</span>
                                                    <span style="font-size: 0.7rem; color: var(--color-text-muted); margin-top: 0.2rem;"><?php echo htmlspecialchars($sel['retake_method']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($student['program_code'] === 'BET-00-V' && $student['current_term'] === 'Third Term' && $student['approval_status'] === 'Enrolled'): ?>
                    <div class="glass-card" style="margin-top: 2rem; border-color: var(--color-primary); background: rgba(14, 165, 233, 0.05); text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1rem; animation: fadeIn 0.6s ease; padding: 2rem;">
                        <i class="fa-solid fa-graduation-cap" style="font-size: 3rem; color: var(--color-primary); filter: drop-shadow(0 0 10px var(--color-primary-glow));"></i>
                        <h2 style="font-family: var(--font-display); font-weight: 700; color: #ffffff; margin: 0;">Ready to Advance to Second Year?</h2>
                        <p style="color: var(--color-text-muted); max-width: 600px; margin: 0; font-size: 0.95rem; line-height: 1.6;">
                            You have successfully completed all terms of the <strong>Common First Year (BET-00-V)</strong> curriculum. To proceed with Second Year enrollment, you must now select your specialized engineering technology major.
                        </p>
                        <a href="index.php?page=shifting" class="btn btn-primary" style="padding: 0.8rem 2rem; font-family: var(--font-display); font-weight: 600; display: inline-flex; align-items: center; gap: 0.6rem; text-decoration: none; border-radius: 8px; margin-top: 0.5rem;">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i> Select Major & Shifting Form
                        </a>
                    </div>
                <?php endif; ?>

                <div class="navigation-row" style="justify-content: center;">
                    <a href="index.php?logout=1" class="btn btn-primary nav-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log Out of Portal</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Steps Indicator Progress Bar -->
            <nav class="steps-progress-bar" aria-label="Enrollment Progress">
                <div class="step-indicator active" id="indicator-1">
                    <div class="step-number">1</div>
                    <span>Step 1: Instructions</span>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" id="indicator-2">
                    <div class="step-number">2</div>
                    <span>Step 2: Schedule & Selections</span>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" id="indicator-3">
                    <div class="step-number">3</div>
                    <span>Step 3: Verification & Submit</span>
                </div>
            </nav>

            <!-- Rejection Reason Banner if applicable -->
            <?php if ($student['approval_status'] === 'Rejected' && !empty($rejectionReason)): ?>
                <div class="rejection-banner">
                    <i class="fa-solid fa-circle-exclamation banner-icon"></i>
                    <div style="flex-grow: 1;">
                        <h4>Registration Re-evaluation Required</h4>
                        <p>Your previous enrollment submission was returned with comments. Please review the remarks below, adjust your selections in Step 2, and submit again.</p>
                        <div class="rejection-reason-box">
                            <strong>Comments:</strong> <?php echo htmlspecialchars($rejectionReason); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= STEP 1 PANEL ================= -->
            <div class="step-panel active" id="panel-step-1">
                <div class="panel">
                    <div class="panel-title">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Enrollment Instructions</span>
                    </div>

                    <div class="instruction-list">
                        <div class="instruction-item">
                            <i class="num-badge">1</i>
                            <div class="instruction-text">
                                <h3>Review Your Upcoming Schedule</h3>
                                <p>In Step 2, you will view the pre-assigned class schedule details for your upcoming term (<strong><?php echo htmlspecialchars($upcomingTerm); ?></strong>). Regular subjects are mapped automatically for your cohort/section.</p>
                            </div>
                        </div>

                        <div class="instruction-item">
                            <i class="num-badge">2</i>
                            <div class="instruction-text">
                                <h3>Resolve Failed Prerequisite Blocks</h3>
                                <p>If you failed a prerequisite in previous terms, any subsequent subjects in the upcoming schedule that depend on it will be automatically <strong>disabled</strong> (grayed out) as you are ineligible to take them.</p>
                            </div>
                        </div>

                        <div class="instruction-item">
                            <i class="num-badge">3</i>
                            <div class="instruction-text">
                                <h3>Manage Retakes for Failed Subjects</h3>
                                <p>Any failed subjects in your history will be listed below the schedule. You can select to retake them through available options: **Summer Class** (only during Third Term), **Tutorial Class** (if offered), or **Sit-In Class** in another program (subject to conflict checking).</p>
                            </div>
                        </div>

                        <div class="instruction-item">
                            <i class="num-badge">4</i>
                            <div class="instruction-text">
                                <h3>Verify Your Enrollment Summary</h3>
                                <p>Ensure that your total units and class times are correct. The live summary panel updates in real-time. You can edit your choices or remove retakes before proceeding.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rule-card">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <h4>Important Reminders</h4>
                            <p>All classes operate strictly between Monday and Saturday and end by 6:00 PM. No classes are scheduled during the lunch hour (12:00 PM – 1:00 PM). Ensure your Sit-In choices do not conflict with your core cohort classes.</p>
                        </div>
                    </div>

                    <div class="navigation-row">
                        <a href="index.php?logout=1" class="btn btn-secondary nav-btn" aria-label="Cancel and return to login"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                        <button type="button" class="btn btn-primary nav-btn" onclick="goToStep(2)" aria-label="Proceed to Schedule and Selections Step">
                            <span>Proceed to Step 2</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 2 PANEL ================= -->
            <div class="step-panel" id="panel-step-2">
                <div class="panel">
                    <div class="panel-title">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>Upcoming Term Schedule (<?php echo htmlspecialchars($upcomingTerm); ?>)</span>
                    </div>                    <!-- Weekly Schedule Card Columns Grid -->
                    <div class="schedule-week-grid">
                        <div class="day-column" id="day-Monday">
                            <div class="day-header"><i class="fa-solid fa-calendar-day"></i> Monday</div>
                            <div class="day-sessions" id="sessions-Monday"></div>
                        </div>
                        <div class="day-column" id="day-Tuesday">
                            <div class="day-header"><i class="fa-solid fa-calendar-day"></i> Tuesday</div>
                            <div class="day-sessions" id="sessions-Tuesday"></div>
                        </div>
                        <div class="day-column" id="day-Wednesday">
                            <div class="day-header"><i class="fa-solid fa-calendar-day"></i> Wednesday</div>
                            <div class="day-sessions" id="sessions-Wednesday"></div>
                        </div>
                        <div class="day-column" id="day-Thursday">
                            <div class="day-header"><i class="fa-solid fa-calendar-day"></i> Thursday</div>
                            <div class="day-sessions" id="sessions-Thursday"></div>
                        </div>
                        <div class="day-column" id="day-Friday">
                            <div class="day-header"><i class="fa-solid fa-calendar-day"></i> Friday</div>
                            <div class="day-sessions" id="sessions-Friday"></div>
                        </div>
                        <div class="day-column" id="day-Saturday">
                            <div class="day-header"><i class="fa-solid fa-calendar-day"></i> Saturday</div>
                            <div class="day-sessions" id="sessions-Saturday"></div>
                        </div>
                    </div></div>

                    <!-- Failed Subjects Section -->
                    <div class="failed-section-header">
                        <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>
                        <span>Failed Prerequisite Subjects / Deficiencies</span>
                    </div>

                    <div class="failed-table-container">
                        <table class="failed-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 15%">Subject Code</th>
                                    <th scope="col" style="width: auto;">Description</th>
                                    <th scope="col" style="width: 10%">Units</th>
                                    <th scope="col" style="width: 1%; white-space: nowrap;">Grades Status</th>
                                    <th scope="col" style="width: 15%">Retake Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($failedSubjects)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted); font-style: italic;">
                                            No deficiencies or failed subjects found. You have a clean academic history!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($failedSubjects as $fs): ?>
                                        <tr id="row-failed-<?php echo htmlspecialchars($fs['subject_code']); ?>">
                                            <td style="font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars($fs['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($fs['description']); ?></td>
                                            <td><?php echo htmlspecialchars($fs['units']); ?> u</td>
                                            <td style="white-space: nowrap;">
                                                <span style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.8rem; border: 1px solid rgba(239, 68, 68, 0.2); white-space: nowrap; display: inline-block;">
                                                    Failed (Grade: <?php echo htmlspecialchars($fs['grade']); ?>)
                                                </span>
                                            </td>
                                            <td>
                                                <label class="retake-checkbox-label" for="chk-<?php echo htmlspecialchars($fs['subject_code']); ?>">
                                                    <input type="checkbox" class="retake-checkbox" id="chk-<?php echo htmlspecialchars($fs['subject_code']); ?>" onchange="toggleRetakeSubject('<?php echo htmlspecialchars($fs['subject_code']); ?>', this)">
                                                    <span>Retake</span>
                                                </label>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Enrollment Live Summary Panel -->
                    <div class="summary-card">
                        <div class="summary-header">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Live Enrollment Registration Summary</span>
                        </div>
                        <div class="summary-items" id="summary-items-list">
                            <!-- Populated in JS dynamically -->
                        </div>
                    </div>

                    <!-- Step 2 Navigation Buttons -->
                    <div class="navigation-row">
                        <button type="button" class="btn btn-secondary nav-btn" onclick="goToStep(1)" aria-label="Go back to Step 1 Instructions"><i class="fa-solid fa-arrow-left"></i> Previous Step</button>
                        <a href="index.php?logout=1" class="btn btn-secondary nav-btn" aria-label="Log out and return to login screen"><i class="fa-solid fa-arrow-right-from-bracket"></i> Back to Login</a>
                        <button type="button" class="btn btn-primary nav-btn" onclick="submitStep2()" aria-label="Proceed to Step 3 Finalization">
                            <span>Proceed to Step 3</span>
                            <i class="fa-solid fa-check-double"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 3 PANEL ================= -->
            <div class="step-panel" id="panel-step-3">
                <!-- Stats Header Row -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">Active Load</div>
                        <div class="stat-value" id="stat-active-load">0 / 0 units</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Retake Selected</div>
                        <div class="stat-value" id="stat-retakes-count">0 subjects</div>
                    </div>
                    <div class="stat-card" id="stat-card-locked">
                        <div class="stat-label">Locked Subjects</div>
                        <div class="stat-value" id="stat-locked-count">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Status</div>
                        <div class="stat-value" id="stat-submission-status" style="color: #f59e0b;">Pending submission</div>
                    </div>
                </div>

                <!-- Regular Subjects Panel -->
                <div class="panel">
                    <div class="panel-title panel-title-flex">
                        <div class="title-group">
                            <i class="fa-solid fa-book-bookmark"></i>
                            <span>Regular subjects — upcoming term</span>
                        </div>
                        <span class="badge badge-info">Cohort core class</span>
                    </div>

                    <div class="failed-table-container">
                        <table class="failed-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 45%">Subject</th>
                                    <th scope="col" style="width: 10%">Units</th>
                                    <th scope="col" style="width: 25%">Schedule</th>
                                    <th scope="col" style="width: 20%">Instructor</th>
                                </tr>
                            </thead>
                            <tbody id="table-regular-subjects">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Retakes Panel -->
                <div class="panel">
                    <div class="panel-title panel-title-flex">
                        <div class="title-group">
                            <i class="fa-solid fa-arrow-rotate-right"></i>
                            <span>Retake subject selection</span>
                        </div>
                        <span class="badge badge-warning">Pending approval</span>
                    </div>

                    <div class="failed-table-container">
                        <table class="failed-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 40%">Subject</th>
                                    <th scope="col" style="width: 10%">Units</th>
                                    <th scope="col" style="width: 10%">Grade</th>
                                    <th scope="col" style="width: 20%">Retake type</th>
                                    <th scope="col" style="width: 20%">Section / program</th>
                                </tr>
                            </thead>
                            <tbody id="table-retake-subjects">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Locked Subjects Panel -->
                <div class="panel">
                    <div class="panel-title">
                        <i class="fa-solid fa-lock" style="color: #ef4444;"></i>
                        <span>Locked & dropped subjects</span>
                    </div>

                    <div class="failed-table-container">
                        <table class="failed-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 50%">Subject</th>
                                    <th scope="col" style="width: 50%">Blocked by</th>
                                </tr>
                            </thead>
                            <tbody id="table-locked-subjects">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ISO 25010 Test & Feedback Panel -->
                <div class="panel">
                    <div class="panel-title">
                        <i class="fa-solid fa-file-shield"></i>
                        <span>ISO 25010 test and feedback panel</span>
                    </div>

                    <div class="iso-grid">
                        <div class="iso-card">
                            <strong>Functional suitability</strong>
                            <p>Prerequisite locks, load validation, dropping, and approval flows operate securely based on historical transcripts.</p>
                        </div>
                        <div class="iso-card">
                            <strong>Performance efficiency</strong>
                            <p>Client-side validations, time overlap checks, and live summaries respond instantly to layout adjustments.</p>
                        </div>
                        <div class="iso-card">
                            <strong>Usability</strong>
                            <p>Staff-visible status badges, dynamic blocked schedule tags, and interactive popups guide users step-by-step.</p>
                        </div>
                        <div class="iso-card">
                            <strong>Reliability</strong>
                            <p>Form submission is blocked programmatically while registered load limits or prerequisite approval conflicts persist.</p>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="feedback-notes" class="form-label" style="text-transform: none; letter-spacing: 0; font-size: 1rem; color: #ffffff;">Structured feedback notes</label>
                        <textarea id="feedback-notes" rows="4" class="feedback-textarea" placeholder="Record comments from TUP-Visayas students or staff..."></textarea>
                    </div>
                </div>

                <!-- Navigation buttons -->
                <div class="navigation-row">
                    <button type="button" class="btn btn-secondary nav-btn" onclick="goToStep(2)" id="btn-step3-prev" aria-label="Go back to Step 2"><i class="fa-solid fa-arrow-left"></i> Previous Step</button>
                    <a href="index.php?logout=1" class="btn btn-secondary nav-btn" id="btn-step3-logout" aria-label="Log out and return to login"><i class="fa-solid fa-arrow-right-from-bracket"></i> Back to Login</a>
                    <button type="button" class="btn btn-primary nav-btn" onclick="submitEnrollment()" id="btn-step3-submit" aria-label="Submit Registration">
                        <span>Submit Registration</span>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <!-- Custom Toast Notification -->
            <div id="toast" class="toast">
                <i id="toast-icon" class="fa-solid"></i>
                <div>
                    <strong id="toast-title" style="display: block; font-size: 0.9rem; color: #ffffff;">Notification</strong>
                    <span id="toast-msg" style="font-size: 0.8rem; color: var(--text-muted);">Details here</span>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <!-- Popup Modal Dialog for Retake Options Selection -->
    <div class="modal-overlay" id="retake-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-box">
            <div class="modal-header">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span id="modal-title">Select Retake Option</span>
            </div>
            <p class="modal-desc" id="modal-description">Choose how you would like to retake this failed subject.</p>
            
            <div class="modal-option-row">
                <!-- Option 1: Summer Class -->
                <div class="option-card" id="opt-summer" onclick="selectModalRadio('summer')">
                    <input type="radio" name="retake-method" id="rad-summer" value="Summer Class" class="option-radio">
                    <div class="option-info">
                        <h5>Summer Class Option</h5>
                        <p id="opt-summer-desc">Enrolls you in a fast-track summer session (only available during Third Term).</p>
                    </div>
                </div>

                <!-- Option 2: Tutorial Class -->
                <div class="option-card" id="opt-tutorial" onclick="selectModalRadio('tutorial')">
                    <input type="radio" name="retake-method" id="rad-tutorial" value="Tutorial Class" class="option-radio">
                    <div class="option-info">
                        <h5>Tutorial Class Option</h5>
                        <p id="opt-tutorial-desc">Allows one-on-one or small-group instruction if offered as a tutorial subject.</p>
                    </div>
                </div>

                <!-- Option 3: Sit-In Class -->
                <div class="option-card" id="opt-sitin" onclick="selectModalRadio('sitin')">
                    <input type="radio" name="retake-method" id="rad-sitin" value="Sit-In Class" class="option-radio">
                    <div class="option-info">
                        <h5>Sit-In Class Option</h5>
                        <p id="opt-sitin-desc">Attend classes of the same subject code running in another program/cohort section.</p>
                    </div>
                </div>
            </div>

            <!-- Sit-in section selector dropdown -->
            <div class="modal-select-wrapper" id="sitin-selector-container">
                <label for="sitin-section-select">Available Sections (Non-Conflicting)</label>
                <select id="sitin-section-select" class="modal-select">
                    <!-- Options populated dynamically -->
                </select>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary modal-btn" onclick="closeRetakeModal(false)">Cancel</button>
                <button type="button" class="btn btn-success modal-btn save-btn" onclick="saveRetakeOption()">Confirm Option</button>
            </div>
        </div>
    </div>

    <script>
        // Inject student and schedule parameters from PHP
        const currentStudent = <?php echo json_encode($student); ?>;
        const nextTermSchedule = <?php echo json_encode($schedule); ?>;
        const failedSubjects = <?php echo json_encode($failedSubjects); ?>;
        const disabledSubjectsMap = <?php echo json_encode($disabledSubjects); ?>;
        const otherSchedules = <?php echo $otherSchedulesJson; ?>;
        const coreUnits = <?php echo intval($totalCoreUnits); ?>;
        const maxUnits = <?php echo intval($maxUnitsAllowed); ?>;

        // Keep track of enrolled subjects and chosen retakes
        let selectedRetakes = {}; // key: subject_code, value: { method: string, sectionDetails: string, scheduleId: int/null }
        let droppedCoreSubjects = new Set(); // Set of subject_codes that have been dropped

        // Helper to format times to 12-hour AM/PM format
        function formatTime12Hour(timeStr) {
            if (!timeStr) return '';
            const parts = timeStr.split(':');
            let hour = parseInt(parts[0], 10);
            const minute = parts[1];
            const ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12;
            hour = hour ? hour : 12;
            return `${hour}:${minute} ${ampm}`;
        }

        // Time overlap helper functions
        function timeToMinutes(timeStr) {
            const parts = timeStr.split(':').map(Number);
            return parts[0] * 60 + parts[1];
        }

        function checkTimesOverlap(startA, endA, startB, endB) {
            const sA = timeToMinutes(startA);
            const eA = timeToMinutes(endA);
            const sB = timeToMinutes(startB);
            const eB = timeToMinutes(endB);
            return sA < eB && sB < eA;
        }

        // Check if a Sit-in section conflicts with student's next-term active (non-disabled, non-dropped) schedule items
        function checkConflictWithNextTerm(optionSched) {
            // Get student core schedule items that are not disabled by prerequisite fails and not dropped
            const activeScheduleItems = nextTermSchedule.filter(item => !disabledSubjectsMap[item.subject_code] && !droppedCoreSubjects.has(item.subject_code));
            
            for (const activeItem of activeScheduleItems) {
                if (activeItem.day_of_week === optionSched.day_of_week) {
                    if (checkTimesOverlap(optionSched.start_time, optionSched.end_time, activeItem.start_time, activeItem.end_time)) {
                        return true; // Conflict found
                    }
                }
            }
            return false; // Safe
        }

        function drawEnrollmentSchedule() {
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            days.forEach(day => {
                const container = document.getElementById(`sessions-${day}`);
                if (container) container.innerHTML = '';
            });

            // Group by day and AM/PM
            const dayGroups = {
                'Monday': { am: [], pm: [] },
                'Tuesday': { am: [], pm: [] },
                'Wednesday': { am: [], pm: [] },
                'Thursday': { am: [], pm: [] },
                'Friday': { am: [], pm: [] },
                'Saturday': { am: [], pm: [] }
            };

            nextTermSchedule.forEach(item => {
                const day = item.day_of_week;
                if (dayGroups[day]) {
                    const startHour = parseInt(item.start_time.split(':')[0], 10);
                    if (startHour < 12) {
                        dayGroups[day].am.push(item);
                    } else {
                        dayGroups[day].pm.push(item);
                    }
                }
            });

            days.forEach(day => {
                const container = document.getElementById(`sessions-${day}`);
                if (!container) return;

                const groups = dayGroups[day];
                // Sort chronologically
                groups.am.sort((a, b) => a.start_time.localeCompare(b.start_time));
                groups.pm.sort((a, b) => a.start_time.localeCompare(b.start_time));

                // Helper to render card
                const renderCard = (item) => {
                    const code = item.subject_code;
                    const isLab = item.schedule_type === 'Laboratory';
                    const isDisabled = !!disabledSubjectsMap[code];
                    const isDropped = droppedCoreSubjects.has(code);
                    
                    let cardClass = isLab ? 'class-card lab' : 'class-card';
                    if (isDisabled || isDropped) {
                        cardClass += ' disabled';
                    }

                    const tagClass = isLab ? 'tag lab-type' : 'tag lec';
                    const tagLabel = isLab ? 'LAB' : 'LEC';

                    const isMajor = !['GEC', 'GEE', 'PATHFIT', 'NSTP', 'IP', 'BOSH'].some(prefix => 
                         code.toUpperCase().startsWith(prefix)
                    );
                    
                    // Style matching index.php major badge
                    const majorBadge = isMajor ? '<span class="tag major-tag" style="background: rgba(139, 92, 246, 0.2); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.3);">MAJOR</span>' : '';

                    const startHour = parseInt(item.start_time.split(':')[0], 10);
                    const endHour = parseInt(item.end_time.split(':')[0], 10);
                    const durationHours = endHour - startHour;

                    const formattedStart = formatTime12Hour(item.start_time);
                    const formattedEnd = formatTime12Hour(item.end_time);

                    let disabledOverlay = '';
                    if (isDisabled) {
                        disabledOverlay = `
                            <div style="margin-top: 0.45rem;">
                                <span class="prereq-badge">⚠️ Blocked: Fail ${disabledSubjectsMap[code]}</span>
                            </div>
                        `;
                    } else if (isDropped) {
                        disabledOverlay = `
                            <div style="margin-top: 0.45rem;">
                                <span class="prereq-badge" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border-color: rgba(239, 68, 68, 0.25);">⚠️ Dropped from Load</span>
                            </div>
                        `;
                    }

                    return `
                        <div class="${cardClass}" style="flex-grow: ${durationHours}; flex-basis: 0;">
                            <div class="class-header">
                                <span>${code}</span>
                                <div style="display: flex; gap: 0.3rem;">
                                    ${majorBadge}
                                    <span class="${tagClass}">${tagLabel}</span>
                                </div>
                            </div>
                            <div class="class-desc" title="${item.description}">
                                ${item.description}
                                <div style="margin-top: 0.25rem; font-weight: 600;">Units: ${item.units}</div>
                            </div>
                            <div class="class-footer">
                                <span class="class-time">
                                    <i class="fa-regular fa-clock"></i>${formattedStart} - ${formattedEnd}
                                </span>
                                <span><i class="fa-solid fa-door-open"></i>${item.room}</span>
                            </div>
                            ${disabledOverlay}
                        </div>
                    `;
                };

                // Render AM group
                if (groups.am.length > 0) {
                    groups.am.forEach(item => {
                        container.innerHTML += renderCard(item);
                    });
                } else {
                    container.innerHTML += `
                        <div class="vacant-card" style="flex-grow: 4; flex-basis: 0;">
                            <i class="fa-solid fa-cloud-sun"></i> AM Vacant
                        </div>
                    `;
                }

                // Render PM group
                if (groups.pm.length > 0) {
                    groups.pm.forEach(item => {
                        container.innerHTML += renderCard(item);
                    });
                } else {
                    container.innerHTML += `
                        <div class="vacant-card" style="flex-grow: 5; flex-basis: 0;">
                            <i class="fa-solid fa-moon"></i> PM Vacant
                        </div>
                    `;
                }
            });
        }

        // Navigation between Step Panels
        function goToStep(stepNumber) {
            document.querySelectorAll('.step-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.getElementById(`panel-step-${stepNumber}`).classList.add('active');

            // Update indicators
            document.querySelectorAll('.step-indicator').forEach(ind => {
                ind.classList.remove('active');
            });
            for (let i = 1; i <= stepNumber; i++) {
                document.getElementById(`indicator-${i}`).classList.add('active');
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Failed Subject Table Retake trigger
        let currentModalSubjectCode = '';

        function toggleRetakeSubject(subjectCode, checkbox) {
            if (checkbox.checked) {
                // Open options modal
                openRetakeModal(subjectCode);
            } else {
                // Remove from summary
                delete selectedRetakes[subjectCode];
                updateSummaryList();
            }
        }

        // Modal triggers
        function openRetakeModal(subjectCode) {
            currentModalSubjectCode = subjectCode;
            const subject = failedSubjects.find(s => s.subject_code === subjectCode);
            if (!subject) return;

            document.getElementById('modal-title').textContent = `Retake: ${subject.subject_code} - ${subject.description}`;
            document.getElementById('modal-description').innerHTML = `Select an available method to clear your deficiency in <strong>${subject.subject_code}</strong> (${subject.units} Units).`;

            // Reset modal options state
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected', 'disabled');
                card.querySelector('input[type="radio"]').disabled = false;
                card.querySelector('input[type="radio"]').checked = false;
            });
            document.getElementById('sitin-selector-container').classList.remove('active');

            // Constraint 1: Summer Class is available only if current term is Third Term
            const isThirdTerm = currentStudent.current_term === 'Third Term';
            const optSummer = document.getElementById('opt-summer');
            const radSummer = document.getElementById('rad-summer');
            if (!isThirdTerm) {
                optSummer.classList.add('disabled');
                radSummer.disabled = true;
                document.getElementById('opt-summer-desc').textContent = "Not available (Current term is not Third Term).";
            } else {
                document.getElementById('opt-summer-desc').textContent = "Enrolls you in a fast-track summer session.";
            }

            // Constraint 2: Tutorial Class is available if is_tutorial = 1
            const optTutorial = document.getElementById('opt-tutorial');
            const radTutorial = document.getElementById('rad-tutorial');
            if (parseInt(subject.is_tutorial, 10) !== 1) {
                optTutorial.classList.add('disabled');
                radTutorial.disabled = true;
                document.getElementById('opt-tutorial-desc').textContent = "Not offered as a tutorial for this subject.";
            } else {
                document.getElementById('opt-tutorial-desc').textContent = "Allows tutorial instruction for this deficiency.";
            }

            // Constraint 3: Sit-In Class - offered in other programs and checking conflicts
            const optSitIn = document.getElementById('opt-sitin');
            const radSitIn = document.getElementById('rad-sitin');
            
            // Populate Sit-In sections select
            const selectEl = document.getElementById('sitin-section-select');
            selectEl.innerHTML = '';

            let hasNonConflictingSitIn = false;

            if (isThirdTerm) {
                optSitIn.classList.add('disabled');
                radSitIn.disabled = true;
                document.getElementById('opt-sitin-desc').textContent = "Not available (No regular classes offered in other programs during the Summer Term).";
            } else {
                const matchingSchedules = otherSchedules.filter(s => s.subject_code === subjectCode);

                if (matchingSchedules.length === 0) {
                    optSitIn.classList.add('disabled');
                    radSitIn.disabled = true;
                    document.getElementById('opt-sitin-desc').textContent = "No sections of other programs are offering this subject.";
                } else {
                    matchingSchedules.forEach(item => {
                        const hasConflict = checkConflictWithNextTerm(item);
                        
                        const optionOpt = document.createElement('option');
                        optionOpt.value = item.id;
                        
                        const timeStr = `${formatTime12Hour(item.start_time)} - ${formatTime12Hour(item.end_time)}`;
                        optionOpt.textContent = `[${item.program_code} ${item.section_name}] - ${item.day_of_week} ${timeStr} (${item.room})`;
                        
                        hasNonConflictingSitIn = true;
                        selectEl.appendChild(optionOpt);
                    });

                    if (!hasNonConflictingSitIn) {
                        optSitIn.classList.add('disabled');
                        radSitIn.disabled = true;
                        document.getElementById('opt-sitin-desc').textContent = "Schedules exist in other programs, but all conflict with your next-term classes.";
                    } else {
                        document.getElementById('opt-sitin-desc').textContent = "Attend this subject running under another program section.";
                    }
                }
            }

            // If we are editing, restore previous selection in modal
            if (selectedRetakes[subjectCode]) {
                const prev = selectedRetakes[subjectCode];
                if (prev.method === 'Summer Class' && isThirdTerm) {
                    radSummer.checked = true;
                    optSummer.classList.add('selected');
                } else if (prev.method === 'Tutorial Class' && parseInt(subject.is_tutorial, 10) === 1) {
                    radTutorial.checked = true;
                    optTutorial.classList.add('selected');
                } else if (prev.method === 'Sit-In Class' && hasNonConflictingSitIn) {
                    radSitIn.checked = true;
                    optSitIn.classList.add('selected');
                    document.getElementById('sitin-selector-container').classList.add('active');
                    if (prev.scheduleId) {
                        selectEl.value = prev.scheduleId;
                    }
                }
            }

            // Display Modal Overlay
            document.getElementById('retake-modal').classList.add('active');
        }

        // Close retake modal
        function closeRetakeModal(isSaving = false) {
            document.getElementById('retake-modal').classList.remove('active');
            if (!isSaving && currentModalSubjectCode) {
                // If cancel and not previously saved, uncheck the row checkbox
                if (!selectedRetakes[currentModalSubjectCode]) {
                    const chk = document.getElementById(`chk-${currentModalSubjectCode}`);
                    if (chk) chk.checked = false;
                }
            }
            currentModalSubjectCode = '';
        }

        // Select modal radio handler
        function selectModalRadio(methodType) {
            const card = document.getElementById(`opt-${methodType}`);
            if (card.classList.contains('disabled')) return;

            // Unselect others
            document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const radio = document.getElementById(`rad-${methodType}`);
            radio.checked = true;

            // Show Sit-in select dropdown if sitin is selected
            if (methodType === 'sitin') {
                document.getElementById('sitin-selector-container').classList.add('active');
            } else {
                document.getElementById('sitin-selector-container').classList.remove('active');
            }
        }

        // Save Modal Selection
        function saveRetakeOption() {
            const selectedRadio = document.querySelector('input[name="retake-method"]:checked');
            if (!selectedRadio) {
                alert('Please select an enrollment retake method or cancel.');
                return;
            }

            const method = selectedRadio.value;
            let sectionDetails = '';
            let scheduleId = null;

            if (method === 'Sit-In Class') {
                const selectEl = document.getElementById('sitin-section-select');
                if (!selectEl.value) {
                    alert('Please select an available sit-in section class.');
                    return;
                }
                
                const chosenOption = selectEl.options[selectEl.selectedIndex];
                sectionDetails = chosenOption.textContent;
                scheduleId = selectEl.value;
            }

            // Add or update selection
            selectedRetakes[currentModalSubjectCode] = {
                method: method,
                sectionDetails: sectionDetails,
                scheduleId: scheduleId
            };

            updateSummaryList();
            closeRetakeModal(true);
        }

        // Edit summary option
        function editSummaryRetake(subjectCode) {
            openRetakeModal(subjectCode);
        }

        // Remove summary option
        function removeSummaryRetake(subjectCode) {
            delete selectedRetakes[subjectCode];
            const chk = document.getElementById(`chk-${subjectCode}`);
            if (chk) chk.checked = false;
            updateSummaryList();
        }

        // Drop core regular subject
        function dropCoreSubject(subjectCode) {
            droppedCoreSubjects.add(subjectCode);
            updateSummaryList();
            drawEnrollmentSchedule();
            showToast('Subject Dropped', `${subjectCode} has been dropped from your upcoming semester load.`, 'info');
        }

        // Restore dropped core subject
        function undropCoreSubject(subjectCode) {
            droppedCoreSubjects.delete(subjectCode);
            updateSummaryList();
            drawEnrollmentSchedule();
            showToast('Subject Restored', `${subjectCode} has been restored to your upcoming semester load.`, 'success');
        }

        // Render live enrollment summary items list
        function updateSummaryList() {
            const listContainer = document.getElementById('summary-items-list');
            listContainer.innerHTML = '';

            let hasItems = false;
            let totalUnits = 0;

            // 1. Render core regular classes (active next term schedule)
            // Group next-term schedule items by subject code (avoid duplicates for lecture/lab)
            const uniqueCoreSubjects = {};
            nextTermSchedule.forEach(item => {
                const code = item.subject_code;
                const isDisabled = !!disabledSubjectsMap[code];
                if (!uniqueCoreSubjects[code] && !isDisabled) {
                    uniqueCoreSubjects[code] = {
                        description: item.description,
                        units: parseInt(item.units, 10)
                    };
                }
            });

            Object.keys(uniqueCoreSubjects).forEach(code => {
                hasItems = true;
                const subject = uniqueCoreSubjects[code];
                const isDropped = droppedCoreSubjects.has(code);

                if (!isDropped) {
                    totalUnits += subject.units;
                    listContainer.innerHTML += `
                        <div class="summary-row regular">
                            <div>
                                <span style="font-weight: 700; color: #ffffff;">${code}</span> - 
                                <span style="color: var(--text-muted);">${subject.description}</span>
                                <span style="color: var(--accent-primary); margin-left: 0.5rem; font-weight:600;">(Regular - ${subject.units} u)</span>
                            </div>
                            <div class="summary-actions">
                                <button type="button" class="btn btn-danger btn-sm summary-btn remove-btn" onclick="dropCoreSubject('${code}')" aria-label="Drop ${code} from enrollment schedule"><i class="fa-solid fa-minus-circle"></i> Drop</button>
                            </div>
                        </div>
                    `;
                } else {
                    listContainer.innerHTML += `
                        <div class="summary-row regular dropped" style="opacity: 0.55; border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05); padding: 0.6rem 0.8rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; border: 1px solid rgba(239,68,68,0.2);">
                            <div>
                                <span style="font-weight: 700; color: #ffffff; text-decoration: line-through;">${code}</span> - 
                                <span style="color: var(--text-muted); text-decoration: line-through;">${subject.description}</span>
                                <span style="color: #ef4444; margin-left: 0.5rem; font-weight:600;">(Dropped - ${subject.units} u)</span>
                            </div>
                            <div class="summary-actions">
                                <button type="button" class="btn btn-secondary btn-sm summary-btn" onclick="undropCoreSubject('${code}')" style="border-color: var(--accent-secondary); color: var(--accent-secondary);" aria-label="Restore ${code}"><i class="fa-solid fa-plus-circle"></i> Restore</button>
                            </div>
                        </div>
                    `;
                }
            });

            // 2. Render chosen retakes
            Object.keys(selectedRetakes).forEach(code => {
                hasItems = true;
                const retake = selectedRetakes[code];
                const origSubject = failedSubjects.find(s => s.subject_code === code);
                const desc = origSubject ? origSubject.description : '';
                const units = origSubject ? origSubject.units : 2;
                totalUnits += parseInt(units, 10);

                let methodText = retake.method;
                if (retake.method === 'Sit-In Class') {
                    methodText += ` (${retake.sectionDetails})`;
                }

                listContainer.innerHTML += `
                    <div class="summary-row retake">
                        <div>
                            <span style="font-weight: 700; color: #ffffff;">${code}</span> - 
                            <span style="color: var(--text-muted);">${desc}</span>
                            <span style="color: var(--accent-secondary); margin-left: 0.5rem; font-weight:600;">(Retake - ${units} u)</span>
                            <div style="font-size: 0.75rem; color: #34d399; margin-top: 0.25rem;">
                                <i class="fa-solid fa-arrow-rotate-right"></i> Method: ${methodText}
                            </div>
                        </div>
                        <div class="summary-actions">
                            <button type="button" class="btn btn-secondary btn-sm summary-btn" onclick="editSummaryRetake('${code}')" aria-label="Edit retake options for ${code}"><i class="fa-solid fa-pen"></i> Edit</button>
                            <button type="button" class="btn btn-danger btn-sm summary-btn remove-btn" onclick="removeSummaryRetake('${code}')" aria-label="Remove ${code} from retake schedule"><i class="fa-solid fa-trash-can"></i> Remove</button>
                        </div>
                    </div>
                `;
            });

            if (!hasItems) {
                listContainer.innerHTML = '<div class="empty-summary">No subjects selected for enrollment.</div>';
            } else {
                const isOverLimit = totalUnits > maxUnits;
                const unitColor = isOverLimit ? '#ef4444' : 'var(--accent-primary)';
                const warningMsg = isOverLimit ? `<div style="color: #ef4444; font-size: 0.8rem; font-weight: 600; text-align: right; margin-top: 0.25rem;"><i class="fa-solid fa-triangle-exclamation"></i> Over credit limit! Max ${maxUnits} units allowed.</div>` : '';

                listContainer.innerHTML += `
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed rgba(14, 165, 233, 0.2);">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 1.1rem; color: #ffffff;">
                            <span>Total Registered Units:</span>
                            <span style="color: ${unitColor}; font-size: 1.25rem;">${totalUnits} / ${maxUnits} Units</span>
                        </div>
                        ${warningMsg}
                    </div>
                `;
            }
        }

        // Aggregates schedules by time key and formats them like M 8:00-9:00 AM, MWF 10:00-11:00 AM, etc.
        function formatSubjectScheduleString(subjectCode) {
            const rows = nextTermSchedule.filter(item => item.subject_code === subjectCode);
            if (!rows || rows.length === 0) return 'No schedule';
            
            const dayMap = {
                'Monday': 'M',
                'Tuesday': 'T',
                'Wednesday': 'W',
                'Thursday': 'Th',
                'Friday': 'F',
                'Saturday': 'S'
            };
            
            const timeGroups = {};
            rows.forEach(item => {
                const timeKey = `${item.start_time}-${item.end_time}`;
                if (!timeGroups[timeKey]) {
                    timeGroups[timeKey] = {
                        days: [],
                        start: item.start_time,
                        end: item.end_time
                    };
                }
                if (!timeGroups[timeKey].days.includes(item.day_of_week)) {
                    timeGroups[timeKey].days.push(item.day_of_week);
                }
            });

            const parts = Object.values(timeGroups).map(group => {
                const weekOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                group.days.sort((a, b) => weekOrder.indexOf(a) - weekOrder.indexOf(b));
                
                const dayStr = group.days.map(d => dayMap[d] || d).join('');
                
                // Format times to short form e.g. 9:00-10:30 AM
                const formatTimeShort = (timeStr) => {
                    if (!timeStr) return '';
                    const parts = timeStr.split(':');
                    let hour = parseInt(parts[0], 10);
                    const minute = parts[1];
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    hour = hour % 12;
                    hour = hour ? hour : 12;
                    return `${hour}:${minute}`;
                };
                
                const startFormatted = formatTimeShort(group.start);
                const endFormatted = formatTimeShort(group.end);
                const ampm = parseInt(group.end.split(':')[0], 10) >= 12 ? 'PM' : 'AM';
                
                return `${dayStr} ${startFormatted}-${endFormatted} ${ampm}`;
            });

            return parts.join(', ');
        }

        // Mappings for instructor names matching mockup precisely
        function getInstructorForSubject(code) {
            const instructors = {
                'PEM122-V': 'Engr. Bautista',
                'COMP122-V': 'Engr. Navarro',
                'WSTP1-V': 'Prof. Villanueva',
                'PATHFIT2-V': 'Engr. Ramos',
                'GEE1-V': 'Prof. Villanueva',
                'ELECT122-V': 'Engr. Bautista',
                'MATH1-V': 'Prof. Villanueva',
                'MATH3-V': 'Engr. Navarro',
                'PHYTECH124-V': 'Engr. Ramos',
                'CHEM114-V': 'Prof. Villanueva',
                'DRAW111-V': 'Engr. Ramos',
                'COMP112-V': 'Engr. Navarro'
            };
            if (instructors[code]) return instructors[code];
            
            const defaults = ['Engr. Bautista', 'Engr. Navarro', 'Prof. Villanueva', 'Engr. Ramos'];
            let hash = 0;
            for (let i = 0; i < code.length; i++) {
                hash = code.charCodeAt(i) + ((hash << 5) - hash);
            }
            return defaults[Math.abs(hash) % defaults.length];
        }

        // Toast notification trigger
        function showToast(title, message, type = 'info') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const titleEl = document.getElementById('toast-title');
            const msgEl = document.getElementById('toast-msg');

            titleEl.textContent = title;
            msgEl.textContent = message;
            
            toast.className = 'toast-notification';
            icon.className = 'fa-solid';

            if (type === 'success') {
                toast.classList.add('success');
                icon.classList.add('fa-circle-check');
                icon.style.color = '#10b981';
            } else if (type === 'error') {
                toast.classList.add('error');
                icon.classList.add('fa-circle-xmark');
                icon.style.color = '#ef4444';
            } else {
                icon.classList.add('fa-info-circle');
                icon.style.color = '#0ea5e9';
            }

            toast.classList.add('active');

            setTimeout(() => {
                toast.classList.remove('active');
            }, 4000);
        }

        // Proceed Step 2 Submission to dynamic Step 3 Panel
        function submitStep2() {
            // Calculate total units dynamically, excluding dropped core subjects and including selected retakes
            let totalUnits = 0;
            
            // 1. Core regular subjects units (not blocked and not dropped)
            const uniqueCoreSubjects = {};
            nextTermSchedule.forEach(item => {
                const code = item.subject_code;
                const isDisabled = !!disabledSubjectsMap[code];
                const isDropped = droppedCoreSubjects.has(code);
                if (!uniqueCoreSubjects[code] && !isDisabled && !isDropped) {
                    uniqueCoreSubjects[code] = {
                        description: item.description,
                        units: parseInt(item.units, 10)
                    };
                }
            });
            
            Object.values(uniqueCoreSubjects).forEach(sub => {
                totalUnits += sub.units;
            });

            // 2. Add retake subjects units
            Object.keys(selectedRetakes).forEach(code => {
                const origSubject = failedSubjects.find(s => s.subject_code === code);
                const units = origSubject ? origSubject.units : 2;
                totalUnits += parseInt(units, 10);
            });

            if (totalUnits > maxUnits) {
                alert(`❌ Cannot proceed: Your registered load of ${totalUnits} units exceeds the maximum limit of ${maxUnits} units. Please remove some retake subjects or drop regular subjects.`);
                return;
            }

            // Populate Step 3 Stats Row
            const retakeCount = Object.keys(selectedRetakes).length;
            const lockedCount = Object.keys(disabledSubjectsMap).length;
            
            document.getElementById('stat-active-load').textContent = `${totalUnits} / ${maxUnits} units`;
            document.getElementById('stat-retakes-count').textContent = `${retakeCount} subject${retakeCount !== 1 ? 's' : ''}`;
            document.getElementById('stat-locked-count').textContent = lockedCount;
            
            const lockedCard = document.getElementById('stat-card-locked');
            if (lockedCount > 0) {
                lockedCard.style.borderColor = '#ef4444';
                lockedCard.style.background = 'rgba(239, 68, 68, 0.05)';
                document.getElementById('stat-locked-count').style.color = '#fca5a5';
            } else {
                lockedCard.style.borderColor = 'var(--card-border)';
                lockedCard.style.background = 'rgba(22, 30, 49, 0.7)';
                document.getElementById('stat-locked-count').style.color = '#ffffff';
            }

            // Reset submission status on back-navigation
            document.getElementById('stat-submission-status').textContent = 'Pending submission';
            document.getElementById('stat-submission-status').style.color = '#f59e0b';

            // Populate regular subjects table
            const tableRegular = document.getElementById('table-regular-subjects');
            tableRegular.innerHTML = '';
            
            // Get unique next-term core subject codes (not blocked, not dropped)
            const activeCoreSubjects = {};
            nextTermSchedule.forEach(item => {
                const code = item.subject_code;
                const isDisabled = !!disabledSubjectsMap[code];
                const isDropped = droppedCoreSubjects.has(code);
                if (!activeCoreSubjects[code] && !isDisabled && !isDropped) {
                    activeCoreSubjects[code] = {
                        description: item.description,
                        units: item.units
                    };
                }
            });

            const uniqueCoreCodes = Object.keys(activeCoreSubjects);
            if (uniqueCoreCodes.length === 0) {
                tableRegular.innerHTML = '<tr><td colspan="4" style="text-align: center; font-style: italic; color: var(--text-muted);">No regular subjects scheduled.</td></tr>';
            } else {
                uniqueCoreCodes.forEach(code => {
                    const subject = activeCoreSubjects[code];
                    const schedStr = formatSubjectScheduleString(code);
                    const instructor = getInstructorForSubject(code);
                    tableRegular.innerHTML += `
                        <tr>
                            <td style="color: #ffffff; font-weight: 600;">${code} — <span style="font-weight: 400; color: var(--text-muted);">${subject.description}</span></td>
                            <td>${subject.units} u</td>
                            <td>${schedStr}</td>
                            <td>${instructor}</td>
                        </tr>
                    `;
                });
            }

            // Populate retake selection table
            const tableRetake = document.getElementById('table-retake-subjects');
            tableRetake.innerHTML = '';
            const retakeKeys = Object.keys(selectedRetakes);
            
            if (retakeKeys.length === 0) {
                tableRetake.innerHTML = '<tr><td colspan="5" style="text-align: center; font-style: italic; color: var(--text-muted);">No retake subjects selected.</td></tr>';
            } else {
                retakeKeys.forEach(code => {
                    const retake = selectedRetakes[code];
                    const origSubject = failedSubjects.find(s => s.subject_code === code);
                    const desc = origSubject ? origSubject.description : '';
                    const units = origSubject ? origSubject.units : 2;
                    const grade = origSubject ? origSubject.grade : '3.0';

                    let cohortStr = currentStudent.program_code + ' — ' + currentStudent.section;
                    if (retake.method === 'Sit-In Class' && retake.sectionDetails) {
                        const match = retake.sectionDetails.match(/\[(.*?)\]/);
                        if (match && match[1]) {
                            cohortStr = match[1].replace(' ', ' — ');
                        }
                    }

                    tableRetake.innerHTML += `
                        <tr style="background: rgba(245, 158, 11, 0.04);">
                            <td style="color: #ffffff; font-weight: 600;">${code} — <span style="font-weight: 400; color: var(--text-muted);">${desc}</span></td>
                            <td>${units} u</td>
                            <td style="color: #fca5a5; font-weight: 700;">${grade}</td>
                            <td style="color: #fbbf24; font-weight: 600;">${retake.method}</td>
                            <td>${cohortStr}</td>
                        </tr>
                    `;
                });
            }

            // Populate locked / dropped subjects table
            const tableLocked = document.getElementById('table-locked-subjects');
            tableLocked.innerHTML = '';
            
            const lockedKeys = Object.keys(disabledSubjectsMap);
            const droppedKeys = Array.from(droppedCoreSubjects);
            
            if (lockedKeys.length === 0 && droppedKeys.length === 0) {
                tableLocked.innerHTML = '<tr><td colspan="2" style="text-align: center; font-style: italic; color: var(--text-muted);">No locked or dropped subjects. All core prerequisites are satisfied.</td></tr>';
            } else {
                // Show prerequisite locked subjects
                lockedKeys.forEach(code => {
                    const schedItem = nextTermSchedule.find(item => item.subject_code === code);
                    const desc = schedItem ? schedItem.description : 'Prerequisite Requirement Blocked';
                    tableLocked.innerHTML += `
                        <tr style="background: rgba(239, 68, 68, 0.02); border-left: 3px solid #ef4444;">
                            <td style="color: var(--text-muted); font-weight: 600;">${code} — <span style="font-weight: 400;">${desc}</span></td>
                            <td style="color: #fca5a5; font-weight: 600;"><i class="fa-solid fa-triangle-exclamation"></i> Failed prerequisite: ${disabledSubjectsMap[code]}</td>
                        </tr>
                    `;
                });

                // Show student dropped subjects
                droppedKeys.forEach(code => {
                    const schedItem = nextTermSchedule.find(item => item.subject_code === code);
                    const desc = schedItem ? schedItem.description : 'Dropped subject';
                    tableLocked.innerHTML += `
                        <tr style="background: rgba(239, 68, 68, 0.02); border-left: 3px solid #ef4444;">
                            <td style="color: var(--text-muted); font-weight: 600;">${code} — <span style="font-weight: 400;">${desc}</span></td>
                            <td style="color: #fca5a5; font-weight: 600;"><i class="fa-solid fa-minus-circle"></i> Dropped by student</td>
                        </tr>
                    `;
                });
            }

            // Switch UI panel to Step 3 review
            goToStep(3);
        }

        // Final Enrollment Submission Flow
        async function submitEnrollment() {
            if (!confirm("Are you sure you want to submit your final enrollment registration for validation?")) {
                return;
            }

            const submitBtn = document.getElementById('btn-step3-submit');
            submitBtn.disabled = true;
            submitBtn.querySelector('span').textContent = 'Submitting...';

            const payload = {
                dropped_subjects: Array.from(droppedCoreSubjects),
                retake_selections: selectedRetakes
            };

            try {
                const response = await fetch('php/api/submit_enrollment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                    credentials: 'include',
                    cache: 'no-store'
                });

                const result = await response.json();

                if (result.success) {
                    // Update status in stats row
                    const statusEl = document.getElementById('stat-submission-status');
                    statusEl.textContent = 'Pending Review';
                    statusEl.style.color = '#f59e0b';

                    // Show Toast Success notification
                    showToast('Success', result.message, 'success');

                    // Disable controls
                    document.getElementById('feedback-notes').disabled = true;
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                    document.getElementById('btn-step3-prev').disabled = true;
                    document.getElementById('btn-step3-prev').style.opacity = '0.5';
                    document.getElementById('btn-step3-prev').style.cursor = 'not-allowed';
                    
                    // Also disable Step 2 retake checkboxes to prevent tampering
                    document.querySelectorAll('.retake-checkbox').forEach(chk => {
                        chk.disabled = true;
                    });

                    // Redirect back to login screen after 2.5 seconds
                    setTimeout(() => {
                        alert("🎉 Enrollment registration successfully saved!\n\nYou will now be redirected to the student portal login page.");
                        window.location.href = 'index.php?logout=1';
                    }, 2500);
                } else {
                    showToast('Submission Failed', result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.querySelector('span').textContent = 'Submit Registration';
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Unable to reach the submission service.', 'error');
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Submit Registration';
            }
        }

        // Initialize display summary on load
        window.addEventListener('DOMContentLoaded', () => {
            updateSummaryList();
            drawEnrollmentSchedule();
        });
    </script>
</body>
</html>
