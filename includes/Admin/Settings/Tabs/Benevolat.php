<?php
/**
 * Benevolat Tab Settings.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Benevolat
 */
class Benevolat {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Bénévolat', 'dame' );
	}

	/**
	 * Register settings fields and sections.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_settings_section(
			'dame_benevolat_section',
			__( 'Synchronisation des appels à bénévoles dans le flux d\'articles', 'dame' ),
			array( $this, 'render_section_intro' ),
			'dame_benevolat_section_group'
		);

		add_settings_field(
			'benevolat_auto_publish',
			__( 'Publication automatique', 'dame' ),
			array( $this, 'render_auto_publish_field' ),
			'dame_benevolat_section_group',
			'dame_benevolat_section'
		);

		add_settings_field(
			'benevolat_default_category',
			__( 'Catégorie d\'articles par défaut', 'dame' ),
			array( $this, 'render_default_category_field' ),
			'dame_benevolat_section_group',
			'dame_benevolat_section'
		);

		add_settings_field(
			'benevolat_default_thumbnail_id',
			__( 'Image mise en avant par défaut', 'dame' ),
			array( $this, 'render_default_thumbnail_field' ),
			'dame_benevolat_section_group',
			'dame_benevolat_section'
		);
	}

	/**
	 * Enqueue scripts and media uploader for benevolat settings tab.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin settings tab navigation check.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'association';
		if ( false === strpos( $hook, 'dame-settings' ) || 'benevolat' !== $tab ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'dame-admin-benevolat-settings',
			\DAME_PLUGIN_URL . 'assets/js/admin-benevolat-settings.js',
			array(),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Render section description.
	 */
	public function render_section_intro(): void {
		echo '<p>' . esc_html__( 'Configurez la synchronisation automatique des appels à bénévoles avec les articles WordPress pour les afficher directement dans le flux d\'actualités de votre site.', 'dame' ) . '</p>';
	}

	/**
	 * Render auto publish toggle field.
	 */
	public function render_auto_publish_field(): void {
		$options = get_option( 'dame_options', array() );
		$options = is_array( $options ) ? $options : array();
		$checked = ! empty( $options['benevolat_auto_publish'] ) ? 'checked' : '';

		echo '<label>';
		echo '<input type="checkbox" name="dame_options[benevolat_auto_publish]" value="1" ' . esc_attr( $checked ) . ' /> ';
		echo esc_html__( 'Créer et publier automatiquement un article WordPress lorsqu\'un appel à bénévoles est créé.', 'dame' );
		echo '</label>';
		echo '<p class="description">' . esc_html__( 'Cette option active la création automatique par défaut. Elle pourra être personnalisée individuellement sur chaque appel.', 'dame' ) . '</p>';
	}

	/**
	 * Render default category dropdown.
	 */
	public function render_default_category_field(): void {
		$options     = get_option( 'dame_options', array() );
		$options     = is_array( $options ) ? $options : array();
		$selected_id = isset( $options['benevolat_default_category'] ) ? (int) $options['benevolat_default_category'] : (int) get_option( 'default_category' );

		wp_dropdown_categories(
			array(
				'name'              => 'dame_options[benevolat_default_category]',
				'id'                => 'dame_benevolat_default_category',
				'selected'          => $selected_id,
				'hierarchical'      => 1,
				'show_option_none'  => __( '— Sélectionner une catégorie —', 'dame' ),
				'option_none_value' => '0',
				'hide_empty'        => 0,
				'class'             => 'regular-text',
			)
		);

		echo '<p class="description">' . esc_html__( 'Catégorie dans laquelle seront classés les articles générés automatiquement pour les appels à bénévoles.', 'dame' ) . '</p>';
	}

	/**
	 * Render default thumbnail media uploader.
	 */
	public function render_default_thumbnail_field(): void {
		$options      = get_option( 'dame_options', array() );
		$options      = is_array( $options ) ? $options : array();
		$thumbnail_id = isset( $options['benevolat_default_thumbnail_id'] ) ? (int) $options['benevolat_default_thumbnail_id'] : 0;
		$image_url    = $thumbnail_id > 0 ? (string) wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';

		echo '<div class="dame-media-uploader-wrapper" data-field="benevolat_default_thumbnail_id">';
		echo '<input type="hidden" id="dame_benevolat_default_thumbnail_id" name="dame_options[benevolat_default_thumbnail_id]" value="' . esc_attr( $thumbnail_id > 0 ? (string) $thumbnail_id : '' ) . '" class="dame-media-input" />';
		echo '<div class="dame-media-preview" style="margin-bottom: 10px;' . ( empty( $image_url ) ? ' display: none;' : '' ) . '">';
		echo '<img src="' . esc_url( $image_url ) . '" alt="" style="max-height: 140px; max-width: 250px; height: auto; border: 1px solid #ccd0d4; padding: 4px; background: #fff; border-radius: 4px; display: block;" />';
		echo '</div>';
		echo '<button type="button" class="button dame-media-upload-btn" data-title="' . esc_attr__( 'Sélectionner l\'image par défaut pour le bénévolat', 'dame' ) . '">' . esc_html__( 'Sélectionner une image', 'dame' ) . '</button> ';
		echo '<button type="button" class="button dame-media-remove-btn" style="' . ( empty( $image_url ) ? 'display: none;' : '' ) . ' margin-left: 5px;">' . esc_html__( 'Supprimer', 'dame' ) . '</button>';
		echo '<p class="description">' . esc_html__( 'Cette image sera assignée automatiquement comme image de mise en avant (couverture) de l\'article si aucun visuel spécifique n\'est défini sur l\'appel.', 'dame' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render tab sections.
	 */
	public function render(): void {
		do_settings_sections( 'dame_benevolat_section_group' );
	}

	/**
	 * Sanitize options for this tab.
	 *
	 * @param array<string, mixed> $input            New options.
	 * @param array<string, mixed> $existing_options Current options.
	 * @return array<string, mixed> Sanitized options.
	 */
	public function sanitize( array $input, array $existing_options ): array {
		$existing_options['benevolat_auto_publish'] = isset( $input['benevolat_auto_publish'] ) ? 1 : 0;

		if ( isset( $input['benevolat_default_category'] ) ) {
			$existing_options['benevolat_default_category'] = (int) $input['benevolat_default_category'];
		}

		if ( isset( $input['benevolat_default_thumbnail_id'] ) ) {
			$existing_options['benevolat_default_thumbnail_id'] = (int) $input['benevolat_default_thumbnail_id'];
		}

		return $existing_options;
	}
}
