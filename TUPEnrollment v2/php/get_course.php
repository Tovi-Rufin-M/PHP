<?php
// ─── DB CONFIG ────────────────────────────────────────────────────────────────
$host = 'localhost';
$db   = 'enrollment_db';
$user = 'root';
$pass = '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Fetch all available courses from the database
$stmt_courses = $pdo->query("
    SELECT id, course, name, units, schedule, room, instructor
    FROM   course_subjects

");
$get_courses = $stmt_courses->fetchAll();

// Selected subjects start empty — populated by JS when user clicks Apply

$data = file_get_contents("php://input");

// Convert JSON into PHP array
$decoded = json_decode($data, true);

// Access subjects array
$subjects = $decoded['subjects'] ?? [];

$get_selected_subjects = $subjects
?>

<style>
    .t {
        width: 100%;
        text-align: center;
        display: flex;
        gap: 2%;
        flex-wrap: wrap;
        align-content: start;
    }

    .tsubject:nth-child(1) {
        width: 25%;
        height: 75%;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
        color: #334155;
    }

    .Tcourse:nth-child(2) {
        flex-grow: 1;
        height: 75%;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
        color: #334155;
    }

    /* ── Selected subject tags ── */
    .selected-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fef2f2;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.8rem;
        color: #7f1d1d;
        margin: 4px 2px;
    }

    .selected-tag button {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 0.9rem;
        padding: 0;
        line-height: 1;
    }

    /* ── Course list rows ── */
    .course-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }

    .course-row:last-child {
        border-bottom: none;
    }

    .course-info strong {
        display: block;
        font-size: 0.9rem;
        color: #1e1e2e;
    }

    .course-info span {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .apply-btn {
        background: #7f1d1d;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 0.8rem;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.2s;
    }

    .apply-btn:hover    { background: #991b1b; }
    .apply-btn.applied  { background: #15803d; cursor: default; }
    .apply-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .empty-note {
        font-size: 0.8rem;
        color: #9ca3af;
        margin-top: 8px;
        text-align: left;
    }
</style>

<div class="subjects-card">
    <div class="table-header">
        <h3>⚠️ Select Course</h3>
        <span style="font-size: 0.8rem; color: #7f1d1d;">Select items to add to current load</span>
    </div>
    <div class="t">
        <!-- ── LEFT: Selected subjects panel ─────────────────────────── -->
        <div class="tsubject" id="selected-panel">
            <?php if (empty($get_selected_subjects)) { ?>
                <p class="empty-note" id="empty-note">No subjects selected yet.</p>
            <?php } ?>
            <?php foreach ($get_selected_subjects as $i => $s) { ?>
                <div class="selected-tag" id="tag-<?php echo htmlspecialchars($s['id']); ?>">
                    <?php echo htmlspecialchars($s['name']); ?>
                    <button onclick="removeSubject(<?php echo $s['id']; ?>)">✕</button>
                </div>
            <?php } ?>
        </div>

        <!-- ── RIGHT: Course list from database ──────────────────────── -->
        <div class="Tcourse" id="course-panel">
            <?php foreach ($get_courses as $i => $s) { ?>
                <div class="course-row" id="course-<?php echo $s['id']; ?>">
                    <div class="course-info">
                        <strong><?php echo htmlspecialchars($s['name']); ?></strong>
                        <span>
                            <?php echo htmlspecialchars($s['course']); ?> &bull;
                            <?php echo htmlspecialchars($s['units']); ?> units &bull;
                            <?php echo htmlspecialchars($s['schedule']); ?> &bull;
                            <?php echo htmlspecialchars($s['room']); ?>
                        </span>
                    </div>
                    <button
                        class="apply-btn"
                        data-id="<?php echo $s['id']; ?>"
                        data-name="<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>"
                        data-course="<?php echo htmlspecialchars($s['course'], ENT_QUOTES); ?>"
                        data-units="<?php echo $s['units']; ?>"
                        data-schedule="<?php echo htmlspecialchars($s['schedule'], ENT_QUOTES); ?>"
                        data-room="<?php echo htmlspecialchars($s['room'], ENT_QUOTES); ?>"
                        data-instructor="<?php echo htmlspecialchars($s['instructor'], ENT_QUOTES); ?>"
                        onclick="applySubject(this)"
                    >
                        Apply
                    </button>
                </div>
            <?php } ?>
        </div>

    </div>
</div>

<script>
    // Stores all applied subjects — client-side only
    let selectedCourseSubjects = [];

    /**
     * Reads all data-* attributes from the Apply button
     * and builds a structured subject object.
     *
     * @param {HTMLButtonElement} btn - The clicked Apply button
     * @returns {{ id, name, course, units, schedule, room, instructor }}
     */
    const extractSubjectFromBtn = (btn) => ({
        id:         btn.dataset.id,
        name:       btn.dataset.name,
        course:     btn.dataset.course,
        units:      btn.dataset.units,
        schedule:   btn.dataset.schedule,
        room:       btn.dataset.room,
        instructor: btn.dataset.instructor,
    });

    /**
     * Creates a selected subject tag element for the left panel.
     * Each tag shows the subject name and an ✕ button to remove it.
     *
     * @param {{ id: string, name: string }} subject
     * @returns {HTMLDivElement}
     */
    const buildSelectedTag = (subject) => {
        const tag = document.createElement('div');
        tag.className = 'selected-tag';
        tag.id        = `tag-${subject.id}`;

        const label  = document.createTextNode(subject.name + ' ');
        const remove = document.createElement('button');
        remove.textContent = '✕';
        remove.onclick     = () => removeSubject(subject.id);

        tag.appendChild(label);
        tag.appendChild(remove);

        return tag;
    };

    /**
     * Applies a course subject when the Apply button is clicked.
     * Adds the subject to the selectedCourseSubjects array,
     * renders a tag in the left panel, and disables the Apply button.
     * Ignores the click if the subject is already applied.
     *
     * @param {HTMLButtonElement} btn - The clicked Apply button
     * @returns {void}
     */
    const applySubject = (btn) => {
        const subject = extractSubjectFromBtn(btn);

        // Prevent duplicate entries
        const alreadyAdded = selectedCourseSubjects.some(s => s.id === subject.id);
        if (alreadyAdded) return;

        // Add to the in-memory array
        selectedCourseSubjects.push(subject);

        // Render the tag in the left panel
        const panel    = document.getElementById('selected-panel');
        const emptyNote = document.getElementById('empty-note');
        if (emptyNote) emptyNote.remove(); // Remove placeholder text

        panel.appendChild(buildSelectedTag(subject));

        // Mark the Apply button as applied
        btn.textContent = 'Applied ✓';
        btn.classList.add('applied');
        btn.disabled = true;

        console.log('Applied subjects:', selectedCourseSubjects);
    };

    /**
     * Removes a subject from the selected panel and the in-memory array.
     * Re-enables the matching Apply button in the right panel.
     *
     * @param {string} id - The subject ID to remove
     * @returns {void}
     */
    const removeSubject = (id) => {
        // Remove from array
        selectedCourseSubjects = selectedCourseSubjects.filter(s => s.id !== String(id));

        // Remove the tag from the left panel
        document.getElementById(`tag-${id}`)?.remove();

        // Show empty note if nothing is left
        const panel = document.getElementById('selected-panel');
        if (selectedCourseSubjects.length === 0) {
            const note = document.createElement('p');
            note.className = 'empty-note';
            note.id        = 'empty-note';
            note.textContent = 'No subjects selected yet.';
            panel.appendChild(note);
        }

        // Re-enable the Apply button for this subject
        const btn = document.querySelector(`.apply-btn[data-id="${id}"]`);
        if (btn) {
            btn.textContent = 'Apply';
            btn.classList.remove('applied');
            btn.disabled = false;
        }

        console.log('Applied subjects:', selectedCourseSubjects);
    };
</script>