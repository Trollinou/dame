(function($) {
    'use strict';

    $(document).ready(function() {
        $('#dame-contact-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var messageContainer = $('#dame-contact-form-messages');
            var submitButton = form.find('button[type="submit"]');

            // Clear previous messages
            messageContainer.text('').removeClass('success error');

            // Client-side validation
            var name = $('#dame_contact_name').val();
            var email = $('#dame_contact_email').val();
            var subject = $('#dame_contact_subject').val();
            var message = $('#dame_contact_message').val();

            if (!name || !email || !subject || !message) {
                messageContainer.text('Veuillez remplir tous les champs obligatoires.').css('color', 'red');
                return;
            }

            // Disable button
            submitButton.prop('disabled', true);
            messageContainer.text('Envoi en cours...').css('color', 'inherit');

            var formData = form.serialize();

            $.ajax({
                type: 'POST',
                url: dame_contact_ajax.ajax_url,
                data: formData + '&action=dame_contact_submit',
                success: function(response) {
                    if (response.success) {
                        messageContainer.text(response.data.message).css('color', 'green');
                        form.trigger('reset'); // Clear form fields
                    } else {
                        messageContainer.text(response.data.message).css('color', 'red');
                    }
                },
                error: function() {
                    messageContainer.text('Une erreur est survenue. Veuillez réessayer.').css('color', 'red');
                },
                complete: function() {
                    // Re-enable button
                    submitButton.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
