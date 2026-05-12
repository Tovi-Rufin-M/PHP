<?php
    $host = 'localhost';
    $db   = 'enrollment_db';
    $user = 'root';
    $pass = '';

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT name, units, room, instructor FROM subjects");
    $subjecttoDrop = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="subjects-card">
    <div class="table-header">
        <h3>⚠️ Subjects To Drop</h3>
        <h3> 2nd Semester</h3>
        <span style="font-size: 0.8rem; color: #7f1d1d;">Select items to add to current load</span>
    </div>

    <table>
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll-drop"></th>
                <th>Subject</th>
                <th>Units</th>
                <th>Room</th>
                <th>Instructor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjecttoDrop as $i => $s) { ?>
                <tr id="drop-row-<?php echo $i; ?>">
                    <td><input type="checkbox" name="select" class="drop-check"></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo $s['units']; ?></td>
                    <td><?php echo htmlspecialchars($s['room']); ?></td>
                    <td><?php echo htmlspecialchars($s['instructor']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function dropSubjects() {
    // 1. Find the "Select All" checkbox by its ID
    const selectAllBox = document.getElementById('selectAll-drop');
    const dropBoxes = document.querySelectorAll('.drop-check');
    dropBoxes.forEach(box => {
        const cells = box.closest('tr').querySelectorAll('td');
        const getname = Array.from(cells).map(td => td.textContent.trim());
        const key = JSON.stringify(getname);
        if (box.checked) {
            const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
            if (index > -1) {
                storedSubjects.splice(index, 1);
            }
        } else {
            if (!storedSubjects.some(item => JSON.stringify(item) === key)) {
                storedSubjects.push(getname);
            }
        }
    });
    updateSummary(storedSubjects);

    // 3. Listen for when the user clicks the "Select All" box
    selectAllBox.addEventListener('change', function() {
        // 4. Loop through every subject checkbox and match its state
        dropBoxes.forEach(box => {
            box.checked = selectAllBox.checked;
            const cells = box.closest('tr').querySelectorAll('td');
            const getname = Array.from(cells).map(td => td.textContent.trim());
            const key = JSON.stringify(getname);
            if (box.checked) {
                const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
                if (index > -1) {
                    storedSubjects.splice(index, 1);
                }
            } else {
                if (!storedSubjects.some(item => JSON.stringify(item) === key)) {
                    storedSubjects.push(getname);
                }
            }
        });
        updateSummary(storedSubjects);
    });
    // 5. Listen for changes on each individual checkbox
    dropBoxes.forEach(box => {
        box.addEventListener('change', function() {
            // 6. Check if all individual boxes are checked
            const allChecked = Array.from(dropBoxes).every(b => b.checked);
            // 7. Set the "Select All" box to match the state of individual boxes
            selectAllBox.checked = allChecked;
            const cells = box.closest('tr').querySelectorAll('td');
            const getname = Array.from(cells).map(td => td.textContent.trim());
            const key = JSON.stringify(getname);
            if (box.checked) {
                const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
                if (index > -1) {
                    storedSubjects.splice(index, 1);
                }
            } else {
                if (!storedSubjects.some(item => JSON.stringify(item) === key)) {
                    storedSubjects.push(getname);
                }
            }
            updateSummary(storedSubjects);
        });
    });
}
document.addEventListener('DOMContentLoaded', dropSubjects());
</script>