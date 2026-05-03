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
                <tr id="retake-row-<?php echo $i; ?>">
                    <td><input type="checkbox" name="select" class="retake-check"></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo $s['units']; ?></td>
                    <td><?php echo htmlspecialchars($s['room']); ?></td>
                    <td><?php echo htmlspecialchars($s['instructor']); ?></td>
                    <td><?php echo htmlspecialchars($s['grade']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>