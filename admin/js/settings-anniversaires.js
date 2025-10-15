( function( $ ) {
    'use strict';

    $( function() {
        $( '#dame-send-test-birthday-email' ).on( 'click', function( e ) {
            e.preventDefault();

            var $button = $( this );
            var $message = $( '#dame-send-test-birthday-email-message' );

            $button.prop( 'disabled', true );
            $message.text( dame_settings_anniversaires.sending_message ).css( 'color', '' );

            var data = {
                action: 'dame_send_test_birthday_email',
                _ajax_nonce: dame_settings_anniversaires.nonce
            };

            $.post( ajaxurl, data, function( response ) {
                if ( response.success ) {
                    $message.text( response.data.message ).css( 'color', 'green' );
                } else {
                    $message.text( response.data.message ).css( 'color', 'red' );
                }
            } ).always( function() {
                $button.prop( 'disabled', false );
            } );
        } );
    } );

} )( jQuery );
