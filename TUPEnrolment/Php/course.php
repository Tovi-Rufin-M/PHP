

<div class="Course-subjects-card">
    <div class="table-header">
        <h3>⚠️ Select Coarse</h3>
        <h3>Coarse Section</h3>
        <span style="font-size: 0.8rem; color: #7f1d1d;">Select items to add to current load</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Subjects</th>
                <th>Course</th>
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

 