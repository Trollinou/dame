(function($) {
    'use strict';

    $(document).ready(function() {
        const availableList = $('#dame-available-items-select');
        const courseList = $('#dame-course-items-select');
        const hiddenInputsContainer = $('#dame-course-items-hidden-inputs');
        const difficultySelect = $('#dame_difficulty');
        const availableItemsPlaceholder = $('#dame-available-items-placeholder');
        const i18n = dame_course_builder_data.i18n;

        function fetchAvailableItems() {
            var difficulty = difficultySelect.val();

            if (!difficulty) {
                availableList.empty().prop('disabled', true);
                if (availableItemsPlaceholder) {
                    availableItemsPlaceholder.text(i18n.no_content).show();
                }
                return;
            }

            availableList.empty().prop('disabled', true);
            if (availableItemsPlaceholder) {
                availableItemsPlaceholder.text(i18n.loading).show();
            }

            $.ajax({
                url: dame_course_builder_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'dame_get_course_builder_items',
                    nonce: dame_course_builder_data.nonce,
                    difficulty: difficulty,
                    course_id: dame_course_builder_data.course_id
                },
                success: function(response) {
                    if (response.success) {
                        var lessons = response.data.lessons;
                        var exercices = response.data.exercices;

                        if (lessons.length > 0) {
                            var lessonOptgroup = $('<optgroup>').attr('label', i18n.lessons);
                            lessons.forEach(function(item) {
                                lessonOptgroup.append($('<option>').val('lecon:' + item.id).text(item.title));
                            });
                            availableList.append(lessonOptgroup);
                        }

                        if (exercices.length > 0) {
                            var exerciceOptgroup = $('<optgroup>').attr('label', i18n.exercices);
                            exercices.forEach(function(item) {
                                exerciceOptgroup.append($('<option>').val('exercice:' + item.id).text(item.title));
                            });
                            availableList.append(exerciceOptgroup);
                        }

                        if (lessons.length === 0 && exercices.length === 0) {
                            if (availableItemsPlaceholder) {
                                availableItemsPlaceholder.text(i18n.no_content).show();
                            }
                        } else {
                            if (availableItemsPlaceholder) {
                                availableItemsPlaceholder.hide();
                            }
                        }

                        availableList.prop('disabled', false);
                    } else {
                        if (availableItemsPlaceholder) {
                            availableItemsPlaceholder.text(i18n.error).show();
                        }
                    }
                },
                error: function() {
                    if (availableItemsPlaceholder) {
                        availableItemsPlaceholder.text(i18n.error).show();
                    }
                }
            });
        }

        difficultySelect.on('focus', function () {
            // Store the original value on focus
            $(this).data('previous-value', $(this).val());
        }).on('change', function() {
            var previousValue = $(this).data('previous-value');
            var currentValue = $(this).val();

            if (courseList.find('option').length > 0) {
                if (confirm("Changer le niveau de difficulté videra la liste des leçons et exercices déjà sélectionnés. Voulez-vous continuer ?")) {
                    // User confirmed, clear the list and fetch new items
                    courseList.empty();
                    syncHiddenInputs();
                    fetchAvailableItems();
                } else {
                    // User canceled, revert the dropdown to its previous value
                    $(this).val(previousValue);
                    return;
                }
            } else {
                // List is empty, just fetch items
                fetchAvailableItems();
            }

            // Update the previous value for the next change event
            $(this).data('previous-value', currentValue);
        });

        // Initial load if a difficulty is already selected
        if (difficultySelect.val()) {
            fetchAvailableItems();
        }

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
                // Just moving it back is fine, it will be gone on next difficulty change, which is expected.
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

        // On load, ensure the hidden inputs match the course list.
        syncHiddenInputs();
    });

})(jQuery);
