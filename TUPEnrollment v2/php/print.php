<?php
$data = file_get_contents("php://input");

// Convert JSON into PHP array
$decoded = json_decode($data, true);

// Access subjects array
$subjects = $decoded['subjects'] ?? [];

$get_selected_subjects = $subjects

echo $get_selected_subjects;
?>