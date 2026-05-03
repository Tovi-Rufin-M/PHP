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
        <tbody id="failed-table-body"></tbody>
    </table>
</div>

<script>
// 1. Pass the PHP array to JavaScript using json_encode
const subjecttoRetake = <?php echo json_encode($subjecttoRetake); ?>;

// 2. Now JavaScript knows what `subjecttoRetake` is and can map over it
document.getElementById('failed-table-body').innerHTML = subjecttoRetake.map(s => `
    <tr>
      <td><input type="checkbox" name="select"></td>
      <td>${s.name}</td>
      <td>${s.units}</td>
      <td>${s.room}</td>
      <td>${s.instructor}</td>
      <td><span class="grade-badge">${s.grade}</span></td>
    </tr>
`).join('');

// 3. Add event listener for "Select All" checkbox
document.getElementById('selectAll-failed').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('#failed-table-body input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
    return true;
});

// 4. Add event listeners for individual checkboxes
document.querySelectorAll('#failed-table-body input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('#failed-table-body input[type="checkbox"]');
        const checkedCheckboxes = document.querySelectorAll('#failed-table-body input[type="checkbox"]:checked');
        document.getElementById('selectAll-failed').checked = checkedCheckboxes.length === allCheckboxes.length;
    });
});
