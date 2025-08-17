(function($) {
    'use strict';

    $(document).ready(function() {
        $('#dame-submit-answer').on('click', function() {
            const submitButton = $(this);
            const exerciseId = $('#dame-exercice-id').val();
            const answerData = $('#dame-exercice-form').serialize();
            const feedbackDiv = $('#dame-exercice-feedback');
            const solutionDiv = $('#dame-exercice-solution');

            submitButton.prop('disabled', true);
            feedbackDiv.html('<p>Vérification...</p>');

            $.ajax({
                url: dame_single_exercice_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dame_check_answer', // We can reuse the same AJAX action
                    nonce: dame_single_exercice_ajax.nonce,
                    exercise_id: exerciseId,
                    answer: answerData
                },
                success: function(response) {
                    if (response.success) {
                        const inputs = $('#dame-exercice-form input[name="dame_answer[]"]');
                        const userSelected = response.data.user_selected_indices || [];
                        const correctAnswers = response.data.correct_indices || [];

                        // Disable all inputs and the submit button
                        inputs.prop('disabled', true);
                        submitButton.hide();

                        // Apply highlighting
                        inputs.each(function() {
                            const input = $(this);
                            const inputValue = parseInt(input.val(), 10);
                            const label = input.closest('label');
                            const isSelected = userSelected.includes(inputValue);
                            const isCorrect = correctAnswers.includes(inputValue);

                            if (isCorrect) {
                                label.addClass('correct-answer'); // Highlight all correct answers green
                            }
                            if (isSelected && !isCorrect) {
                                label.addClass('user-incorrect-choice'); // Strike through user's incorrect choices
                            }
                        });

                        // Display feedback message
                        let feedbackHtml = '';
                        if (response.data.correct) {
                            feedbackHtml = '<p style="color:green;">' + response.data.message + '</p>';
                        } else {
                            feedbackHtml = '<p style="color:red;">' + response.data.message + '</p>';
                        }
                        feedbackDiv.html(feedbackHtml);
                        solutionDiv.html(response.data.solution).show();

                    } else {
                        feedbackDiv.html('<p style="color:red;">' + response.data + '</p>');
                        submitButton.prop('disabled', false);
                    }
                },
                error: function() {
                    feedbackDiv.html('<p style="color:red;">Une erreur est survenue.</p>');
                    submitButton.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
