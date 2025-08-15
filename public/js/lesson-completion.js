(function($) {
    'use strict';

    $(document).ready(function() {
        $('#dame-complete-lesson-btn').on('click', function() {
            var $button = $(this);
            var lessonId = $button.data('lesson-id');
            var $feedbackDiv = $('#dame-lesson-completion-feedback');

            $button.prop('disabled', true);
            $feedbackDiv.text('Processing...');

            $.ajax({
                url: dame_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dame_complete_lesson',
                    nonce: dame_ajax.nonce,
                    lesson_id: lessonId
                },
                success: function(response) {
                    if (response.success) {
                        $feedbackDiv.text(response.data).css('color', 'green');
                        $button.hide();
                    } else {
                        $feedbackDiv.text(response.data).css('color', 'red');
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    $feedbackDiv.text('An error occurred.').css('color', 'red');
                    $button.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
