<?php

// TODO Change this as needed. SQLite will look for a file with this name, or
// create one if it can't find it.
$dbName = 'data.db';

// Leave this alone. It checks if you have a directory named www-data in
// you home directory (on a *nix server). If so, the database file is
// sought/created there. Otherwise, it uses the current directory.
// The former works on digdug where I've set up the www-data folder for you;
// the latter should work on your computer.
$matches = [];
preg_match('#^/~([^/]*)#', $_SERVER['REQUEST_URI'], $matches);
$homeDir = count($matches) > 1 ? $matches[1] : '';
$dataDir = "/home/$homeDir/www-data";
if(!file_exists($dataDir)){
    $dataDir = __DIR__;
}
$dbh = new PDO("sqlite:$dataDir/$dbName");
// Set our PDO instance to raise exceptions when errors are encountered.
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


/**
 * Returns an associative array with two fields:
 *  - success: false
 *  - error:  $message
 * 
 * @return An associative array describing the error.
 */
function error($message){
    return [
        'success' => false, 
        'error' => $message
    ];
}

/**
 * Creates all of the tables for this project:
 *  - QuizItems
 *  - Submissions
 *  - QuizItemResponses
 */
function createTables(){
    global $dbh;

    // Create the Users table.
    try{
        $dbh->exec('create table if not exists Users('. 
            'id integer primary key autoincrement, '. 
            'username text UNIQUE, '. 
            'password text, '. 
            'createdAt datetime default(datetime()), '.
            'updatedAt datetime default(datetime()))');
    } catch(PDOException $e){
        echo "There was an error creating the Users table: $e";
    }

    // Create the Quizzes table.
    try{
        $dbh->exec('create table if not exists Quizzes('. 
            'id integer primary key autoincrement, '. 
            'authorId integer, '.
            'name text, '. 
            'createdAt datetime default(datetime()), '.
            'updatedAt datetime default(datetime()), '.
            'foreign key(authorId) references Users(id))');
    } catch(PDOException $e){
        echo "There was an error creating the Quizzes table: $e";
    }

    // Create the QuizItems table.
    try{
        $dbh->exec('create table if not exists QuizItems('. 
            'id integer primary key autoincrement, '. 
            'quizId int, question text, answer text, '. 
            'createdAt datetime default(datetime()), '.
            'updatedAt datetime default(datetime()), '.
            'foreign key(quizId) references Quizzes(id))');
    } catch(PDOException $e){
        echo "There was an error creating the QuizItems table: $e";
    }

    // Create the Submissions table.
    try{
        $dbh->exec('create table if not exists Submissions('. 
            'id integer primary key autoincrement, '. 
            'submitterId int, quizId int, '.
            'score real, correct int, questionCount int, '. 
            'createdAt datetime default(datetime()), '.
            'updatedAt datetime default(datetime()), '.
            'foreign key(submitterId) references Users(id), '.
            'foreign key(quizId) references Quizzes(id))');
    } catch(PDOException $e){
        echo "There was an error creating the Submissions table: $e";
    }

    // Create the QuizItemResponses table.
    try{
        $dbh->exec('create table if not exists QuizItemResponses('. 
            'submissionId integer, quizItemId integer, '. 
            'response text, isCorrect boolean, '.
            'foreign key(submissionId) references Submissions(id), '.
            'foreign key(quizItemId) references QuizItems(id))');
    } catch(PDOException $e){
        echo "There was an error creating the QuizItemResponses table: $e";
    }
}

////////////////////////////////////////////////////////////////////////////////
// Users functions
////////////////////////////////////////////////////////////////////////////////
/**
 * Adds a user to the database.
 * 
 * @param $username The username to add.
 * @param $hashedPassword The password to add.
 * 
 * @return One of: {"success": true, "id": <id of new user>} or 
 *  {"success": false, "error": <error message>}. 
 */
