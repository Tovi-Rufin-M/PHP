// ==================================
// Technowatch Club Main Script
// ==================================
document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const darkModeSwitch = document.getElementById("darkModeSwitch");
    const darkModeLabel = document.querySelector(".darkmode-toggle span");
    const menuBtn = document.querySelector(".menu-btn");
    const navMain = document.querySelector(".nav-main");
    const dropdowns = document.querySelectorAll(".dropdown");
    const navbarLogo = document.getElementById("navbarLogo");

    // New selectors for the hero image navigation
    const heroMainImage = document.querySelector(".main-hero-image");
    const navArrowLeft = document.querySelector(".hero-right-nav .left-arrow");
    const navArrowRight = document.querySelector(".hero-right-nav .right-arrow");
    const smallThumb = document.querySelector(".small-thumb"); // The bottom-right thumbnail
    const heroDotsContainer = document.querySelector(".hero-dot-nav");

    // --- Dynamic Data for Hero Section ---
    // NOTE: You must ensure these image paths are correct in your project structure.
    const heroContent = [
        {
            mainImage: "assets/imgs/carousel_6.jpg",
            smallThumb: "assets/imgs/carousel_7.jpg",
        },
        {
            mainImage: "assets/imgs/carousel_8.jpg",
            smallThumb: "assets/imgs/carousel_9.jpg",
        },
        {
            mainImage: "assets/imgs/carousel_10.jpg",
            smallThumb: "assets/imgs/carousel_11.jpg",
        },
        {
            mainImage: "assets/imgs/carousel_12.jpg",
            smallThumb: "assets/imgs/carousel_13.jpg",
        }
        // Add more objects for additional slides
    ];
    let currentHeroIndex = 0; // Tracks current main image

  // NEW FUNCTION: Handles the logo source change
  const updateNavbarLogo = (isDark) => {
      if (navbarLogo) {
          if (isDark) {
              // MUST USE FORWARD SLASHES (/)
              navbarLogo.src = "assets/imgs/logo_white.png";
              navbarLogo.alt = "Technowatch Logo - White";
          } else {
              // MUST USE FORWARD SLASHES (/)
              navbarLogo.src = "assets/imgs/logo_dark.png";
              navbarLogo.alt = "Technowatch Logo - Blue";
          }
      }
  };
  // -------- DARK MODE - INITIAL LOAD --------
  const savedTheme = localStorage.getItem("theme");
  const isDark = savedTheme === "dark";


  if (isDark) {
      body.classList.add("dark-theme");
      if (darkModeSwitch) darkModeSwitch.checked = true;
      if (darkModeLabel) darkModeLabel.textContent = "🌙 Dark Mode On";
  } else {
      if (darkModeLabel) darkModeLabel.textContent = "☀️ Dark Mode Off";
  }

  // NEW: Call the update function on initial load
  updateNavbarLogo(isDark);

  // -------- DARK MODE - TOGGLE LISTENER --------
  darkModeSwitch?.addEventListener("change", () => {
      if (darkModeSwitch.checked) {
          body.classList.add("dark-theme");
          localStorage.setItem("theme", "dark");
          if (darkModeLabel) darkModeLabel.textContent = "🌙 Dark Mode On";
          // NEW: Update logo to WHITE
          updateNavbarLogo(true);
      } else {
          body.classList.remove("dark-theme");
          localStorage.setItem("theme", "light");
          if (darkModeLabel) darkModeLabel.textContent = "☀️ Dark Mode Off";
          // NEW: Update logo to BLUE/DARK
          updateNavbarLogo(false);
      }
  });

    // -------- MOBILE SIDEBAR --------
    if (menuBtn && navMain) {
        menuBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            navMain.classList.toggle("active");
            body.classList.toggle("no-scroll", navMain.classList.contains("active"));
        });

        document.addEventListener("click", (e) => {
            // Check if navMain is active and the click target is outside the navigation elements
            if (navMain && navMain.classList.contains("active") && !e.target.closest(".navbar") && !e.target.closest(".menu-btn")) {
                navMain.classList.remove("active");
                body.classList.remove("no-scroll");
                dropdowns.forEach(d => d.classList.remove("active"));
            }
        });

        // Dropdown toggle for mobile
        dropdowns.forEach(drop => {
            const link = drop.querySelector("a");
            link.addEventListener("click", (e) => {
                if (window.innerWidth <= 992) {
                    e.preventDefault();
                    drop.classList.toggle("active");
                }
            });
        });
    }

    // -------- DESKTOP HOVER DROPDOWNS --------
    dropdowns.forEach(drop => {
        const submenu = drop.querySelector(".dropdown-menu");
        drop.addEventListener("mouseenter", () => {
            if (window.innerWidth > 992 && submenu) submenu.classList.add("show");
        });
        drop.addEventListener("mouseleave", () => {
            if (window.innerWidth > 992 && submenu) submenu.classList.remove("show");
        });
    });

    // -------- HERO IMAGE NAVIGATION LOGIC (Replaced old carousel logic) --------
    if (heroMainImage && navArrowLeft && navArrowRight && heroContent.length > 0) {
        const heroDots = Array.from(heroDotsContainer.children); // Get the dots

        const updateHeroContent = (index) => {
            // Ensure index wraps around the array
            index = (index + heroContent.length) % heroContent.length;
            
            // 1. Apply fade-out effect
            heroMainImage.classList.remove("active-image");

            // 2. After CSS transition, change source and fade in
            setTimeout(() => {
                heroMainImage.src = heroContent[index].mainImage;
                smallThumb.src = heroContent[index].smallThumb; // Update small thumb dynamically
                heroMainImage.classList.add("active-image"); // Re-add active-image to fade in

                currentHeroIndex = index; // Update the current index

                // 3. Update dots for visual feedback
                heroDots.forEach((dot, idx) => {
                    dot.classList.toggle("active-dot", idx === currentHeroIndex);
                });

            }, 500); // 500ms must match your CSS transition duration (e.g., transition: opacity 0.5s)
        };

        // Navigation Arrows
        navArrowLeft.addEventListener("click", () => {
            updateHeroContent(currentHeroIndex - 1);
        });

        navArrowRight.addEventListener("click", () => {
            updateHeroContent(currentHeroIndex + 1);
        });

        // Dot Navigation
        heroDots.forEach(dot => {
            dot.addEventListener("click", (e) => {
                const index = parseInt(e.target.dataset.index);
                if (!isNaN(index) && index !== currentHeroIndex) {
                    updateHeroContent(index);
                }
            });
        });
        
        // Optional: Auto-slide functionality (uncomment to enable)
        /*
        setInterval(() => {
            updateHeroContent(currentHeroIndex + 1);
        }, 5000); // Change image every 5 seconds
        */

        // Initialize the first content
        updateHeroContent(0);
    }
});