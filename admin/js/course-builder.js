(function($) {
    'use strict';

    $(document).ready(function() {
        const availableList = $('#dame-available-items-select');
        const courseList = $('#dame-course-items-select');

        // Move selected items to the course list
        $('#dame-add-to-course').on('click', function() {
            availableList.find('option:selected').each(function() {
                $(this).remove().appendTo(courseList);
            });
        });

        // Remove selected items from the course list
        $('#dame-remove-from-course').on('click', function() {
            courseList.find('option:selected').each(function() {
                $(this).remove().appendTo(availableList);
                // We might need to re-sort the available list if we want it to stay alphabetical
            });
        });

        // Move selected items up in the course list
        $('#dame-move-up').on('click', function() {
            courseList.find('option:selected').each(function() {
                const prev = $(this).prev();
                if (prev.length) {
                    $(this).insertBefore(prev);
                }
            });
        });

        // Move selected items down in the course list
        $('#dame-move-down').on('click', function() {
            // We need to reverse the selection to move down correctly in a multi-select
            $(courseList.find('option:selected').get().reverse()).each(function() {
                const next = $(this).next();
                if (next.length) {
                    $(this).insertAfter(next);
                }
            });
        });

        // Before the form submits, select all items in the course list
        // so they are included in the POST data.
        $('#post').on('submit', function() {
            courseList.find('option').prop('selected', true);
        });
    });

})(jQuery);
