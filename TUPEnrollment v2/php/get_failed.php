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
<?php ?>
<script>
    let selectedRetakeSubjects = [];

    /**
     * Safely retrieves the text content of a cell by column index.
     * @param {HTMLTableRowElement} row
     * @param {number} index
     * @returns {string}
     */
    const getCellText = (row, index) =>
        row.cells[index]?.innerText?.trim() ?? '';

    /**
     * Builds a structured subject object from a given table row.
     * @param {HTMLTableRowElement} row
     * @returns {object}
     */
    const extractRowData = (row) => ({
        name:       getCellText(row, 1).split('\n')[0].trim(),
        course:     getCellText(row, 1).split('\n')[1]?.trim() ?? '',
        units:      getCellText(row, 2),
        room:       getCellText(row, 3),
        instructor: getCellText(row, 4),
        grade:      getCellText(row, 5),
    });

    /**
     * Scans all checked retake rows and rebuilds the selectedRetakeSubjects array.
     * @returns {Promise<void>}
     */
    const collectSelectedSubjects = async () => {
        await Promise.resolve();

        selectedRetakeSubjects = [];

        document.querySelectorAll('.retake-check:checked').forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (!row) return;
            selectedRetakeSubjects.push(extractRowData(row));
        });
        // send to php

        console.clear();
        console.table(selectedRetakeSubjects);
        console.log('Selected retake subjects array:', selectedRetakeSubjects);
    };

    /**
     * Toggles all retake checkboxes to match the master checkbox state.
     * @param {Event} event
     * @returns {Promise<void>}
     */
    const handleSelectAll = async (event) => {
        await Promise.resolve();

        document.querySelectorAll('.retake-check').forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });

        await collectSelectedSubjects();
    };

    /**
     * Syncs the master checkbox state and refreshes the selected array.
     * @returns {Promise<void>}
     */
    const handleIndividualCheck = async () => {
        await Promise.resolve();

        const allBoxes     = document.querySelectorAll('.retake-check');
        const checkedBoxes = document.querySelectorAll('.retake-check:checked');
        const selectAllEl  = document.getElementById('selectAll-failed');

        if (selectAllEl) {
            selectAllEl.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allBoxes.length;
            selectAllEl.checked       = checkedBoxes.length === allBoxes.length && allBoxes.length > 0;
        }

        await collectSelectedSubjects();
    };

    /**
     * Attaches all event listeners to the retake table.
     * Called directly (no DOMContentLoaded) because this script
     * runs AFTER the content is already injected into the DOM by loadPage().
     *
     * @returns {void}
     */
    const initRetakeTable = () => {
        const selectAllCheckbox = document.getElementById('selectAll-failed');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', handleSelectAll);
        }

        const tableBody = document.querySelector('.subjects-card tbody');
        if (tableBody) {
            tableBody.addEventListener('change', async (event) => {
                if (event.target.classList.contains('retake-check')) {
                    await handleIndividualCheck();
                }
            });
        }

        console.log('Retake table initialized ✅');
    };

    // ✅ Call directly — DOMContentLoaded is NOT used here
    // because loadPage() in index.js injects this script AFTER
    // the DOM is already fully loaded and ready.
    initRetakeTable();
</script>