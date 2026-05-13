<?php
    $host = 'localhost';
    $db   = 'enrollment_db';
    $user = 'root';
    $pass = '';

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT name, course, units, room, instructor, grade FROM failed_subjects");
    $subjecttoRetake = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="subjects-card">
    <div class="table-header">
        <h3>⚠️ Subjects for Retake</h3>
        <span style="font-size: 0.8rem; color: #7f1d1d;">Select items to add to current load</span>
    </div>
    <table>
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll-failed"></th>
                <th>Subject</th>
                <th>Units</th>
                <th>Room</th>
                <th>Instructor</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($subjecttoRetake as $i => $s) { ?>
            <tr id="retake-row-<?php echo $i; ?>">
                <td><input type="checkbox" name="select" class="retake-check"></td>
                <td>
                    <strong style="font-size: 0.95rem; color: #1e1e2e; font-weight: 600; line-height: 1.2;">
                        <?php echo htmlspecialchars($s['name']); ?>
                    </strong>
                    <p style="font-size: 0.75rem; color: #6b7280; font-weight: 400; margin: 0;">
                        <?php echo htmlspecialchars($s['course']); ?>
                    </p>
                </td>
                <td><?php echo $s['units']; ?></td>
                <td><?php echo htmlspecialchars($s['room']); ?></td>
                <td><?php echo htmlspecialchars($s['instructor']); ?></td>
                <td><?php echo htmlspecialchars($s['grade']); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script>
    /** 
     * Stores the currently selected retake subjects as an array of objects.
     * Each entry mirrors the row data captured from the DOM.
     */
    let selectedRetakeSubjects = [];

    /**
     * Safely retrieves the text content of a cell by column index.
     * Uses optional chaining to prevent null reference errors.
     * 
     * @param {HTMLTableRowElement} row  - The table row element to extract data from
     * @param {number}             index - The zero-based cell (td) index
     * @returns {string} Trimmed cell text or empty string if not found
     */
    const getCellText = (row, index) =>
        row.cells[index]?.innerText?.trim() ?? '';

    /**
     * Builds a structured subject object from a given table row.
     * Skips the checkbox cell (index 0) and reads the remaining columns.
     * 
     * @param {HTMLTableRowElement} row - A <tr> element from the retake table
     * @returns {{ name: string, course: string, units: string, room: string, instructor: string, grade: string }}
     */
    const extractRowData = (row) => ({
        name:       getCellText(row, 1).split('\n')[0].trim(),   // First line is the subject name
        course:     getCellText(row, 1).split('\n')[1]?.trim() ?? '', // Second line is the course
        units:      getCellText(row, 2),
        room:       getCellText(row, 3),
        instructor: getCellText(row, 4),
        grade:      getCellText(row, 5),
    });

    /**
     * Asynchronously scans all checked retake rows and
     * rebuilds the `selectedRetakeSubjects` array from scratch.
     * Awaits a microtask tick to keep the UI responsive.
     * 
     * @returns {Promise<void>}
     */
    const collectSelectedSubjects = async () => {
        // Yield to the event loop so the checkbox state is fully settled
        await Promise.resolve();

        // Reset the array before repopulating
        selectedRetakeSubjects = [];

        // Gather every individual retake checkbox that is currently checked
        const checkedBoxes = document.querySelectorAll('.retake-check:checked');

        checkedBoxes.forEach((checkbox) => {
            const row = checkbox.closest('tr'); // Safe upward DOM traversal
            if (!row) return;                   // Guard against detached nodes

            const subjectData = extractRowData(row);
            selectedRetakeSubjects.push(subjectData);
        });

        // Log the current selection for debugging / downstream consumption
        console.table(selectedRetakeSubjects);
        console.log('Selected retake subjects array:', selectedRetakeSubjects);
    };

    /**
     * Asynchronously toggles all individual retake checkboxes to match
     * the state of the "Select All" master checkbox, then re-collects data.
     * 
     * @param {Event} event - The change event fired by the #selectAll-failed checkbox
     * @returns {Promise<void>}
     */
    const handleSelectAll = async (event) => {
        // Yield so the browser paints the master checkbox state first
        await Promise.resolve();

        const isChecked        = event.target.checked;
        const allRetakeBoxes   = document.querySelectorAll('.retake-check');

        // Apply the master state to every individual checkbox safely
        allRetakeBoxes.forEach((checkbox) => {
            checkbox.checked = isChecked;
        });

        // Rebuild the array to reflect the new selection state
        await collectSelectedSubjects();
    };

    /**
     * Asynchronously handles a change on any individual retake checkbox.
     * Keeps the "Select All" checkbox in sync and refreshes the array.
     * 
     * @returns {Promise<void>}
     */
    const handleIndividualCheck = async () => {
        await Promise.resolve(); // Yield for consistent state reads

        const allBoxes     = document.querySelectorAll('.retake-check');
        const checkedBoxes = document.querySelectorAll('.retake-check:checked');
        const selectAllEl  = document.getElementById('selectAll-failed');

        if (selectAllEl) {
            // Reflect partial / full selection on the master checkbox
            selectAllEl.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allBoxes.length;
            selectAllEl.checked       = checkedBoxes.length === allBoxes.length && allBoxes.length > 0;
        }

        // Rebuild the selected subjects array
        await collectSelectedSubjects();
    };

    /**
     * Registers all event listeners once the DOM is fully loaded.
     * Uses event delegation where possible to avoid attaching
     * many listeners to individual checkboxes.
     *
     * @returns {void}
     */
    const initRetakeTable = () => {
        const selectAllCheckbox = document.getElementById('selectAll-failed');

        // Wire the "Select All" master checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', handleSelectAll);
        }

        // Use delegation on the <tbody> for individual checkboxes
        const tableBody = document.querySelector('.subjects-card tbody');
        if (tableBody) {
            tableBody.addEventListener('change', async (event) => {
                if (event.target.classList.contains('retake-check')) {
                    await handleIndividualCheck();
                }
            });
        }

        return true; // Signal that init completed successfully
    };

    /**
     * Entry point — waits for the DOM to be ready, then boots the
     * retake table and verifies the initializer actually ran.
     * Checking `run` here is valid because we are INSIDE the
     * DOMContentLoaded callback, not outside it.
     *
     * @returns {void}
     */
    document.addEventListener('DOMContentLoaded', () => {
        const run = initRetakeTable(); // run = true only AFTER initRetakeTable finishes

        // ✅ Safe to check here — we are inside the same callback scope
        if (run) {
            console.log('Retake table initialized and running.');
        } else {
            console.log('Initialization failed.');
        }
    });
</script>