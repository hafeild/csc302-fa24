var questions = [];
var username, userURI, quizId, quizInfo;

$(document).ready(function(){

    // Add listeners to the buttons.
    $(document).on('click', '.new-quiz', addQuiz);


    $(document).on('click', '#add-question', addQuestion);
    $(document).on('click', '.remove-question', removeQuestion);
    $(document).on('click', '#save-quiz', saveQuiz);
    $(document).on('click', '#reset-quiz', saveQuiz);
    $(document).on('click', '#check-quiz', checkQuiz);

    $(document).on('click', '.toggle-quiz-name-edit', toggleEditQuizName);
    $(document).on('click', '.update-quiz-name', updateQuizName);
    $(window).on('hashchange', renderView);

    $(document).on('click', '.signout', signout);

    loadUserOrBoot();
    $('.username').html(username);

    renderView();
});

/**
 * Load the user's information from localStorage.
 */
function loadUserOrBoot(){
    // Redirect the user to sign in if they aren't already signed in.
    if(localStorage.getItem('username') === null){
        window.location.href = 'signin.html';
    }
    username = localStorage.getItem('username');
    userURI = localStorage.getItem('userURI');
}

/**
 * Determines which panel to show: the quiz or the admin panel. If the 
 * URI's hash is '#admin', the admin panel is displayed, otherwise the
 * quiz view is shown.
 */
function renderView(){
    var hash = window.location.hash.match(/^#?([^?]*)/)[1];
    var quizIdMatches = window.location.hash.match(/id=(\w+)/);
    if(quizIdMatches && quizIdMatches.length > 0){
        quizId = quizIdMatches[1];
    }

    $('.panel').addClass('hidden');

    console.log('hash', hash);

    if(hash === 'admin'){
        loadQuiz(populateQuizAdmin);
        $('#quiz-admin-panel').removeClass('hidden');
    } else if(hash == 'quiz') {
        loadQuiz(populateQuiz);
        $('#quiz-panel').removeClass('hidden');
    } else {
        loadQuizzes();
        $('#home-panel').removeClass('hidden');
    }
}


/**
 * Retrieves a list of all quizzes and updates the list.
 */
function loadQuizzes(){
    // User quizzes.
    $.ajax('/quizzes', {
        method: 'get',
        headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt')}`
        },
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            var $allQuizzes = $('.all-quizzes');
            var $myQuizzes = $('.my-quizzes'); 
            var i = 0;
            if(data.quizzes.length > 0){
                $allQuizzes.html('');
            }
            for(i = 0; i < data.quizzes.length; i++){
                var quiz = data.quizzes[i];

                // Add every quiz to the "all quizzes" list.
                $allQuizzes.append(`<li data-uri="${quiz.quizURI}">${quiz.name}&mdash;created by ${quiz.authorUsername} on ${quiz.createdAt}</li>`);
                // Add only quizzes corresponding to the current user to the
                // "My quizzes" list.
                if(quiz.authorURI == userURI){
                    $myQuizzes.append(`<li data-uri="${quiz.quizURI}">`+
                        `<span class="quiz-name-edit hidden"><input class="edited-quiz-name" type="text" `+
                        `value="${quiz.name}"/> <button class="update-quiz-name">update</button>`+
                        `<button class="toggle-quiz-name-edit">cancel</button></span>`+
                        `<span class="quiz-name"><a href="#quiz?id=${quiz.id}">${quiz.name}</a> `+
                        `(<a href="#" class="toggle-quiz-name-edit">edit name</a> | `+
                        `<a href="#admin?id=${quiz.id}">edit quiz</a>)`+
                        `</span>&mdash;created by ${quiz.authorUsername} on ${quiz.createdAt}</li>`);
                }
            }

        },
        error: function(jqXHR, textStatus, errorThrown){
            console.log(textStatus, errorThrown, jqXHR);
            alert(`There was an error with your request! ${textStatus} ${errorThrown}: ${jqXHR.responseJSON.error}`);
        
            if(jqXHR.status === 401){
                signout();
            }
        }
    });
}


/**
 * Toggles whether quiz names are editable in a quiz listing.
 */
function toggleEditQuizName(){
    var $quizLi = $(this).parents('li');
    $quizLi.find('.quiz-name-edit').toggleClass('hidden');
    $quizLi.find('.quiz-name').toggleClass('hidden');
}

/**
 * Updates the name of a quiz.
 */
function updateQuizName(){
    var $quizLi = $(this).parents('li');
    var newName = $quizLi.find('.edited-quiz-name').val();
    var quizURI = $quizLi.data('uri');
    console.log('quizURI', quizURI);
    $.ajax(quizURI, {
        method: 'post',
        headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt')}`
        },
        dataType: 'json',
        data: {
            _method: 'patch',
            name: newName
        },
        success: function(data, textStatus, jqXHR){
            window.location.reload();
        },
        error: function(jqXHR, textStatus, errorThrown){
            console.log(textStatus, errorThrown, jqXHR);
            alert(`There was an error with your request! ${textStatus} ${errorThrown}: ${jqXHR.responseJSON.error}`);
        }
    });
}

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
    localStorage.setItem('questions', JSON.stringify(questions));

    // Update quiz panel.
    populateQuiz(questions);
}

/**
 * Loads a quiz.
 */
