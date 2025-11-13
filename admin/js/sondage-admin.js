jQuery( document ).ready( function( $ ) {
    // Add Date
    $( '#add-sondage-date' ).on( 'click', function() {
        const dateIndex = $( '#sondage-dates-wrapper .sondage-date-group' ).length;
        const newDateGroup = `
            <div class="sondage-date-group">
                <hr>
                <h4>Date ` + ( dateIndex + 1 ) + `</h4>
                <p>
                    <label for="sondage_date_` + dateIndex + `">Date:</label>
                    <input type="date" id="sondage_date_` + dateIndex + `" name="_dame_sondage_data[` + dateIndex + `][date]" value="" class="sondage-date-input">
                    <button type="button" class="button remove-sondage-date">Supprimer cette date</button>
                </p>
                <div class="sondage-time-slots-wrapper">
                </div>
                <button type="button" class="button add-sondage-time-slot">Ajouter une plage horaire</button>
            </div>
        `;
        $( '#sondage-dates-wrapper' ).append( newDateGroup );
    } );

    // Remove Date
    $( '#sondage-dates-wrapper' ).on( 'click', '.remove-sondage-date', function() {
        $( this ).closest( '.sondage-date-group' ).remove();
        // Re-index h4 titles
        $( '#sondage-dates-wrapper .sondage-date-group' ).each( function( index ) {
            $( this ).find( 'h4' ).text( 'Date ' + ( index + 1 ) );
        } );
    } );

    // Add Time Slot
    $( '#sondage-dates-wrapper' ).on( 'click', '.add-sondage-time-slot', function() {
        const dateGroup = $( this ).closest( '.sondage-date-group' );
        const dateIndex = dateGroup.index();
        const timeIndex = dateGroup.find( '.sondage-time-slot-group' ).length;
        const newTimeSlot = `
            <div class="sondage-time-slot-group">
                <label>Plage horaire:</label>
                <input type="time" name="_dame_sondage_data[` + dateIndex + `][time_slots][` + timeIndex + `][start]" value="" step="900">
                <span>-</span>
                <input type="time" name="_dame_sondage_data[` + dateIndex + `][time_slots][` + timeIndex + `][end]" value="" step="900">
                <button type="button" class="button remove-sondage-time-slot">Supprimer</button>
            </div>
        `;
        dateGroup.find( '.sondage-time-slots-wrapper' ).append( newTimeSlot );
    } );

    // Remove Time Slot
    $( '#sondage-dates-wrapper' ).on( 'click', '.remove-sondage-time-slot', function() {
        $( this ).closest( '.sondage-time-slot-group' ).remove();
    } );
} );
