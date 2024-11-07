<?php
// File:        router.php
// Author:      CSC302 Class
// Date:        07-Nov-2024
// Purpose:     A RESTful API for Quizzer.

// If the file being requested exists, load it. This is for running in
// PHP dev mode.
if(file_exists(".". $_SERVER['REQUEST_URI'])){
    return false;
}

require_once('db.php');
require_once('jwt.php');

header('Content-type: application/json');

// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Routes.
$routes = [
    // Users.
    makeRoute("POST", "#^/users/?(\?.*)?$#", "addUserController"),
    // TODO -- Question 2: Make route for "Sign in"
    makeRoute("POST", "#^/tokens/?(\?.*)?$#", "createTokenController"),
    // TODO -- Make route for "sign out" -- NEVER MIND, there is no sign out.


    // Quizzes.
    makeRoute("POST", "#^/quizzes/?(\?.*)?$#", "addQuizController"),
    makeRoute("GET", "#^/quizzes/?(\?.*)?$#", "getQuizzesController"),
    makeRoute("GET", "#^/quizzes/(\w+)/?(\?.*)?$#", "getQuizController"),

    // QuizItems.
    # /quizzes/:quizId/quizitems
    makeRoute("POST", "#^/quizzes/(\w+)/quizitems/?(\?.*)?$#", "addQuizItemController"),
    makeRoute("GET", "#^/quizzes/(\w+)/quizitems/?(\?.*)?$#", "getQuizItemsController"),
    
    makeRoute("DELETE", "#^/quizzes/(\w+)/quizitems/(\w+)/?(\?.*)?$#", "removeQuizItemController"),
    makeRoute("PATCH", "#^/quizzes/(\w+)/quizitems/(\w+)/?(\?.*)?$#", "removeQuizItemController"),


    // Submissions.
    makeRoute("POST", "#^/quizzes/(\w+)/submissions/?(\?.*)?$#", "addSubmissionController"),
    makeRoute("GET", "#^/quizzes/(\w+)/submissions/?(\?.*)?$#", "getSubmissionsController"),
    makeRoute("GET", "#^/quizzes/(\w+)/submissions/(\w+)/?(\?.*)?$#", "getSubmissionController"),
];

// Initial request processing.
// If this is being served from a public_html folder, find the prefix (e.g., 
// /~jsmith/path/to/dir).
$matches = [];
preg_match('#^/~([^/]*)#', $_SERVER['REQUEST_URI'], $matches);
if(count($matches) > 0){
    $matches = [];
    preg_match("#/home/([^/]+)/public_html/(.*$)#", dirname(__FILE__), $matches);
    $prefix = "/~". $matches[1] ."/". $matches[2];
    $uri = preg_replace("#^". $prefix ."/?#", "/", $_SERVER['REQUEST_URI']);
} else {
    $prefix = "";
    $uri = $_SERVER['REQUEST_URI'];
}

