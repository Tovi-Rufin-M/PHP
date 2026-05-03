<?php
    $host = 'localhost';
    $db   = 'enrollment_db';
    $user = 'root';
    $pass = '';

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT name, units, room, instructor, grade FROM failed_subjects");
    $subjecttoRetake = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="Failed-subjects-card">
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
                <tr id="failed-row-<?php echo $i; ?>">
                    <td><input type="checkbox" name="select" class="failed-check"></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo $s['units']; ?></td>
                    <td><?php echo htmlspecialchars($s['room']); ?></td>
                    <td><?php echo htmlspecialchars($s['instructor']); ?></td>
                    <td><span class="grade-badge"><?php echo htmlspecialchars($s['grade']); ?></span></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<script>
const checkboxesFailed = document.querySelectorAll('.failed-check');
const selectAllFailed = document.getElementById('selectAll-failed');
let listItems = [];

// Get selected values
function updateSelectedList() {
    listItems = [];

    checkboxesFailed.forEach(cb => {
        if (cb.checked) {
            listItems.push(cb.value);
        }
    });

    console.log("Selected:", listItems);
}

// Reusable handler
function handleCheckboxChange(cb) {
    updateRowStyle(cb);

    // If one is unchecked → uncheck select all
    if (!cb.checked) {
        selectAllFailed.checked = false;
    } else {
        // If ALL are checked → check select all
        const allChecked = Array.from(checkboxesFailed).every(c => c.checked);
        selectAllFailed.checked = allChecked;
    }

    updateSelectedList();
}

// Individual checkboxes
checkboxesFailed.forEach(cb => {
    cb.addEventListener('change', () => handleCheckboxChange(cb));
});

// Select all checkbox
selectAllFailed.addEventListener('change', function () {
    checkboxesFailed.forEach(cb => {
        cb.checked = this.checked;
        updateRowStyle(cb);
    });

    updateSelectedList();
});
</script>
