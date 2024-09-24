<?php
$quiz = array(
    array("question" => "What is the capital of Canada?",
        "answer" => "Ottawa"),
    array("question" => "Question 2",
        "answer" => "Answer 2")
);
// Come back to here and extract QA pairs from $_GET.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> 
    <script src="quizzer.js"></script>
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
    <h1>Quizzer</h1>
    <a href="quizzer-admin.php" class="quizzer">Switch to Quizzer Admin</a>

    <div id="quiz-panel" class="panel quizzer">
        <h2>Quiz</h2>
        <span id="score"></span>
        <form>
            <ol id="quiz">
            
            <?php
            $i = 1;
            foreach($quiz as $qaPair) {
                $questionResponseId = "q${i}_response";
                $correctnessClass = "";
                if(array_key_exists($questionResponseId, $_GET)){
                    if($_GET[$questionResponseId] == $qaPair["answer"]){
                        $correctnessClass = "correct";
                    } else {
                        $correctnessClass = "incorrect";
                    }
                }
                    
            ?>
                <li data-id="<?= $i ?>" class="<?= $correctnessClass ?>">
                    
                    <?= $qaPair["question"] ?><br/>
                    <textarea name="<?= $questionResponseId ?>" rows="3" class="response"><?= $_GET[$questionResponseId] ?></textarea></li>
            <?php
                $i++;
            }
            ?>
        
            </ol>
            <button id="check-quiz">Check</button>
        </form>

        <button id="reset-quiz">Reset</button>
    </div>

    <footer>
        <?php 
            echo "Generated on: " . date("Y-m-d");
        ?>
    </footer>
</body>
</html>