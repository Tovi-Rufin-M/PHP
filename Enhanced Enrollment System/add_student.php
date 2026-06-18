<?php
/**
 * Test Student Profile Creator
 * Helper tool to generate student records with custom academic histories/grades for testing.
 */

require_once __DIR__ . '/php/config/db.php';

$dbClass = new Database();
$conn = $dbClass->getConnection();

$message = null;
$messageType = 'info';

// Handle Student Creation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? 'password123';
    $programCode = $_POST['program_code'] ?? '';
    $section = $_POST['section'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $currentTerm = $_POST['current_term'] ?? '';
    $subjectGrades = $_POST['grades'] ?? []; // Array: [subject_code => selection]

    if (empty($studentId) || empty($name) || empty($programCode) || empty($section) || empty($birthday) || empty($currentTerm)) {
        $message = "Please fill in all profile details.";
        $messageType = "error";
    } else {
        try {
            // Check if student_id already exists
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $stmtCheck->execute([$studentId]);
            if ($stmtCheck->fetchColumn() > 0) {
                $message = "Student ID '$studentId' already exists. Use a different one.";
                $messageType = "error";
            } else {
                $conn->beginTransaction();

                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insert student
                $stmtInsert = $conn->prepare("
                    INSERT INTO students (student_id, name, password, program_code, section, birthday, current_term)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([
                    $studentId,
                    $name,
                    $passwordHash,
                    $programCode,
                    $section,
                    $birthday,
                    $currentTerm
                ]);

                // Insert subject history grades
                $stmtHist = $conn->prepare("
                    INSERT INTO student_subject_history (student_id, subject_code, grade, status)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($subjectGrades as $subCode => $val) {
                    if ($val === 'not_taken') {
                        continue;
                    }

                    $grade = '';
                    $status = 'Passed';

                    if ($val === 'passed_1.0') {
                        $grade = '1.0';
                        $status = 'Passed';
                    } elseif ($val === 'passed_1.5') {
                        $grade = '1.5';
                        $status = 'Passed';
                    } elseif ($val === 'passed_2.5') {
                        $grade = '2.5';
                        $status = 'Passed';
                    } elseif ($val === 'failed_3.0') {
                        $grade = '3.0';
                        $status = 'Failed';
                    } elseif ($val === 'failed_5.0') {
                        $grade = '5.0';
                        $status = 'Failed';
                    }

                    $stmtHist->execute([
                        $studentId,
                        $subCode,
                        $grade,
                        $status
                    ]);
                }

                $conn->commit();
                
                // Redirect on success with query params to prefill login
                header("Location: login.php?student_id=" . urlencode($studentId) . "&birthday=" . urlencode($birthday) . "&created=1");
                exit;
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = "Error creating profile: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Fetch programs for select element
$programs = [];
if ($conn) {
    $programs = $conn->query("SELECT * FROM programs ORDER BY program_code")->fetchAll();
}

// Fetch subjects for academic history list
$subjects = [];
if ($conn) {
    $subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_code")->fetchAll();
}

// Fetch curriculums for filtering subjects
$curriculums = [];
if ($conn) {
    $curriculums = $conn->query("SELECT program_code, term, subject_code FROM curriculums")->fetchAll();
}

// Generate the next candidate Student ID (e.g. look at current max and increment)
$nextStudentId = 'TUPV-00-0013';
if ($conn) {
    $stmtMax = $conn->query("SELECT student_id FROM students WHERE student_id LIKE 'TUPV-00-%' ORDER BY student_id DESC LIMIT 1");
    $lastId = $stmtMax->fetchColumn();
    if ($lastId) {
        $num = intval(substr($lastId, 8));
        $nextStudentId = 'TUPV-00-' . sprintf('%04d', $num + 1);
    }
}

// Random name generation placeholder
$randomNames = ["Jose Rizal", "Andres Bonifacio", "Juan Luna", "Emilio Aguinaldo", "Melchora Aquino", "Gabriela Silang"];
$placeholderName = $randomNames[array_rand($randomNames)] . " (Test Profile)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Test Student - Enhanced Enrollment System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #0b1120;
            --card-bg: rgba(22, 30, 49, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-primary: #0ea5e9;
            --accent-primary-glow: rgba(14, 165, 233, 0.25);
            --accent-secondary: #10b981;
            --accent-warning: #f59e0b;
            --text-main: #f1f5f9;
            --text-muted: #cbd5e1;
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
            background-image: 
                radial-gradient(at 10% 20%, rgba(59, 130, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            padding: 3rem 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 900px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 1.5rem;
        }

        .header h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .header h1 i {
            color: var(--accent-primary);
        }

        .header p {
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .form-group.full-width {
                grid-column: span 1;
            }
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .input-wrapper input, .input-wrapper select {
            width: 100%;
            background: rgba(17, 24, 39, 0.4);
            border: 1px solid var(--card-border);
            color: #ffffff;
            padding: 0.9rem 1.2rem 0.9rem 2.8rem;
            border-radius: 12px;
            outline: none;
            font-family: var(--font-sans);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .input-wrapper select {
            cursor: pointer;
            appearance: none;
        }

        .input-wrapper::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1.2rem;
            color: var(--text-muted);
            pointer-events: none;
        }

        .input-wrapper.no-arrow::after {
            display: none;
        }

        .input-wrapper input:focus, .input-wrapper select:focus {
            border-color: var(--accent-primary);
            background: rgba(17, 24, 39, 0.75);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.35);
        }

        /* Birthday date input specific style override */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.6;
        }

        /* Subjects Section Header */
        .section-divider {
            margin: 2.5rem 0 1.5rem 0;
            border-top: 1px solid var(--card-border);
            padding-top: 1.5rem;
        }

        .section-divider h3 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #ffffff;
        }

        .section-divider h3 i {
            color: var(--accent-secondary);
        }

        .section-divider p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        /* Subjects list styling */
        .subjects-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            background: rgba(17, 24, 39, 0.3);
            padding: 0.5rem;
            margin-bottom: 2rem;
        }

        /* Custom scrollbar */
        .subjects-container::-webkit-scrollbar {
            width: 8px;
        }
        .subjects-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .subjects-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .subjects-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .subject-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.2s ease;
        }

        .subject-row:last-child {
            border-bottom: none;
        }

        .subject-row:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .subject-info {
            flex-grow: 1;
            margin-right: 1.5rem;
        }

        .subject-code {
            font-weight: 700;
            font-size: 0.9rem;
            color: #ffffff;
        }

        .subject-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.1rem;
        }

        .subject-units {
            font-size: 0.75rem;
            background: rgba(14, 165, 233, 0.15);
            color: var(--accent-primary);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            margin-left: 0.5rem;
            border: 1px solid rgba(14, 165, 233, 0.2);
            display: inline-block;
        }

        .subject-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .grade-pill {
            cursor: pointer;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            transition: all 0.2s ease;
        }

        .grade-pill:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.06);
        }

        /* Selected states for grade pills */
        .grade-pill.selected-not_taken {
            border-color: var(--text-muted);
            background: rgba(203, 213, 225, 0.1);
            color: #ffffff;
        }

        .grade-pill.selected-passed {
            border-color: var(--accent-secondary);
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }

        .grade-pill.selected-failed {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        .submit-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            padding: 0.9rem 1.8rem;
            border-radius: 12px;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            border: none;
        }

        .btn-cancel {
            background: none;
            color: var(--text-muted);
            border: 1px solid var(--card-border);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.04);
            color: #ffffff;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: #ffffff;
            box-shadow: 0 4px 12px var(--accent-primary-glow);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            filter: brightness(1.1);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Error/Info banners */
        .banner {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .banner.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        /* Search Filter */
        .search-row {
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
            pointer-events: none;
        }

        .search-input-wrapper input {
            width: 100%;
            background: rgba(17, 24, 39, 0.4);
            border: 1px solid var(--card-border);
            color: #ffffff;
            padding: 0.6rem 1rem 0.6rem 2.4rem;
            border-radius: 8px;
            outline: none;
            font-family: var(--font-sans);
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus {
            border-color: var(--accent-primary);
            background: rgba(17, 24, 39, 0.6);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1><i class="fa-solid fa-user-plus"></i> Test Student Creator</h1>
            <p>Generate a test student profile with a customizable academic grade history</p>
        </div>

        <?php if ($message): ?>
            <div class="banner <?php echo $messageType; ?>">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="student-form">
            <!-- Student Profile Grid -->
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <div class="input-wrapper no-arrow">
                        <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($nextStudentId); ?>" required>
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper no-arrow">
                        <input type="text" id="name" name="name" placeholder="<?php echo htmlspecialchars($placeholderName); ?>" required>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password (Test Default)</label>
                    <div class="input-wrapper no-arrow">
                        <input type="text" id="password" name="password" value="password123" required>
                        <i class="fa-solid fa-key"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="birthday">Date of Birth</label>
                    <div class="input-wrapper no-arrow">
                        <input type="date" id="birthday" name="birthday" value="2006-06-01" required>
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="program_code">Academic Program / Major</label>
                    <div class="input-wrapper">
                        <select id="program_code" name="program_code" required>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo htmlspecialchars($prog['program_code']); ?>" <?php echo ($prog['program_code'] === 'BET-00-V') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['program_code']); ?> - <?php echo htmlspecialchars($prog['program_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="section">Cohort Section</label>
                    <div class="input-wrapper">
                        <select id="section" name="section" required>
                            <option value="Section A" selected>Section A</option>
                            <option value="Section B">Section B</option>
                            <option value="Section C">Section C</option>
                            <option value="Section D">Section D</option>
                            <option value="Section E">Section E</option>
                            <option value="Section F">Section F</option>
                            <option value="Section G">Section G</option>
                        </select>
                        <i class="fa-solid fa-users-rectangle"></i>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="current_term">Current Enrollment Term</label>
                    <div class="input-wrapper">
                        <select id="current_term" name="current_term" required>
                            <option value="First Term">First Term (Next will be Second Term)</option>
                            <option value="Second Term" selected>Second Term (Next will be Third Term)</option>
                            <option value="Third Term">Third Term (Next will be Summer/Vacant Term)</option>
                        </select>
                        <i class="fa-solid fa-circle-play"></i>
                    </div>
                </div>
            </div>

            <!-- Subject Grades Divider -->
            <div class="section-divider">
                <h3><i class="fa-solid fa-file-invoice"></i> Academic Subject History (Transcript)</h3>
                <p>Configure which subjects the student has already taken. Failed prerequisites will trigger blocks in Step 2.</p>
                
                <div class="search-row">
                    <div class="search-input-wrapper">
                        <input type="text" id="subject-search" oninput="filterSubjects()" placeholder="Search subjects by code or description...">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>
            </div>

            <!-- Subjects List -->
            <div class="subjects-container" id="subjects-list">
                <?php foreach ($subjects as $subj): ?>
                    <div class="subject-row" data-code-raw="<?php echo htmlspecialchars($subj['subject_code']); ?>" data-code="<?php echo htmlspecialchars(strtolower($subj['subject_code'])); ?>" data-desc="<?php echo htmlspecialchars(strtolower($subj['description'])); ?>">
                        <div class="subject-info">
                            <span class="subject-code"><?php echo htmlspecialchars($subj['subject_code']); ?></span>
                            <span class="subject-units"><?php echo htmlspecialchars($subj['units']); ?> Units</span>
                            <div class="subject-desc"><?php echo htmlspecialchars($subj['description']); ?></div>
                        </div>
                        <div class="subject-actions">
                            <!-- Hidden inputs to submit values -->
                            <input type="hidden" name="grades[<?php echo htmlspecialchars($subj['subject_code']); ?>]" id="grade-input-<?php echo htmlspecialchars($subj['subject_code']); ?>" value="not_taken">
                            
                            <!-- Toggle Pills -->
                            <span class="grade-pill selected-not_taken" id="pill-nt-<?php echo htmlspecialchars($subj['subject_code']); ?>" onclick="selectGrade('<?php echo htmlspecialchars($subj['subject_code']); ?>', 'not_taken')">
                                Not Taken
                            </span>
                            <span class="grade-pill" id="pill-p-<?php echo htmlspecialchars($subj['subject_code']); ?>" onclick="selectGrade('<?php echo htmlspecialchars($subj['subject_code']); ?>', 'passed_1.5')">
                                Passed
                            </span>
                            <span class="grade-pill" id="pill-f-<?php echo htmlspecialchars($subj['subject_code']); ?>" onclick="selectGrade('<?php echo htmlspecialchars($subj['subject_code']); ?>', 'failed_3.0')">
                                Failed (3.0)
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Submission Row -->
            <div class="submit-row">
                <a href="login.php" class="btn btn-cancel"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                <button type="submit" class="btn btn-submit">
                    <span>Create Test Student</span>
                    <i class="fa-solid fa-user-plus"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Inject curriculums mapping from PHP
        const curriculums = <?php echo json_encode($curriculums); ?>;

        // Set grade selection and update pills UI
        function selectGrade(subCode, val) {
            // Set hidden input value
            document.getElementById(`grade-input-${subCode}`).value = val;

            // Reset pills classes
            const ntPill = document.getElementById(`pill-nt-${subCode}`);
            const pPill = document.getElementById(`pill-p-${subCode}`);
            const fPill = document.getElementById(`pill-f-${subCode}`);

            ntPill.className = 'grade-pill';
            pPill.className = 'grade-pill';
            fPill.className = 'grade-pill';

            // Apply selected class based on selection
            if (val === 'not_taken') {
                ntPill.classList.add('selected-not_taken');
            } else if (val.startsWith('passed')) {
                pPill.classList.add('selected-passed');
            } else if (val.startsWith('failed')) {
                fPill.classList.add('selected-failed');
            }
        }

        // Search and curriculum filters (Program + Term)
        function filterSubjects() {
            const query = document.getElementById('subject-search').value.toLowerCase().trim();
            const selectedProgram = document.getElementById('program_code').value;
            const selectedTerm = document.getElementById('current_term').value;

            // Filter matching subject codes in curriculum
            const allowedCodes = curriculums
                .filter(c => c.program_code === selectedProgram && c.term === selectedTerm)
                .map(c => c.subject_code);

            const rows = document.querySelectorAll('.subject-row');

            rows.forEach(row => {
                const codeRaw = row.getAttribute('data-code-raw');
                const codeSearch = row.getAttribute('data-code');
                const descSearch = row.getAttribute('data-desc');

                const matchesSearch = codeSearch.includes(query) || descSearch.includes(query);
                const matchesCurriculum = allowedCodes.includes(codeRaw);

                if (matchesSearch && matchesCurriculum) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Attach dynamic change event listeners
        document.getElementById('program_code').addEventListener('change', filterSubjects);
        document.getElementById('current_term').addEventListener('change', filterSubjects);

        // Run initial filter on load
        window.addEventListener('DOMContentLoaded', () => {
            filterSubjects();
        });
    </script>
</body>
</html>
