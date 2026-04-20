// ===========================================
// TECHNOWATCH CLUB | JOIN US - SHARED INTERACTIONS
// ===========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. HERO GALLERY AUTO-SLIDE (Main Page)
    const galleryItems = document.querySelectorAll('.gallery-item');
    if (galleryItems.length > 0) {
        let currentGalleryIndex = 0;
        
        function nextGallerySlide() {
            galleryItems[currentGalleryIndex].classList.remove('active');
            currentGalleryIndex = (currentGalleryIndex + 1) % galleryItems.length;
            galleryItems[currentGalleryIndex].classList.add('active');
        }
        
        setInterval(nextGallerySlide, 4000);
    }

    // 2. STATS COUNTER ANIMATION (Main Page)
    const counters = document.querySelectorAll('.stat-number');
    const statsSection = document.querySelector('.hero-stats');
    let statsAnimated = false;

    function animateCounters() {
        if (statsAnimated) return;
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const increment = target / 100;
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target + '+';
                }
            };
            
            updateCounter();
        });
        
        statsAnimated = true;
    }

    // 3. INTERSECTION OBSERVER FOR ANIMATIONS
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Stats animation
                if (entry.target.classList.contains('hero-stats')) {
                    animateCounters();
                }
                
                // Fade in elements
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements for fade-in
    document.querySelectorAll('.benefit-card, .eligibility-card, .process-step, .after-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        observer.observe(el);
    });

    // 4. REQUIREMENTS TOGGLE (Requirements Page)
    const reqToggle = document.getElementById('reqToggle');
    const reqContent = document.getElementById('reqContent');
    
    if (reqToggle && reqContent) {
        reqToggle.addEventListener('click', function() {
            reqContent.classList.toggle('show');
            const isOpen = reqContent.classList.contains('show');
            this.querySelector('span').textContent = isOpen ? 'Hide Requirements' : 'Show Requirements';
            this.classList.toggle('active');
        });
    }

    // 5. PROCESS TIMELINE PROGRESS (How to Apply Page)
    const processSteps = document.querySelectorAll('.process-step');
    const progressBar = document.getElementById('progressBar');
    
    if (processSteps.length > 0 && progressBar) {
        function updateProgress() {
            const activeSteps = document.querySelectorAll('.process-step.active');
            const progress = (activeSteps.length / processSteps.length) * 100;
            progressBar.style.height = progress + '%';
        }

        const processObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    updateProgress();
                }
            });
        }, { threshold: 0.5 });

        processSteps.forEach(step => {
            processObserver.observe(step);
        });
    }

    // 6. FAQ SEARCH & TOGGLE (All Pages)
    const faqSearch = document.getElementById('faqSearch');
    const faqItems = document.querySelectorAll('.faq-item');
    const faqQuestions = document.querySelectorAll('.faq-question');

    // FAQ Search
    if (faqSearch && faqItems.length > 0) {
        faqSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer p').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // FAQ Toggle
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all other FAQs
            faqItems.forEach(item => item.classList.remove('active'));
            
            // Toggle current FAQ
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });

    // 7. SMOOTH SCROLL FOR ANCHOR LINKS
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // 8. CARD HOVER EFFECTS
    document.querySelectorAll('.benefit-card, .eligibility-card, .process-card, .preview-card, .after-card, .support-method').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
            this.style.transition = 'all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // 9. PAGE LOAD ANIMATION
    document.body.style.opacity = '0';
    window.addEventListener('load', function() {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    });

    // 10. STICKY APPLY BAR (Main Page)
    const stickyBar = document.querySelector('.sticky-apply-bar');
    const heroSection = document.querySelector('.join-hero-gallery');
    
    if (stickyBar && heroSection) {
        function toggleStickyBar() {
            const heroHeight = heroSection.offsetHeight;
            if (window.scrollY > heroHeight) {
                stickyBar.classList.add('visible');
            } else {
                stickyBar.classList.remove('visible');
            }
        }
        
        window.addEventListener('scroll', toggleStickyBar);
    }

});