// Extract Authorization header if present.
$jwtData = null;
if(array_key_exists('HTTP_AUTHORIZATION', $_SERVER)){
    $jwtData = verifyJWT(
        str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']), $SECRET);
}

// Get the request method; PHP doesn't handle non-GET or POST requests
// well, so we'll mimic them with POST requests with a "_method" param
// set to the method we want to use.
$method = $_SERVER["REQUEST_METHOD"];
$params = $_GET;
if($method == "POST"){
    $params = $_POST;
    if(array_key_exists("_method", $_POST))
        $method = strtoupper($_POST["_method"]);
} 

// Parse the request and send it to the corresponding handler.
$foundMatchingRoute = false;
$match = [];
foreach($routes as $route){
    if($method == $route["method"]){
        preg_match($route["pattern"], $uri, $match);
        if($match){
            dieWithError(json_encode($route["controller"]($uri, $match, $params)));
            $foundMatchingRoute = true;
        }
    }
}

if(!$foundMatchingRoute){
    dieWithError("No route found for: $method $uri");
}


////////////////////////////////////////////////////////////////////////////////
// Controllers
////////////////////////////////////////////////////////////////////////////////


//////////////////////////
/////// Users / Tokens
//////////////////////////

/**
 * Creates a new user and returns the id/URI for them. Requires the parameters:
 *  - username
 *  - password
 *
 * @param uri The URI of the request.
 * @param matches An array of matches (unused).
 * @param data An associative array holding parameters and their values.
 */
function addUserController($uri, $matches, $data){
    global $prefix, $SECRET;

    $saltedHash = password_hash($data['password'], PASSWORD_BCRYPT);
    $allUserInfo = addUser($data['username'], $saltedHash);

    if(!$allUserInfo['success']){
        clientError($allUserInfo['error']);
    }

    $userInfo = [
        'id' => $allUserInfo['id']
    ];

    // TODO -- Question 3: What do we need to add here?
    $userInfo['jwt'] = makeJWT([
        'user-id' => $userInfo['id'],
        'exp' => (new DateTime('NOW'))->modify('+1 day')->format('c')
    ], $SECRET);


    created("$prefix/users/". $userInfo['id'], $userInfo);
}


/**
 * Creates a new token. Requires the parameters:
 *  - username
 *  - password
 *
 * @param uri The URI of the request.
 * @param matches An array of matches (unused).
 * @param data An associative array holding parameters and their values.
 */
function createTokenController($uri, $matches, $data){
    global $SECRET;

    // Authenticate.
    $allUserInfo = getUserByUsername($data['username']);

    if($allUserInfo['success'] && password_verify($data['password'], $allUserInfo['password'])){

        $userInfo = [
            'jwt' => makeJWT([
                    'user-id' => $allUserInfo['id'],
                    'exp' => (new DateTime('NOW'))->modify('+1 day')->format('c')
                ], $SECRET),
            'id' => $allUserInfo['id']
            ];
        success($userInfo);

    } else {
        clientError('Invalid username or password');
    }
}

/**
 * Stops the script unelss the requester is logged in.
 */
function stopUnlessSignedIn(){
    global $jwtData;

    // FIXED -- needs to be updated to use the token, not $_SESSION.
    // Stop if the requester isn't signed in.
    if($jwtData == null || !$jwtData['verified'] || isExpired($jwtData)){
        unauthenticated();
    }
}
/////////////////
/////// Quizzes
/////////////////

/**
 * Creates a new quiz and returns the id/URI for it. Requires the parameters:
 *  - name
 *  - authorId
 *
 * @param uri The URI of the request.
 * @param matches An array of matches (unused).
 * @param data An associative array holding parameters and their values.
 */
function addQuizController($uri, $matches, $data){
    global $prefix;

    stopUnlessSignedIn();
    $quizInfo = addQuiz($data['name'], $data['authorId']);
    created("$prefix/quizzes/". $quizInfo['id'], $quizInfo);
}

/**
 * Gets a listing of all quizzes.
 * 
 * @param uri The URI of the request.
 * @param matches An array of matches. (unused)
 * @param data An associative array holding parameters and their values.
 */
function getQuizzesController($uri, $matches, $data){
    stopUnlessSignedIn();
    success(getQuizzes());
}

/**
 * Gets all of the quiz items associated with a quiz.
 * 
 * @param uri The URI of the request.
 * @param matches An array of matches: [1] - quiz id.
 * @param data An associative array holding parameters and their values.
 */
function getQuizController($uri, $matches, $data){
    stopUnlessSignedIn();
    success(getQuiz($matches[1]));
}


///////////////////
/////// Quiz items
///////////////////


/**
 * Creates a new quiz item and returns the id/URI for it. Requires the data
 * parameters:
 *  - question
 *  - answer
 *
 * @param uri The URI of the request.
 * @param matches An array of matches: [1] - quiz id.
 * @param data An associative array holding parameters and their values.
 */
function addQuizItemController($uri, $matches, $data){
    global $prefix;
    $quizId = $matches[1];

    stopUnlessSignedIn();
    $quizItemInfo = addQuizItem($quizId, $data['name'], $data['authorId']);
    created("$prefix/quizzes/$quizId/quizzitems/". $quizItemInfo['id'], $quizItemInfo);
}

/**
 * Gets all of the quiz items associated with a quiz.
 * 
 * @param uri The URI of the request.
 * @param matches An array of matches: [1] - quiz id.
 * @param data An associative array holding parameters and their values.
 */
function getQuizItemsController($uri, $matches, $data){
    stopUnlessSignedIn();
    success(getQuizItemsForQuiz($matches[1]));
}


/**
 * Deletes a quiz item from the database.
 *
 * @param uri The URI of the request.
 * @param matches An array of matches: [1] - quiz id, [2] - quiz item id.
 * @param data An associative array holding parameters and their values. (unused)
 */
function removeQuizItemController($uri, $matches, $data){
    stopUnlessSignedIn();
    $response = removeQuizItem($matches[2]);
    success($response);
}

/**
 * Updates a quiz item in the database.
 *
 * @param uri The URI of the request.
 * @param matches An array of matches: [1] - quiz id, [2] - quiz item id.
 * @param data An associative array holding parameters and their values. Should
 *            contain the fields to update and their values.
 */
function updateQuizItemController($uri, $matches, $data){
    $quizId = $matches[1];
    $quizItemId = $matches[2];
    stopUnlessSignedIn();
    updateQuizItem($quizItemId, $quizId, $data['question'], $data['answer']);
    success([]);
}








////////////////////////////////////////////////////////////////////////////////
// Helper funcitons
////////////////////////////////////////////////////////////////////////////////



/**
 * Emits a 200 response along with a JSON object with two fields:
 *   - success => true
 *   - data => the data that was passed in as `$data`
 * 
 * @param $data The value to assign to the `data` field of the output.
 */
function success($data){
    $response = ['success' => true];
    if($data){
        $response['data'] = $data;
    }
    die(json_encode($response));
}

/**
 * Emits a 201 Created response along with a JSON object with two fields:
 *   - success => true
 *   - data => the data that was passed in as `$data`
 * Sets the "Location" field of the header to the given URI.
 * 
 * @param $uri The URI of the created resource.
 * @param $data The value to assign to the `data` field of the output.
 */
function created($uri, $data){
    http_response_code(201);
    header("Location: $uri");
    $response = ['success' => true];
    if($data){
        $response['data'] = $data;
    }
    die(json_encode($response));
}

/**
 * Emits a 500 response along with a JSON object with two fields:
 *   - success => false
 *   - error => an error message`
 * 
 * @param $error The value to assign to the `error` field of the output.
 */
function dieWithError($error){
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => $error
    ]));
}

function clientError($error){
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => $error
    ]));
}

function unauthenticated(){
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'error' => 'You must be signed in to access this resource.'
    ]));
}

function unauthorized(){
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'You are not authorized to access this resource.'
    ]));
}

/**
 * Emits a 404 response along with a JSON object with two fields:
 *   - success => false
 *   - error => an error message`
 * 
 * @param $error The value to assign to the `error` field of the output.
 */
function notFound($error){
    http_response_code(404);
    die(json_encode([
        'success' => false,
        'error' => $error
    ]));
}


/**
 *  Creates a map with three keys pointing the the arguments passed in:
 *      - method => $method
 *      - pattern => $pattern
 *      - controller => $function
 * 
 * @param method The http method for this route.
 * @param pattern The pattern the URI is matched against. Include groupings
 *                around ids, etc.
 * @param function The name of the function to call.
 * @return A map with the key,value pairs described above.
 */
function makeRoute($method, $pattern, $function){
    return [
        "method" => $method,
        "pattern" => $pattern,
        "controller" => $function
    ];
}


?>