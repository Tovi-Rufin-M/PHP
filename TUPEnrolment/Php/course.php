<?php

?>

<style>
.Course-subjects-card {
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    font-family: 'Inter', system-ui, sans-serif;
    margin: 20px 0;
}
</style>
<div class="Course-subjects-card">
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
        console.log('Clicked:', code);
        // do whatever you need here
    }
</script>
 