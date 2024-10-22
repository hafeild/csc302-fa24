<?php
header('Content-type: application/json');
// Need for PHP sessions.
session_start();

// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('db.php');

// Handle incoming requests.
if(array_key_exists('action', $_POST)){
    $action = $_POST['action'];

    if($action == 'getQuizItems'){
        signedInOrDie();
        echo json_encode(getQuizItems());

    } else if($action == 'signin'){
        // Sign the user in.
        echo json_encode(signin($_POST));

    } else if($action == 'signout'){
        // Sign the user in.
        signedInOrDie();
        echo json_encode(signout());

    } else if($action == 'addQuizItem'){
        signedInOrDie();
        echo json_encode(addQuizItem($_POST['quizId'], $_POST['question'], $_POST['answer']));

    } else if($action == 'removeQuizItem') {
        signedInOrDie();
        echo json_encode(removeQuizItem($_POST['quizItemId']));


    } else if($action == 'updateQuizItem'){
        signedInOrDie();
        echo json_encode(updateQuizItem($_POST['quizItemId'], $_POST['quizId'], $_POST['question'], $_POST['answer']));

    } else if($action == 'addUser'){
        $saltedHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        echo json_encode(addUser($_POST['username'], $saltedHash));

    } else if($action == 'addQuiz'){
        signedInOrDie();
        echo json_encode(addQuiz($_POST['name'], $_POST['authorId']));

    } else if($action == 'submitResponses'){
        // echo json_decode($_POST['responses'])[0]['response'];
        signedInOrDie();
        echo json_encode(grade($_POST['submitterUsername'], $_POST['quizId'], json_decode($_POST['responses'], true)));

    } else if($action == 'getSubmissions'){
        signedInOrDie();
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

function signedInOrDie(){
    // This is a good way to do authenticated sessions and uses PHP sessions.
    if(array_key_exists('signed-in', $_SESSION) && $_SESSION['signed-in']){

    // THIS IS A BAD WAY TO DO THIS. DO NOT DO THIS IN A REAL APPLICATION.
    // if(array_key_exists('signed-in', $_COOKIE) && $_COOKIE['signed-in']){


        return true;
    } else {
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'error' => 'You must be signed in to perform this action.'
        ]));
    }
}


/**
 * Signs in the user if their credentials are authenticated.
 * 
 * @param data An associative array holding parameters and their values. Should
 *             have these keys:
 *              - username
 *              - password
 */
function signin($data){
    // TODO: add code to check that username and password params are
    //       present.

    authenticateOrDie($_POST);
    $userInfo = getUserByUsername($data['username']);

    // This is a good way to do authenticated sessions and uses PHP sessions.
    $_SESSION['signed-in'] = true;
    $_SESSION['user-id'] = $userInfo['id'];

    // THIS IS A BAD WAY TO DO THIS. DO NOT DO THIS IN A REAL APPLICATION.
    // setcookie("signed-in", true, time()+3600*4); 
    // setcookie("user-id", $userInfo['id'], time()+3600*4);

    return ['success' => true];
}

/**
 * Signs the user out.
 */
function signout(){
    // This is a good way to do authenticated sessions and uses PHP sessions.
    session_destroy();

    // THIS IS A BAD WAY TO DO THIS. DO NOT DO THIS IN A REAL APPLICATION.
    // setcookie("signed-in", false, time()-3600);
    // setcookie("user-id", "", time()-3600);

    return ['success' => true];
}

?>