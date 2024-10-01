<?php
header('Content-type: text/plain');
// header('Content-type: application/json');
 
$scores = array(
    array("name" => "Bob",    "score" => 13),
    array("name" => "Alice",  "score" => 16)
);
 
echo json_encode($scores);
?>

