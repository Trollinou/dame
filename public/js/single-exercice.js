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
                        let message = '';
                        if (response.data.correct) {
                            message = '<p style="color:green;">' + response.data.message + '</p>';
                        } else {
                            message = '<p style="color:red;">' + response.data.message + '</p>';
                        }
                        feedbackDiv.html(message);
                        solutionDiv.html(response.data.solution).show();
                        submitButton.hide();
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
