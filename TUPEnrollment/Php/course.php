<div class="subjects-card">
    <div class="table-header">
        <h3>⚠️ Select Course</h3>
        <h3>Course Section</h3>
        <span style="font-size: 0.8rem; color: #7f1d1d;">Select items to add to current load</span>
    </div>
    <table style="border-collapse: collapse;">  <!-- ✅ fixed table tag -->
        <thead>
            <tr>
                <th style="width: 20%;">Subjects</th>
                <th>Course</th>
            </tr>
        </thead>
        <tbody id="subjects-rows"></tbody>
        <tfoot>
            <tr>
                <td style="height: 20vh; border-right: 2px solid black;"></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
function senttocourse(array){
    const subjects = [];

    for (let i = 0; i < array.length; i++) {
        const row = array[i];
        if (row.length < 5) continue;

        // ✅ Split "MATH101\n BSIT" into separate parts
        const parts = row[1].trim().split(/\s*\n\s*/);
        const subjectObj = {
            code:   parts[0]?.trim() ?? '',   // e.g. "MATH101"
        };
        subjects.push(subjectObj);
    }

    const subjectsrows = document.getElementById('subjects-rows');
    subjectsrows.innerHTML = '';

    subjects.forEach(subject => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="border-right: 2px solid black; cursor: pointer;"
                onclick="handleClick('${subject.code}', this)">${subject.code}
            </td>
        `;
        subjectsrows.appendChild(row);
    });
}

function handleClick(code, tdElement) {
    console.log('Code:', code);

    // ✅ Deselect all first
    document.querySelectorAll('#subjects-rows td.selected').forEach(td => {
        td.classList.remove('selected');
        td.style.backgroundColor = '';
        td.style.color = '';
    });

    // ✅ Select only the clicked one
    tdElement.classList.add('selected');
    tdElement.style.backgroundColor = '#7f1d1d';
    tdElement.style.color = '#ffffff';
}
</script>