function loadQuiz(nextFunction){
    $.ajax(`/quizzes/${quizId}`, {
        method: 'get',
        headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt')}`
        },
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            quizInfo = data;
            $('.quiz-name').html(data.name);
            $('.quiz-description .author-id').html(data.authorUsername);
            $('.quiz-description .created-at').html(data.createdAt);

            if(data.authorURI === userURI){
                $('.edit-quiz').removeClass('hidden');
                $('.edit-quiz a').attr('href', `#admin?id=${quizId}`);
            } else {
                $('.edit-quiz').addClass('hidden');
            }
            nextFunction();
        },
        error: function(jqXHR, textStatus, errorThrown){
            console.log(textStatus, errorThrown, jqXHR);
            alert(`There was an error with your request! ${textStatus} ${errorThrown}: ${jqXHR.responseJSON.error}`);
        
            if(jqXHR.status === 401){
                signout();
            }
        }
    });
}


/**
 * Re-populates the quiz with the given questions.
 * 
 * @param questions A list of question/answer pairs (each item is an object
 *                  with the fields 'question' and 'answer').
 */
function populateQuiz(questions){
    $('#score').html('');
    $('#quiz').html('');

    $.ajax(`/quizzes/${quizId}/quizitems`, {
        method: 'get',
        headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt')}`
        },
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            for(var i = 0; i < data.quizItems.length; i++){
                var quizItem = data.quizItems[i];
                $quiz.append(`<li data-id="${quizItem.id}">${quizItem.question}<br/>`+
                    '<textarea rows="3" class="response"></textarea></li>');
            }
        },
        error: function(jqXHR, textStatus, errorThrown){
            console.log(textStatus, errorThrown, jqXHR);
            alert(`There was an error with your request! ${textStatus} ${errorThrown}: ${jqXHR.responseJSON.error}`);
        
            if(jqXHR.status === 401){
                signout();
            }
        }
    });


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
        
    }

    $.ajax(`/quizzes/${quizId}/quizitems`, {
        method: 'get',
        headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt')}`
        },
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            for(var i = 0; i < data.quizItems.length; i++){
                var quizItem = data.quizItems[i];
                $quiz.append(`<tr data-id="${quizItem.id}"><td>`+
                    `<textarea rows="2" class="question">${quizItem.question}</textarea></td>`+
                    `<td><textarea rows="2" class="answer">${quizItem.answer}</textarea></td>`+
                    '<td><button class="remove-question">Delete</button></td></tr>');
            }
        },
        error: function(jqXHR, textStatus, errorThrown){
            console.log(textStatus, errorThrown, jqXHR);
            alert(`There was an error with your request! ${textStatus} ${errorThrown}: ${jqXHR.responseJSON.error}`);
        
            if(jqXHR.status === 401){
                signout();
            }
        }
    });

}

/**
 * Adds a new quiz to the server and the UI.
 */
function addQuiz(){
    // Add a new quiz to the server.
    // Upon hearing back, change the view to #admin.
    // Clear the admin page.
    $.ajax('/quizzes', {
        method: 'post',
        headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt')}`
        },
        data: {name: $('#new-quiz-name').val()},
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            window.location.reload();
        },
        error: function(jqXHR, textStatus, errorThrown){
            console.log(textStatus, errorThrown, jqXHR);
            alert(`There was an error with your request! ${textStatus} ${errorThrown}: ${jqXHR.responseJSON.error}`);
        }
    });
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
 * Creates an object compatable with the server's expected format for the user's responses:
 * {
 *     'questions': [{
 *        'id': ...,
 *        'question': ...,
 *        'response': ...
 *     }]
 * }
 * @returns An object with the user's responses.
 */
function getUserResponses(){
    var questions = [];
    $('#quiz .response').each(function(i, elm){
        var $response = $(elm);
        var $row = $response.parents('li');
        var $question = $row.find('.question');
        var questionId = parseInt($row.data('id'));
        var response = $response.val();
        var question = $question.html();
        questions.push({
            'id': questionId, 
            'question': question,
            'response': response
        });
    });

    return {'questions': questions};
}

/**
 * Checks each of the answers in the quiz and marks them as correct/incorrect.
 * Also tallies up a score and records it.
 */
function checkQuiz(event){
    event.preventDefault();

    // Call the server to check the answers.
    $.ajax('grade.php',{
        method: 'post',
        dataType: 'json',
        data: {response: JSON.stringify(getUserResponses())},
        success: function(data){
            console.log(data);

            $('#quiz li').each(function(i, elm){
                var $row = $(elm);
                var id = parseInt($row.data('id'));
                var $response = $row.find('.response');

                if(data['questions'][id] != undefined){
                    $row.removeClass(['correct', 'incorrect']);

                    if(data['questions'][id]['correct']){
                        $row.addClass('correct');
                    } else {
                        $row.addClass('incorrect');
                    }
                }

            });
            $('#score').html(`${data['score']} / ${data['questions'].length} = ${data['score']/data['questions'].length}`);
        }
    });

    return 0;
}


/**
 * Signs the user out and removes their data from localStorage.
 */
function signout(){
    localStorage.removeItem('username');
    localStorage.removeItem('userURI');
    localStorage.removeItem('jwt');
    loadUserOrBoot(); // This will boot the user over to the signin page.

}