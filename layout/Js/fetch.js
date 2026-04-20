fetch('Components/header.php')
  .then(res => res.text())
  .then(html => {
    document.getElementById('header').innerHTML = html;

    // Run AFTER header is injected
    const hamburger = document.getElementById('hamburger');
    const nav = document.getElementById('mainNav');
    hamburger.addEventListener('click', () => nav.classList.toggle('open'));

    // Mark active link based on current page
    const current = location.pathname.split('/').pop();
    document.querySelectorAll('nav a').forEach(a => {
      a.classList.toggle('active', a.getAttribute('href') === current);
    });
  })
  .catch(err => console.error('Failed to load header:', err));