<?php
    $name = htmlspecialchars($_GET['name'] ?? 'Guest');
    $lastname = htmlspecialchars($_GET['lastname'] ?? '');
    $middlename = !empty($_GET['middlename']) ? htmlspecialchars($_GET['middlename']) . "." : "";
    $student_id = htmlspecialchars($_GET['student_id'] ?? 'N/A');
    $course = htmlspecialchars($_GET['course'] ?? 'General Education');
    $isEnrolled = ($student_id !== 'N/A');
    $status = $isEnrolled ? 'Enrolled' : 'Not Enrolled';
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
<link rel="stylesheet" href="Style/statusbar.css">
<div class="statusbar">
    <!-- Grid Item 1 -->
    <div class="stat-group">
        <span class="stat-label">Student Name</span>
        <span class="stat-value"><?php echo "$name $middlename $lastname"; ?></span>
    </div>

    <!-- Grid Item 2 -->
    <div class="stat-group">
        <span class="stat-label">ID Number</span>
        <span class="stat-value"><?php echo $student_id; ?></span>
    </div>

    <!-- Grid Item 3 -->
    <div class="stat-group">
        <span class="stat-label">Course</span>
        <span class="stat-value"><?php echo $course; ?></span>
    </div>

    <!-- Grid Item 4 -->
    <div class="status-pill">
        <?php echo $status; ?>
    </div>
</div>
<br>
<link rel="stylesheet" href="Style/nav.css">
<div class="stepper-wrapper">
  <div class="stepper-item active">
    <div class="step-counter">1</div>
    <div class="step-name">First</div>
  </div>
  <div class="stepper-item">
    <div class="step-counter">2</div>
    <div class="step-name">Second</div>
  </div>
  <div class="stepper-item">
    <div class="step-counter">3</div>
    <div class="step-name">Third</div>
  </div>
  <div class="stepper-item">
    <div class="step-counter">4</div>
    <div class="step-name">Forth</div>
  </div>
</div>
<link rel="stylesheet" href="Style/form.css">
<div class="form-container active">
    <?php
    include 'procces.php';
    include 'failed.php';
    include 'course.php';
    include 'drop.php';
    include 'summary.php';
    ?>
</div>