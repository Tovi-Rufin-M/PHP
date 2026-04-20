document.addEventListener('DOMContentLoaded', function () {
  const preloader = document.getElementById('preloader');
  const clipper = document.getElementById('white-logo-clipper');
  // ⭐ NEW: Get the logo-stack element for the zoom effect
  const logoStack = document.querySelector('.logo-stack'); 

  const scanDuration = 2000;
  const fadeOutDuration = 500;
  const minimumDisplayTime = scanDuration + 500;
  const startTime = performance.now();

  const hasSeenPreloader = sessionStorage.getItem('hasSeenPreloader');

  // Hide instantly if seen before
  if (hasSeenPreloader && preloader) {
      // Wait for next paint to ensure Chrome renders the new state
      requestAnimationFrame(() => {
          preloader.style.opacity = '0';
          preloader.style.pointerEvents = 'none';
          preloader.style.transition = 'opacity 0.2s ease-out';
          // Force Chrome to apply opacity change (read layout property)
          preloader.offsetHeight; 
          setTimeout(() => {
              preloader.style.display = 'none';
              // Assuming 'loading' class is used to hide main content
              document.body.classList.remove('loading'); 
          }, 200);
      });
      return;
  }

  sessionStorage.setItem('hasSeenPreloader', 'true');
  document.body.classList.add('loading');

  function runAnimation() {
      // Ensure both preloader elements and the logoStack exist
      if (!clipper || !preloader || !logoStack) return; 

      requestAnimationFrame(() => {
          // 1. Start scanning animation
          clipper.classList.add('scan-complete');

          const elapsed = performance.now() - startTime;
          // Calculate time needed to ensure the preloader runs for minimumDisplayTime
          const remaining = Math.max(0, minimumDisplayTime - elapsed);

          // 2. Start fade-out and zoom after the scanning animation is complete AND the minimum display time has passed
          setTimeout(() => {
              // ⭐ ADDED: Start the smooth zoom animation simultaneously with the opacity change
              logoStack.classList.add('logo-exit-zoom'); 
              
              // Start fade out
              preloader.style.opacity = '0';
              
              // 3. Hide completely after the fadeOutDuration
              setTimeout(() => {
                  preloader.classList.add('hidden');
                  preloader.style.display = 'none';
                  document.body.classList.remove('loading');
              }, fadeOutDuration);
          }, remaining);
      });
  }

  // Use window.load for the safest timing after all resources are fetched
  window.addEventListener('load', runAnimation);
  
  // Fallback for very fast loading
  if (document.readyState === 'complete') {
      setTimeout(runAnimation, 50);
  }

});