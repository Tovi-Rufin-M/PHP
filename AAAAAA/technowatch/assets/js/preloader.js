window.addEventListener('load', function () {
    // --- FIX: Logic to ensure preloader runs only once per session ---
    const PRELOADER_SEEN_KEY = 'preloader_has_run';

    // 1. Check if the preloader has already run in this session
    if (sessionStorage.getItem(PRELOADER_SEEN_KEY) === 'true') {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.style.display = 'none'; // Hide it instantly
            console.log("Preloader skipped: already shown in this session.");
        }
        return; // Stop execution immediately
    }
    // ------------------------------------------------------------------

    console.log("Preloader loaded ✅");

    const preloader = document.getElementById('preloader');
    const clipper = document.getElementById('white-logo-clipper');
    const logoStack = document.querySelector('.logo-stack');

    // Debug log — to confirm elements exist
    console.log("Elements found:", { preloader, clipper, logoStack });

    if (!preloader || !clipper || !logoStack) {
        console.warn("❌ One or more preloader elements missing! Hiding preloader immediately.");
        // Emergency hide if elements are missing
        if (preloader) {
            preloader.style.display = 'none';
        }
        return;
    }
    
    // 2. Set flag to prevent preloader from running again on subsequent loads/navigation
    sessionStorage.setItem(PRELOADER_SEEN_KEY, 'true');

    // --- Smoothing Step 1: Set up the fade-out transition property immediately. ---
    preloader.style.transition = 'opacity 0.5s ease-out';
    
    // --- Smoothing Step 3: Ensure preloader is fully visible from the start. ---
    preloader.style.opacity = '1';

    // --- Start the Scan (Clipper Animation) ---
    // 100ms delay to ensure all assets and styles are rendered before animation starts.
    setTimeout(() => {
        clipper.classList.add('scan-complete');
        console.log("Scan animation started");
        
        // Calculate the total time needed before fade-out: 
        const scanDuration = 2000; // Must match the 'width' transition in your CSS for #white-logo-clipper
        const pauseTime = 300;
        const fadeOutStart = scanDuration + pauseTime; 

        // --- Start Preloader Fade-Out ---
        setTimeout(() => {
            // Trigger the logo zoom-out for dramatic effect
            logoStack.classList.add('logo-exit-zoom');
            
            // Trigger the smooth opacity transition
            preloader.style.opacity = '0';
            console.log("Preloader fading out");
            
            // --- Hide Preloader Element (after Fade Duration) ---
            const fadeOutDuration = 500; // Must match the 'opacity' transition time (0.5s)
            
            setTimeout(() => {
                preloader.style.display = 'none';
                console.log("Preloader hidden");
            }, fadeOutDuration);
            
        }, fadeOutStart);

    }, 100); // Small initial delay
});