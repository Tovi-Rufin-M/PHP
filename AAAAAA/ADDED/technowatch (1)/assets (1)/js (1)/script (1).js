// ==================================
// Technowatch Club Main Script
// ==================================
document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const menuBtn = document.querySelector(".menu-btn");
    const navMain = document.querySelector(".nav-main");
    const dropdowns = document.querySelectorAll(".dropdown");

    // New selectors for the hero image navigation
    const heroMainImage = document.querySelector(".main-hero-image");
    const navArrowLeft = document.querySelector(".hero-right-nav .left-arrow");
    const navArrowRight = document.querySelector(".hero-right-nav .right-arrow");
    const smallThumb = document.querySelector(".small-thumb"); // The bottom-right thumbnail
    const heroDotsContainer = document.querySelector(".hero-dot-nav");

    // New selector for the Scroll-to-Top button
    const scrollToTopBtn = document.querySelector(".scroll-to-top-btn");

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


// -------- MOBILE SIDEBAR --------
if (menuBtn && navMain) {
    // Create overlay dynamically (if not already in HTML)
    let overlay = document.querySelector(".menu-overlay");
    if (!overlay) {
        overlay = document.createElement("div");
        overlay.classList.add("menu-overlay");
        document.body.appendChild(overlay);
    }

    // Toggle sidebar open/close
    menuBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        navMain.classList.toggle("active");
        overlay.classList.toggle("active");
        body.classList.toggle("no-scroll", navMain.classList.contains("active"));
    });

    // Close when clicking overlay
    overlay.addEventListener("click", () => {
        navMain.classList.remove("active");
        overlay.classList.remove("active");
        body.classList.remove("no-scroll");
        dropdowns.forEach(d => d.classList.remove("active"));
    });

    // Close if clicking outside (failsafe)
    document.addEventListener("click", (e) => {
        if (
            navMain.classList.contains("active") &&
            !e.target.closest(".nav-main") &&
            !e.target.closest(".menu-btn")
        ) {
            navMain.classList.remove("active");
            overlay.classList.remove("active");
            body.classList.remove("no-scroll");
            dropdowns.forEach(d => d.classList.remove("active"));
        }
    });

    // Dropdown toggle (mobile only)
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
        
        // Auto-slide functionality (Now Active)
        setInterval(() => {
            updateHeroContent(currentHeroIndex + 1);
        }, 5000); // Change image every 5 seconds
        

        // Initialize the first content
        updateHeroContent(0);
    }

    // -------- SCROLL-TO-TOP BUTTON LOGIC --------
    const scrollThreshold = 300; // Pixels to scroll down before button appears

    if (scrollToTopBtn) {
        // Show/hide button on scroll
        window.addEventListener("scroll", () => {
            if (window.scrollY > scrollThreshold) {
                // Ensure it's not already visible before adding the class (optimization)
                if (!scrollToTopBtn.classList.contains("visible")) {
                    scrollToTopBtn.classList.add("visible");
                }
            } else {
                scrollToTopBtn.classList.remove("visible");
            }
        });

        // Scroll to top on click
        scrollToTopBtn.addEventListener("click", () => {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }
});


