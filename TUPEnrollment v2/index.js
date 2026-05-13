const root = document.getElementById('root');

function loadPage(page) {
    console.log(page, "");
    fetch(page)
        .then(response => response.text())
        .then(data => {
            document.querySelector('.root').innerHTML = data;
        });
}

async function loadPage(file) {
    // update active nav link
    try {
        const res  = await fetch(file);
        const html = await res.text();

        // force re-trigger animation
        root.innerHTML = html;

        // run any <script> tags inside the loaded content
        root.querySelectorAll('script').forEach(old => {
            const s = document.createElement('script');
            s.textContent = old.textContent;
            old.replaceWith(s);
        });
    } catch (err) {
        root.innerHTML = `<p style="color:#ff6b6b">Failed to load <strong>${file}</strong>. Make sure a PHP server is running.</p>`;
    }
}

if (sessionStorage.getItem("page")) {
    loadPage(sessionStorage.getItem("page"));
} else {
    loadPage("php/login.php");
}

loadPage('php/from.php'); // original is loadPage('php/login.php'); temporary debuging 