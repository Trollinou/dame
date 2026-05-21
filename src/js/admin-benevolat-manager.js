jQuery( document ).ready( function ( $ ) {
	// Add Date
	$( '#add-benevolat-date' ).on( 'click', function () {
		const dateIndex = $(
			'#benevolat-dates-wrapper .benevolat-date-group'
		).length;
		const newDateGroup =
			`
			<div class="benevolat-date-group">
				<hr>
				<h4>Date ` +
			( dateIndex + 1 ) +
			`</h4>
				<p>
					<label for="benevolat_date_` +
			dateIndex +
			`">Date:</label>
					<input type="date" id="benevolat_date_` +
			dateIndex +
			`" name="_dame_benevolat_data[` +
			dateIndex +
			`][date]" value="" class="benevolat-date-input">
					<button type="button" class="button remove-benevolat-date">Supprimer cette date</button>
				</p>
				<div class="benevolat-time-slots-wrapper">
				</div>
				<button type="button" class="button add-benevolat-time-slot">Ajouter une plage horaire</button>
			</div>
		`;
		$( '#benevolat-dates-wrapper' ).append( newDateGroup );
	} );

	// Remove Date
	$( '#benevolat-dates-wrapper' ).on(
		'click',
		'.remove-benevolat-date',
		function () {
			$( this ).closest( '.benevolat-date-group' ).remove();
			// Re-index h4 titles
			$( '#benevolat-dates-wrapper .benevolat-date-group' ).each(
				function ( index ) {
					$( this )
						.find( 'h4' )
						.text( 'Date ' + ( index + 1 ) );
				}
			);
		}
	);

	// Add Time Slot
	$( '#benevolat-dates-wrapper' ).on(
		'click',
		'.add-benevolat-time-slot',
		function () {
			const dateGroup = $( this ).closest( '.benevolat-date-group' );
			const dateIndex = dateGroup.index();
			const timeSlotsWrapper = dateGroup.find(
				'.benevolat-time-slots-wrapper'
			);
			const timeIndex = timeSlotsWrapper.find(
				'.benevolat-time-slot-group'
			).length;

			let previousEndTime = '';
			if ( timeIndex > 0 ) {
				previousEndTime = timeSlotsWrapper
					.find( '.benevolat-time-slot-group' )
					.last()
					.find( 'input[type="time"]' )
					.eq( 1 )
					.val();
			}

			const newTimeSlot =
				`
			<div class="benevolat-time-slot-group">
				<label>Plage horaire:</label>
				<input type="time" name="_dame_benevolat_data[` +
				dateIndex +
				`][time_slots][` +
				timeIndex +
				`][start]" value="` +
				previousEndTime +
				`" step="900">
				<span>-</span>
				<input type="time" name="_dame_benevolat_data[` +
				dateIndex +
				`][time_slots][` +
				timeIndex +
				`][end]" value="" step="900">
				<button type="button" class="button remove-benevolat-time-slot">Supprimer</button>
			</div>
		`;
			timeSlotsWrapper.append( newTimeSlot );
		}
	);

	// Remove Time Slot
	$( '#benevolat-dates-wrapper' ).on(
		'click',
		'.remove-benevolat-time-slot',
		function () {
			$( this ).closest( '.benevolat-time-slot-group' ).remove();
		}
	);
} );
