var questions = [];
var questionCounter = 0;

$(document).ready(function(){

    // Add listeners to the buttons.
    $(document).on('click', '#add-question', addQuestion);
    $(document).on('click', '.remove-question', removeQuestion);
    //$(document).on('click', '#save-quiz', saveQuiz);
    // TODO: handle quiz reset
    $(document).on('click', '#reset-quiz', saveQuiz);
    // TODO: handle quiz check
    // $(document).on('click', '#check-quiz', checkQuiz);

    $(window).on('hashchange', function(){
        var mode = window.location.hash.match(/^#?([^?]*)/)[1]; 
        if(mode == 'admin'){
            $('body').addClass('quizzer-admin');
            $('body').removeClass('quizzer');
        } else {
            $('body').removeClass('quizzer-admin');
            $('body').addClass('quizzer');
        }
    });
    // Load the questions from local storage.
    // loadList();
    // populateQuiz(questions);
});

/**
 * Saves the quiz questions from the admin panel, updates the quiz panel.
 */
function saveQuiz(){
    // Extract all of the questions and answers.
    questions = []; // Resets the questions.
    $('#quiz-admin-questions .question').each(function(i, elm){
        var $row = $(elm).parents('tr');
        var question = $(elm).val();
        var answer = $row.find('.answer').val();
        questions.push({question: question, answer: answer});
    });

    // Save quiz.
    // TODO HW2
    localStorage.setItem('questions', JSON.stringify(questions));

    // Update quiz panel.
    populateQuiz(questions);
}

/**
 * Re-populates the quiz with the given questions.
 * 
 * @param questions A list of question/answer pairs (each item is an object
 *                  with the fields 'question' and 'answer').
 */
function populateQuiz(questions){
    var $quiz = $('#quiz')
    $quiz.html('');
    $('#score').html('');

    for(var i = 0; i < questions.length; i++){
        $quiz.append(`<li data-id="${i}">${questions[i].question}<br/>`+
            '<textarea rows="3" class="response"></textarea></li>');
    }
}

/**
 * Re-populates the quiz admin panel with the given questions and answers.
 * 
 * @param questions A list of question/answer pairs (each item is an object
 *                  with the fields 'question' and 'answer').
 */
function populateQuizAdmin(questions){
    var $quiz = $('#quiz-admin-questions')
    $quiz.html('');

    for(var i = 0; i < questions.length; i++){
        $quiz.append(`<tr><td><textarea rows="2" class="question">${questions[i].question}</textarea></td>`+
            `<td><textarea rows="2" class="answer">${questions[i].answer}</textarea></td>`+
            '<td><button class="remove-question">Delete</button></td></tr>');
    }
}


/**
 * Adds a new row to the quiz admin question editor table.
 */
function addQuestion(){
    var newRow = `<tr><td><textarea name="question-${questionCounter}" rows="2" class="question"></textarea></td>`+
        `<td><textarea name="answer-${questionCounter}" rows="2" class="answer"></textarea></td>`+
        '<td><button class="remove-question" onclick="return false;">Delete</button></td></tr>';
    $('#quiz-admin-questions').append(newRow);
    questionCounter++;
}

/**
 * Removes a new row to the quiz admin question editor table. It is assumed that
 * this is called with the context (this) of the specific "remove" button that
 * was clicked.
 */
function removeQuestion(){
    $(this).parents('tr').remove();
}

/**
 * Checks each of the answers in the quiz and marks them as correct/incorrect.
 * Also tallies up a score and records it.
 */
function checkQuiz(){
    var correct = 0;
    $('#quiz .response').each(function(i, elm){
        // TODO: check the answer and mark it as correct/incorrect.
        var $row = $(elm).parents('li')

        // Check the value of the .response textarea against the solution.
        var response = $(elm).val();
        var questionIndex = parseInt($row.data('id'));
        // questions[questionIndex]['answer']
        if(response == questions[questionIndex].answer){
            $row.addClass('correct');
            correct++;
        } else {
            $row.addClass('incorrect');
        }
    });
    $('#score').html(`Score: ${correct}/${questions.length} = ${correct/questions.length}`);
}

/**
 * Loads the questions from local storage if there are any.
 */
function loadList(){
    //if(localStorage.getItem('questions') !== null){ <-- this will trigger if there is a key called 'questions' in the local storage, even if the value is blank
    if(localStorage.getItem('questions')){ // <-- this will trigger if there is a key called 'questions' in the local storage, and the value isn't undefined, null, or blank; we could add better safety checks here (make sure it's an array and formatted as we want it to).
        questions = JSON.parse(localStorage.getItem('questions'));
        populateQuiz(questions);
        populateQuizAdmin(questions);
    }
}