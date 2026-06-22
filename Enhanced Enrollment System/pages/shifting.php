<?php
/**
 * Student Major Shifting Portal (First Year -> Second Year)
 * Allows students in BET-00-V to shift to a specialized major.
 * Dynamic 5-question aptitude questionnaire is rendered on program select.
 */

require_once dirname(__DIR__) . '/php/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$studentId = $_SESSION['student_id'];
$dbClass = new Database();
$conn = $dbClass->getConnection();

$student = null;
$activeRequest = null;
$deficiencies = [];
$availablePrograms = [];

if ($conn) {
    try {
        // Fetch student details
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id LIMIT 1");
        $stmt->execute([':student_id' => $studentId]);
        $student = $stmt->fetch();

        // Fetch active shifting request
        $stmtReq = $conn->prepare("
            SELECT r.*, p.program_name as target_program_name 
            FROM shifting_requests r
            JOIN programs p ON r.target_program_code = p.program_code
            WHERE r.student_id = :student_id
            ORDER BY r.created_at DESC LIMIT 1
        ");
        $stmtReq->execute([':student_id' => $studentId]);
        $activeRequest = $stmtReq->fetch();

        // Fetch deficiencies (failed subjects from student_subject_history)
        $stmtDef = $conn->prepare("
            SELECT h.*, s.description, s.units 
            FROM student_subject_history h
            JOIN subjects s ON h.subject_code = s.subject_code
            WHERE h.student_id = :student_id AND h.status = 'Failed'
        ");
        $stmtDef->execute([':student_id' => $studentId]);
        $deficiencies = $stmtDef->fetchAll();

        // Fetch target programs
        $stmtProg = $conn->query("SELECT * FROM programs WHERE program_code != 'BET-00-V' ORDER BY program_code");
        $availablePrograms = $stmtProg->fetchAll();

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
    <title>Major Shifting Portal - Enhanced Enrollment System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">

    <style>
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

        body {
            background-image: 
                radial-gradient(at 10% 20%, rgba(14, 165, 233, 0.1) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            padding-bottom: 5rem;
        }

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

        /* Program Cards Selection Grid */
        .program-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .program-card {
            background: rgba(22, 30, 49, 0.45);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .program-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: transparent;
            transition: background 0.3s;
        }

        .program-card:hover {
            transform: translateY(-4px);
            background: rgba(22, 30, 49, 0.75);
            border-color: rgba(14, 165, 233, 0.3);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }

        .program-card.selected {
            border-color: var(--accent-primary);
            background: rgba(14, 165, 233, 0.08);
            box-shadow: 0 0 20px -5px var(--accent-primary-glow);
        }

        .program-card.selected::before {
            background: var(--accent-primary);
        }

        .program-code-badge {
            font-family: var(--font-display);
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            background: rgba(14, 165, 233, 0.15);
            color: var(--accent-primary);
            border-radius: 6px;
            width: fit-content;
            text-transform: uppercase;
        }

        .program-card.selected .program-code-badge {
            background: var(--accent-primary);
            color: #ffffff;
        }

        .program-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.05rem;
            color: #ffffff;
            line-height: 1.4;
        }

        /* Shifting Questionnaire */
        .test-section {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: none; /* Shown dynamically on selection */
            animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .test-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.3rem;
            color: #ffffff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 0.75rem;
        }

        .test-title i {
            color: var(--accent-primary);
        }

        .question-group {
            margin-bottom: 1.50rem;
        }

        .question-label {
            display: block;
            font-weight: 500;
            color: #ffffff;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        /* Scale rating buttons (1-5) */
        .rating-scale-container {
            display: flex;
            gap: 0.75rem;
            max-width: 400px;
        }

        .rating-btn {
            flex: 1;
            position: relative;
        }

        .rating-btn input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }

        .rating-btn label {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 44px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            color: var(--text-muted);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .rating-btn label:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
            color: #ffffff;
        }

        .rating-btn input[type="radio"]:checked + label {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: #ffffff;
            box-shadow: 0 0 10px var(--accent-primary-glow);
        }

        .aptitude-textarea {
            width: 100%;
            height: 90px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #ffffff;
            font-family: inherit;
            font-size: 0.9rem;
            resize: none;
            transition: all 0.3s ease;
        }

        .aptitude-textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 8px var(--accent-primary-glow);
        }

        /* Timeline and Status View */
        .timeline-container {
            position: relative;
            padding-left: 2.5rem;
            margin: 2rem 0;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            left: 11px; top: 0; width: 2px; height: 100%;
            background: var(--card-border);
            z-index: 1;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-icon {
            position: absolute;
            left: -2.5rem;
            top: 2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            z-index: 2;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .timeline-item.active .timeline-icon {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            box-shadow: 0 0 8px var(--accent-primary-glow);
        }

        .timeline-item.completed .timeline-icon {
            border-color: var(--accent-secondary);
            color: var(--accent-secondary);
            background: rgba(16, 185, 129, 0.1);
        }

        .timeline-item.rejected .timeline-icon {
            border-color: var(--color-danger);
            color: var(--color-danger);
            background: rgba(239, 68, 68, 0.1);
        }

        .timeline-content {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1rem 1.5rem;
        }

        .timeline-title {
            font-family: var(--font-display);
            font-weight: 700;
            color: #ffffff;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .timeline-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>

    <header class="enrollment-header">
        <div class="logo-container">
            <i class="fa-solid fa-arrow-right-arrow-left logo-icon"></i>
            <div class="logo-text">
                <h1>Major Shifting Portal</h1>
                <p>Common First Year → Specialized 2nd Year</p>
            </div>
        </div>
        
        <?php if ($student): ?>
            <div class="student-chip">
                <span>Student ID: <strong><?php echo htmlspecialchars($student['student_id']); ?></strong></span>
                <span class="chip-divider">|</span>
                <span>Name: <strong><?php echo htmlspecialchars($student['name']); ?></strong></span>
            </div>
        <?php endif; ?>
    </header>

    <main>
        
        <?php if ($activeRequest): ?>
            <!-- ACTIVE SHIFTING REQUEST VIEW -->
            <div class="glass-card" style="animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
                <h2 style="font-family: var(--font-display); font-weight: 800; color: #ffffff; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-circle-nodes" style="color: var(--accent-primary);"></i>
                    Shifting Request Status
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
                    Your request to shift from the Common First Year into a specialized major is being processed.
                </p>

                <!-- Status Progress Flow -->
                <div class="timeline-container">
                    
                    <!-- Step 1: Submission -->
                    <div class="timeline-item completed">
                        <div class="timeline-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Request Submitted</div>
                            <div class="timeline-time"><?php echo date('M d, Y h:i A', strtotime($activeRequest['created_at'])); ?></div>
                            <div class="timeline-desc">
                                Shifting application submitted for program: <strong><?php echo htmlspecialchars($activeRequest['target_program_name'] . ' (' . $activeRequest['target_program_code'] . ')'); ?></strong>.
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Department Head Review -->
                    <?php 
                        $deptClass = '';
                        $deptDesc = 'Pending academic evaluation and screening by the target program\'s Department Head.';
                        if ($activeRequest['status'] === 'Pending Dept Head') {
                            $deptClass = 'active';
                        } elseif ($activeRequest['status'] === 'Approved by Dept Head' || $activeRequest['status'] === 'Approved') {
                            $deptClass = 'completed';
                            $deptDesc = 'Approved by the Department Head. Awaiting final Registrar enrollment confirmation.';
                        } elseif ($activeRequest['status'] === 'Rejected') {
                            $deptClass = 'rejected';
                            $deptDesc = 'Rejected by the Department Head. Reason: <strong style="color: #fca5a5;">' . htmlspecialchars($activeRequest['rejection_reason']) . '</strong>';
                        }
                    ?>
                    <div class="timeline-item <?php echo $deptClass; ?>">
                        <div class="timeline-icon">
                            <?php if ($deptClass === 'completed'): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php elseif ($deptClass === 'active'): ?>
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            <?php elseif ($deptClass === 'rejected'): ?>
                                <i class="fa-solid fa-xmark"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Department Head Screening</div>
                            <div class="timeline-desc"><?php echo $deptDesc; ?></div>
                        </div>
                    </div>

                    <!-- Step 3: Registrar Final Verification -->
                    <?php 
                        $regClass = '';
                        $regDesc = 'Pending final verification and program change database updates.';
                        if ($activeRequest['status'] === 'Approved by Dept Head') {
                            $regClass = 'active';
                        } elseif ($activeRequest['status'] === 'Approved') {
                            $regClass = 'completed';
                            $regDesc = 'Program shifted successfully! You are now enrolled in your target major and section: <strong>' . htmlspecialchars($activeRequest['target_section']) . '</strong>.';
                        }
                    ?>
                    <div class="timeline-item <?php echo $regClass; ?>">
                        <div class="timeline-icon">
                            <?php if ($regClass === 'completed'): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php elseif ($regClass === 'active'): ?>
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Registrar Confirmation</div>
                            <div class="timeline-desc"><?php echo $regDesc; ?></div>
                        </div>
                    </div>

                </div>

                <div class="navigation-row" style="margin-top: 2rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <a href="index.php?page=enrollment" class="btn btn-secondary nav-btn"><i class="fa-solid fa-house"></i> Back to Enrollment Portal</a>
                    
                    <?php if ($activeRequest['status'] === 'Rejected'): ?>
                        <button type="button" class="btn btn-primary" onclick="resetRejectedRequest()"><i class="fa-solid fa-rotate-right"></i> File a New Shifting Request</button>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- SHIFTING REQUEST SUBMISSION FORM -->
            <div class="glass-card" style="animation: slideDown 0.5s ease;">
                <h2 style="font-family: var(--font-display); font-weight: 800; color: #ffffff; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-arrow-right-arrow-left" style="color: var(--accent-primary);"></i>
                    Second Year Shifting Application
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
                    You are shifting from the Common First Year (`BET-00-V`) curriculum. Choose your target specialized engineering technology major and complete the program suitability test to proceed.
                </p>

                <!-- Academic Overview & Warnings -->
                <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid var(--card-border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="font-weight: 600; color: #ffffff; font-size: 0.95rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-clipboard-list" style="color: var(--accent-secondary);"></i> First Year Status Summary
                    </div>
                    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Current Program: <strong style="color: #ffffff;">BET-00-V</strong></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Current Section: <strong style="color: #ffffff;"><?php echo htmlspecialchars($student['section']); ?></strong></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Academic Deficiencies: 
                            <strong style="color: <?php echo empty($deficiencies) ? 'var(--accent-secondary)' : '#ef4444'; ?>;">
                                <?php echo count($deficiencies); ?> Failed Subject(s)
                            </strong>
                        </div>
                    </div>
                    
                    <?php if (!empty($deficiencies)): ?>
                        <div style="margin-top: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.25); background: rgba(239, 68, 68, 0.05); padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.8rem; color: #fca5a5; display: flex; gap: 0.75rem; align-items: flex-start;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top: 0.15rem;"></i>
                            <div>
                                <strong>Prerequisite Lock Warning:</strong> You have failed subjects (listed below). Shifting is allowed, but these failed subjects will remain in your academic history and will continue to lock second-year subjects that depend on them as prerequisites.
                                <ul style="margin-top: 0.4rem; padding-left: 1.25rem;">
                                    <?php foreach ($deficiencies as $def): ?>
                                        <li><?php echo htmlspecialchars($def['subject_code'] . ' - ' . $def['description']); ?> (Grade: <?php echo htmlspecialchars($def['grade']); ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <form id="shiftingForm" onsubmit="submitShifting(event)">
                    
                    <!-- Program Select Grid -->
                    <div style="font-family: var(--font-display); font-weight: 700; color: #ffffff; font-size: 1.1rem; margin-bottom: 1rem;">
                        1. Select Your Target Program / Major
                    </div>
                    <div class="program-selection-grid">
                        <?php foreach ($availablePrograms as $prog): ?>
                            <div class="program-card" id="card-<?php echo htmlspecialchars($prog['program_code']); ?>" onclick="selectProgram('<?php echo htmlspecialchars($prog['program_code']); ?>')">
                                <div class="program-code-badge"><?php echo htmlspecialchars($prog['program_code']); ?></div>
                                <div class="program-title"><?php echo htmlspecialchars($prog['program_name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="target_program_code" id="input_target_program_code" required>

                    <!-- Interactive Eligibility Test Container -->
                    <div class="test-section" id="test-section-panel">
                        <div class="test-title" id="test-panel-title">
                            <i class="fa-solid fa-file-signature"></i> Program Eligibility & Aptitude Screening
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem; margin-top: -1rem; line-height: 1.5;">
                            This mini-test will be reviewed by the Department Head of your target program to verify if you meet the requirements and are suitable for the major.
                        </p>
                        
                        <div id="dynamic-questions-container">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <!-- Buttons Row -->
                    <div class="navigation-row" style="justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem; margin-top: 2.5rem;">
                        <a href="index.php?page=enrollment" class="btn btn-secondary nav-btn"><i class="fa-solid fa-house"></i> Cancel & Return</a>
                        <button type="submit" class="btn btn-primary" id="btn-submit-shifting" disabled style="padding: 0.75rem 2.5rem; font-family: var(--font-display); font-weight: 600;">
                            <i class="fa-solid fa-paper-plane"></i> Submit Shifting Request
                        </button>
                    </div>

                </form>
            </div>
        <?php endif; ?>

    </main>

    <!-- Glassmorphic Toast Notification -->
    <div class="toast-container" id="toast-box">
        <div class="toast-notification" id="toast-notice">
            <i class="fa-solid fa-circle-info toast-icon" id="toast-icon"></i>
            <div class="toast-body">
                <h4 id="toast-title">Notification</h4>
                <p id="toast-desc">Message details go here.</p>
            </div>
        </div>
    </div>

    <!-- Aptitude Test Questions Seed Data -->
    <script>
        const questionnaires = {
            "BET-01-V": {
                title: "Automotive Engineering Technology",
                questions: [
                    { type: "scale", text: "How would you rate your interest in internal combustion engines & hybrid/electric vehicle drivetrains?" },
                    { type: "scale", text: "How comfortable are you with hands-on mechanical repairs and working with tools?" },
                    { type: "scale", text: "How comfortable are you reading technical schematics and automotive diagnostics?" },
                    { type: "text", text: "What mechanical issues might cause an engine to overheat, and how would you inspect it?" },
                    { type: "text", text: "Why do you want to specialize in Automotive Engineering Technology?" }
                ]
            },
            "BET-02-V": {
                title: "Chemical Engineering Technology",
                questions: [
                    { type: "scale", text: "How comfortable are you working with laboratory glassware, reagents, and chemicals?" },
                    { type: "scale", text: "How would you rate your understanding of unit conversions, chemistry equations, and math?" },
                    { type: "scale", text: "How strictly do you follow laboratory safety protocols and PPE requirements?" },
                    { type: "text", text: "Explain why understanding material safety data sheets (MSDS) is critical in a chemical plant." },
                    { type: "text", text: "Why do you want to specialize in Chemical Engineering Technology?" }
                ]
            },
            "BET-04-V": {
                title: "Electrical Engineering Technology",
                questions: [
                    { type: "scale", text: "How would you rate your interest in high-voltage power transmission, grids, and electrical wiring?" },
                    { type: "scale", text: "How comfortable are you analyzing AC/DC electrical circuit diagrams?" },
                    { type: "scale", text: "How would you rate your caution and safety awareness around live electrical components?" },
                    { type: "text", text: "Explain Ohm's Law and how it relates to electrical safety in power circuits." },
                    { type: "text", text: "Why do you want to specialize in Electrical Engineering Technology?" }
                ]
            },
            "BET-05-V": {
                title: "Electronics Engineering Technology",
                questions: [
                    { type: "scale", text: "How would you rate your interest in microcontrollers, microchips, and low-voltage electronics?" },
                    { type: "scale", text: "How comfortable are you soldering components and using oscilloscopes/multimeters?" },
                    { type: "scale", text: "How would you rate your interest in telecommunications and signal processing?" },
                    { type: "text", text: "Describe a basic electronic circuit component (like a transistor or capacitor) and its function." },
                    { type: "text", text: "Why do you want to specialize in Electronics Engineering Technology?" }
                ]
            },
            "BET-06-V": {
                title: "Manufacturing Engineering Technology",
                questions: [
                    { type: "scale", text: "How interested are you in CNC machinery, lathes, milling processes, and assembly lines?" },
                    { type: "scale", text: "How would you rate your spatial visualization skills (reading 3D blueprint models)?" },
                    { type: "scale", text: "How interested are you in industrial automation, robotics, and workflow efficiency?" },
                    { type: "text", text: "Briefly explain the role of quality control in a modern manufacturing pipeline." },
                    { type: "text", text: "Why do you want to specialize in Manufacturing Engineering Technology?" }
                ]
            },
            "BET-07-V": {
                title: "HVAC/R Engineering Technology",
                questions: [
                    { type: "scale", text: "How interested are you in thermodynamic cycles, heat exchange, and cooling systems?" },
                    { type: "scale", text: "How comfortable are you troubleshooting mechanical compressor units and piping?" },
                    { type: "scale", text: "How would you rate your awareness of environmental regulations regarding refrigerants?" },
                    { type: "text", text: "Explain the difference between refrigeration and air conditioning in terms of heat transfer." },
                    { type: "text", text: "Why do you want to specialize in HVAC/R Engineering Technology?" }
                ]
            },
            "BET-08-V": {
                title: "Electro-Mechanical Engineering Technology",
                questions: [
                    { type: "scale", text: "How would you rate your interest in systems combining electrical control and mechanical output?" },
                    { type: "scale", text: "How comfortable are you reading both mechanical blueprints and electrical schematics?" },
                    { type: "scale", text: "How would you rate your troubleshooting skills for physical motor controls and actuators?" },
                    { type: "text", text: "Give an example of an electro-mechanical system you interact with daily and how it works." },
                    { type: "text", text: "Why do you want to specialize in Electro-Mechanical Engineering Technology?" }
                ]
            },
            "BET-09-V": {
                title: "Computer Engineering Technology",
                questions: [
                    { type: "scale", text: "How good are your coding/programming skills?" },
                    { type: "scale", text: "How would you rate your interest in hardware circuitry, microcontrollers, and microprocessors?" },
                    { type: "scale", text: "How comfortable are you with logical problem-solving, algorithms, and debugging code?" },
                    { type: "text", text: "What is the main difference between software and hardware engineering, and how do they interface?" },
                    { type: "text", text: "Why do you want to specialize in Computer Engineering Technology?" }
                ]
            },
            "BETMXT-V": {
                title: "Mechatronics Engineering Technology",
                questions: [
                    { type: "scale", text: "How interested are you in building autonomous robots, sensors, and automated control loops?" },
                    { type: "scale", text: "How comfortable are you combining coding (software) with electronic circuits and mechanical systems?" },
                    { type: "scale", text: "How would you rate your interest in PLC programming and industrial automation controls?" },
                    { type: "text", text: "Describe what a closed-loop feedback control system is, and give a simple example." },
                    { type: "text", text: "Why do you want to specialize in Mechatronics Engineering Technology?" }
                ]
            }
        };

        function selectProgram(code) {
            // Remove selection class from all cards
            document.querySelectorAll('.program-card').forEach(card => card.classList.remove('selected'));
            
            // Add selection class to clicked card
            const selectedCard = document.getElementById(`card-${code}`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }

            // Set input value
            document.getElementById('input_target_program_code').value = code;
            
            // Enable submit button
            document.getElementById('btn-submit-shifting').removeAttribute('disabled');

            // Render dynamic questions
            renderQuestions(code);
        }

        function renderQuestions(code) {
            const container = document.getElementById('dynamic-questions-container');
            const panel = document.getElementById('test-section-panel');
            
            container.innerHTML = '';
            const qData = questionnaires[code] || questionnaires["BET-09-V"]; // Fallback to Computer Eng.

            qData.questions.forEach((q, index) => {
                const group = document.createElement('div');
                group.className = 'question-group';

                const label = document.createElement('label');
                label.className = 'question-label';
                label.innerHTML = `<strong>Q${index + 1}.</strong> ${q.text}`;
                group.appendChild(label);

                if (q.type === 'scale') {
                    const scaleContainer = document.createElement('div');
                    scaleContainer.className = 'rating-scale-container';

                    for (let i = 1; i <= 5; i++) {
                        const btn = document.createElement('div');
                        btn.className = 'rating-btn';
                        
                        const id = `q-${index}-rating-${i}`;
                        btn.innerHTML = `
                            <input type="radio" name="q_${index}" id="${id}" value="${i}" required>
                            <label for="${id}">${i}</label>
                        `;
                        scaleContainer.appendChild(btn);
                    }
                    group.appendChild(scaleContainer);
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.className = 'aptitude-textarea';
                    textarea.name = `q_${index}`;
                    textarea.placeholder = "Enter your short-answer response here...";
                    textarea.required = true;
                    group.appendChild(textarea);
                }

                container.appendChild(group);
            });

            // Show panel
            panel.style.display = 'block';
        }

        async function submitShifting(e) {
            e.preventDefault();
            const form = document.getElementById('shiftingForm');
            const submitBtn = document.getElementById('btn-submit-shifting');
            submitBtn.disabled = true;

            const targetProgramCode = document.getElementById('input_target_program_code').value;
            const qData = questionnaires[targetProgramCode] || questionnaires["BET-09-V"];

            // Collect answers
            const answers = [];
            qData.questions.forEach((q, index) => {
                let ans = "";
                if (q.type === 'scale') {
                    const selected = form.querySelector(`input[name="q_${index}"]:checked`);
                    ans = selected ? selected.value : "";
                } else {
                    const textEl = form.querySelector(`textarea[name="q_${index}"]`);
                    ans = textEl ? textEl.value.trim() : "";
                }
                answers.push({
                    question: q.text,
                    type: q.type,
                    answer: ans
                });
            });

            try {
                const response = await fetch('php/api/submit_shifting.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        target_program_code: targetProgramCode,
                        eligibility_answers: JSON.stringify(answers)
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('Success', result.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    showToast('Submission Failed', result.message, 'error');
                    submitBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                showToast('Error', 'Unable to connect to shifting service.', 'error');
                submitBtn.disabled = false;
            }
        }

        async function resetRejectedRequest() {
            if (!confirm('Filing a new request will archive your rejected request. Proceed?')) {
                return;
            }

            try {
                const response = await fetch('php/api/submit_shifting.php?action=reset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    showToast('Error', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Failed to communicate with shifting service.', 'error');
            }
        }

        // Premium Toast Notification
        function showToast(title, desc, type = 'info') {
            const box = document.getElementById('toast-box');
            const notice = document.getElementById('toast-notice');
            const icon = document.getElementById('toast-icon');
            
            document.getElementById('toast-title').textContent = title;
            document.getElementById('toast-desc').textContent = desc;

            // Reset classes
            notice.className = 'toast-notification active';
            notice.classList.remove('success', 'error');
            
            icon.className = 'fa-solid toast-icon';
            if (type === 'success') {
                notice.classList.add('success');
                icon.classList.add('fa-circle-check');
                icon.style.color = 'var(--accent-secondary)';
            } else if (type === 'error') {
                notice.classList.add('error');
                icon.classList.add('fa-circle-xmark');
                icon.style.color = '#ef4444';
            } else {
                icon.classList.add('fa-circle-info');
                icon.style.color = 'var(--accent-primary)';
            }

            box.style.display = 'block';

            setTimeout(() => {
                notice.classList.remove('active');
                setTimeout(() => {
                    box.style.display = 'none';
                }, 400);
            }, 4000);
        }
    </script>
</body>
</html>
