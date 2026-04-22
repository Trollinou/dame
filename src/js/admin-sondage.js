jQuery( document ).ready( function ( $ ) {
	/**
	 * Re-sort and re-index all dates and their time slots.
	 */
	function refreshSondageData() {
		const $wrapper = $( '#sondage-dates-wrapper' );
		const $groups = $wrapper.find( '.sondage-date-group' );

		// 1. Sort Date Groups by their date input value
		const sortedGroups = $groups.toArray().sort( function ( a, b ) {
			const dateA =
				$( a ).find( '.sondage-date-input' ).val() || '9999-12-31';
			const dateB =
				$( b ).find( '.sondage-date-input' ).val() || '9999-12-31';
			return dateA.localeCompare( dateB );
		} );

		// 2. Clear and re-append sorted groups, then fix indices
		$wrapper.empty().append( sortedGroups );

		$( '#sondage-dates-wrapper .sondage-date-group' ).each(
			function ( dateIndex ) {
				const $dateGroup = $( this );

				// Update Date Title
				$dateGroup.find( 'h4' ).text( 'Date ' + ( dateIndex + 1 ) );

				// Update Date Input Name & ID
				const $dateInput = $dateGroup.find( '.sondage-date-input' );
				$dateInput.attr( 'id', 'sondage_date_' + dateIndex );
				$dateInput.attr(
					'name',
					'_dame_sondage_data[' + dateIndex + '][date]'
				);
				$dateGroup
					.find( 'label[for^="sondage_date_"]' )
					.attr( 'for', 'sondage_date_' + dateIndex );

				// 3. Sort Time Slots within this date group
				const $slotsWrapper = $dateGroup.find(
					'.sondage-time-slots-wrapper'
				);
				const $slots = $slotsWrapper.find( '.sondage-time-slot-group' );

				const sortedSlots = $slots.toArray().sort( function ( a, b ) {
					const timeA =
						$( a ).find( 'input[type="time"]' ).first().val() ||
						'23:59';
					const timeB =
						$( b ).find( 'input[type="time"]' ).first().val() ||
						'23:59';
					return timeA.localeCompare( timeB );
				} );

				$slotsWrapper.empty().append( sortedSlots );

				// 4. Update Time Slot Input Names
				$slotsWrapper
					.find( '.sondage-time-slot-group' )
					.each( function ( timeIndex ) {
						const $slot = $( this );
						$slot
							.find( 'input[type="time"]' )
							.first()
							.attr(
								'name',
								'_dame_sondage_data[' +
									dateIndex +
									'][time_slots][' +
									timeIndex +
									'][start]'
							);
						$slot
							.find( 'input[type="time"]' )
							.last()
							.attr(
								'name',
								'_dame_sondage_data[' +
									dateIndex +
									'][time_slots][' +
									timeIndex +
									'][end]'
							);
					} );
			}
		);
	}

	// Add Date
	$( '#add-sondage-date' ).on( 'click', function () {
		const dateIndex = $(
			'#sondage-dates-wrapper .sondage-date-group'
		).length;
		const newDateGroup = `
            <div class="sondage-date-group">
                <hr>
                <h4>Date ${ dateIndex + 1 }</h4>
                <p>
                    <label for="sondage_date_${ dateIndex }">Date:</label>
                    <input type="date" id="sondage_date_${ dateIndex }" name="_dame_sondage_data[${ dateIndex }][date]" value="" class="sondage-date-input">
                    <button type="button" class="button remove-sondage-date">Supprimer cette date</button>
                </p>
                <div class="sondage-time-slots-wrapper">
                </div>
                <button type="button" class="button add-sondage-time-slot">Ajouter une plage horaire</button>
            </div>
        `;
		$( '#sondage-dates-wrapper' ).append( newDateGroup );
	} );

	// Trigger re-sort when date changes
	$( '#sondage-dates-wrapper' ).on(
		'change',
		'.sondage-date-input',
		function () {
			refreshSondageData();
		}
	);

	// Remove Date
	$( '#sondage-dates-wrapper' ).on(
		'click',
		'.remove-sondage-date',
		function () {
			$( this ).closest( '.sondage-date-group' ).remove();
			refreshSondageData();
		}
	);

	// Add Time Slot
	$( '#sondage-dates-wrapper' ).on(
		'click',
		'.add-sondage-time-slot',
		function () {
			const $dateGroup = $( this ).closest( '.sondage-date-group' );
			const dateIndex = $dateGroup.index();
			const $slotsWrapper = $dateGroup.find(
				'.sondage-time-slots-wrapper'
			);
			const timeIndex = $slotsWrapper.find(
				'.sondage-time-slot-group'
			).length;

			let previousEndTime = '';
			if ( timeIndex > 0 ) {
				previousEndTime = $slotsWrapper
					.find( '.sondage-time-slot-group' )
					.last()
					.find( 'input[type="time"]' )
					.eq( 1 )
					.val();
			}

			const newTimeSlot = `
            <div class="sondage-time-slot-group">
                <label>Plage horaire:</label>
                <input type="time" name="_dame_sondage_data[${ dateIndex }][time_slots][${ timeIndex }][start]" value="${ previousEndTime }" step="900" class="sondage-time-start">
                <span>-</span>
                <input type="time" name="_dame_sondage_data[${ dateIndex }][time_slots][${ timeIndex }][end]" value="" step="900" class="sondage-time-end">
                <button type="button" class="button remove-sondage-time-slot">Supprimer</button>
            </div>
        `;
			$slotsWrapper.append( newTimeSlot );
		}
	);

	// Trigger re-sort when time changes
	$( '#sondage-dates-wrapper' ).on(
		'change',
		'.sondage-time-start',
		function () {
			refreshSondageData();
		}
	);

	// Remove Time Slot
	$( '#sondage-dates-wrapper' ).on(
		'click',
		'.remove-sondage-time-slot',
		function () {
			$( this ).closest( '.sondage-time-slot-group' ).remove();
			refreshSondageData();
		}
	);
} );
