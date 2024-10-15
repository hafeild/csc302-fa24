<?php
header('Content-type: application/json');

// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('db.php');

// Handle incoming requests.
if(array_key_exists('action', $_POST)){
    $action = $_POST['action'];

    if($action == 'getQuizItems'){
        echo json_encode(getQuizItems());


    } else if($action == 'addQuizItem'){
        echo json_encode(addQuizItem($_POST['quizId'], $_POST['question'], $_POST['answer']));

    } else if($action == 'removeQuizItem') {
        echo json_encode(removeQuizItem($_POST['quizItemId']));


    } else if($action == 'updateQuizItem'){
        echo json_encode(updateQuizItem($_POST['quizItemId'], $_POST['quizId'], $_POST['question'], $_POST['answer']));

    } else if($action == 'addUser'){
        echo json_encode(addUser($_POST['username']));

    } else if($action == 'addQuiz'){
        echo json_encode(addQuiz($_POST['name'], $_POST['authorId']));




    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid action: '. $action
        ]);
    }
}

?>