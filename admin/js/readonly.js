jQuery(document).ready(function($) {
    // Disable all form elements
    $('#post-body-content :input').prop('disabled', true);

    // Also hide/disable metabox actions
    $('#submitdiv .button').hide(); // Hide all buttons in the publish metabox
    $('.misc-pub-section').css('pointer-events', 'none'); // Disable links like visibility, publish date

    // Remove the 'Add Media' button
    $('#insert-media-button').hide();

    // Change the title to indicate view mode
    $('#wp-admin-bar-edit a').text('Consulter Adhérent');
    $('h1.wp-heading-inline').text('Consulter la fiche de l\'adhérent');

    // Add a 'Back to list' button.
    $('h1.wp-heading-inline').after('<a href="edit.php?post_type=adherent" class="page-title-action">Retour à la liste</a>');

    // Hide the slug editor
    $('#edit-slug-box').hide();
});
