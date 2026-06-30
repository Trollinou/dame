jQuery( document ).ready( function ( $ ) {
	/**
	 * Re-sort and re-index all dates and their time slots.
	 */
	function refreshBenevolatData() {
		const $wrapper = $( '#benevolat-dates-wrapper' );
		const $groups = $wrapper.find( '.benevolat-date-group' );

		// 1. Sort Date Groups by their date input value
		const sortedGroups = $groups.toArray().sort( function ( a, b ) {
			const dateA =
				$( a ).find( '.benevolat-date-input' ).val() || '9999-12-31';
			const dateB =
				$( b ).find( '.benevolat-date-input' ).val() || '9999-12-31';
			return dateA.localeCompare( dateB );
		} );

		// 2. Clear and re-append sorted groups, then fix indices
		$wrapper.empty().append( sortedGroups );

		$( '#benevolat-dates-wrapper .benevolat-date-group' ).each(
			function ( dateIndex ) {
				const $dateGroup = $( this );

				// Update Date Title
				$dateGroup.find( 'h4' ).text( 'Date ' + ( dateIndex + 1 ) );

				// Update Date Input Name & ID
				const $dateInput = $dateGroup.find( '.benevolat-date-input' );
				$dateInput.attr( 'id', 'benevolat_date_' + dateIndex );
				$dateInput.attr(
					'name',
					'_dame_benevolat_data[' + dateIndex + '][date]'
				);
				$dateGroup
					.find( 'label[for^="benevolat_date_"]' )
					.attr( 'for', 'benevolat_date_' + dateIndex );

				// 3. Sort Time Slots within this date group
				const $slotsWrapper = $dateGroup.find(
					'.benevolat-time-slots-wrapper'
				);
				const $slots = $slotsWrapper.find(
					'.benevolat-time-slot-group'
				);

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
					.find( '.benevolat-time-slot-group' )
					.each( function ( timeIndex ) {
						const $slot = $( this );
						$slot
							.find( 'input[type="time"]' )
							.first()
							.attr(
								'name',
								'_dame_benevolat_data[' +
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
								'_dame_benevolat_data[' +
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
	$( '#add-benevolat-date' ).on( 'click', function () {
		const dateIndex = $(
			'#benevolat-dates-wrapper .benevolat-date-group'
		).length;

		let defaultDate = '';
		const dateInputs = $( '#benevolat-dates-wrapper .benevolat-date-input' );
		if ( dateInputs.length > 0 ) {
			let maxDateVal = '';
			dateInputs.each( function () {
				const val = $( this ).val();
				if ( val && ( ! maxDateVal || val > maxDateVal ) ) {
					maxDateVal = val;
				}
			} );
			if ( maxDateVal ) {
				const parts = maxDateVal.split( '-' );
				if ( parts.length === 3 ) {
					const dateObj = new Date(
						Date.UTC(
							parseInt( parts[ 0 ], 10 ),
							parseInt( parts[ 1 ], 10 ) - 1,
							parseInt( parts[ 2 ], 10 )
						)
					);
					dateObj.setUTCDate( dateObj.getUTCDate() + 1 );
					const year = dateObj.getUTCFullYear();
					const month = String( dateObj.getUTCMonth() + 1 ).padStart( 2, '0' );
					const day = String( dateObj.getUTCDate() ).padStart( 2, '0' );
					defaultDate = `${ year }-${ month }-${ day }`;
				}
			}
		}

		const newDateGroup = `
            <div class="benevolat-date-group">
                <hr>
                <h4>Date ${ dateIndex + 1 }</h4>
                <p>
                    <label for="benevolat_date_${ dateIndex }">Date:</label>
                    <input type="date" id="benevolat_date_${ dateIndex }" name="_dame_benevolat_data[${ dateIndex }][date]" value="${ defaultDate }" class="benevolat-date-input">
                    <button type="button" class="button remove-benevolat-date">Supprimer cette date</button>
                </p>
                <div class="benevolat-time-slots-wrapper">
                </div>
                <button type="button" class="button add-benevolat-time-slot">Ajouter une plage horaire</button>
            </div>
        `;
		$( '#benevolat-dates-wrapper' ).append( newDateGroup );
	} );

	// Trigger re-sort when date changes
	$( '#benevolat-dates-wrapper' ).on(
		'change',
		'.benevolat-date-input',
		function () {
			refreshBenevolatData();
		}
	);

	// Remove Date
	$( '#benevolat-dates-wrapper' ).on(
		'click',
		'.remove-benevolat-date',
		function () {
			$( this ).closest( '.benevolat-date-group' ).remove();
			refreshBenevolatData();
		}
	);

	// Add Time Slot
	$( '#benevolat-dates-wrapper' ).on(
		'click',
		'.add-benevolat-time-slot',
		function () {
			const $dateGroup = $( this ).closest( '.benevolat-date-group' );
			const dateIndex = $dateGroup.index();
			const $slotsWrapper = $dateGroup.find(
				'.benevolat-time-slots-wrapper'
			);
			const timeIndex = $slotsWrapper.find(
				'.benevolat-time-slot-group'
			).length;

			let previousEndTime = '';
			if ( timeIndex > 0 ) {
				previousEndTime = $slotsWrapper
					.find( '.benevolat-time-slot-group' )
					.last()
					.find( 'input[type="time"]' )
					.eq( 1 )
					.val();
			}

			const newTimeSlot = `
            <div class="benevolat-time-slot-group">
                <label>Plage horaire:</label>
                <input type="time" name="_dame_benevolat_data[${ dateIndex }][time_slots][${ timeIndex }][start]" value="${ previousEndTime }" step="900" class="benevolat-time-start">
                <span>-</span>
                <input type="time" name="_dame_benevolat_data[${ dateIndex }][time_slots][${ timeIndex }][end]" value="" step="900" class="benevolat-time-end">
                <button type="button" class="button remove-benevolat-time-slot">Supprimer</button>
            </div>
        `;
			$slotsWrapper.append( newTimeSlot );
		}
	);

	// Trigger re-sort when time changes
	$( '#benevolat-dates-wrapper' ).on(
		'change',
		'.benevolat-time-start',
		function () {
			refreshBenevolatData();
		}
	);

	// Remove Time Slot
	$( '#benevolat-dates-wrapper' ).on(
		'click',
		'.remove-benevolat-time-slot',
		function () {
			$( this ).closest( '.benevolat-time-slot-group' ).remove();
			refreshBenevolatData();
		}
	);
} );
