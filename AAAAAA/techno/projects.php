<?php 
// projects.php - Main public projects page with AJAX refresh

// Define the available tags (Must match what is saved in the DB/Admin side)
$tags = ['FEATURED', 'CURRENT', 'ARCHIVED']; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Projects - Technowatch</title>
<link rel="icon" type="image/png" href="assets/imgs/logo_white.png">

<link rel="stylesheet" href="assets/css/index.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/projects.css">
<link rel="stylesheet" href="assets/css/responsive.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/script.js" defer></script>
<script src="https://kit.fontawesome.com/f6afcc28f7.js" crossorigin="anonymous"></script>

</head>
<body class="dark-theme">

<?php include 'header.php'; ?>
<main class="projects-page-main">
<section class="hero-section">
<h1 class="main-headline">Innovate. Engineer. Impact.</h1>
<p class="sub-headline">Discover the groundbreaking projects driving the future of Computer Engineering Technology.</p>
</section>

<section class="projects-showcase">

<div class="filter-controls">
<div class="project-filters" role="tablist" id="category-filters">
<button class="filter-btn active" data-filter="all" role="tab" aria-selected="true">
<i class="fas fa-grip-horizontal"></i> All Projects
</button>

<button class="filter-btn" data-filter="ai-robotics" role="tab" aria-selected="false">
<i class="fas fa-robot"></i> Ai Robotics
</button>

<button class="filter-btn" data-filter="iot-mobile" role="tab" aria-selected="false">
<i class="fas fa-mobile-alt"></i> IoT Mobile
</button>

<button class="filter-btn" data-filter="data-analytics" role="tab" aria-selected="false">
<i class="fas fa-chart-line"></i> Data Analytics
</button>

<button class="filter-btn" data-filter="web-development" role="tab" aria-selected="false">
<i class="fas fa-laptop-code"></i> Web Development
</button>

<button class="filter-btn" data-filter="gaming" role="tab" aria-selected="false">
<i class="fas fa-gamepad"></i> Gaming
</button>
</div>
</div>

<div class="tag-filter-container">
<label for="tag-filter-dropdown" class="sr-only">Filter by Status Tag</label>
<select id="tag-filter-dropdown" class="custom-dropdown">
<option value="all-tags" selected>Filter by Status</option>
<?php foreach ($tags as $tag_val): ?>
<option value="<?php echo ucwords(strtolower($tag_val)); ?>">
<?php echo ucwords(strtolower($tag_val)); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="projects-grid">
<p class="loading-message">Loading projects...</p>
</div> 

<div class="load-more-container">
<button id="loadMoreBtn" class="primary-btn" style="display:none;">Load More Projects</button>
</div>
</section>
</main>

<div id="projectModal" class="project-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
<div class="project-modal-content">
<button class="close-btn" aria-label="Close Modal">&times;</button>

<div class="modal-project-image-container">
<img id="modalImage" src="" alt="Project featured image" class="modal-project-image">
<span class="modal-project-tag" id="modalTag"></span>
</div>

<h2 class="modal-project-title" id="modalTitle"></h2>

<div class="modal-project-description">
<div class="overview-section">
<h4>Project Overview</h4>
<p id="modalDescription"></p>
</div>
<div class="features-section">
<h4>Key Features</h4>
<ul id="modalFeaturesList"></ul>
</div>
</div>

<div class="modal-cta">
<a href="#" class="primary-btn" id="modalCtaLink" target="_blank">Read Full Case Study <i class="fas fa-file-alt"></i></a>
</div>
</div>
</div>

<?php include 'footer.php'; ?>

</body>

