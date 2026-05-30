<?php
    require_once 'process.php';
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
        <?php foreach ($subjectToRetake as $i => $s) { ?>
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
    let selectedRetakeSubjects = [];

    const getCellText = (row, index) => row.cells[index]?.innerText?.trim() ?? '';

    const extractRowData = (row) => ({
        name:       getCellText(row, 1).split('\n')[0].trim(),
        course:     getCellText(row, 1).split('\n')[1]?.trim() ?? '',
        units:      getCellText(row, 2),
        room:       getCellText(row, 3),
        instructor: getCellText(row, 4),
        grade:      getCellText(row, 5),
    });

    const sendSelectedSubjects = async () => {
        try {
            const response = await fetch('print.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ subjects: selectedRetakeSubjects }),
            });

            const result = await response.text();
            console.log('get_course.php response:', result);
        } catch (error) {
            console.error('Error sending selected retake subjects:', error);
        }
    };

    const collectSelectedSubjects = async () => {
        await Promise.resolve();

        selectedRetakeSubjects = [];

        document.querySelectorAll('.retake-check:checked').forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (!row) return;
            selectedRetakeSubjects.push(extractRowData(row));
        });

        console.clear();
        console.table(selectedRetakeSubjects);
        console.log('Selected retake subjects array:', selectedRetakeSubjects);

        await sendSelectedSubjects();
    };

    const handleSelectAll = async (event) => {
        await Promise.resolve();

        document.querySelectorAll('.retake-check').forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });

        await collectSelectedSubjects();
    };

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

    initRetakeTable();
</script>
