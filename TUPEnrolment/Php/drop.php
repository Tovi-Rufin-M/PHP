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

<div class="Drop-subjects-card">
    <div class="table-header">
        <h3>⚠️ Subjects To Drop</h3>
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