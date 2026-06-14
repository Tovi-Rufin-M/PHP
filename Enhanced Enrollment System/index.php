<?php
/**
 * Enhanced Enrollment System Dashboard Entrypoint
 * Modern dashboard showing system status, statistics, student directories, 
 * academic histories, and an interactive schedule grid viewer.
 */

require_once __DIR__ . '/php/config/db.php';

$dbClass = new Database();
$conn = $dbClass->getConnection();

$dbError = null;
$studentCount = 0;
$programCount = 0;
$subjectCount = 0;
$scheduleCount = 0;
$programs = [];
$students = [];
$schedulesJson = "[]";
$studentHistoryMap = [];

if ($conn) {
    try {
        // Fetch stats
        $studentCount = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $programCount = $conn->query("SELECT COUNT(*) FROM programs")->fetchColumn();
        $subjectCount = $conn->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
        $scheduleCount = $conn->query("SELECT COUNT(*) FROM section_schedules")->fetchColumn();

        // Fetch programs
        $programs = $conn->query("SELECT * FROM programs ORDER BY program_code")->fetchAll();

        // Fetch students
        $students = $conn->query("
            SELECT s.*, p.program_name 
            FROM students s
            LEFT JOIN programs p ON s.program_code = p.program_code
            ORDER BY s.student_id
        ")->fetchAll();

        // Fetch histories
        $histories = $conn->query("
            SELECT h.*, s.description, s.units
            FROM student_subject_history h
            JOIN subjects s ON h.subject_code = s.subject_code
        ")->fetchAll();

        foreach ($histories as $h) {
            $studentHistoryMap[$h['student_id']][] = $h;
        }

        // Fetch all schedules for JS viewer
        $schedules = $conn->query("
            SELECT ss.*, s.description, s.units
            FROM section_schedules ss
            JOIN subjects s ON ss.subject_code = s.subject_code
            ORDER BY ss.program_code, ss.section_name, ss.term, ss.day_of_week, ss.start_time
        ")->fetchAll();
        $schedulesJson = json_encode($schedules);

    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
} else {
    $dbError = "Failed to connect to the database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Enrollment System - Architect Dashboard</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #0b1120; /* Deep calm slate/navy */
            --card-bg: rgba(22, 30, 49, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-primary: #0ea5e9; /* Sky blue (high contrast) */
            --accent-primary-glow: rgba(14, 165, 233, 0.25);
            --accent-secondary: #10b981; /* Emerald green (high contrast/calming) */
            --accent-success: #10b981;
            --accent-warning: #f59e0b;
            --text-main: #f1f5f9; /* Slate 100 - high contrast */
            --text-muted: #cbd5e1; /* Slate 300 - high contrast for labels */
            --font-display: 'Outfit', sans-serif;
            --font-sans: 'Inter', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-sans);
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(at 10% 20%, rgba(59, 130, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(139, 92, 246, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
        }

        header {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--card-border);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 10px var(--accent-primary-glow));
        }

        .logo-text h1 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-text p {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 500;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--accent-success);
        }

        .status-badge.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent-success);
            box-shadow: 0 0 8px var(--accent-success);
            animation: pulse-animation 1.5s infinite;
        }

        .pulse.error {
            background-color: #ef4444;
            box-shadow: 0 0 8px #ef4444;
        }

        @keyframes pulse-animation {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Metrics grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 1.5rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent-primary), var(--accent-secondary));
        }

        .metric-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-primary);
        }

        .metric-info h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
        }

        .metric-info p {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
        }

        /* Primary Content Section: Tabs */
        .tabs-header {
            display: flex;
            gap: 1rem;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 0.5rem;
            margin-top: 1rem;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.95rem 1.5rem; /* Larger touch target */
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.03);
        }

        .tab-btn.active {
            color: #ffffff;
            background: rgba(14, 165, 233, 0.15);
            border: 1px solid rgba(14, 165, 233, 0.25);
        }

        .tab-btn:focus-visible {
            outline: 3px solid var(--accent-primary);
            outline-offset: 2px;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Section Layout */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(12px);
            margin-bottom: 2rem;
        }

        .panel-title {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .panel-title i {
            color: var(--accent-primary);
        }

        /* Database error banner */
        .error-banner {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            color: #fca5a5;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .error-banner i {
            font-size: 2.5rem;
        }

        /* Interactive Schedules Controls */
        .controls-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        select {
            background: #1e293b;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.95rem 1.5rem; /* Taller padding for easy touch targets */
            border-radius: 12px;
            outline: none;
            font-family: var(--font-sans);
            font-size: 1rem; /* Prevent auto-zooming on mobile iOS */
            min-width: 240px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        select:focus-visible {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.35);
            outline: none;
        }

        /* Interactive Schedule Week Grid styling */
        .schedule-week-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-top: 1rem;
        }

        .day-column {
            background: rgba(17, 24, 39, 0.4);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem;
            height: 520px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        @media (max-width: 1200px) {
            .day-column {
                height: auto;
                min-height: 350px;
            }
        }

        .day-column:hover {
            border-color: rgba(59, 130, 246, 0.2);
            background: rgba(17, 24, 39, 0.55);
        }

        .day-header {
            font-family: var(--font-display);
            font-size: 1.15rem;
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 0.8rem;
            text-align: left;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .class-card:hover {
            transform: scale(1.02);
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.3);
            border-color: var(--accent-primary);
        }

        .class-card.lab {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(59, 130, 246, 0.15));
            border-color: rgba(16, 185, 129, 0.3);
        }

        .class-card.lab:hover {
            border-color: var(--accent-success);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            flex-direction: column;
            align-items: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #ffffff;
            margin-bottom: 0.2rem;
        }

        .class-header span.tag {
            font-size: 0.65rem;
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
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
            margin-bottom: 0.6rem;
            align-content: center;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .class-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 0.3rem;
        }

        .class-footer i {
            margin-right: 0.2rem;
        }

        .class-time {
            font-weight: 600;
            color: #ffffff;
        }

        /* Vacant card styling */
        .vacant-card {
            background: rgba(255, 255, 255, 0.015);
            border: 1px dashed rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 1.25rem 0.8rem;
            text-align: center;
            font-size: 0.75rem;
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
            font-size: 0.9rem;
            opacity: 0.6;
        }

        /* Students Grid / Table */
        .students-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .student-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .student-card:hover {
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 8px 16px -5px rgba(0,0,0,0.4);
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .student-name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            color: #ffffff;
        }

        .student-id {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: monospace;
            background: rgba(255,255,255,0.05);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-top: 0.2rem;
            display: inline-block;
        }

        .student-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-primary);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .student-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            font-size: 0.8rem;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 1rem;
        }

        .meta-item span.label {
            color: var(--text-muted);
            font-size: 0.75rem;
            display: block;
            margin-bottom: 0.15rem;
        }

        .meta-item span.val {
            color: #ffffff;
            font-weight: 600;
        }

        .history-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .history-items {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .history-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.02);
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
        }

        .history-code {
            font-weight: 600;
            color: #ffffff;
        }

        .history-grade-badge {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-success);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .empty-state {
            color: var(--text-muted);
            text-align: center;
            padding: 2rem;
            font-style: italic;
        }
        
        .no-records {
            grid-column: 1 / -1;
            padding: 3rem;
            text-align: center;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-container">
            <i class="fa-solid fa-graduation-cap logo-icon"></i>
            <div class="logo-text">
                <h1>Enhanced Enrollment System</h1>
                <p>Architect Panel & Scheduler</p>
            </div>
        </div>
        
        <div>
            <?php if ($dbError): ?>
                <div class="status-badge error">
                    <div class="pulse error"></div>
                    DB Connection Error
                </div>
            <?php else: ?>
                <div class="status-badge">
                    <div class="pulse"></div>
                    Database Healthy
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <?php if ($dbError): ?>
            <div class="error-banner">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <h2>Connection Failed</h2>
                    <p><?php echo htmlspecialchars($dbError); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats row -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="metric-info">
                    <h3>Total Students</h3>
                    <p><?php echo $studentCount; ?></p>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-book-open"></i></div>
                <div class="metric-info">
                    <h3>Programs/Majors</h3>
                    <p><?php echo $programCount; ?></p>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="metric-info">
                    <h3>Total Subjects</h3>
                    <p><?php echo $subjectCount; ?></p>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="metric-info">
                    <h3>Schedule Items</h3>
                    <p><?php echo $scheduleCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Section Navigation Tabs -->
        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab('schedules-tab', this)">
                <i class="fa-solid fa-calendar"></i> Interactive Schedule Viewer
            </button>
            <button class="tab-btn" onclick="switchTab('students-tab', this)">
                <i class="fa-solid fa-users"></i> Student Directory (Seeded Profiles)
            </button>
        </div>

        <!-- 1. Interactive Schedule Viewer Tab -->
        <div id="schedules-tab" class="tab-content active">
            <div class="panel">
                <div class="panel-title">
                    <i class="fa-solid fa-clock"></i>
                    <span>Conflict-Free Section Class Schedule</span>
                </div>

                <div class="controls-row">
                    <div class="form-group">
                        <label for="program-select">Program / Major</label>
                        <select id="program-select" onchange="updateSectionFilter()" aria-label="Select Program or Major">
                            <option value="BET-00-V">BET-00-V (Common 1st Year)</option>
                            <option value="BET-09-V" selected>BET-09-V (Computer Eng.)</option>
                            <option value="BET-02-V">BET-02-V (Chemical Eng.)</option>
                            <option value="BET-06-V">BET-06-V (Manufacturing Eng.)</option>
                            <option value="BET-05-V">BET-05-V (Electronics Eng.)</option>
                            <option value="BET-07-V">BET-07-V (HVACR Eng.)</option>
                            <option value="BET-04-V">BET-04-V (Electrical Eng.)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="term-select">Academic Term</label>
                        <select id="term-select" onchange="updateSectionFilter()" aria-label="Select Academic Term">
                            <option value="First Term" selected>First Term</option>
                            <option value="Second Term">Second Term</option>
                            <option value="Third Term">Third Term</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section-select">Section</label>
                        <select id="section-select" onchange="updateSectionFilter()" aria-label="Select Cohort Section">
                            <option value="Section A" selected>Section A</option>
                            <option value="Section B">Section B</option>
                            <option value="Section C">Section C</option>
                        </select>
                    </div>
                </div>

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
                </div>
            </div>
        </div>

        <!-- 2. Student Directory Tab -->
        <div id="students-tab" class="tab-content">
            <div class="panel">
                <div class="panel-title">
                    <i class="fa-solid fa-users"></i>
                    <span>Seeded Student Profiles & Prerequisites History</span>
                </div>
                
                <div class="students-list">
                    <?php if (empty($students)): ?>
                        <div class="no-records">No students found. Run the seed script.</div>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <div class="student-card">
                                <div class="student-header">
                                    <div>
                                        <div class="student-name"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <div class="student-id"><?php echo htmlspecialchars($s['student_id']); ?></div>
                                    </div>
                                    <div class="student-badge">
                                        <?php 
                                            // Determine course year
                                            echo htmlspecialchars($s['program_code']); 
                                        ?>
                                    </div>
                                </div>

                                <div class="student-meta">
                                    <div class="meta-item">
                                        <span class="label">Section / Cohort</span>
                                        <span class="val"><?php echo htmlspecialchars($s['section']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="label">Enrollment Term</span>
                                        <span class="val"><?php echo htmlspecialchars($s['current_term']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="label">Date of Birth</span>
                                        <span class="val"><?php echo date("M d, Y", strtotime($s['birthday'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="label">Curriculum Scope</span>
                                        <span class="val">TUPV-BET</span>
                                    </div>
                                </div>

                                <div class="history-title">Academic Subject History (Seeded Grades)</div>
                                <div class="history-items">
                                    <?php 
                                    $hasHistory = isset($studentHistoryMap[$s['student_id']]);
                                    if ($hasHistory): 
                                        foreach ($studentHistoryMap[$s['student_id']] as $hist):
                                    ?>
                                            <div class="history-row">
                                                <div>
                                                    <span class="history-code"><?php echo htmlspecialchars($hist['subject_code']); ?></span>
                                                    <span style="font-size: 0.7rem; color: var(--text-muted); display: block;">
                                                        <?php echo htmlspecialchars($hist['description']); ?> (<?php echo $hist['units']; ?>u)
                                                    </span>
                                                </div>
                                                <div class="history-grade-badge">
                                                    Grade: <?php echo htmlspecialchars($hist['grade']); ?>
                                                </div>
                                            </div>
                                    <?php 
                                        endforeach; 
                                    else:
                                    ?>
                                        <div class="empty-state">No academic history records (New enrollee).</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Inject seeded schedules data
        const allSchedules = <?php echo $schedulesJson; ?>;

        // Switch panel tabs
        function switchTab(tabId, btn) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            // Show target content
            document.getElementById(tabId).classList.add('active');

            // Toggle active buttons
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
        }

        // Draw section schedule in calendar
        function drawSchedule() {
            // Get chosen filters
            const program = document.getElementById('program-select').value;
            const term = document.getElementById('term-select').value;
            const section = document.getElementById('section-select').value;

            // Clear previous day containers
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            days.forEach(day => {
                const container = document.getElementById(`sessions-${day}`);
                if (container) container.innerHTML = '';
            });

            // Filter relevant schedules
            const filtered = allSchedules.filter(item => 
                item.program_code === program && 
                item.term === term && 
                item.section_name === section
            );

            // Group by day and AM/PM
            const dayGroups = {
                'Monday': { am: [], pm: [] },
                'Tuesday': { am: [], pm: [] },
                'Wednesday': { am: [], pm: [] },
                'Thursday': { am: [], pm: [] },
                'Friday': { am: [], pm: [] },
                'Saturday': { am: [], pm: [] }
            };

            filtered.forEach(item => {
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

            // Populate the grid columns
            days.forEach(day => {
                const container = document.getElementById(`sessions-${day}`);
                if (!container) return;

                const groups = dayGroups[day];
                // Sort both groups by start time
                groups.am.sort((a, b) => a.start_time.localeCompare(b.start_time));
                groups.pm.sort((a, b) => a.start_time.localeCompare(b.start_time));

                // Helper to render card
                const renderCard = (item) => {
                    const isLab = item.schedule_type === 'Laboratory';
                    const cardClass = isLab ? 'class-card lab' : 'class-card';
                    const tagClass = isLab ? 'tag lab-type' : 'tag lec';
                    const tagLabel = isLab ? 'LAB' : 'LEC';

                    const isMajor = !['GEC', 'GEE', 'PATHFIT', 'NSTP', 'IP', 'BOSH'].some(prefix => 
                        item.subject_code.toUpperCase().startsWith(prefix)
                    );
                    const majorBadge = isMajor ? '<span class="tag major-tag" style="background: rgba(139, 92, 246, 0.2); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.3);">MAJOR</span>' : '';

                    const startHour = parseInt(item.start_time.split(':')[0], 10);
                    const endHour = parseInt(item.end_time.split(':')[0], 10);
                    const durationHours = endHour - startHour;

                    return `
                        <div class="${cardClass}" style="flex-grow: ${durationHours}; flex-basis: 0;">
                            <div class="class-header">
                                <span>${item.subject_code}</span>
                                <div style="display: flex; gap: 0.3rem;">
                                    ${majorBadge}
                                    <span class="${tagClass}">${tagLabel}</span>
                                </div>
                            </div>
                            <div class="class-desc" title="${item.description}">${item.description}</div>
                            <div class="class-footer">
                                <span class="class-time">
                                    <i class="fa-regular fa-clock"></i>${item.start_time.substring(0, 5)} - ${item.end_time.substring(0, 5)}
                                </span>
                                <span><i class="fa-solid fa-door-open"></i>${item.room}</span>
                            </div>
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

        function updateSectionFilter() {
            drawSchedule();
        }

        // Initialize display
        window.addEventListener('DOMContentLoaded', () => {
            drawSchedule();
        });
    </script>
</body>
</html>
