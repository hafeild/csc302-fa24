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


?>
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

    <?php
        // TODO: add the table summarizing submissions.

    ?>
</body>
</html>