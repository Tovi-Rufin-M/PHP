<?php
// Try to get data from URL parameters first, then use defaults
$name = htmlspecialchars($_GET['name'] ?? 'Guest');
$lastname = htmlspecialchars($_GET['lastname'] ?? '');
$middlename = !empty($_GET['middlename']) 
    ? htmlspecialchars($_GET['middlename']) . "." 
    : "";

$student_id = htmlspecialchars($_GET['student_id'] ?? 'N/A');
$course = htmlspecialchars($_GET['course'] ?? 'General Education');

$isEnrolled = ($student_id !== 'N/A');
$status = $isEnrolled ? 'Enrolled' : 'Not Enrolled';

// Store data into array
$studentInfo = [
    "Student Name" => "$name $middlename $lastname",
    "ID Number" => $student_id,
    "Course" => $course
];
?>
<style>
    /* 3. Status Pill (Spans 1 column on mobile) */
.status-pill {
    display: flex;
    align-items: center;
    justify-content: center;
    height: fit-content;
    align-self: center; /* Centers vertically within the grid cell */
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    background: <?php echo $isEnrolled ? '#dcfce7' : '#fee2e2'; ?>;
    color: <?php echo $isEnrolled ? '#15803d' : '#b91c1c'; ?>;
}
</style>

<link rel="stylesheet" href="styles/statusbar.css">
<link rel="stylesheet" href="styles/nav.css">
<link rel="stylesheet" href="styles/form.css">

<br>
<h1 class="text-center">Enrollment Module</h1>
<br>

<div class="statusbar">
    <?php foreach ($studentInfo as $label => $value) { ?>
    <div class="stat-group">
        <span class="stat-label"><?php echo $label; ?></span>
        <span class="stat-value"><?php echo $value; ?></span>
    </div>
    <?php } ?>
    <div class="status-pill">
        <?php echo $status; ?>
    </div>
</div>

<br>

<?php
    $steps = ["First", "Second", "Third", "Fourth"];
    $activeStep = 1; // active step number
    echo '<div class="stepper-wrapper">';
    foreach ($steps as $index => $step) {
        $stepNumber = $index + 1;
        $activeClass = ($stepNumber == $activeStep) ? 'active' : '';
        echo '
        <div class="stepper-item '.$activeClass.'">
            <div class="step-counter">'.$stepNumber.'</div>
            <div class="step-name">'.$step.'</div>
        </div>';
    }
    echo '</div>';
?>

<div class="form-container">
    <?php //include 'process.php'?>
    <?php include 'get_failed.php'?>
</div>
