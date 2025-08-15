(function($) {
    'use strict';

    $(document).ready(function() {
        // 1. Make the list on the right (course content) sortable
        $("#dame-course-item-list").sortable({
            placeholder: 'dame-item-placeholder',
            revert: true,
            // The 'receive' event fires when a draggable is dropped on the sortable
            receive: function(event, ui) {
                const droppedItem = ui.item;
                const droppedItemId = droppedItem.data('id');
                let isDuplicate = false;

                // Check for duplicates by looking at other items already in the list.
                // The .not(ui.item) is crucial to exclude the item just dropped.
                $(this).find('.dame-course-item').not(ui.item).each(function() {
                    if ($(this).data('id') === droppedItemId) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    alert("Cet élément est déjà présent dans le cours.");
                    // Remove the item that was just dropped.
                    // Using .remove() on ui.item is not reliable here as it's a clone.
                    // The best way is to cancel the operation from the sender.
                    $(ui.sender).sortable('cancel');
                    return;
                }

                // If not a duplicate, add the hidden input and delete button
                const postType = droppedItem.data('type');
                // The dropped element is a clone, we need to add the input and button to it.
                // The helper is what is dragged, but ui.item is what is in the list now.
                droppedItem.append(
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
            revert: "invalid"
        });

        // 3. Use a more robust delegated event handler for the delete button, attached to the document
        $(document).on('click', '#dame-course-content .dame-delete-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.dame-course-item').fadeOut(300, function() {
                $(this).remove();
            });
        });

        // 4. Add a placeholder style for visual feedback during drag
        $('<style>.dame-item-placeholder { background-color: #f0f9ff; border: 1px dashed #a0c0ff; height: 30px; margin-bottom: 5px; }</style>').appendTo('head');
    });

})(jQuery);
