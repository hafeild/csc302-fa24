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
        authenticateOrDie($_POST);
        echo json_encode(getQuizItems());


    } else if($action == 'addQuizItem'){
        authenticateOrDie($_POST);
        echo json_encode(addQuizItem($_POST['quizId'], $_POST['question'], $_POST['answer']));

    } else if($action == 'removeQuizItem') {
        authenticateOrDie($_POST);
        echo json_encode(removeQuizItem($_POST['quizItemId']));


    } else if($action == 'updateQuizItem'){
        authenticateOrDie($_POST);
        echo json_encode(updateQuizItem($_POST['quizItemId'], $_POST['quizId'], $_POST['question'], $_POST['answer']));

    } else if($action == 'addUser'){
        $saltedHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        echo json_encode(addUser($_POST['username'], $saltedHash));

    } else if($action == 'addQuiz'){
        authenticateOrDie($_POST);
        echo json_encode(addQuiz($_POST['name'], $_POST['authorId']));

    } else if($action == 'submitResponses'){
        // echo json_decode($_POST['responses'])[0]['response'];
        authenticateOrDie($_POST);
        echo json_encode(grade($_POST['submitterUsername'], $_POST['quizId'], json_decode($_POST['responses'], true)));

    } else if($action == 'getSubmissions'){
        authenticateOrDie($_POST);
        echo json_encode(getSubmissions());

    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid action: '. $action
        ]);
    }
}

/**
 * Grades a submission and adds it to the database.
 * 
 * @param int $submitterUsername The ID of the user who submitted the quiz.
 * @param int $quizId The ID of the quiz that was submitted.
 * @param array $responses An array of responses. Each response should be an object with the following fields:
 * 
 * @returns Either {"success": true, "id": <submission id>} or {"success": false, "error": "error message"}
 */
function grade($submitterUsername, $quizId, $responses){

    $correctCount = 0;

    // Get the ID of the user who submitted the quiz.
    $userInfo = getUserByUsername($submitterUsername);
    if(!$userInfo['success']){
        return $userInfo;
    }
    $submitterId = $userInfo['id'];

    // Grab the questions and answers for this particular quiz; we are mapping
    // the quizItem ID to the answer.
    $quizItems = getQuizItemsForQuiz($quizId);
    if(!$quizItems['success']){
        return $quizItems;
    }
    $quizItems = $quizItems['quizItems'];

    $quizItemAnswers = [];
    foreach($quizItems as $quizItem){
        $quizItemAnswers[$quizItem['id']] = $quizItem['answer'];
    }

    // echo "responses: $responses";

    // Check each response to see if it is correct.
    foreach($responses as $response){
        if($response['response'] == $quizItemAnswers[$response['quizItemId']]){
            $correctCount++;
        }
    }

    // Create a new entry in Submissions.
    $response = addSubmission($submitterId, $quizId, count($responses), $correctCount);

    // Only try to add responses if the submission was successful created.
    if($response['success']){
        $submissionId = $response['id'];
        // Add each response to the QuizItemResponses table.
        foreach($responses as $response){
            $status = addQuizItemResponse($response['quizItemId'], $submissionId,
                $response['response'], $response['response'] == $quizItemAnswers[$response['quizItemId']]);

            // Error check.
            if(!$status['success']){
                echo json_encode($status);
                return;
            }
        }

        // Add the score to the response.
        $response = [
            'success' => true,
            'id' => $submissionId,
            'correctCount' => $correctCount,
            'questionCount' => count($responses)
        ];
    }

    return $response; 
}

/**
 * Authenticates the user based on the stored credentials.
 * 
 * @param data An associative array holding parameters and their values. Should
 *             have these keys:
 *              - username
 *              - password
 */
function authenticateOrDie($data){
    // TODO: add code to check that username and password params are
    //       present.

    $userInfo = getUserByUsername($data['username']);
    if($userInfo['success'] && password_verify($data['password'], $userInfo['password'])){
        return true;

    } else {
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'error' => 'Invalid username or password'
        ]));

    }
}


?>