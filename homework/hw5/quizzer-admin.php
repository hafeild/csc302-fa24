<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> 
    <script src="quizzer.js"></script>
    <title>Quizzer</title>

    <!-- TODO: add styles. -->
    <style>
        .incorrect {
            background-color: rgba(161, 11, 11, 0.172);
        }

        .correct {
            background-color: rgba(11, 161, 11, 0.172);
        }

        .panel {
            border: 1px solid black;
            padding: 1em;
            padding-top: 0;
            margin-bottom: 0.5em;
        }

        .hidden {
            display: none;
        }

        .toggle-link {
            margin-bottom: 1em;
            display: inline-block;
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }

        /* body.quizzer .quizzer-admin {
            display: none;
        }

        body.quizzer-admin .quizzer {
            display: none;
        } */

    </style>
</head>
<body class="quizzer">
    <h1>Quizzer</h1>
    <a href="quizzer.php" class="quizzer-admin">Switch to Quizzer</a>

    <div id="quiz-admin-panel" class="panel quizzer-admin">
        <h2>Quiz Admin</h2>
        <form action="quizzer.php" method="get">
        <table id="quiz-admin-questions">
            <tr>
                <th>Question</th>
                <th>Answer</th>
                <th></th>
            </tr>
        </table>
        <button id="add-question" onclick="return false;">Add question</button>
        <input type="submit">Generate quiz</button>
    </form>
    </div>

    <footer>
        <?php 
            echo "Generated on: " . date("Y-m-d");
        ?>
    </footer>
</body>
</html>