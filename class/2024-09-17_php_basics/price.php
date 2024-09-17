<?php  // See https://www.w3schools.com/php/php_oop_classes_objects.asp
for($i = 0; $i < 100; $i++){
    # echo "number $i<br/>";
    print "number " . $i . "<br/>";
}

$values = array(10, 239, 12, 394, 204, 493);

foreach($values as $value){
    echo "value: $value<br/>";
}

$priceLookup = array("apple" => 1.09, "juice" => 1.59, "sandwich" => 2.54);

foreach($priceLookup as $item => $price){
    echo "$item (\$$price)<br/>";
}
?>