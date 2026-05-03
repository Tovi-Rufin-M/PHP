<script>
const storedSubjects = [];
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
                    storedSubjects.push(getname);
                }
            } else {
                const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
                if (index > -1) {
                    storedSubjects.splice(index, 1);
                }
            }
        });
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
                    storedSubjects.push(getname);
                }
            } else {
                const index = storedSubjects.findIndex(item => JSON.stringify(item) === key);
                if (index > -1) {
                    storedSubjects.splice(index, 1);
                }
            }
            updateSummary(storedSubjects);
        });
    });
}
// Run the function when the page finishes loading
document.addEventListener('DOMContentLoaded', failedSubjects());

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

    summaryrows.innerHTML = '';
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
    if (totalUnits > 24){
        totalUnitsCell.innerHTML = `<h1 id="error">${"error"}</h1>`;
        return;
    }else{
        totalUnitsCell.innerHTML = `<h3>${totalUnits}</h3>`;
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
    updateSummary(storedSubjects);
});
</script>