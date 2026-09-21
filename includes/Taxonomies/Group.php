<?php
/**
 * Group Taxonomy.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Taxonomies;

use WP_Term;

/**
 * Class Group
 */
class Group {

	/**
	 * Initialize the taxonomy.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ), 0 );

		// Fields.
		add_action( 'dame_group_add_form_fields', array( $this, 'add_form_fields' ), 10, 1 );
		add_action( 'dame_group_edit_form_fields', array( $this, 'edit_form_fields' ), 10, 2 );
		add_action( 'edited_dame_group', array( $this, 'save_group_type' ), 10, 1 );
		add_action( 'create_dame_group', array( $this, 'save_group_type' ), 10, 1 );

		// Columns.
		add_filter( 'manage_edit-dame_group_columns', array( $this, 'add_type_column' ) );
		add_filter( 'manage_dame_group_custom_column', array( $this, 'render_type_column' ), 10, 3 );

		// Actions.
		add_filter( 'tag_row_actions', array( $this, 'add_reset_action' ), 10, 2 );
		add_action( 'admin_post_dame_reset_group', array( $this, 'handle_reset_action' ) );
		add_action( 'admin_notices', array( $this, 'show_reset_notice' ) );
	}

	/**
	 * Register the taxonomy.
	 */
	public function register(): void {
		$labels = array(
			'name'                       => _x( 'Groupes', 'taxonomy general name', 'dame' ),
			'singular_name'              => _x( 'Groupe', 'taxonomy singular name', 'dame' ),
			'search_items'               => __( 'Rechercher les groupes', 'dame' ),
			'popular_items'              => __( 'Groupes populaires', 'dame' ),
			'all_items'                  => __( 'Tous les groupes', 'dame' ),
			'parent_item'                => __( 'Groupe parent', 'dame' ),
			'parent_item_colon'          => __( 'Groupe parent :', 'dame' ),
			'edit_item'                  => __( 'Modifier le groupe', 'dame' ),
			'update_item'                => __( 'Mettre à jour le groupe', 'dame' ),
			'add_new_item'               => __( 'Ajouter un nouveau groupe', 'dame' ),
			'new_item_name'              => __( 'Nom du nouveau groupe', 'dame' ),
			'separate_items_with_commas' => __( 'Séparer les groupes avec des virgules', 'dame' ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des groupes', 'dame' ),
			'choose_from_most_used'      => __( 'Choisir parmi les groupes les plus utilisés', 'dame' ),
			'not_found'                  => __( 'Aucun groupe trouvé.', 'dame' ),
			'menu_name'                  => __( 'Groupes', 'dame' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'groupe' ),
			'show_in_rest'      => true,
			'rest_base'         => 'groups',
		);

		register_taxonomy( 'dame_group', array( 'adherent' ), $args );
	}

	/**
	 * Add a "Type" field to the "Add New Group" form.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	public function add_form_fields( $taxonomy ): void {
		?>
		<div class="form-field">
			<label for="term_meta[group_type]"><?php esc_html_e( 'Type de groupe', 'dame' ); ?></label>
			<select name="term_meta[group_type]" id="term_meta[group_type]">
				<option value="saisonnier" selected><?php esc_html_e( 'Saisonnier', 'dame' ); ?></option>
				<option value="permanent"><?php esc_html_e( 'Permanent', 'dame' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Saisonnier : membres actifs. Permanent : contacts extérieurs (bénévoles, élus, presse).', 'dame' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add a "Type" field to the "Edit Group" form.
	 *
	 * @param WP_Term $term The term object.
	 * @param string  $taxonomy The taxonomy slug.
	 */
	public function edit_form_fields( $term, $taxonomy ): void {
		$group_type = get_term_meta( $term->term_id, '_dame_group_type', true );
		if ( empty( $group_type ) ) {
			$group_type = 'saisonnier'; // Default value.
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[group_type]"><?php esc_html_e( 'Type de groupe', 'dame' ); ?></label></th>
			<td>
				<select name="term_meta[group_type]" id="term_meta[group_type]">
					<option value="saisonnier" <?php selected( $group_type, 'saisonnier' ); ?>><?php esc_html_e( 'Saisonnier', 'dame' ); ?></option>
					<option value="permanent" <?php selected( $group_type, 'permanent' ); ?>><?php esc_html_e( 'Permanent', 'dame' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Saisonnier : membres actifs. Permanent : contacts extérieurs (bénévoles, élus, presse).', 'dame' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the "Type" field for the Group taxonomy.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_group_type( $term_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['term_meta']['group_type'] ) ) {
			$group_type = sanitize_key( wp_unslash( $_POST['term_meta']['group_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_term_meta( $term_id, '_dame_group_type', $group_type );
		}
	}

	/**
	 * Add a "Type" column to the group list table.
	 *
	 * @param array<string, string> $columns Columns array.
	 * @return array<string, string> Modified columns array.
	 */
	public function add_type_column( $columns ): array {
		$columns['group_type'] = __( 'Type', 'dame' );
		return $columns;
	}

	/**
	 * Display the content for the "Type" column.
	 *
	 * @param string $content Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id Term ID.
	 * @return string Modified content.
	 */
	public function render_type_column( $content, $column_name, $term_id ): string {
		if ( 'group_type' === $column_name ) {
			$group_type = get_term_meta( $term_id, '_dame_group_type', true );
			if ( 'permanent' === $group_type ) {
				$content = __( 'Permanent', 'dame' );
			} else {
				$content = __( 'Saisonnier', 'dame' ); // Default.
			}
		}
		return $content;
	}

	/**
	 * Add a "Reset" action to the group taxonomy list table.
	 *
	 * @param array<string, string> $actions An array of action links.
	 * @param WP_Term               $term    The term object.
	 * @return array<string, string>
	 */
	public function add_reset_action( array $actions, $term ): array {
		// Only add the action if the current user has the capability to edit terms.
		if ( ! current_user_can( 'edit_term', $term->term_id ) ) {
			return $actions;
		}

		$reset_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'dame_reset_group',
					'taxonomy' => 'dame_group',
					'tag_ID'   => $term->term_id,
				),
				admin_url( 'edit-tags.php' )
			),
			'dame_reset_group_' . $term->term_id
		);

		$actions['reset'] = sprintf(
			'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
			esc_url( $reset_url ),
			esc_js( __( 'Êtes-vous sûr de vouloir réinitialiser ce groupe ? Tous les adhérents seront retirés de ce groupe.', 'dame' ) ),
			esc_html__( 'Réinitialiser', 'dame' )
		);

		return $actions;
	}

	/**
	 * Handle the "Reset" action for the group taxonomy.
	 */
	public function handle_reset_action(): void {
		// Check if our action is triggered.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['action'] ) || 'dame_reset_group' !== $_GET['action'] || ! isset( $_GET['tag_ID'] ) ) {
			return;
		}

		$term_id = (int) $_GET['tag_ID'];

		// Verify the nonce.
		check_admin_referer( 'dame_reset_group_' . $term_id );

		// Check user capabilities.
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas la permission d\'effectuer cette action.', 'dame' ) );
		}

		// Get all adherents in this group.
		$adherents = get_posts(
			array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'dame_group',
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
				),
				'fields'         => 'ids', // We only need the post IDs.
			)
		);

		// If there are adherents, remove them from the group.
		if ( ! empty( $adherents ) ) {
			/* @var int[] $adherents */
			foreach ( $adherents as $adherent_id ) {
				wp_remove_object_terms( $adherent_id, $term_id, 'dame_group' );
			}
		}

		// Redirect back to the taxonomy list table with a success message.
		$redirect_url = add_query_arg(
			array(
				'taxonomy' => 'dame_group',
				'message'  => 'group_reset',
			),
			admin_url( 'edit-tags.php' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display an admin notice after a group has been reset.
	 */
	public function show_reset_notice(): void {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : '';

		// Check if we are on the correct page and the message is set.
		if ( 'edit-tags.php' === $pagenow && 'dame_group' === $taxonomy && 'group_reset' === $message ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Le groupe a été réinitialisé avec succès. Tous les adhérents ont été retirés.', 'dame' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Get groups for a specific adherent.
	 *
	 * @param int $adherent_id Adherent post ID.
	 * @return array<int, array{id: int, name: string, slug: string}> List of groups.
	 */
	public static function get_groups_for_adherent( int $adherent_id ): array {
		if ( $adherent_id <= 0 ) {
			return array();
		}
		$terms = wp_get_object_terms( $adherent_id, 'dame_group' );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}
		$result = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$result[] = array(
					'id'   => (int) $term->term_id,
					'name' => html_entity_decode( (string) $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'slug' => (string) $term->slug,
				);
			}
		}
		return $result;
	}
}