<script>
$(document).ready(function() {

const $grid = $('.projects-grid');
const $categoryBtns = $('#category-filters').find('.filter-btn');
const $tagDropdown = $('#tag-filter-dropdown');
let $projectCards = $();
const $modal = $('#projectModal');
const $closeBtn = $modal.find('.close-btn');

const projectsPerPage = 6;
let projectsShown = 0;

let activeCategory = 'all';
let activeTag = 'all-tags';

const categoryMap = {
'all': ['ai robotics', 'iot mobile', 'data analytics', 'web development', 'gaming', 'featured', 'current'],
'ai-robotics': ['ai robotics'],
'iot-mobile': ['iot mobile'],
'data-analytics': ['data analytics'],
'web-development': ['web development'],
'gaming': ['gaming']
};

const projectObserver = new IntersectionObserver(entries => {
entries.forEach(entry => {
if (entry.isIntersecting) {
$(entry.target).addClass('reveal');
projectObserver.unobserve(entry.target);
}
});
}, {
rootMargin: '0px',
threshold: 0.1
});

function refreshProjectsGrid() {
$.ajax({
url: 'projects_fetch.php',
type: 'GET',
success: function(data) {
$grid.html(data);
$projectCards = $grid.find('.project-card');
$projectCards.off('click').on('click', openProjectModal);
applyFilters(true);
console.log('Projects list refreshed successfully.');
},
error: function(xhr, status, error) {
if ($grid.find('.project-card').length === 0) {
$grid.html('<p class="error-message">Error fetching projects. Please check your connection.</p>');
}
console.error("Error fetching projects:", status, error);
}
});
}

function applyFilters(isRefresh = false) {
$projectCards.each(function() {
projectObserver.unobserve(this);
$(this).removeClass('visible reveal').addClass('hidden');
});

let $filteredCards = $projectCards;
let $categoryFilteredCards = $projectCards;

if (activeCategory !== 'all') {
const targetSlugs = categoryMap[activeCategory] || [];
const categorySelectors = targetSlugs.map(slug => `[data-category*="${slug}"]`).join(', ');
$categoryFilteredCards = $projectCards.filter(categorySelectors);
}

$filteredCards = $categoryFilteredCards;

if (activeTag !== 'all-tags') {
$filteredCards = $filteredCards.filter(`[data-tag="${activeTag}"]`);
}

projectsShown = projectsPerPage;
const $initialCards = $filteredCards.slice(0, projectsShown);
$initialCards.removeClass('hidden').addClass('visible');

$initialCards.each(function() {
projectObserver.observe(this);
});

updateLoadMoreButton($filteredCards);
}

function loadMoreProjects() {
let $currentFilteredCards = $projectCards;

if (activeCategory !== 'all') {
const targetSlugs = categoryMap[activeCategory] || [];
const categorySelectors = targetSlugs.map(slug => `[data-category*="${slug}"]`).join(', ');
$currentFilteredCards = $currentFilteredCards.filter(categorySelectors);
}

if (activeTag !== 'all-tags') {
$currentFilteredCards = $currentFilteredCards.filter(`[data-tag="${activeTag}"]`);
}

const $nextCards = $currentFilteredCards.slice(projectsShown, projectsShown + projectsPerPage);
$nextCards.removeClass('hidden').addClass('visible');

$nextCards.each(function() {
projectObserver.observe(this);
});

projectsShown += projectsPerPage;

updateLoadMoreButton($currentFilteredCards);
}

function updateLoadMoreButton($currentFilteredCards) {
const totalFiltered = $currentFilteredCards.length;
const $loadMoreBtn = $('#loadMoreBtn');

if (projectsShown >= totalFiltered || totalFiltered === 0) {
$loadMoreBtn.hide();
} else {
$loadMoreBtn.show();
}
}

function openProjectModal(e) {
e.preventDefault();
const $card = $(this);

const title = $card.data('title');
const tag = $card.data('tag');
const image = $card.data('image');
const fullDescription = $card.data('full-description'); 
const caseStudyLink = $card.data('case-study-link') || $card.attr('href');
const featuresString = $card.data('features') || '';
const features = featuresString.split(',').map(f => f.trim()).filter(f => f.length > 0);

$('#modalTitle').text(title);
$('#modalTag').text(tag);
$('#modalImage').attr('src', image).attr('alt', `${title} featured image`);
$('#modalDescription').html(fullDescription ? fullDescription.replace(/\n/g, '<br>') : 'No full description provided.');

const $featuresList = $('#modalFeaturesList').empty();
if (features.length > 0) {
features.forEach(f => {
$featuresList.append(`<li>${f}</li>`);
});
$featuresList.parent('.features-section').show();
} else {
$featuresList.parent('.features-section').hide();
}

const $modalCtaLink = $('#modalCtaLink');
if (caseStudyLink && caseStudyLink !== '#') {
$modalCtaLink.attr('href', caseStudyLink).parent('.modal-cta').show();
$modalCtaLink.html('Read Full Case Study <i class="fas fa-file-alt"></i>');
} else {
$modalCtaLink.parent('.modal-cta').hide();
}

$modal.addClass('open').attr('aria-hidden', 'false');
$('body').addClass('modal-open');
}

$categoryBtns.on('click', function(e) {
e.preventDefault();
const filter = $(this).data('filter');
activeCategory = filter;
$categoryBtns.removeClass('active').attr('aria-selected', 'false');
$(this).addClass('active').attr('aria-selected', 'true');
applyFilters(false);
});

$tagDropdown.on('change', function() {
activeTag = $(this).val();
applyFilters(false);
});

$('#loadMoreBtn').on('click', function() {
loadMoreProjects();
});

function closeModal() {
$modal.removeClass('open').attr('aria-hidden', 'true');
$('body').removeClass('modal-open');
}
$closeBtn.on('click', closeModal);
$modal.on('click', function(e) { if (e.target === this) { closeModal(); } });
$(document).on('keydown', function(e) { if (e.key === "Escape" && $modal.hasClass('open')) { closeModal(); } });

refreshProjectsGrid();
setInterval(refreshProjectsGrid, 3000); 
});
</script>
</html>
