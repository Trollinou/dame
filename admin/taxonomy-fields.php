<?php
/**
 * Handles custom fields for taxonomies.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds a color picker field to the "Add New Category" form for events.
 *
 * @param string $taxonomy The slug of the taxonomy.
 */
function dame_add_event_category_color_field( $taxonomy ) {
	?>
	<div class="form-field term-color-wrap">
		<label for="term-color"><?php _e( 'Couleur', 'dame' ); ?></label>
		<input name="term_color" value="#ffffff" id="term-color" class="dame-color-picker" type="text" />
		<p><?php _e( 'Choisissez une couleur pour cette catégorie. Utile pour l\'affichage sur le calendrier.', 'dame' ); ?></p>
	</div>
	<?php
}
add_action( 'dame_categorie_evenement_add_form_fields', 'dame_add_event_category_color_field' );

/**
 * Adds a color picker field to the "Edit Category" form for events.
 *
 * @param WP_Term $term     Current taxonomy term object.
 * @param string  $taxonomy Slug of the taxonomy.
 */
function dame_edit_event_category_color_field( $term, $taxonomy ) {
	$color = get_term_meta( $term->term_id, 'dame_category_color', true );
	$color = ! empty( $color ) ? esc_attr( $color ) : '#ffffff';
	?>
	<tr class="form-field term-color-wrap">
		<th scope="row">
			<label for="term-color"><?php _e( 'Couleur', 'dame' ); ?></label>
		</th>
		<td>
			<input name="term_color" value="<?php echo $color; ?>" id="term-color" class="dame-color-picker" type="text" />
			<p class="description"><?php _e( 'Choisissez une couleur pour cette catégorie. Utile pour l\'affichage sur le calendrier.', 'dame' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'dame_categorie_evenement_edit_form_fields', 'dame_edit_event_category_color_field', 10, 2 );

/**
 * Saves the custom color field for event categories.
 *
 * @param int $term_id Term ID.
 */
function dame_save_event_category_color( $term_id ) {
	if ( ! isset( $_POST['term_color'] ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$color = sanitize_hex_color( $_POST['term_color'] );
	update_term_meta( $term_id, 'dame_category_color', $color );
}
add_action( 'created_dame_categorie_evenement', 'dame_save_event_category_color' );
add_action( 'edited_dame_categorie_evenement', 'dame_save_event_category_color' );

/**
 * Enqueues the color picker script and styles.
 *
 * @param string $hook_suffix The current admin page.
 */
function dame_enqueue_color_picker( $hook_suffix ) {
	if ( 'term.php' !== $hook_suffix && 'edit-tags.php' !== $hook_suffix ) {
		return;
	}

	// Check if we are on the correct taxonomy page.
	$screen = get_current_screen();
	if ( ! $screen || 'edit-dame_categorie_evenement' !== $screen->id ) {
		return;
	}

	// Enqueue the color picker.
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	// Inline script to initialize the color picker.
	$script = 'jQuery(document).ready(function($){ $(".dame-color-picker").wpColorPicker(); });';
	wp_add_inline_script( 'wp-color-picker', $script );
}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_color_picker' );
