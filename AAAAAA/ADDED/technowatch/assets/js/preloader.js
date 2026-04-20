// ============================
// Technowatch Club Preloader
// ============================
document.addEventListener('DOMContentLoaded', () => {
  const preloader = document.getElementById('preloader');
  const loaderContent = document.querySelector('.loader-content');
  if (!preloader || !loaderContent) return;

  // Timings (in ms)
  const fadeInDelay = 600;
  const fadeOutDelay = 4200;
  const removeDelay = 5200;

  // Lock scroll while loading
  document.body.classList.add('loading');

  // Fade in logo and text
  setTimeout(() => {
    loaderContent.classList.add('show');
  }, fadeInDelay);

  // Fade out preloader
  setTimeout(() => {
    preloader.classList.add('fade-out');
  }, fadeOutDelay);

  // Remove preloader and restore scroll
  setTimeout(() => {
    if (preloader.parentNode) preloader.parentNode.removeChild(preloader);
    document.body.classList.remove('loading');
  }, removeDelay);
});
