<?php
require_once("questions.php");

// Check get data to see if the given answers are correct.
// Expects a POST parameter with key "response" and value JSON with these
// fields:
//  - questions (array of objects)
//      * id (int)          -- question number
//      * question (string) -- the question text
//      * response (string) -- user's response

// Will generate a JSON object with these fields:
//  - questions (array of objects)
//      * id (int)          -- question number
//      * question (string) -- the question text
//      * response (string) -- user's response
//      * correct (bool)    -- whether the response was correct
//  - score (int)           -- number of correct responses
//  - submissionId (int)    -- unique identifier for this submission

$gradedQuiz = array('questions' => array());
$userResponses = json_decode($_POST["response"], true);
$score = 0;

foreach($userResponses["questions"] as $question) {
    $questionData = array(
        "id" => $question["id"],
        "question" => $quiz[$question["id"]]["question"],
        "response" => $question["response"],
        "correct" => $question["response"] == $quiz[$question["id"]]["answer"]
    );

    // Update score.
    if ($questionData["correct"]) {
        $score++;
    }

    // Append this question to the graded quiz.
    array_push($gradedQuiz['questions'], $questionData);
}

$gradedQuiz["score"] = $score;
$gradedQuiz["submissionId"] = 0; // This needs to be updated in the future.

echo json_encode($gradedQuiz);

?>