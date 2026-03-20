<?php
/**
 * Agenda Shortcode Class.
 *
 * @package DAME\Shortcodes
 */

namespace DAME\Shortcodes;

use WP_Query;
use DateTime;

/**
 * Class Agenda
 * Handles the [dame_agenda] and [dame_liste_agenda] shortcodes.
 */
class Agenda {

	/**
	 * Initialize the shortcodes and AJAX handlers.
	 */
	public function init() {
		add_shortcode( 'dame_agenda', [ $this, 'render_agenda' ] );
		add_shortcode( 'dame_liste_agenda', [ $this, 'render_list' ] );
		add_action( 'wp_ajax_dame_get_agenda_events', [ $this, 'get_events_ajax' ] );
		add_action( 'wp_ajax_nopriv_dame_get_agenda_events', [ $this, 'get_events_ajax' ] );
	}

	/**
	 * Renders the [dame_agenda] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The shortcode output.
	 */
	public function render_agenda( $atts ) {
		// Enqueue scripts and styles (using legacy paths as requested if files haven't moved).
		// Assuming files are in assets/css and assets/js relative to plugin root.
		// Since this class is in includes/Shortcodes, dirname(__DIR__, 2) gets to root.
		$plugin_url = plugin_dir_url( dirname( __DIR__, 2 ) . '/index.php' );

		wp_enqueue_style( 'dame-agenda-style', \DAME_PLUGIN_URL . 'assets/css/public-agenda.css', array(), \DAME_VERSION );
		wp_enqueue_script( 'dame-agenda-script', \DAME_PLUGIN_URL . 'assets/js/public-agenda.js', array( 'jquery' ), \DAME_VERSION, true );

		// Get WordPress's start_of_week option.
		$start_of_week = intval( get_option( 'start_of_week', 1 ) ); // Default to Monday.

		// Create the full weekdays array.
		$weekdays = array(
			__( 'Dim', 'dame' ),
			__( 'Lun', 'dame' ),
			__( 'Mar', 'dame' ),
			__( 'Mer', 'dame' ),
			__( 'Jeu', 'dame' ),
			__( 'Ven', 'dame' ),
			__( 'Sam', 'dame' ),
		);

		// Reorder the weekdays array based on the start_of_week setting.
		$ordered_weekdays = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$day_index          = ( $start_of_week + $i ) % 7;
			$ordered_weekdays[] = $weekdays[ $day_index ];
		}

