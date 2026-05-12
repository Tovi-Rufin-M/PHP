<script>
const storedSubjects = [];
const selectedfailedSubjects = [];

function updateSummary(array) {
    const subjects = [];

    for (let i = 0; i < array.length; i++) {
        const row = array[i];
        if (row.length < 5) continue;
        const subjectObj = {
            code: row[1],
            units: parseInt(row[2]),
            room: row[3],
            instructor: row[4]
        };
        subjects.push(subjectObj);
    }

    // Final object to send
    const userData = { subjects: subjects };

    const summaryrows = document.getElementById('summary-rows');
    const totalUnitsCell = document.getElementById('summary-total');
    const max = document.getElementById('Max');
    const button = document.getElementById('nextpagebtn');
    summaryrows.innerHTML = '';
    let Max = 24;
    let totalUnits = 0;

    subjects.forEach(subject => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${subject.code}</td>
            <td>${subject.units}</td>
            <td>${subject.room}</td>
            <td>${subject.instructor}</td>
        `;
        summaryrows.appendChild(row);
        totalUnits += subject.units;
    });
    if (totalUnits > Max){
        totalUnitsCell.innerHTML = `<h1 style="color: red;">${totalUnits}</h1>`;
        max.innerHTML = `<h1 style="color: red;">Max Units:${Max}</h1>`;
        button.innerHTML = ` `;
        return;
    }else{
        totalUnitsCell.innerHTML = `<h3>${totalUnits}</h3>`;
        max.innerHTML = `<h3>Max Units:${Max}</h3>`;
        button.innerHTML = `<button id="nextBtn" onclick="pagetwo()">Next Step</button>`;
    }

    // ✅ fetch is now INSIDE the function, runs only when called
    // ✅ Guard: only send if there's actual data
    if (subjects.length === 0) return;

    fetch('Php/senttodatabase.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => console.log('Server response:', data))
    .catch(err => console.error('Fetch error:', err));
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Page loaded, initializing summary...");
    senttocourse(storedSubjects);
    updateSummary(storedSubjects);
});
</script>