const root = document.getElementById('root');

async function loadPage(file) {
    try {
        const res  = await fetch(file, {
            credentials: 'same-origin' // ✅ Send & receive session cookies
        });
        if (res.status === 401 && file !== 'php/login.php') {
            sessionStorage.removeItem('page');
            await loadPage('php/login.php');
            return;
        }

        if (!res.ok) {
            throw new Error(`Request failed with status ${res.status}`);
        }

        const html = await res.text();

        root.innerHTML = html;

        // Re-execute inline <script> blocks against the live DOM
        root.querySelectorAll('script').forEach(old => {
            const s = document.createElement('script');
            s.textContent = old.textContent;
            old.replaceWith(s);
        });

    } catch (err) {
        root.innerHTML = `
            <p style="color:#ff6b6b">
                Failed to load <strong>${file}</strong>.
                Make sure a PHP server is running.
            </p>`;
    }
}
function bootApp() {
    sessionStorage.removeItem('page');
    loadPage('php/login.php');
}
bootApp();
