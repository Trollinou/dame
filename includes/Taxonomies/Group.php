<?php
/**
 * Group Taxonomy.
 *
 * @package DAME
 */

namespace DAME\Taxonomies;

/**
 * Class Group
 */
class Group {

	/**
	 * Initialize the taxonomy.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register' ], 0 );

		// Fields
		add_action( 'dame_group_add_form_fields', [ $this, 'add_form_fields' ], 10, 2 );
		add_action( 'dame_group_edit_form_fields', [ $this, 'edit_form_fields' ], 10, 2 );
		add_action( 'edited_dame_group', [ $this, 'save_group_type' ], 10, 2 );
		add_action( 'create_dame_group', [ $this, 'save_group_type' ], 10, 2 );

		// Columns
		add_filter( 'manage_edit-dame_group_columns', [ $this, 'add_type_column' ] );
		add_filter( 'manage_dame_group_custom_column', [ $this, 'render_type_column' ], 10, 3 );

		// Actions
		add_filter( 'tag_row_actions', [ $this, 'add_reset_link' ], 10, 2 );
		add_action( 'admin_post_dame_reset_group', [ $this, 'handle_reset_action' ] );
		add_action( 'admin_notices', [ $this, 'show_reset_notice' ] );
	}

	/**
	 * Register the taxonomy.
	 */
	public function register() {
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
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'groupe' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'dame_group', [ 'adherent' ], $args );
	}

	/**
	 * Add a "Type" field to the "Add New Group" form.
	 */
	public function add_form_fields() {
		?>
		<div class="form-field">
			<label for="term_meta[group_type]"><?php _e( 'Type de groupe', 'dame' ); ?></label>
			<select name="term_meta[group_type]" id="term_meta[group_type]">
				<option value="saisonnier" selected><?php _e( 'Saisonnier', 'dame' ); ?></option>
				<option value="permanent"><?php _e( 'Permanent', 'dame' ); ?></option>
			</select>
			<p class="description"><?php _e( 'Saisonnier : membres actifs. Permanent : contacts extérieurs (bénévoles, élus, presse).', 'dame' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add a "Type" field to the "Edit Group" form.
	 *
	 * @param \WP_Term $term The term object.
	 */
	public function edit_form_fields( $term ) {
		$group_type = get_term_meta( $term->term_id, '_dame_group_type', true );
		if ( empty( $group_type ) ) {
			$group_type = 'saisonnier'; // Default value
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[group_type]"><?php _e( 'Type de groupe', 'dame' ); ?></label></th>
			<td>
				<select name="term_meta[group_type]" id="term_meta[group_type]">
					<option value="saisonnier" <?php selected( $group_type, 'saisonnier' ); ?>><?php _e( 'Saisonnier', 'dame' ); ?></option>
					<option value="permanent" <?php selected( $group_type, 'permanent' ); ?>><?php _e( 'Permanent', 'dame' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Saisonnier : membres actifs. Permanent : contacts extérieurs (bénévoles, élus, presse).', 'dame' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the "Type" field for the Group taxonomy.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_group_type( $term_id ) {
		if ( isset( $_POST['term_meta']['group_type'] ) ) {
			$group_type = sanitize_key( $_POST['term_meta']['group_type'] );
			update_term_meta( $term_id, '_dame_group_type', $group_type );
		}
	}

	/**
	 * Add a "Type" column to the group list table.
	 *
	 * @param array $columns Columns array.
	 * @return array Modified columns array.
	 */
	public function add_type_column( $columns ) {
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
	public function render_type_column( $content, $column_name, $term_id ) {
		if ( 'group_type' === $column_name ) {
			$group_type = get_term_meta( $term_id, '_dame_group_type', true );
			if ( 'permanent' === $group_type ) {
				$content = __( 'Permanent', 'dame' );
			} else {
				$content = __( 'Saisonnier', 'dame' ); // Default
			}
		}
		return $content;
	}

	/**
	 * Add a "Reset" action to the group taxonomy list table.
	 *
	 * @param array    $actions An array of action links.
	 * @param \WP_Term $term    The term object.
	 * @return array   The modified array of action links.
	 */
	public function add_reset_link( $actions, $term ) {
		// Check if we are on the 'dame_group' taxonomy screen.
		if ( 'dame_group' !== $term->taxonomy ) {
			return $actions;
		}

		// Check if the user has the required capability.
		if ( current_user_can( 'manage_categories' ) ) {
			// Build the URL for the reset action.
			$reset_url = add_query_arg(
				array(
					'action'   => 'dame_reset_group',
					'taxonomy' => 'dame_group',
					'tag_ID'   => $term->term_id,
					'_wpnonce' => wp_create_nonce( 'dame_reset_group_' . $term->term_id ),
				),
				admin_url( 'admin-post.php' )
			);

			// Add a confirmation dialog.
			$actions['reset'] = sprintf(
				'<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( $reset_url ),
				esc_js( sprintf( __( 'Êtes-vous sûr de vouloir supprimer tous les adhérents du groupe "%s" ? Cette action est irréversible.', 'dame' ), $term->name ) ),
				__( 'Réinitialiser', 'dame' )
			);
		}
		return $actions;
	}

	/**
	 * Handle the group reset action.
	 */
	public function handle_reset_action() {
		// Check if the action is correct.
		if ( ! isset( $_GET['action'] ) || 'dame_reset_group' !== $_GET['action'] ) {
			return;
		}

		// Check user capabilities first.
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( __( 'Vous n\'avez pas les permissions suffisantes pour effectuer cette action.', 'dame' ) );
		}

		// Get the term ID and verify the nonce.
		$term_id = isset( $_GET['tag_ID'] ) ? intval( $_GET['tag_ID'] ) : 0;
		if ( ! $term_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_reset_group_' . $term_id ) ) {
			wp_die( __( 'Échec de la vérification de sécurité.', 'dame' ) );
		}

		// Get all adherents in the group.
		$adherents = get_posts(
			array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'tax_query'      => array(
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
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display an admin notice after a group has been reset.
	 */
	public function show_reset_notice() {
		global $pagenow;
		// Check if we are on the correct page and the message is set.
		if (
			'edit-tags.php' === $pagenow &&
			isset( $_GET['taxonomy'] ) && 'dame_group' === $_GET['taxonomy'] &&
			isset( $_GET['message'] ) && 'group_reset' === $_GET['message']
		) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'Le groupe a été réinitialisé avec succès. Tous les adhérents ont été retirés.', 'dame' ); ?></p>
			</div>
			<?php
		}
	}
}