function addUser($username, $hashedPassword){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into Users(username, password) values (:username, :password)');
        $statement->execute([
            ':username' => $username,
            ':password' => $hashedPassword
        ]);

        $id = $dbh->lastInsertId();
    } catch(PDOException $e){
        return error("There was an error adding a user: $e");
    }

    return [
        'success' => true,
        'id' => $id
    ];
}

/**
 * Gets a user entry from the database by username.
 * 
 * @param $username The username.
 * 
 * @return One of: {"success": true, <user data>} or 
 *  {"success": false, "error": <error message>}. <user data> includes the following
 *  key-value pairs:
 *    - id: The id of the user.
 *    - username: The username of the user.
 *    - password: The hashed password of the user.
 *    - createdAt: The time the user was created.
 *    - updatedAt: The time the user was last updated.
 * 
 */
function getUserByUsername($username){
    global $dbh;
    $userData = null;
    try {
        $statement = $dbh->prepare('select * from Users where username = :username');
        $statement->execute([
            ':username' => $username
        ]);
        $userData = $statement->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        return error("There was an error retrieving the user: $e");
    }

    $userData['success'] = true;
    return $userData;
}


////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Quizzes functions
////////////////////////////////////////////////////////////////////////////////
/**
 * Adds a quiz to the database.
 * 
 * @param $name The name of the quiz.
 * @param $authorId The id of the user who created the quiz.
 * 
 * @return One of: {"success": true, "id": <id of new quiz>} or 
 *  {"success": false, "error": <error message>}. 
 */
function addQuiz($name, $authorId){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into Quizzes(name, authorId) values (:name, :authorId)');
        $statement->execute([
            ':name' => $name,
            ':authorId' => $authorId
        ]);

        $id = $dbh->lastInsertId();
    } catch(PDOException $e){
        return error("There was an error adding a quiz: $e");
    }

    return [
        'success' => true,
        'id' => $id
    ];
}

/**
 * Gets all of the quizzes from the database.
 * 
 * @return If successful, an associative array with two fields: {"success": true,
 * "quizzes": <quizzes>}, where <quizzes> is an array of all the quizzes in the
 * database; each element is an associative array representing a row in the
 * database with column names as keys:
 *   - id: The id of the quiz.
 *   - name: The name of the quiz.
 *   - authorId: The author
 *   - authorUsername: The username of the author.
 *   - createdAt: The time the quiz was created.
 *   - updatedAt: The time the quiz was last updated.
 * Otherwise, an associative array that
 * looks like {"success": false, "error": <error message>}.
 */
function getQuizzes(){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare('select Users.username as authorUsername,Quizzes.* from Quizzes join Users on Users.id = authorId');
        $statement->execute();
        $quizzes = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        return error("There was an error fetching rows from QuizItems: $e");
    }

    return [
        'success' => true,
        'quizzes' => $quizzes
    ];
}

/**
 * Gets information about a specific quiz from the database.
 * 
 * @param $quizId The id of the quiz to get.
 * 
 * @return If successful, an associative array with these fields: 
 *   - success: true,
 *   - id: The id of the quiz.
 *   - name: The name of the quiz.
 *   - authorId: The author
 *   - authorUsername: The username of the author.
 *   - createdAt: The time the quiz was created.
 *   - updatedAt: The time the quiz was last updated.
 * Otherwise, an associative array that
 * looks like {"success": false, "error": <error message>}.
 */
function getQuiz($quizId){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare(
            'select Users.username as authorUsername, Quizzes.* from Quizzes '. 
            'join Users on Users.id = authorId where Quizzes.id = :quizId');
        $statement->execute([
            ':quizId' => $quizId
        ]);
        $quizInfo = $statement->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        return error("There was an error fetching rows from QuizItems: $e");
    }

    $quizInfo['success'] = true;
    return $quizInfo;
}



////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// QuizItems functions
////////////////////////////////////////////////////////////////////////////////
/**
 * Adds a quiz item to the database.
 * 
 * @param $quizId The quiz this quiz item is associated with.
 * @param $question The question to add.
 * @param $answer The answer to add.
 * 
 * @return One of: {"success": true, "id": <id of new quiz item>} or 
 *  {"success": false, "error": <error message>}. 
 */