		// Localize script with ajax url, nonce, and dynamic i18n values.
		wp_localize_script(
			'dame-agenda-script',
			'dame_agenda_ajax',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'dame_agenda_nonce' ),
				'start_of_week' => $start_of_week,
				'i18n'          => array(
					'all_day'        => __( 'Toute la journée', 'dame' ),
					'months'         => array(
						__( 'Janvier', 'dame' ),
						__( 'Février', 'dame' ),
						__( 'Mars', 'dame' ),
						__( 'Avril', 'dame' ),
						__( 'Mai', 'dame' ),
						__( 'Juin', 'dame' ),
						__( 'Juillet', 'dame' ),
						__( 'Août', 'dame' ),
						__( 'Septembre', 'dame' ),
						__( 'Octobre', 'dame' ),
						__( 'Novembre', 'dame' ),
						__( 'Décembre', 'dame' ),
					),
					'weekdays_short' => $ordered_weekdays,
				),
			)
		);

		// Get all agenda categories for the filter.
		$categories = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
			)
		);

		ob_start();
		?>
		<div id="dame-agenda-wrapper">
			<div class="dame-agenda-header">
				<div class="dame-agenda-primary-controls">
					<div class="dame-agenda-month-display">
						<h2 id="dame-agenda-current-month" class="dame-agenda-month-picker-toggle"></h2>
						<div id="dame-month-year-selector" style="display: none;">
							<div class="dame-month-year-selector-header">
								<button id="dame-selector-prev-year">&lt;&lt;</button>
								<span id="dame-selector-year"></span>
								<button id="dame-selector-next-year">&gt;&gt;</button>
							</div>
							<div class="dame-month-grid"></div>
						</div>
					</div>
					<div class="dame-agenda-nav-buttons">
						<button id="dame-agenda-prev-month" class="button">&lt;</button>
						<button id="dame-agenda-today" class="button">
							<span class="dame-desktop-text"><?php _e( 'Aujourd\'hui', 'dame' ); ?></span>
							<span class="dame-mobile-text"><?php _e( 'Auj.', 'dame' ); ?></span>
						</button>
						<button id="dame-agenda-next-month" class="button">&gt;</button>
					</div>
				</div>

				<div class="dame-agenda-secondary-controls">
					<div class="dame-agenda-search">
						<label for="dame-agenda-search-input" class="screen-reader-text"><?php _e( 'Rechercher un événement', 'dame' ); ?></label>
						<input type="search" id="dame-agenda-search-input" placeholder="<?php _e( 'Rechercher...', 'dame' ); ?>">
					</div>
					<div class="dame-agenda-filter">
						<button id="dame-agenda-filter-toggle" class="button"><?php _e( 'Filtres', 'dame' ); ?></button>
						<div id="dame-agenda-filter-panel" style="display: none;">
							<h5><?php _e( 'Catégories', 'dame' ); ?></h5>
							<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
								<?php $this->render_category_checklist( $categories ); ?>
							<?php else : ?>
								<p><?php _e( 'Aucune catégorie d\'événement trouvée.', 'dame' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
			<div id="dame-calendar-container">
				<div class="dame-calendar-weekdays"></div>
				<div id="dame-calendar-grid"></div>
			</div>
			<div id="dame-event-tooltip" class="dame-tooltip" style="display: none;"></div>
		</div>
		<script>
		// Inline script to handle checkbox hierarchy behavior
		jQuery(document).ready(function($) {
			$('.dame-agenda-cat-filter').on('change', function() {
				var isChecked = $(this).prop('checked');
				// Check/uncheck all children inputs
				$(this).closest('li').find('ul input.dame-agenda-cat-filter').prop('checked', isChecked);

				// Trigger reload of events (assuming main script listens to change on these inputs)
				// The main script should bind to '.dame-agenda-cat-filter' change event.
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Helper function to render category checklist recursively.
	 *
	 * @param array $categories Array of terms.
	 * @param int   $parent_id Parent ID.
	 */
	private function render_category_checklist( $categories, $parent_id = 0 ) {
		$children = array();
		foreach ( $categories as $category ) {
			if ( $category->parent == $parent_id ) {
				$children[] = $category;
			}
		}

		if ( empty( $children ) ) {
			return;
		}

		echo '<ul>';
		foreach ( $children as $category ) {
			$term_meta = get_option( 'taxonomy_' . $category->term_id );
			$color     = ! empty( $term_meta['color'] ) ? $term_meta['color'] : '#ccc';
			?>
			<li>
				<label>
					<input type="checkbox" class="dame-agenda-cat-filter" value="<?php echo esc_attr( $category->term_id ); ?>" checked>
					<span class="dame-agenda-cat-color" style="background-color: <?php echo esc_attr( $color ); ?>"></span>
					<?php echo esc_html( $category->name ); ?>
				</label>
				<?php $this->render_category_checklist( $categories, $category->term_id ); ?>
			</li>
			<?php
		}
		echo '</ul>';
	}

	/**
	 * AJAX handler to fetch agenda events.
	 */
	public function get_events_ajax() {
		check_ajax_referer( 'dame_agenda_nonce', 'nonce' );

		$start_date_str = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date_str   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		$date_regex = '/^\d{4}-\d{2}-\d{2}$/';
		if ( ! preg_match( $date_regex, $start_date_str ) || ! preg_match( $date_regex, $end_date_str ) ) {
			wp_send_json_error( 'Invalid date format provided.' );
		}

		$categories           = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
		$unchecked_categories = isset( $_POST['unchecked_categories'] ) ? array_map( 'intval', $_POST['unchecked_categories'] ) : array();
		$search_term          = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		$post_status   = array( 'publish' );
		$allowed_roles = array( 'staff', 'administrator', 'editor', 'entraineur' );
		$current_user  = wp_get_current_user();

		if ( array_intersect( $allowed_roles, $current_user->roles ) ) {
			$post_status[] = 'private';
		}

		$args = array(
			'post_type'      => 'dame_agenda',
			'post_status'    => $post_status,
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_dame_start_date',
					'value'   => $end_date_str,
					'compare' => '<=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_dame_end_date',
					'value'   => $start_date_str,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => '_dame_start_date',
			'order'          => 'ASC',
		);

		if ( empty( $categories ) && ! empty( $unchecked_categories ) ) {
			$args['post__in'] = array( 0 );
		} else {
			$tax_query = array(
				'relation' => 'AND',
			);

			if ( ! empty( $categories ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'dame_agenda_category',
					'field'            => 'term_id',
					'terms'            => $categories,
					'include_children' => true,
				);
			}

			if ( ! empty( $unchecked_categories ) ) {
				$tax_query[] = array(
					'taxonomy' => 'dame_agenda_category',
					'field'    => 'term_id',
					'terms'    => $unchecked_categories,
					'operator' => 'NOT IN',
				);
			}

			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query;
			}
		}

		if ( ! empty( $search_term ) ) {
			$args['dame_search'] = $search_term;
			add_filter( 'posts_join', [ $this, 'filter_search_join' ], 10, 2 );
			add_filter( 'posts_where', [ $this, 'filter_search_where' ], 10, 2 );
			add_filter( 'posts_distinct', [ $this, 'filter_search_distinct' ], 10, 2 );
		}

		$query = new WP_Query( $args );

		if ( ! empty( $search_term ) ) {
			remove_filter( 'posts_join', [ $this, 'filter_search_join' ], 10, 2 );
			remove_filter( 'posts_where', [ $this, 'filter_search_where' ], 10, 2 );
			remove_filter( 'posts_distinct', [ $this, 'filter_search_distinct' ], 10, 2 );
		}

		$events = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$term      = get_the_terms( $post_id, 'dame_agenda_category' );
				$term_id   = ! empty( $term ) ? $term[0]->term_id : 0;
				$term_meta = get_option( "taxonomy_$term_id" );
				$color     = ! empty( $term_meta['color'] ) ? $term_meta['color'] : '#ccc';

				$start_date = get_post_meta( $post_id, '_dame_start_date', true );
				$end_date   = get_post_meta( $post_id, '_dame_end_date', true );
				$status     = get_post_status( $post_id );

				$event_data = array(
					'id'          => $post_id,
					'title'       => get_the_title(),
					'status'      => $status,
					'url'         => get_permalink(),
					'start_date'  => $start_date,
					'start_time'  => get_post_meta( $post_id, '_dame_start_time', true ),
					'end_date'    => $end_date,
					'end_time'    => get_post_meta( $post_id, '_dame_end_time', true ),
					'all_day'     => get_post_meta( $post_id, '_dame_all_day', true ),
					'location'    => get_post_meta( $post_id, '_dame_location_name', true ),
					'description' => get_post_meta( $post_id, '_dame_agenda_description', true ),
					'color'       => $color,
					'category'    => ! empty( $term ) ? $term[0]->name : '',
				);

				$bg_color = $color;
				// If single-day public event, lighten the background color.
				if ( $start_date === $end_date && 'private' !== $status ) {
					$bg_color = \DAME\Core\Utils::lighten_color( $color, 0.75 );
				}
				$event_data['background_color'] = $bg_color;

				// Determine text color based on background.
				$text_color = \DAME\Core\Utils::get_text_color_based_on_bg( $bg_color );
				$event_data['text_color'] = $text_color;

				$events[] = $event_data;
			}
		}

		wp_reset_postdata();
		wp_send_json_success( $events );
	}

	/**
	 * Search Join Filter.
	 *
	 * @param string   $join Query join.
	 * @param WP_Query $query Query object.
	 * @return string Modified join.
	 */
	public function filter_search_join( $join, $query ) {
		global $wpdb;
		if ( ! empty( $query->get( 'dame_search' ) ) ) {
			$join .= $wpdb->prepare(
				" LEFT JOIN {$wpdb->postmeta} AS dame_desc_meta ON {$wpdb->posts}.ID = dame_desc_meta.post_id AND dame_desc_meta.meta_key = %s",
				'_dame_agenda_description'
			);
			$join .= " LEFT JOIN {$wpdb->term_relationships} AS dame_tr ON {$wpdb->posts}.ID = dame_tr.object_id";
			$join .= " LEFT JOIN {$wpdb->term_taxonomy} AS dame_tt ON dame_tr.term_taxonomy_id = dame_tt.term_taxonomy_id";
			$join .= " LEFT JOIN {$wpdb->terms} AS dame_t ON dame_tt.term_id = dame_t.term_id";
		}
		return $join;
	}

	/**
	 * Search Where Filter.
	 *
	 * @param string   $where Query where.
	 * @param WP_Query $query Query object.
	 * @return string Modified where.
	 */
	public function filter_search_where( $where, $query ) {
		global $wpdb;
		$search_term = $query->get( 'dame_search' );
		if ( ! empty( $search_term ) ) {
			$search_term_like = '%' . $wpdb->esc_like( $search_term ) . '%';
			$search_where     = $wpdb->prepare(
				"(
					{$wpdb->posts}.post_title LIKE %s
					OR dame_desc_meta.meta_value LIKE %s
					OR (dame_tt.taxonomy = 'dame_agenda_category' AND dame_t.name LIKE %s)
				)",
				$search_term_like,
				$search_term_like,
				$search_term_like
			);
			$where           .= " AND ( " . $search_where . " )";
		}
		return $where;
	}

	/**
	 * Search Distinct Filter.
	 *
	 * @param string   $distinct Query distinct.
	 * @param WP_Query $query Query object.
	 * @return string Modified distinct.
	 */
	public function filter_search_distinct( $distinct, $query ) {
		if ( ! empty( $query->get( 'dame_search' ) ) ) {
			return 'DISTINCT';
		}
		return $distinct;
	}

	/**
	 * Renders the [dame_liste_agenda] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The shortcode output.
	 */
	public function render_list( $atts ) {
		$plugin_url = plugin_dir_url( dirname( __DIR__, 2 ) . '/index.php' );
		wp_enqueue_style( 'dame-agenda-style', \DAME_PLUGIN_URL . 'assets/css/public-agenda.css', array(), \DAME_VERSION );

		$atts = shortcode_atts(
			array(
				'nombre' => 4,
			),
			$atts,
			'dame_liste_agenda'
		);

		$nombre = intval( $atts['nombre'] );
		$today  = date( 'Y-m-d' );

		$post_status   = array( 'publish' );
		$allowed_roles = array( 'staff', 'administrator', 'editor', 'entraineur' );
		$current_user  = wp_get_current_user();

		if ( array_intersect( $allowed_roles, $current_user->roles ) ) {
			$post_status[] = 'private';
		}

		$args = array(
			'post_type'      => 'dame_agenda',
			'post_status'    => $post_status,
			'posts_per_page' => $nombre,
			'meta_key'       => '_dame_start_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_dame_start_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p>' . __( 'Aucun événement à venir.', 'dame' ) . '</p>';
		}

		ob_start();
		?>
		<div class="dame-liste-agenda-wrapper">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<?php
				$post_id        = get_the_ID();
				$start_date_str = get_post_meta( $post_id, '_dame_start_date', true );
				$end_date_str   = get_post_meta( $post_id, '_dame_end_date', true );
				$start_time     = get_post_meta( $post_id, '_dame_start_time', true );
				$end_time       = get_post_meta( $post_id, '_dame_end_time', true );
				$all_day        = get_post_meta( $post_id, '_dame_all_day', true );

				$start_date = new DateTime( $start_date_str );
				$end_date   = new DateTime( $end_date_str );

				$day_of_week = date_i18n( 'D', $start_date->getTimestamp() );
				$day_number  = $start_date->format( 'd' );
				$month_abbr  = date_i18n( 'M', $start_date->getTimestamp() );

				$date_display = date_i18n( 'j F Y', $start_date->getTimestamp() );
				if ( $start_date_str !== $end_date_str ) {
					$date_display = date_i18n( 'j F Y', $start_date->getTimestamp() ) . ' - ' . date_i18n( 'j F Y', $end_date->getTimestamp() );
				}

				if ( ! $all_day && $start_time ) {
					$time_display  = esc_html( $start_time . '-' . $end_time );
					$date_display .= "&nbsp;<i>$time_display</i>";
				}

				$is_private        = get_post_status( $post_id ) === 'private';
				$date_circle_style = $is_private ? 'style="background-color: #c9a0dc;"' : '';
				?>
				<div class="dame-liste-agenda-item">
					<div class="dame-liste-agenda-date-icon">
						<div class="date-circle" <?php echo $date_circle_style; ?>>
							<span class="day-of-week"><?php echo esc_html( mb_strtoupper( $day_of_week, 'UTF-8' ) ); ?></span>
							<span class="day-number"><?php echo esc_html( $day_number ); ?></span>
							<span class="month-abbr"><?php echo esc_html( mb_strtoupper( $month_abbr, 'UTF-8' ) ); ?></span>
						</div>
					</div>
					<div class="dame-liste-agenda-details">
						<h4 class="event-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_post_field( 'post_title', get_the_ID() ) ); ?></a></h4>
						<p class="event-date"><?php echo $date_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php
						$description = get_post_meta( get_the_ID(), '_dame_agenda_description', true );
						if ( ! empty( $description ) ) :
							$truncated_description = '';
							$permalink             = get_permalink();
							$read_more_link        = '&nbsp;<a href="' . esc_url( $permalink ) . '" class="dame-read-more">...</a>';

							// Regex to find trailing <br> tags, whitespace, and &nbsp;
							$cleanup_regex = '/(?:<br\s*\/?>|\s|&nbsp;)*$/i';

							// Find the position of the first closing paragraph tag
							$first_p_closing_pos = strpos( $description, '</p>' );

							if ( $first_p_closing_pos !== false ) {
								// Paragraph tag exists.
								$first_paragraph_content = substr( $description, 0, $first_p_closing_pos );
								$rest_of_description     = substr( $description, $first_p_closing_pos + strlen( '</p>' ) );

								if ( trim( $rest_of_description ) !== '' ) {
									// More content exists after the first paragraph.
									$cleaned_content       = preg_replace( $cleanup_regex, '', $first_paragraph_content );
									$truncated_description = $cleaned_content . $read_more_link . '</p>';
								} else {
									// Only one paragraph, so display the whole description.
									$truncated_description = $description;
								}
							} else {
								// No paragraph tags, fall back to truncating by the first line break.
								$lines      = explode( "\n", $description, 2 );
								$first_line = $lines[0];

								if ( isset( $lines[1] ) && trim( $lines[1] ) !== '' ) {
									// More lines exist.
									$cleaned_line          = preg_replace( $cleanup_regex, '', $first_line );
									$truncated_description = $cleaned_line . $read_more_link;
								} else {
									$truncated_description = $first_line;
								}
							}
							?>
							<div class="event-description"><?php echo apply_filters( 'the_content', $truncated_description ); ?></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}
