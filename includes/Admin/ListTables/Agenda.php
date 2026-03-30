<?php
/**
 * Agenda List Table Class.
 *
 * @package DAME\Admin\ListTables
 */

namespace DAME\Admin\ListTables;

use WP_Query;

/**
 * Class Agenda
 * Manages the columns and filters for the Agenda CPT list table.
 */
class Agenda {

	/**
	 * Initialize the list table customizations.
	 */
	public function init() {
		add_filter( 'manage_edit-dame_agenda_columns', [ $this, 'set_columns' ] );
		add_action( 'manage_dame_agenda_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );
		add_filter( 'manage_edit-dame_agenda_sortable_columns', [ $this, 'set_sortable_columns' ] );
		add_action( 'restrict_manage_posts', [ $this, 'add_filters' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_and_sort' ] );
		add_filter( 'months_dropdown_results', [ $this, 'suppress_months_dropdown' ], 10, 2 );
	}

	/**
	 * Sets the custom columns for the Agenda CPT.
	 *
	 * @param array $columns The existing columns.
	 * @return array The modified columns.
	 */
	public function set_columns( $columns ) {
		$new_columns = array(
			'cb'              => $columns['cb'],
			'title'           => __( 'Titre', 'dame' ),
			'dame_start_date' => __( 'Date de début', 'dame' ),
			'dame_end_date'   => __( 'Date de fin', 'dame' ),
			'dame_location'   => __( 'Lieu', 'dame' ),
			'dame_category'   => __( 'Catégorie', 'dame' ),
			'dame_actions'    => __( 'Actions', 'dame' ),
		);
		return $new_columns;
	}

	/**
	 * Renders the content for the custom Agenda columns.
	 *
	 * @param string $column The name of the column to render.
	 * @param int    $post_id The ID of the post.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'dame_start_date':
				$start_date = get_post_meta( $post_id, '_dame_start_date', true );
				$start_time = get_post_meta( $post_id, '_dame_start_time', true );
				$all_day    = get_post_meta( $post_id, '_dame_all_day', true );

				if ( $start_date ) {
					$timestamp = strtotime( $start_date );
					echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
					if ( ! $all_day && $start_time ) {
						echo '<br>' . esc_html( $start_time );
					}
				} else {
					echo '—';
				}
				break;

			case 'dame_end_date':
				$end_date = get_post_meta( $post_id, '_dame_end_date', true );
				$end_time = get_post_meta( $post_id, '_dame_end_time', true );
				$all_day  = get_post_meta( $post_id, '_dame_all_day', true );

				if ( $end_date ) {
					$timestamp = strtotime( $end_date );
					echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
					if ( ! $all_day && $end_time ) {
						echo '<br>' . esc_html( $end_time );
					}
				} else {
					echo '—';
				}
				break;

			case 'dame_location':
				$location = get_post_meta( $post_id, '_dame_location_name', true );
				echo esc_html( $location ? $location : '—' );
				break;

			case 'dame_category':
				$categories = get_the_terms( $post_id, 'dame_agenda_category' );
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$category_links = array();
					foreach ( $categories as $category ) {
						$term_meta = get_option( "taxonomy_{$category->term_id}" );
						$color     = isset( $term_meta['color'] ) && ! empty( $term_meta['color'] ) ? $term_meta['color'] : '#e0e0e0';

						$text_color = \DAME\Core\Utils::get_contrast_color( $color );

						$category_links[] = sprintf(
							'<span style="display: inline-block; background-color: %s; color: %s; padding: 2px 8px; margin: 2px; border-radius: 4px; font-size: 0.9em;">%s</span>',
							esc_attr( $color ),
							esc_attr( $text_color ),
							esc_html( $category->name )
						);
					}
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo implode( ' ', $category_links );
				} else {
					echo '—';
				}
				break;
			case 'dame_actions':
				$duplicate_url = wp_nonce_url(
					admin_url( 'admin.php?action=dame_duplicate_event&post=' . $post_id ),
					'dame_duplicate_event_nonce_' . $post_id,
					'dame_duplicate_nonce'
				);
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<a href="' . esc_url( $duplicate_url ) . '">' . esc_html__( 'Dupliquer', 'dame' ) . '</a>';
				break;
		}
	}

	/**
	 * Makes the custom date columns sortable for the Agenda CPT.
	 *
	 * @param array $columns The existing sortable columns.
	 * @return array The modified sortable columns.
	 */
	public function set_sortable_columns( $columns ) {
		$columns['dame_start_date'] = '_dame_start_date';
		$columns['dame_end_date']   = '_dame_end_date';
		return $columns;
	}

	/**
	 * Adds custom filters to the Agenda CPT admin list for category and date range.
	 */
	public function add_filters() {
		global $typenow, $wp_locale;

		if ( 'dame_agenda' !== $typenow ) {
			return;
		}

		// --- Category Filter (existing) ---
		$taxonomy   = 'dame_agenda_category';
		$selected_category = $_GET[ $taxonomy ] ?? '';
		$category_terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( $category_terms && ! is_wp_error( $category_terms ) ) {
			echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '" class="postform">';
			echo '<option value="">' . esc_html__( 'Toutes les catégories', 'dame' ) . '</option>';
			foreach ( $category_terms as $term ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $term->slug ),
					selected( $selected_category, $term->slug, false ),
					esc_html( $term->name )
				);
			}
			echo '</select>';
		}

		// --- New Date Range Filter ---
		global $wpdb;

		// Get all distinct years from event start dates
		$years = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT YEAR(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY meta_value DESC", '_dame_start_date' ) );

		// Get min and max dates for defaults
		$min_date = $wpdb->get_var( $wpdb->prepare( "SELECT MIN(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_dame_start_date' ) );
		$max_date = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_dame_start_date' ) );

		$default_start_month = $min_date ? wp_date( 'm', strtotime( $min_date ) ) : wp_date( 'm' );
		$default_start_year  = $min_date ? wp_date( 'Y', strtotime( $min_date ) ) : wp_date( 'Y' );
		$default_end_month   = $max_date ? wp_date( 'm', strtotime( $max_date ) ) : wp_date( 'm' );
		$default_end_year    = $max_date ? wp_date( 'Y', strtotime( $max_date ) ) : wp_date( 'Y' );

		// Get user's saved preference for start date
		$user_id = get_current_user_id();
		$saved_start_date = get_user_meta( $user_id, 'dame_agenda_filter_start_date', true );
		if ( ! empty( $saved_start_date ) && is_array( $saved_start_date ) ) {
			$default_start_month = $saved_start_date['month'];
			$default_start_year  = $saved_start_date['year'];
		}

		// Determine current values from GET or defaults
		$start_month = $_GET['dame_start_month'] ?? $default_start_month;
		$start_year  = $_GET['dame_start_year'] ?? $default_start_year;
		$end_month   = $_GET['dame_end_month'] ?? $default_end_month;
		$end_year    = $_GET['dame_end_year'] ?? $default_end_year;

		$months = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$months[ sprintf( '%02d', $i ) ] = $wp_locale->get_month( $i );
		}

		// Render the dropdowns.
		?>
		<label for="dame_start_month" class="screen-reader-text"><?php _e( 'Mois de début', 'dame' ); ?></label>
		<select name="dame_start_month" id="dame_start_month">
			<?php foreach ( $months as $month_val => $month_name ) : ?>
				<option value="<?php echo esc_attr( $month_val ); ?>" <?php selected( $start_month, $month_val ); ?>><?php echo esc_html( $month_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<label for="dame_start_year" class="screen-reader-text"><?php _e( 'Année de début', 'dame' ); ?></label>
		<select name="dame_start_year" id="dame_start_year">
			<?php foreach ( $years as $year ) : ?>
				<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $start_year, $year ); ?>><?php echo esc_html( $year ); ?></option>
			<?php endforeach; ?>
		</select>
		<label for="dame_end_month" class="screen-reader-text"><?php _e( 'Mois de fin', 'dame' ); ?></label>
		<select name="dame_end_month" id="dame_end_month">
			<?php foreach ( $months as $month_val => $month_name ) : ?>
				<option value="<?php echo esc_attr( $month_val ); ?>" <?php selected( $end_month, $month_val ); ?>><?php echo esc_html( $month_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<label for="dame_end_year" class="screen-reader-text"><?php _e( 'Année de fin', 'dame' ); ?></label>
		<select name="dame_end_year" id="dame_end_year">
			<?php foreach ( $years as $year ) : ?>
				<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $end_year, $year ); ?>><?php echo esc_html( $year ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Handles sorting and filtering for the Agenda CPT list.
	 *
	 * @param WP_Query $query The main WP_Query instance.
	 */
	public function filter_and_sort( $query ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_admin() || ! $query->is_main_query() || ! isset( $_GET['post_type'] ) || 'dame_agenda' !== $_GET['post_type'] ) {
			return;
		}

		// Handle sorting.
		$orderby = $query->get( 'orderby' );
		if ( '_dame_start_date' === $orderby ) {
			$query->set( 'meta_key', '_dame_start_date' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_type', 'DATE' );
		} elseif ( '_dame_end_date' === $orderby ) {
			$query->set( 'meta_key', '_dame_end_date' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_type', 'DATE' );
		}

		// Handle category filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['dame_agenda_category'] ) && '' !== $_GET['dame_agenda_category'] ) {
			$tax_query = $query->get( 'tax_query' ) ?: array();
			if (empty($tax_query)) {
				$tax_query = array('relation' => 'AND');
			}
			$tax_query[] = array(
				'taxonomy' => 'dame_agenda_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( wp_unslash( $_GET['dame_agenda_category'] ) ),
			);
			$query->set( 'tax_query', $tax_query );
		}

		// --- Handle New Date Range Filter ---
		$start_month = $_GET['dame_start_month'] ?? '';
		$start_year  = $_GET['dame_start_year'] ?? '';
		$end_month   = $_GET['dame_end_month'] ?? '';
		$end_year    = $_GET['dame_end_year'] ?? '';

		// Only filter if at least one parameter is present (the form is always submitted)
		if ( ! empty( $start_month ) && ! empty( $start_year ) && ! empty( $end_month ) && ! empty( $end_year ) ) {

			// Persist the user's choice for the start date if filter is actively used.
			// We check for 'filter_action' which is the name of the filter button.
			if ( isset( $_GET['filter_action'] ) ) {
				$user_id = get_current_user_id();
				update_user_meta( $user_id, 'dame_agenda_filter_start_date', array( 'month' => $start_month, 'year' => $start_year ) );
			}

			$start_date_str = $start_year . '-' . $start_month . '-01';
			$end_date_str   = $end_year . '-' . $end_month . '-01';

			// Ensure start date is not after end date
			if ( strtotime( $start_date_str ) > strtotime( $end_date_str ) ) {
				return; // Or swap them, for now just ignore invalid range
			}

			$first_day = wp_date( 'Y-m-d', strtotime( $start_date_str ) );
			$last_day  = wp_date( 'Y-m-t', strtotime( $end_date_str ) );

			$meta_query = $query->get( 'meta_query' ) ?: array();
			if ( empty( $meta_query ) ) {
				$meta_query = array( 'relation' => 'AND' );
			} elseif ( ! isset( $meta_query['relation'] ) ) {
				$meta_query['relation'] = 'AND';
			}

			$meta_query[] = array(
				'key'     => '_dame_start_date',
				'value'   => array( $first_day, $last_day ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			);
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Suppresses the default months dropdown for the Agenda CPT.
	 *
	 * @param array  $months    The default months list.
	 * @param string $post_type The current post type.
	 * @return array The modified months list (empty for our CPT).
	 */
	public function suppress_months_dropdown( $months, $post_type ) {
		if ( 'dame_agenda' === $post_type ) {
			return array();
		}
		return $months;
	}
}
