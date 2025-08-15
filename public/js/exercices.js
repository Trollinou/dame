(function($) {
    'use strict';

    $(document).ready(function() {
        let scoreCorrect = 0;
        let scoreAttempted = 0;
        let currentExerciseId = null;

        // Start fetching exercises
        $('#dame-start-exercices').on('click', function() {
            fetchNextExercise();
        });

        // Delegate click for answer submission
        $('#dame-exercice-display').on('click', '#dame-submit-answer', function() {
            submitAnswer();
        });

        // Delegate click for next exercise
        $('#dame-exercice-display').on('click', '#dame-next-exercice', function() {
            fetchNextExercise();
        });


        function fetchNextExercise() {
            const difficulty = $('#dame-difficulty-filter').val();
            const category = $('#dame-category-filter').val();
            const displayDiv = $('#dame-exercice-display');

            displayDiv.html('<p>Chargement du prochain exercice...</p>');

            $.ajax({
                url: dame_exercices_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dame_fetch_exercice',
                    nonce: dame_exercices_ajax.nonce,
                    difficulty: difficulty,
                    category: category,
                    exclude: currentExerciseId // To avoid showing the same one twice in a row
                },
                success: function(response) {
                    if (response.success) {
                        displayDiv.html(response.data.html);
                        currentExerciseId = response.data.id;
                    } else {
                        displayDiv.html('<p>' + response.data + '</p>');
                    }
                },
                error: function() {
                    displayDiv.html('<p>Une erreur est survenue.</p>');
                }
            });
        }

        function submitAnswer() {
            const exerciseId = $('#dame-exercice-id').val();
            const answerData = $('#dame-exercice-form').serialize();
            const solutionDiv = $('#dame-exercice-solution');
            const submitButton = $('#dame-submit-answer');

            submitButton.prop('disabled', true);

            $.ajax({
                url: dame_exercices_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dame_check_answer',
                    nonce: dame_exercices_ajax.nonce,
                    exercise_id: exerciseId,
                    answer: answerData
                },
                success: function(response) {
                    scoreAttempted++;
                    $('#dame-score-attempted').text(scoreAttempted);

                    if (response.success) {
                        if (response.data.correct) {
                            scoreCorrect++;
                            $('#dame-score-correct').text(scoreCorrect);
                            solutionDiv.before('<p style="color:green;">' + response.data.message + '</p>');
                        } else {
                             solutionDiv.before('<p style="color:red;">' + response.data.message + '</p>');
                        }
                        solutionDiv.html(response.data.solution).show();
                        submitButton.hide();
                        $('#dame-next-exercice').show();
                    } else {
                        solutionDiv.before('<p style="color:red;">' + response.data + '</p>');
                        submitButton.prop('disabled', false);
                    }
                },
                error: function() {
                     solutionDiv.before('<p style="color:red;">Une erreur est survenue.</p>');
                     submitButton.prop('disabled', false);
                }
            });
        }
    });

})(jQuery);