function addQuizItem($quizId, $question, $answer){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into QuizItems(quizId, question, answer) '.
            'values (:quizId, :question, :answer)');
        $statement->execute([
            ':quizId' => $quizId, 
            ':question' => $question, 
            ':answer'  => $answer
        ]);

        $id = $dbh->lastInsertId();
    } catch(PDOException $e){
        return error("There was an error adding a quiz item: $e");
    }

    return [
        'success' => true,
        'id' => $id
    ];
}

/**
 * Gets all of the quiz items from the database.
 * 
 * @return If successful, an associative array with two fields: {"success": true,
 * "quizItems": <items>}, where <items> is an array of all the quiz items in the
 * database; each element is an associative array representing a row in the
 * database with column names as keys:
 *   - id: The id of the quiz item.
 *   - question: The question.
 *   - answer: The answer.
 *   - createdAt: The time the quiz item was created.
 *   - updatedAt: The time the quiz item was last updated.
 * Otherwise, an associative array that
 * looks like {"success": false, "error": <error message>}.
 */
function getQuizItems(){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare('select * from QuizItems');
        $statement->execute();
        $quizItems = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        return error("There was an error fetching rows from QuizItems: $e");
    }

    return [
        'success' => true,
        'quizItems' => $quizItems
    ];
}

/**
 * Gets all of the quiz items from the database associated with the given quizId.
 * 
 * @param $quizId The id of the quiz to get quiz items for.
 * 
 * @return If successful, an associative array with two fields: {"success": true,
 * "quizItems": <items>}, where <items> is an array of all the quiz items in the
 * database; each element is an associative array representing a row in the
 * database with column names as keys:
 *   - id: The id of the quiz item.
 *   - question: The question.
 *   - answer: The answer.
 *   - createdAt: The time the quiz item was created.
 *   - updatedAt: The time the quiz item was last updated.
 * Otherwise, an associative array that
 * looks like {"success": false, "error": <error message>}.
 */
function getQuizItemsForQuiz($quizId){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare('select * from QuizItems where quizId = :quizId');
        $statement->execute([':quizId' => $quizId]);
        $quizItems = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        return error("There was an error fetching rows from QuizItems: $e");
    }

    return [
        'success' => true,
        'quizItems' => $quizItems
    ];
}

/**
 * Removes a quiz item from the database.
 * 
 * @param $quizItemId The id of the quiz item to remove.
 * 
 * @return One of: {"success": true} or {"success": false, "error": <error message>}.
 */
function removeQuizItem($quizItemId){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare('delete from QuizItems where id = :id');
        $statement->execute([':id' => $quizItemId]);
    } catch(PDOException $e){
        return error("There was an error removing data from QuizItems: $e");
    }

    return [
        'success' => true
    ];
}

/**
 * Updates a quiz item in the database.
 * 
 * @param $quizItemId The id of the quiz item to update.
 * @param $question The new question.
 * @param $answer The new answer.
 * 
 * @return One of: {"success": true} or {"success": false, "error": <error message>}.
 */
function updateQuizItem($quizItemId, $quizId, $question, $answer){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare('update QuizItems set question = :question, '. 
            'quizId = :quizId, '.
            'answer = :answer, updatedAt = datetime("now") where id = :id');
        $statement->execute([
            ':id' => $quizItemId,
            ':quizId' => $quizId,
            ':question' => $question,
            ':answer' => $answer
        ]);
    } catch(PDOException $e){
        return error("There was an error updating data in QuizItems: $e");
    }

    return [
        'success' => true
    ];
}

