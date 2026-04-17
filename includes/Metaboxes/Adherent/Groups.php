<?php
/**
 * Adherent Groups Checklist Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

/**
 * Class Groups
 */
class Groups {

	/**
	 * Register the meta box.
	 */
	public function register() {
		// Remove the default taxonomy metabox and add our custom one with checkboxes.
		remove_meta_box( 'dame_groupdiv', 'adherent', 'side' );

		add_meta_box(
			'dame_group_checklist_metabox',
			__( 'Groupes', 'dame' ),
			[ $this, 'render' ],
			'adherent',
			'side',
			'high'
		);

		// Add filter to open it by default
		add_filter( 'postbox_classes_adherent_dame_group_checklist_metabox', [ $this, 'open_metabox_by_default' ] );
	}

	/**
	 * Open the "Groupes" metabox by default by removing the 'closed' class.
	 *
	 * @param array $classes An array of postbox classes.
	 * @return array The modified array of classes.
	 */
	public function open_metabox_by_default( $classes ) {
		if ( function_exists( 'get_current_screen' ) && get_current_screen() && get_current_screen()->id === 'adherent' ) {
			$classes = array_diff( $classes, array( 'closed' ) );
		}
		return $classes;
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		$taxonomy = 'dame_group';
		?>
		<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv">
			<ul id="<?php echo esc_attr( $taxonomy ); ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo esc_attr( $taxonomy ); ?>-all"><?php echo esc_html__( 'Tous les groupes', 'dame' ); ?></a></li>
			</ul>

			<div id="<?php echo esc_attr( $taxonomy ); ?>-all" class="tabs-panel" style="display: block;">
				<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist" data-wp-lists="list:<?php echo esc_attr( $taxonomy ); ?>" class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist(
						$post->ID,
						array(
							'taxonomy'      => $taxonomy,
							'popular_cats'  => false,
							'checked_ontop' => false, // Keep alphabetical order.
						)
					);
					?>
				</ul>
			</div>
			<?php
			$tax_obj = get_taxonomy( $taxonomy );
			if ( current_user_can( $tax_obj->cap->manage_terms ) ) {
				echo '<p style="margin-top:1em;"><a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) ) . '">' . esc_html( $tax_obj->labels->add_new_item ) . '</a></p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Save the meta box.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		// Standard taxonomy saving is handled by WordPress core via 'tax_input'.
	}
}
