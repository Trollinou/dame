(function($) {
    'use strict';

    $(document).ready(function() {
        // 1. Make the list on the right (course content) sortable
        $("#dame-course-item-list").sortable({
            placeholder: 'dame-item-placeholder',
            revert: true,
            // The 'receive' event fires when a draggable is dropped on the sortable
            receive: function(event, ui) {
                const droppedItemId = ui.item.data('id');
                let isDuplicate = false;

                // Check for duplicates by scanning existing items in the target list.
                // We check items other than the placeholder where the new item is about to land.
                $(this).find('.dame-course-item').not('.ui-sortable-placeholder').each(function() {
                    if ($(this).data('id') === droppedItemId) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    // Alert the user and cancel the drop
                    alert("Cet élément est déjà présent dans le cours.");
                    $(ui.sender).sortable('cancel');
                    return;
                }

                // If not a duplicate, add hidden input and delete button
                const postType = ui.item.data('type');
                ui.item.append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'dame_course_items[]',
                        value: postType + ':' + droppedItemId
                    })
                ).append(
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
        $('#dame-course-content').on('click', '.dame-delete-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.dame-course-item').fadeOut(300, function() { $(this).remove(); });
        });

        // 4. Add a placeholder style for visual feedback during drag
        $('<style>.dame-item-placeholder { background-color: #f0f9ff; border: 1px dashed #a0c0ff; height: 30px; margin-bottom: 5px; }</style>').appendTo('head');
    });

})(jQuery);
