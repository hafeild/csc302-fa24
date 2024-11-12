<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> 
    <script src="/quizzer.js"></script>
    <!-- <script>
        questions = <?= json_encode($quiz) ?>;

    </script> -->
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

        body.quizzer .quizzer-admin {
            display: none;
        }

        body.quizzer-admin .quizzer {
            display: none;
        }

    </style>
</head>
<body class="quizzer">
        <?= json_encode($quiz) ?>

    <h1>Quizzer</h1>
    <div id="account-info">
        <span class="username"></span> | 
        <a href="#" class="signout">Signout</a>
        <?php 
        if($jwtData['payload']['user-id'] == $quiz['authorId']) { 
        ?>
            | <a href="/<?= $quiz['quizURI'] ?>/edit" class="edit">Edit</a>
        <?php 
        } 
        ?>
    </div>


    <div id="quiz-panel" class="panel quizzer">
        <h2>Quiz</h2>
        <span id="score"></span>
        <form id="response-form">
            <ol id="quiz">
            
            <?php
            foreach($quiz['quizItems'] as $quizItem) {
            ?>
                <?= json_encode($quizItem) ?>
                <li data-id="<?= $quizItem['id'] ?>" data-uri="<?= $quizItem['uri'] ?>">
                    
                    <span class="question"><?= $quizItem["question"] ?></span><br/>
                    <textarea rows="3" class="response"></textarea></li>
            <?php
            }
            ?>
        
            </ol>
            <button id="check-quiz">Check</button>
            <button id="reset-quiz" onclick="return false;">Reset</button>

        </form>

    </div>

    <footer>
        <?php 
            echo "Generated on: " . date("Y-m-d");
        ?>
    </footer>
</body>
</html>