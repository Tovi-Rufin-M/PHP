<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<?php
$updates_pages = ['eventsNews.php', 'jobposting.php', 'projects.php'];
?>

<header class="navbar">
    <div class="nav-container">
        <div class="nav-logo" id="logoClickArea">
            <img src="assets/imgs/logo_white.png" alt="Technowatch Logo" id="navbarLogo">
            <div class="nav-title">
                <h2>TechnoWatch</h2>
                <p>College of Computer Engineering Technology</p>
            </div>
        </div>
        <?php // tovi added?>
        <button class="menu-btn">Menu</button>

        <nav class="nav-main">
            <ul class="nav-links">
                <li><a href="index.php" class="<?php if ($current_page == 'index.php') echo 'active'; ?>">Home</a></li>
                <li><a href="about.php" class="<?php if ($current_page == 'about.php') echo 'active'; ?>">About</a></li>
                <li><a href="join-us.php" class="<?php if ($current_page == 'join-us.php') echo 'active'; ?>">Join Us</a></li>
                <li><a href="organization.php" class="<?php if ($current_page == 'organization.php') echo 'active'; ?>">Organization</a></li>
                <li class="dropdown">
                    <a href="resources.php" class="<?php if (in_array($current_page, $updates_pages)) echo 'active'; ?>">Resources</a>
                    <ul class="dropdown-menu">
                        <li><a href="eventsNews.php" class="<?php if ($current_page == 'eventsNews.php') echo 'active'; ?>">Events & News</a></li>
                        <li><a href="jobposting.php" class="<?php if ($current_page == 'jobposting.php') echo 'active'; ?>">Job Postings</a></li>
                        <li><a href="projects.php" class="<?php if ($current_page == 'projects.php') echo 'active'; ?>">Projects</a></li>
                    </ul>
                </li>
                <li><a href="merch.php" class="<?php if ($current_page == 'merch.php') echo 'active'; ?>">Merchandise</a></li>
            </ul>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoArea = document.getElementById('logoClickArea');
    
    if (logoArea) {
        // 1. Add the click handler to redirect to the home page (index.php)
        logoArea.addEventListener('click', function() {
            window.location.href = 'index.php';
        });

        // 2. Mimic YouTube style by making the cursor a pointer on hover (CSS is better for this)
        logoArea.style.cursor = 'pointer';
    }
});


/*tovi added*/
document.addEventListener('DOMContentLoaded', function () {
  const menuBtn = document.querySelector('.menu-btn');
  const navbar = document.querySelector('.navbar') || document.querySelector('header') || document.querySelector('.site-header');

  if (!navbar) return;

  // If menu button missing, create one (safety)
  let finalMenuBtn = menuBtn;
  if (!finalMenuBtn) {
    finalMenuBtn = document.createElement('button');
    finalMenuBtn.className = 'menu-btn';
    finalMenuBtn.type = 'button';
    finalMenuBtn.textContent = 'Menu';
    // place it before nav-main for consistent layout
    const navContainer = document.querySelector('.nav-container');
    if (navContainer) navContainer.insertBefore(finalMenuBtn, navContainer.querySelector('.nav-main'));
  }

  // Create overlay if not present
  let overlay = document.querySelector('.mobile-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    document.body.appendChild(overlay);
  }

  // Create mobile menu container if not present
  let mobileMenu = document.querySelector('.mobile-menu');
  if (!mobileMenu) {
    mobileMenu = document.createElement('nav');
    mobileMenu.className = 'mobile-menu';
    mobileMenu.setAttribute('aria-hidden', 'true');

    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'mobile-close';
    closeBtn.type = 'button';
    closeBtn.textContent = 'Close';
    mobileMenu.appendChild(closeBtn);

    // Clone nav-links
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) {
      const clone = navLinks.cloneNode(true); // deep clone
      // remove desktop-only styles if needed and mark as mobile
      clone.classList.remove('nav-links');
      clone.classList.add('mobile-nav-links');
      mobileMenu.appendChild(clone);
    } else {
      // fallback: create empty list so menu isn't blank while debugging
      const ul = document.createElement('ul');
      ul.className = 'mobile-nav-links';
      mobileMenu.appendChild(ul);
    }

    document.body.appendChild(mobileMenu);
  }

  // Open/close functions
  function openNav() {
    document.documentElement.classList.add('nav-open');
    mobileMenu.setAttribute('aria-hidden', 'false');
    finalMenuBtn.setAttribute('aria-expanded', 'true');
    // prevent body scroll when open
    document.body.style.overflow = 'hidden';
  }

  function closeNav() {
    document.documentElement.classList.remove('nav-open');
    mobileMenu.setAttribute('aria-hidden', 'true');
    finalMenuBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    // also collapse any open dropdowns inside mobile menu
    mobileMenu.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
  }

  // Toggle menu button
  finalMenuBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    if (document.documentElement.classList.contains('nav-open')) closeNav();
    else openNav();
  });

  // overlay click closes menu
  overlay.addEventListener('click', closeNav);

  // Close button inside mobile menu
  const closeBtnInner = mobileMenu.querySelector('.mobile-close');
  if (closeBtnInner) closeBtnInner.addEventListener('click', closeNav);

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeNav();
  });

  // Mobile dropdown accordion behavior (only act on mobile widths)
  mobileMenu.addEventListener('click', function (e) {
    const clicked = e.target;
    // if clicking an anchor directly under .dropdown, toggle submenu
    const dropdownAnchor = clicked.closest('.dropdown > a');
    if (dropdownAnchor && window.innerWidth <= 768) {
      e.preventDefault(); // prevent navigating to parent resource page
      const dropdownLi = dropdownAnchor.closest('.dropdown');
      if (dropdownLi) dropdownLi.classList.toggle('open');
      return;
    }

    // close menu if a real leaf link (not a dropdown parent) is clicked
    const link = clicked.closest('a');
    if (link && window.innerWidth <= 768) {
      // if the clicked link is inside a dropdown parent, let accordion handle it
      if (clicked.closest('.dropdown')) {
        // if it's a child dropdown item, close menu so user sees new page
        if (!clicked.closest('.dropdown > a')) closeNav();
      } else {
        closeNav();
      }
    }
  }, { passive: false });

  // Ensure mobile menu is above header
  mobileMenu.style.zIndex = 1250;
  overlay.style.zIndex = 1200;

  // Safety: if window resizes to desktop, ensure nav-open removed and restore body scroll
  window.addEventListener('resize', function () {
    if (window.innerWidth > 768 && document.documentElement.classList.contains('nav-open')) {
      closeNav();
    }
  });
});
</script>
