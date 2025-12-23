<?php
/**
 * Adherent List Table Columns.
 *
 * @package DAME
 */

namespace DAME\Admin\Columns;

use DateTime;
use WP_Query;
use WP_Post;

/**
 * Class Adherent
 */
class Adherent {

	/**
	 * Initialize the columns logic.
	 */
	public function init() {
		add_filter( 'manage_edit-dame_adherent_columns', [ $this, 'set_columns' ] );
		add_action( 'manage_dame_adherent_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );
		add_filter( 'manage_edit-dame_adherent_sortable_columns', [ $this, 'set_sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'sort_columns' ] );
		add_action( 'load-edit.php', [ $this, 'remove_date_filter' ] );
		add_action( 'restrict_manage_posts', [ $this, 'add_filters' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_query' ] );
		add_filter( 'posts_search', [ $this, 'extend_search' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_row_actions' ], 10, 2 );
	}

	/**
	 * Sets the custom columns for the Adherent CPT.
	 *
	 * @param array $columns The existing columns.
	 * @return array The modified columns.
	 */
	public function set_columns( $columns ) {
		$new_columns = array(
			'cb'                     => $columns['cb'],
			'title'                  => __( 'Nom de l\'adhérent', 'dame' ),
			'dame_age_category'      => __( 'Catégorie d\'âge', 'dame' ),
			'dame_license_number'    => __( 'Licence', 'dame' ),
			'dame_phone'             => __( 'Téléphone', 'dame' ),
			'dame_email'             => __( 'Email', 'dame' ),
			'dame_membership_status' => __( 'Statut Adhésion', 'dame' ),
			'dame_saisons'           => __( 'Saisons d\'adhésion', 'dame' ),
			'dame_classification'    => __( 'Classification', 'dame' ),
		);
		return $new_columns;
	}

	/**
	 * Renders the content for the custom columns.
	 *
	 * @param string $column The name of the column to render.
	 * @param int    $post_id The ID of the post.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'dame_age_category':
				$birth_date_str = get_post_meta( $post_id, '_dame_birth_date', true );
				$gender         = get_post_meta( $post_id, '_dame_sexe', true );

				// Using legacy helper function for now as it contains complex business logic
				// TODO: Migrate dame_get_adherent_age_category to a Service
				$category = function_exists( 'dame_get_adherent_age_category' )
					? dame_get_adherent_age_category( $birth_date_str, $gender )
					: 'N/A';

				if ( $birth_date_str ) {
					$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $birth_date_str );
					if ( $birth_date_obj ) {
						$formatted_birth_date = $birth_date_obj->format( 'd/m/Y' );
						echo '<span title="' . esc_attr( $formatted_birth_date ) . '">' . esc_html( $category ) . '</span>';
						break;
					}
				}
				echo esc_html( $category );
				break;

			case 'dame_license_number':
				$license = get_post_meta( $post_id, '_dame_license_number', true );
				echo esc_html( $license );
				break;

			case 'dame_email':
				$email = get_post_meta( $post_id, '_dame_email', true );
				echo esc_html( $email );
				break;

			case 'dame_phone':
				$phone = get_post_meta( $post_id, '_dame_phone_number', true );
				echo esc_html( $phone );
				break;

			case 'dame_classification':
				$groups = get_the_terms( $post_id, 'dame_group' );
				if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) {
					$group_names = wp_list_pluck( $groups, 'name' );
					echo esc_html( implode( ', ', $group_names ) );
				} else {
					echo '—';
				}
				break;

			case 'dame_membership_status':
				$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
				if ( $current_season_tag_id && has_term( (int) $current_season_tag_id, 'dame_saison_adhesion', $post_id ) ) {
					echo '<span style="color: green; font-weight: bold;">' . esc_html__( 'Actif', 'dame' ) . '</span>';
				} else {
					echo esc_html__( 'Non adhérent', 'dame' );
				}
				break;

			case 'dame_saisons':
				$saisons = get_the_terms( $post_id, 'dame_saison_adhesion' );
				if ( ! empty( $saisons ) && ! is_wp_error( $saisons ) ) {
					$saison_names = array();
					foreach ( $saisons as $saison ) {
						$saison_names[] = '<span style="display: inline-block; background-color: #e0e0e0; color: #333; padding: 2px 8px; margin: 2px; border-radius: 4px; font-size: 0.9em;">' . esc_html( $saison->name ) . '</span>';
					}
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo implode( ' ', $saison_names );
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Sets sortable columns.
	 *
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function set_sortable_columns( $columns ) {
		$columns['dame_license_number'] = 'dame_license_number';
		$columns['dame_age_category']   = 'dame_birth_date'; // Sort by birth date for age
		return $columns;
	}

	/**
	 * Sorts the query based on custom sortable columns.
	 *
	 * @param WP_Query $query The query object.
	 */
	public function sort_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'dame_adherent' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'dame_license_number' === $orderby ) {
			$query->set( 'meta_key', '_dame_license_number' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'dame_birth_date' === $orderby ) {
			$query->set( 'meta_key', '_dame_birth_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Removes the date filter from the Adherent CPT admin list.
	 */
	public function remove_date_filter() {
		$screen = get_current_screen();
		if ( $screen && 'edit-dame_adherent' === $screen->id ) {
			add_filter( 'months_dropdown_results', '__return_empty_array' );
		}
	}

	/**
	 * Adds custom filters to the Adherent CPT admin list.
	 */
	public function add_filters() {
		global $typenow;

		if ( 'dame_adherent' === $typenow ) {
			// Group filter
			$group_terms = get_terms(
				array(
					'taxonomy'   => 'dame_group',
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			if ( ! is_wp_error( $group_terms ) && ! empty( $group_terms ) ) {
				$current_group = $_GET['dame_group_filter'] ?? '';
				?>
				<select name="dame_group_filter">
					<option value=""><?php _e( 'Tous les groupes', 'dame' ); ?></option>
					<?php foreach ( $group_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $term->term_id, $current_group ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
			}

			// Membership Status filter
			$current_status = $_GET['dame_membership_filter'] ?? '';
			?>
			<select name="dame_membership_filter">
				<option value=""><?php _e( 'Tous les statuts d\'adhésion', 'dame' ); ?></option>
				<option value="active" <?php selected( 'active', $current_status ); ?>><?php _e( 'Adhésion active', 'dame' ); ?></option>
				<option value="inactive" <?php selected( 'inactive', $current_status ); ?>><?php _e( 'Adhésion inactive', 'dame' ); ?></option>
			</select>
			<?php

			// Season filter
			$saisons = get_terms(
				array(
					'taxonomy'   => 'dame_saison_adhesion',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'DESC',
				)
			);
			if ( ! is_wp_error( $saisons ) && ! empty( $saisons ) ) {
				$current_saison = $_GET['dame_saison_filter'] ?? '';
				?>
				<select name="dame_saison_filter">
					<option value=""><?php _e( 'Toutes les saisons', 'dame' ); ?></option>
					<?php foreach ( $saisons as $saison ) : ?>
						<option value="<?php echo esc_attr( $saison->term_id ); ?>" <?php selected( $saison->term_id, $current_saison ); ?>><?php echo esc_html( $saison->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
			}

			// Age Category filter
			// Using legacy helper
			$age_categories = function_exists( 'dame_get_all_age_categories' ) ? dame_get_all_age_categories() : [];
			if ( ! empty( $age_categories ) ) {
				$current_age_category = $_GET['dame_age_category_filter'] ?? '';
				?>
				<select name="dame_age_category_filter">
					<option value=""><?php _e( 'Toutes les catégories d\'âge', 'dame' ); ?></option>
					<?php foreach ( $age_categories as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $current_age_category ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
			}
		}
	}

	/**
	 * Filters the Adherent CPT query based on custom filters.
	 *
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function filter_query( $query ) {
		global $pagenow;
		$post_type = $_GET['post_type'] ?? '';

		if ( is_admin() && 'edit.php' === $pagenow && 'dame_adherent' === $post_type && $query->is_main_query() ) {
			$meta_query = $query->get( 'meta_query' ) ?: array();
			$tax_query  = $query->get( 'tax_query' ) ?: array();

			// Group filter
			if ( isset( $_GET['dame_group_filter'] ) && ! empty( $_GET['dame_group_filter'] ) ) {
				$group_id = absint( $_GET['dame_group_filter'] );
				if ( $group_id > 0 ) {
					$tax_query[] = array(
						'taxonomy' => 'dame_group',
						'field'    => 'term_id',
						'terms'    => $group_id,
					);
				}
			}

			// Membership Status filter
			if ( isset( $_GET['dame_membership_filter'] ) && ! empty( $_GET['dame_membership_filter'] ) ) {
				$status_filter         = sanitize_key( $_GET['dame_membership_filter'] );
				$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

				if ( $current_season_tag_id ) {
					if ( 'active' === $status_filter ) {
						$tax_query[] = array(
							'taxonomy' => 'dame_saison_adhesion',
							'field'    => 'term_id',
							'terms'    => (int) $current_season_tag_id,
						);
					} elseif ( 'inactive' === $status_filter ) {
						$tax_query[] = array(
							'taxonomy' => 'dame_saison_adhesion',
							'field'    => 'term_id',
							'terms'    => (int) $current_season_tag_id,
							'operator' => 'NOT IN',
						);
					}
				}
			}

			// Season filter
			if ( isset( $_GET['dame_saison_filter'] ) && ! empty( $_GET['dame_saison_filter'] ) ) {
				$saison_id = absint( $_GET['dame_saison_filter'] );
				if ( $saison_id > 0 ) {
					$tax_query[] = array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $saison_id,
					);
				}
			}

			// Age Category filter
			if ( isset( $_GET['dame_age_category_filter'] ) && ! empty( $_GET['dame_age_category_filter'] ) ) {
				$age_category_filter = sanitize_key( $_GET['dame_age_category_filter'] );

				// Using legacy helper
				$date_range = function_exists( 'dame_get_birth_date_range_for_category' )
					? dame_get_birth_date_range_for_category( $age_category_filter )
					: null;

				if ( $date_range ) {
					$meta_query[] = array(
						'key'     => '_dame_birth_date',
						'value'   => array( $date_range['start'], $date_range['end'] ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					);

					if ( strpos( $age_category_filter, 'f' ) !== false ) {
						$meta_query[] = array(
							'key'   => '_dame_sexe',
							'value' => 'Féminin',
						);
					}
				}
			}

			if ( count( $meta_query ) > 0 ) {
				if ( ! isset( $meta_query['relation'] ) ) {
					$meta_query['relation'] = 'AND';
				}
				$query->set( 'meta_query', $meta_query );
			}

			if ( count( $tax_query ) > 0 ) {
				if ( ! isset( $tax_query['relation'] ) ) {
					$tax_query['relation'] = 'AND';
				}
				$query->set( 'tax_query', $tax_query );
			}
		}
	}

	/**
	 * Extends the search functionality for the Adherent CPT.
	 *
	 * @param string   $search    The search query.
	 * @param WP_Query $query     The WP_Query instance.
	 * @return string The modified search query.
	 */
	public function extend_search( $search, $query ) {
		global $wpdb;

		if ( $query->is_search() && $query->get( 'post_type' ) === 'dame_adherent' ) {
			$search_term = $query->get( 's' );
			if ( ! empty( $search_term ) ) {
				$search_term_like = '%' . $wpdb->esc_like( $search_term ) . '%';
				$search = " AND (
					({$wpdb->posts}.post_title LIKE %s)
					OR EXISTS (
						SELECT 1 FROM {$wpdb->postmeta}
						WHERE post_id = {$wpdb->posts}.ID
						AND meta_key IN ('_dame_email', '_dame_legal_rep_1_email', '_dame_legal_rep_2_email')
						AND meta_value LIKE %s
					)
				)";
				return $wpdb->prepare( $search, $search_term_like, $search_term_like );
			}
		}

		return $search;
	}

	/**
	 * Adds a 'Consulter' action link to the adherent list table.
	 *
	 * @param array   $actions The existing row actions.
	 * @param WP_Post $post    The post object.
	 * @return array The modified row actions.
	 */
	public function add_row_actions( $actions, $post ) {
		if ( 'dame_adherent' === $post->post_type ) {
			// CPT is not public, so the default 'View' link is not needed/broken.
			unset( $actions['view'] );

			$url = add_query_arg(
				array(
					'page'        => 'dame-view-adherent',
					'adherent_id' => $post->ID,
				),
				admin_url( 'admin.php' )
			);

			$view_link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), __( 'Consulter', 'dame' ) );

			// Add the 'Consulter' link before the 'Edit' link.
			return array_merge( array( 'dame_view' => $view_link ), $actions );
		}
		return $actions;
	}
}
