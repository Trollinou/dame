<?php
/**
 * The template for displaying a single DAME Agenda event.
 *
 * @package DAME
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

		<?php
		while ( have_posts() ) :
			the_post();
			$event_id = get_the_ID();

			// --- Retrieve Event Meta Data ---
			$start_date_str = get_post_meta( $event_id, '_dame_event_start_date', true );
			$end_date_str   = get_post_meta( $event_id, '_dame_event_end_date', true );
			$all_day        = get_post_meta( $event_id, '_dame_event_allday', true );
			$start_time     = get_post_meta( $event_id, '_dame_event_start_time', true );
			$end_time       = get_post_meta( $event_id, '_dame_event_end_time', true );

			$location_name      = get_post_meta( $event_id, '_dame_event_location_name', true );
			$location_address_1 = get_post_meta( $event_id, '_dame_event_location_address_1', true );
			$location_address_2 = get_post_meta( $event_id, '_dame_event_location_address_2', true );
			$location_postal_code = get_post_meta( $event_id, '_dame_event_location_postal_code', true );
			$location_city      = get_post_meta( $event_id, '_dame_event_location_city', true );
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'dame-event-single' ); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>

				<div class="entry-content">
					<div class="dame-event-details">

						<!-- Date and Time -->
						<div class="dame-event-section dame-event-date-time">
							<h3><?php _e( 'Quand', 'dame' ); ?></h3>
							<?php
							$start_date = new DateTime( $start_date_str );
							$end_date   = new DateTime( $end_date_str );

							if ( $start_date_str === $end_date_str ) {
								// Single day event
								echo '<p>' . esc_html( date_i18n( get_option( 'date_format' ), $start_date->getTimestamp() ) ) . '</p>';
							} else {
								// Multi-day event
								echo '<p>' . sprintf(
									/* translators: 1: Start date, 2: End date */
									__( 'Du %1$s au %2$s', 'dame' ),
									esc_html( date_i18n( get_option( 'date_format' ), $start_date->getTimestamp() ) ),
									esc_html( date_i18n( get_option( 'date_format' ), $end_date->getTimestamp() ) )
								) . '</p>';
							}

							if ( $all_day ) {
								echo '<p><em>' . esc_html__( 'Journée entière', 'dame' ) . '</em></p>';
							} elseif ( $start_time && $end_time ) {
								echo '<p>' . sprintf(
									/* translators: 1: Start time, 2: End time */
									__( 'De %1$s à %2$s', 'dame' ),
									esc_html( $start_time ),
									esc_html( $end_time )
								) . '</p>';
							}
							?>
						</div>

						<!-- Location -->
						<?php if ( ! empty( $location_name ) || ! empty( $location_address_1 ) ) : ?>
							<div class="dame-event-section dame-event-location">
								<h3><?php _e( 'Où', 'dame' ); ?></h3>
								<?php if ( ! empty( $location_name ) ) : ?>
									<strong><?php echo esc_html( $location_name ); ?></strong>
								<?php endif; ?>
								<address>
									<?php
									echo ! empty( $location_address_1 ) ? esc_html( $location_address_1 ) . '<br>' : '';
									echo ! empty( $location_address_2 ) ? esc_html( $location_address_2 ) . '<br>' : '';
									echo ! empty( $location_postal_code ) ? esc_html( $location_postal_code ) . ' ' : '';
									echo ! empty( $location_city ) ? esc_html( $location_city ) : '';
									?>
								</address>
							</div>
						<?php endif; ?>
					</div>

					<hr>

					<!-- Description -->
					<div class="dame-event-description">
						<h3><?php _e( 'Description', 'dame' ); ?></h3>
						<?php the_content(); ?>
					</div>

				</div>

			</article>

		<?php endwhile; ?>

	</main>
</div>

<style>
	.dame-event-details { display: flex; gap: 2em; margin-bottom: 1.5em; flex-wrap: wrap; }
	.dame-event-section { flex: 1; min-width: 250px; }
	.dame-event-section h3 { margin-top: 0; }
	.dame-event-section p, .dame-event-section address { margin-bottom: 0; }
	address { font-style: normal; }
</style>

<?php get_footer(); ?>
