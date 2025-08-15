(function($) {
    'use strict';

    $(document).ready(function() {
        // 1. Make the list on the right (course content) sortable
        $("#dame-course-item-list").sortable({
            placeholder: 'dame-item-placeholder',
            revert: true,
            // The 'receive' event fires when a draggable is dropped on the sortable
            receive: function(event, ui) {
                // The 'ui.item' is the clone from the draggable.
                // We need to add our hidden input and delete button to it.
                const postId = ui.item.data('id');
                const postType = ui.item.data('type');

                // Add hidden input for saving the value
                ui.item.append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'dame_course_items[]',
                        value: postType + ':' + postId
                    })
                );

                // Add a delete button
                ui.item.append(
                    $('<button type="button" class="button-link-delete dame-delete-item" style="margin-left: 10px; cursor: pointer; color: #a00; border: none; background: none; font-size: 1.5em; line-height: 1; vertical-align: middle;">&times;</button>')
                );
            }
        }).disableSelection();

        // 2. Make the items on the left (available content) draggable
        $("#dame-available-items .dame-course-item").draggable({
            connectToSortable: "#dame-course-item-list",
            helper: "clone",
            revert: "invalid" // Snap back if not dropped on a valid sortable
        });

        // 3. Handle click on the delete button using event delegation
        $('#dame-course-content').on('click', '.dame-delete-item', function() {
            $(this).closest('.dame-course-item').remove();
        });

        // 4. Add a placeholder style for visual feedback during drag
        $('<style>.dame-item-placeholder { background-color: #f0f9ff; border: 1px dashed #a0c0ff; height: 30px; margin-bottom: 5px; }</style>').appendTo('head');
    });

})(jQuery);
