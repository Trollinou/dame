jQuery( document ).ready( function ( $ ) {
	$( '#dame_send_test_btn' ).on( 'click', function () {
		const email = $( '#dame_test_email' ).val();
		const post_id = dame_test_send_data.post_id;
		const nonce = dame_test_send_data.nonce;

		if ( ! email ) {
			alert( dame_test_send_data.alert_empty );
			return;
		}

		$( '#dame_test_spinner' ).addClass( 'is-active' );
		$( '#dame_test_result' ).html( '' );

		const form = $(
			'<form action="' +
				dame_test_send_data.admin_url +
				'" method="post">' +
				'<input type="hidden" name="action" value="dame_send_test_email">' +
				'<input type="hidden" name="post_ID" value="' +
				post_id +
				'">' +
				'<input type="hidden" name="test_email" value="' +
				email +
				'">' +
				'<input type="hidden" name="_wpnonce" value="' +
				nonce +
				'">' +
				'</form>'
		);
		$( 'body' ).append( form );
		form.submit();
	} );
} );