////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Submissions functions
////////////////////////////////////////////////////////////////////////////////
/**
 * Adds a submission to the database.
 * 
 * @param $submiterId The id of the user who submitted the quiz.
 * @param $quizId The id of the quiz the user submitted.
 * @param $questionCount The total number of questions in the quiz.
 * @param $correct The number of questions the user answered correctly.
 *  
 * @return One of: {"success": true, "id": <id of new submission item>} or 
 *  {"success": false, "error": <error message>}. 
 */
function addSubmission($submiterId, $quizId, $questionCount, $correct){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into Submissions(submitterId, quizId, score, questionCount, correct) '.
            'values (:submitterId, :quizId, :score, :questionCount, :correct)');
        $statement->execute([
            ':submitterId' => $submiterId,
            ':quizId' => $quizId,
            ':score' => $correct / $questionCount,
            ':questionCount' => $questionCount,
            ':correct' => $correct
        ]);

        $id = $dbh->lastInsertId();
    } catch(PDOException $e){
        return error("There was an error adding a submission: $e");
    }

    return [
        'success' => true,
        'id' => $id
    ];
}

/**
 * Gets all of the submissions from the database, including the associated
 * QuizItemResponses.
 * 
 * @return If successful, an associative array with two fields: {"success": true,
 * "submissions": <submissions>}, where <submissions> is an array of submissions; 
 * each element is an associative array that contains the following data:
 *     - id: The id of the submission.
 *     - submitterUsername: The id of the user who submitted the quiz.
 *     - quizId: The id of the quiz the user submitted.
 *     - score: The score of the submission.
 *     - questionCount: The total number of questions in the quiz.
 *     - correct: The number of questions the user answered correctly.
 *     - createdAt: The time the submission was created.
 *     - updatedAt: The time the submission was last updated.
 *     - responses: An array of associative arrays, each representing a row in
 *                 the QuizItemResponses table with column names as keys.
 * Otherwise, an associative array that looks like {"success": false, 
 * "error": <error message>}.
 */
function getSubmissions(){
    global $dbh;
    $submissions = [];
    try {
        // Just submitterId
        // $statement = $dbh->prepare('select * from Submissions');
        // Replace submitterId with submitterUsername
        $statement = $dbh->prepare('select Submissions.id, username as '. 
            'submitterUsername, quizId, score, questionCount, correct, '. 
            'Submissions.createdAt, submissions.updatedAt '.
            'from Submissions join Users on Users.id = submitterId');
        $statement->execute();
        $submissions = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Now we need to get the list of quiz item responses for each submission.
        // foreach($submissions as $submission){
        for($i = 0; $i < count($submissions); $i++){
            $submission = $submissions[$i];
            $statement = $dbh->prepare('select * from QuizItemResponses where submissionId = :submissionId');
            $statement->execute([':submissionId' => $submission['id']]);
            $submissions[$i]['responses'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e){
        return error("There was an error fetching rows from Submissions/QuizItemResponses: $e");
    }

    return [
        'success' => true,    
        'submissions' => $submissions
    ];

}

////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// QuizItemResponses functions
////////////////////////////////////////////////////////////////////////////////

/**
 * Adds a quiz item response to the database.
 * 
 * @param $quizItemId The id of the quiz item this is a response to.
 * @param $submissionId The id of the submission this is associated with.
 * @param $response The user resposne to the question.
 * @param $isCorrect True if the response is correct, false otherwise.
 * 
 * @return One of: {"success": true, "id": <id of new quiz item response>} or 
 *  {"success": false, "error": <error message>}. 
 */
function addQuizItemResponse($quizItemId, $submissionId, $response, $isCorrect){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into QuizItemResponses(quizItemId, submissionId, response, isCorrect) '.
            'values (:quizItemId, :submissionId, :response, :isCorrect)');
        $statement->execute([
            ':quizItemId' => $quizItemId,
            ':submissionId' => $submissionId,
            ':response' => $response,
            ':isCorrect' => $isCorrect
        ]);
    } catch(PDOException $e){
        return error("There was an error adding a quiz item response: $e");
    }

    return [
        'success' => true,
        'id' => $id
    ];
}


createTables();
?>