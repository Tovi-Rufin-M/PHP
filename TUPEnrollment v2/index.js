const root = document.getElementById('root');

/**
 * Fetches a PHP-rendered page and mounts it into the #root element.
 * credentials: 'same-origin' ensures session cookies are sent and
 * stored on every fetch — required for CSRF tokens to work correctly.
 *
 * @param {string} file - Relative path to the PHP file to load
 * @returns {Promise<void>}
 */
async function loadPage(file) {
    try {
        const res  = await fetch(file, {
            credentials: 'same-origin' // ✅ Send & receive session cookies
        });
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

/**
 * Resolves the startup page from sessionStorage, falling back
 * to the login page if no session entry is found.
 *
 * @returns {void}
 */
function bootApp() {
    const savedPage = sessionStorage.getItem('page');
    loadPage(savedPage ?? 'php/form.php'); // ?? keeps it null-safe savedPage ?? savedPage ?? 
}
bootApp();