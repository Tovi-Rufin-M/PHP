<script>
const storedSubjects = [];
const selectedfailedSubjects = [];
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

function senttocourse(array){
    const subjects = [];

    for (let i = 0; i < array.length; i++) {
        const row = array[i];
        if (row.length < 5) continue;
        const subjectObj = {
            code: row[1]
        };
        subjects.push(subjectObj);
    }
    const subjectsrows = document.getElementById('subjects-rows');
    subjectsrows.innerHTML = '';
    subjects.forEach(subject => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="border-right: 2px solid black; cursor: pointer;" 
                onclick="handleClick('${subject.code}')">${subject.code}
            </td>
        `;
        subjectsrows.appendChild(row);
    });

}

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