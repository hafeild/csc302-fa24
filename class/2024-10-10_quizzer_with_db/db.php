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
 * Creates all of the tables for this project:
 *  - QuizItems
 *  - Submissions
 *  - QuizItemResponses
 */
function createTables(){
    global $dbh;

    // Create the QuizItems table.
    try{
        $dbh->exec('create table if not exists QuizItems('. 
            'id integer primary key autoincrement, '. 
            'question text, answer text, '. 
            'createdAt datetime default(datetime()), '.
            'updatedAt datetime default(datetime()))');
    } catch(PDOException $e){
        echo "There was an error creating the QuizItems table: $e";
    }

    // Create the Submissions table.
    try{
        $dbh->exec('create table if not exists Submissions('. 
            'id integer primary key autoincrement, '. 
            'score real, correct int, questionCount int, '. 
            'createdAt datetime default(datetime()), '.
            'updatedAt datetime default(datetime()))');
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
// QuizItems functions
////////////////////////////////////////////////////////////////////////////////
/**
 * Adds a quiz item to the database.
 * 
 * @param $question The question to add.
 * @param $answer The answer to add.
 * 
 * @return The id of the newly added quiz item.
 */
function addQuizItem($question, $answer){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into QuizItems(question, answer) '.
            'values (:question, :answer)');
        $statement->execute([
            ':question' => $question, 
            ':answer'  => $answer
        ]);

        $id = $dbh->lastInsertId();
    } catch(PDOException $e){
        echo "There was an error adding a quiz item: $e";
    }

    return $id;
}

/**
 * Gets all of the quiz items from the database.
 * 
 * @return An array of all the quiz items in the database; each element is an
 *  associative array representing a row in the database with column names as
 *  keys.
 */
function getQuizItems(){
    global $dbh;
    $quizItems = [];
    try {
        $statement = $dbh->prepare('select * from QuizItems');
        $statement->execute();
        $quizItems = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        echo "There was an error fetching rows from QuizItems: $e";
    }

    return $quizItems;
}

////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Submissions functions
////////////////////////////////////////////////////////////////////////////////
/**
 * Adds a submission to the database.
 * 
 * @param $questionCount The total number of questions in the quiz.
 * @param $correct The number of questions the user answered correctly.
 *  
 * @return The id of the newly added quiz item.
 */
function addSubmission($questionCount, $correct){
    global $dbh;
    $id = null;
    try {
        $statement = $dbh->prepare(
            'insert into Submissions(score, questionCount, correct) '.
            'values (:score, :questionCount, :correct)');
        $statement->execute([
            ':score' => $correct / $questionCount,
            ':questionCount' => $questionCount,
            ':correct' => $correct
        ]);

        $id = $dbh->lastInsertId();
    } catch(PDOException $e){
        echo "There was an error adding a submission: $e";
    }

    return $id;
}

/**
 * Gets all of the submissions from the database, including the associated
 * QuizItemResponses.
 * 
 * @return An array of submissions; each element is an associative array that
 *  contains the following data:
 *     - id: The id of the submission.
 *     - score: The score of the submission.
 *     - total: The total number of questions in the quiz.
 *     - correct: The number of questions the user answered correctly.
 *     - createdAt: The time the submission was created.
 *     - updatedAt: The time the submission was last updated.
 *     - responses: An array of associative arrays, each representing a row in
 *                 the QuizItemResponses table with column names as keys.
 */
function getSubmissions(){
    global $dbh;
    $submissions = [];
    try {
        # TODO: implement this.
    } catch(PDOException $e){
        echo "There was an error fetching rows from Submissions/QuizItemResponses: $e";
    }

    return $submissions;
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
 * @return The id of the newly added quiz item response.
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
        echo "There was an error adding a quiz item response: $e";
    }

    return $id;
}


createTables();
?>