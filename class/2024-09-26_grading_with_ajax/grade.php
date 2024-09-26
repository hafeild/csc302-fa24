<?php
require_once("questions.php");

// Check get data to see if the given answers are correct.
// E.g., q1_response=lkj&q2_response=lskjdflksj

$i = 1;
foreach($quiz as $qaPair) {
    $questionResponseId = "q${i}_response";
    $correctnessClass = "";
    if(array_key_exists($questionResponseId, $_GET)){
        if($_GET[$questionResponseId] == $qaPair["answer"]){
            $correctnessClass = "correct";
        } else {
            $correctnessClass = "incorrect";
        }
    }
    $i++;
}

?>