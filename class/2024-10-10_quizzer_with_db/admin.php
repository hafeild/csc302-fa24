<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDO demo</title>
    <style>
        table, tr, td, th {
            border: 1px solid gray;
        }
    </style>
</head>
<body>

<?php
// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('db.php');


// Add a quiz item.
if(array_key_exists('question', $_POST)){
    addQuizItem($_POST['question'], $_POST['answer']);
}

?>
    <h1>Add quiz item</h1>
    <form method="post">
        Question: <input type="text" name="question"/><br/>
        Answer: <input type="text" name="answer"/><br/>
        <input type="submit" value="Add quiz item"/>
    </form>

    <h1>QuizItems table</h1>
    <table>
        <tr><th>id</th><th>question</th><th>answer</th><th>createdAt</th><th>updatedAt</th></tr>

        <?php
            $columns = ['id', 'question', 'answer', 'createdAt', 'updatedAt'];
            $quizItems = getQuizItems();
            foreach($quizItems as $quizItem){
                echo "<tr>";
                foreach($columns as $col){
                    echo "<td>${quizItem[$col]}</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        ?>
    </table>

</body>
</html>