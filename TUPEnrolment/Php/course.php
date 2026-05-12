<?php

?>
<div class="subjects-card">
    <div class="table-header">
        <h3>⚠️ Select Coarse</h3>
        <h3>Coarse Section</h3>
        <span style="font-size: 0.8rem; color: #7f1d1d;">Select items to add to current load</span>
    </div>
    <table border-collapse: collapse;>
        <thead>
            <tr>
                <th style="width: 20%;">Subjects</th>
                <th>Course</th>
            </tr>
        </thead>
        <tbody id="subjects-rows">
        </tbody>
        <tfoot>
            <tr>
                <td style="height: 20vh; border-right: 2px solid black;"></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
    function handleClick(code) {
        var codes = code + ""
        console.log('Clicked:', codes);
        // do whatever you need here
    }
</script>
 