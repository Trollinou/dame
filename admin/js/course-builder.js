(function($) {
    'use strict';

    $(document).ready(function() {
        const availableList = $('#dame-available-items-select');
        const courseList = $('#dame-course-items-select');
        const hiddenInputsContainer = $('#dame-course-items-hidden-inputs');

        // Function to synchronize the hidden inputs with the course list
        function syncHiddenInputs() {
            hiddenInputsContainer.empty(); // Clear existing inputs
            courseList.find('option').each(function() {
                hiddenInputsContainer.append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'dame_course_items[]',
                        value: $(this).val()
                    })
                );
            });
        }

        // Move selected items to the course list
        $('#dame-add-to-course').on('click', function() {
            availableList.find('option:selected').each(function() {
                $(this).remove().appendTo(courseList);
            });
            syncHiddenInputs(); // Sync after adding
        });

        // Remove selected items from the course list
        $('#dame-remove-from-course').on('click', function() {
            courseList.find('option:selected').each(function() {
                $(this).remove().appendTo(availableList);
            });
            syncHiddenInputs(); // Sync after removing
        });

        // Move selected items up in the course list
        $('#dame-move-up').on('click', function() {
            courseList.find('option:selected').each(function() {
                const prev = $(this).prev();
                if (prev.length) {
                    $(this).insertBefore(prev);
                }
            });
            syncHiddenInputs(); // Sync after reordering
        });

        // Move selected items down in the course list
        $('#dame-move-down').on('click', function() {
            $(courseList.find('option:selected').get().reverse()).each(function() {
                const next = $(this).next();
                if (next.length) {
                    $(this).insertAfter(next);
                }
            });
            syncHiddenInputs(); // Sync after reordering
        });

        // No pre-submit hook is needed with this new method.
        // The hidden inputs are always in sync with the visual list.
    });

})(jQuery);
