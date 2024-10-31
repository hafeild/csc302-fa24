<?php

$regex = "#^/courses/(\w+)/assignments/(\w+)/?(\?.*)?$#"; 
// $regex = "#^/courses/(\w+)/assignments/(\w+)/?(\?([^=]*=[^&]*)&?*)?$#"; 
$uri = "/courses/48774/assignments/776374?module_item_id=1411139";
$matches = [];
preg_match($regex, $uri, $matches);
echo $matches[0] . "\n";
echo $matches[1] . "\n";
echo $matches[2] . "\n";
echo $matches[3] . "\n";

?>

