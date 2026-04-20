<?php
header('Content-Type: application/json');

$users = [
  ["id" => 1, "name" => "Akame"],
  ["id" => 2, "name" => "Person Chatting me"]
];

echo json_encode($users);
?>
