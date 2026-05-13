<?php
//process all data from get_failed goes here

// JSON data is not populated in $_POST, read raw input instead
$rawInput = file_get_contents("php://input");
$datas = json_decode($rawInput, true);

$name = $datas['name'] ?? 'Unknown';
$age = $datas['units'] ?? 0;

echo datas['name'];
// Send JSON back to JavaScript
?>
