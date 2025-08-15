(function($) {
    'use strict';

    $(document).ready(function() {
        // Make both lists sortable and connected
        $('#dame-available-items .dame-item-list, #dame-course-content .dame-item-list').sortable({
            connectWith: '.dame-item-list',
            placeholder: 'dame-item-placeholder',
            receive: function(event, ui) {
                // When an item is dropped into the right-hand list, add the hidden input
                if ($(this).attr('id') === 'dame-course-item-list') {
                    const item = ui.item;
                    const postId = item.data('id');
                    const postType = item.data('type');
                    const hiddenInput = $('<input>', {
                        type: 'hidden',
                        name: 'dame_course_items[]',
                        value: postType + ':' + postId
                    });
                    item.append(hiddenInput);
                }
            },
            remove: function(event, ui) {
                // When an item is moved out of the right-hand list, remove its hidden input
                if ($(this).attr('id') === 'dame-course-item-list') {
                    ui.item.find('input[type="hidden"]').remove();
                }
            }
        }).disableSelection();

        // Add a placeholder style
        $('<style>.dame-item-placeholder { background-color: #f0f9ff; border: 1px dashed #a0c0ff; height: 30px; margin-bottom: 5px; }</style>').appendTo('head');
    });

})(jQuery);
