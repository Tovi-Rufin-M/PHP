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
                    <strong><?php echo htmlspecialchars($s['name']); ?></strong><br>
                    <?php echo htmlspecialchars($s['course']); ?>
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
function failedSubjects() {
    // 1. Find the "Select All" checkbox by its ID
    const selectAllBox = document.getElementById('selectAll-failed');
    const retakeBoxes = document.querySelectorAll('.retake-check');

    // 3. Listen for when the user clicks the "Select All" box
    selectAllBox.addEventListener('change', function() {
        // 4. Loop through every subject checkbox and match its state
        retakeBoxes.forEach(box => {
            box.checked = selectAllBox.checked;
            const cells = box.closest('tr').querySelectorAll('td');
            const getname = Array.from(cells).map(td => td.textContent.trim());
            const key = JSON.stringify(getname);
            if (box.checked) {
                if (!storedSubjects.some(item => JSON.stringify(item) === key)) {
                    selectedfailedSubjects.push(getname);
                    storedSubjects.push(getname);
                }
            } else {
                const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
                const index1 =  selectedfailedSubjects.findIndex(item => JSON.stringify(item)=== key)
                if (index > -1) {
                    selectedfailedSubjects.splice(index1, 1);
                    storedSubjects.splice(index, 1);
                    
                }
            }
        });
        senttocourse(selectedfailedSubjects);
        updateSummary(storedSubjects);
        
    });
    // 5. Listen for changes on each individual checkbox
    retakeBoxes.forEach(box => {
        box.addEventListener('change', function() {
            // 6. Check if all individual boxes are checked
            const allChecked = Array.from(retakeBoxes).every(b => b.checked);
            // 7. Set the "Select All" box to match the state of individual boxes
            selectAllBox.checked = allChecked;
            const cells = box.closest('tr').querySelectorAll('td');
            const getname = Array.from(cells).map(td => td.textContent.trim());
            const key = JSON.stringify(getname);
            if (box.checked) {
                if (!storedSubjects.some(item => JSON.stringify(item) === key)) {
                    selectedfailedSubjects.push(getname);
                    storedSubjects.push(getname);
                }
            } else {
                const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
                const index1 =  selectedfailedSubjects.findIndex(item => JSON.stringify(item)=== key)
                if (index > -1) {
                    selectedfailedSubjects.splice(index1, 1);
                    storedSubjects.splice(index, 1);
                    
                }
            }
            senttocourse(selectedfailedSubjects);
            updateSummary(storedSubjects);
        });
    });
}
// Run the function when the page finishes loading
document.addEventListener('DOMContentLoaded', failedSubjects());
</script>