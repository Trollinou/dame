<?php
/**
 * DAME Admin Menu class.
 *
 * @package DAME
 */

namespace DAME\Admin;

use DAME\Admin\Pages\Mailing;
use DAME\Admin\Pages\Backups;
use DAME\Admin\Settings\Main as SettingsMain;
use DAME\Admin\Pages\MessageReport;

class Menu {

	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menus' ], 10 );
		add_action( 'admin_menu', [ $this, 'reorder_dame_submenu' ], 999 );
		add_filter( 'parent_file', [ $this, 'highlight_parent_menu' ] );
		add_filter( 'submenu_file', [ $this, 'highlight_submenu' ] );
		(new MessageReport())->init();
	}

	public function add_menus() {
		// 1. Menu Parent & Tableau de bord (slug: dame-admin)
		add_menu_page(
			__( "DAME - Gestion", "dame" ),
			__( "DAME", "dame" ),
			'manage_options',
			'dame-admin',
			[ $this, 'render_dashboard' ],
			'dashicons-groups',
			30
		);

		// Envoyer un message (Mailing)
		add_submenu_page(
			'dame-admin',
			__( "Envoyer un message", "dame" ),
			__( "Envoyer un message", "dame" ),
			'edit_dame_messages',
			'dame-mailing',
			[ new Mailing(), 'render' ]
		);

		// Sauvegardes
		add_submenu_page(
			'dame-admin',
			__( "Sauvegardes et Import", "dame" ),
			__( "Sauvegardes", "dame" ),
			'manage_options',
			'dame-backups',
			[ new Backups(), 'render' ]
		);

		// Réglages
		add_submenu_page(
			'dame-admin',
			__( "Réglages DAME", "dame" ),
			__( "Réglages", "dame" ),
			'manage_options',
			'dame-settings',
			[ new SettingsMain(), 'render_page' ]
		);

		// Page cachée : Rapport détaillé d'un message
		add_submenu_page(
			'dame-admin', // <-- Changement ici
			__( "Rapport du message", "dame" ),
			__( "Rapport", "dame" ),
			'edit_dame_messages',
			'dame-message-report',
			[ new MessageReport(), 'render' ]
		);
	}

		public function reorder_dame_submenu() {
		global $submenu;

		if ( ! isset( $submenu['dame-admin'] ) ) {
			return;
		}

		$dame_submenu = $submenu['dame-admin'];
		$reordered = [];

		$desired_order = [
			'dame-admin' => __( "DAME", "dame" ),
			'edit.php?post_type=adherent' => __( "Tous les adhérents", "dame" ),
			'edit.php?post_type=dame_pre_inscription' => __( "Toutes les préinscriptions", "dame" ),
			'edit-tags.php?taxonomy=dame_saison_adhesion&amp;post_type=adherent' => __( "Saisons d'adhésion", "dame" ),
			'edit-tags.php?taxonomy=dame_group&amp;post_type=adherent' => __( "Groupes", "dame" ),
			'edit.php?post_type=dame_message' => __( "Tous les messages", "dame" ),
			'dame-mailing' => __( "Envoyer un message", "dame" ),
			'edit.php?post_type=dame_agenda' => __( "Tous les évènements", "dame" ),
			'edit-tags.php?taxonomy=dame_agenda_category&amp;post_type=dame_agenda' => __( "Catégories d'évènements", "dame" ),
			'edit.php?post_type=dame_ical_feed' => __( "Flux d'agenda", "dame" ),
			'edit.php?post_type=sondage' => __( "Tous les sondages", "dame" ),
			'dame-backups' => __( "Sauvegardes", "dame" ),
			'dame-settings' => __( "Réglages", "dame" ),
		];

		foreach ( $desired_order as $url => $new_title ) {
			$found = false;
			foreach ( $dame_submenu as $key => $item ) {
				$item_url = str_replace( '&', '&amp;', $item[2] );
				$target_url = str_replace( '&', '&amp;', $url );

				if ( $item_url === $target_url || $item[2] === $url ) {
					$item[0] = $new_title;
					// SÉCURITÉ PHP 8.1 : S'assurer que l'index 3 (Page Title) existe
					if ( ! isset( $item[3] ) ) {
						$item[3] = $new_title;
					}
					$reordered[] = $item;
					unset( $dame_submenu[$key] );
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$cap = ( strpos( $url, 'edit-tags.php' ) !== false ) ? 'manage_categories' : 'manage_options';
				$clean_url = str_replace( '&amp;', '&', $url );
				// SÉCURITÉ PHP 8.1 : Ajout du 4ème paramètre (Index 3)
				$reordered[] = [ $new_title, $cap, $clean_url, $new_title ];
			}
		}

		foreach ( $dame_submenu as $item ) {
			if ( ! isset( $item[3] ) ) {
				$item[3] = $item[0];
			}
			$reordered[] = $item;
		}

		$submenu['dame-admin'] = $reordered;
	}

	public function render_dashboard() {
		// 1. Saison en cours
		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_term           = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
		$season_name           = ( $season_term && ! is_wp_error( $season_term ) ) ? $season_term->name : __( 'Non définie', 'dame' );

		// 2. Comptage Adhérents (Saison en cours)
		$adherents_args = [
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		];
		if ( $current_season_tag_id ) {
			$adherents_args['tax_query'] = [
				[
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => $current_season_tag_id,
				],
			];
		}
		$adherent_ids    = get_posts( $adherents_args );
		$total_adherents = count( $adherent_ids );

		$licence_counts = [
			'A'      => 0,
			'B'      => 0,
			'Autres' => 0,
		];
		if ( $total_adherents > 0 ) {
			foreach ( $adherent_ids as $id ) {
				$licence_type = get_post_meta( $id, '_dame_license_type', true );
				if ( strpos( $licence_type, 'Licence A' ) !== false || $licence_type === 'A' ) {
					$licence_counts['A']++;
				} elseif ( strpos( $licence_type, 'Licence B' ) !== false || $licence_type === 'B' ) {
					$licence_counts['B']++;
				} else {
					$licence_counts['Autres']++;
				}
			}
		}

		// 3. Derniers Adhérents
		$latest_adherents = get_posts( [
			'post_type'      => 'adherent',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
		] );

		// 4. Préinscriptions en attente
		// Note : wp_count_posts returns object with properties as statuses. 'draft' or 'pending' might be used for new ones.
		// As per standard WP, 'draft' or a custom status like 'pending' is used. Let's get total minus publish, or just count 'draft'.
		// Often forms save as draft or pending. We will check post_status = 'pending' or 'draft'.
		$pre_inscriptions_query = new \WP_Query( [
			'post_type'      => 'dame_pre_inscription',
			'post_status'    => [ 'pending', 'draft' ],
			'posts_per_page' => 1, // We only need the count
		] );
		$pending_preinscriptions = $pre_inscriptions_query->found_posts;

		// 5. Prochains événements Agenda
		$today = current_time( 'Y-m-d' );
		$upcoming_events = get_posts( [
			'post_type'      => 'dame_agenda',
			'posts_per_page' => 5,
			'meta_key'       => '_dame_start_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_dame_start_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				],
			],
			'post_status'    => 'publish',
		] );

		// Render HTML
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tableau de Bord DAME', 'dame' ); ?></h1>
			<p><?php esc_html_e( 'Bienvenue dans l\'espace de gestion de votre club.', 'dame' ); ?></p>

			<div class="welcome-panel" style="padding: 20px;">
				<h2><?php esc_html_e( 'Vue d\'ensemble - Saison active :', 'dame' ); ?> <strong><?php echo esc_html( $season_name ); ?></strong></h2>
				<div style="display: flex; gap: 20px; margin-top: 20px;">
					<div style="flex: 1; background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Total Adhérents', 'dame' ); ?></h3>
						<p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo intval( $total_adherents ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adherent' ) ); ?>"><?php esc_html_e( 'Voir tout', 'dame' ); ?></a>
					</div>
					<div style="flex: 1; background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Préinscriptions en attente', 'dame' ); ?></h3>
						<p style="font-size: 24px; font-weight: bold; margin: 0;"><?php echo intval( $pending_preinscriptions ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dame_pre_inscription' ) ); ?>"><?php esc_html_e( 'Voir tout', 'dame' ); ?></a>
					</div>
				</div>
			</div>

			<div id="dashboard-widgets-wrap">
				<div id="dashboard-widgets" class="metabox-holder">

					<!-- Colonne Gauche -->
					<div class="postbox-container" style="width: 49%; float: left; margin-right: 2%;">

						<!-- Répartition des licences -->
						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( 'Répartition des licences', 'dame' ); ?></span></h2>
							<div class="inside">
								<ul>
									<li><strong><?php esc_html_e( 'Licence A :', 'dame' ); ?></strong> <?php echo intval( $licence_counts['A'] ); ?></li>
									<li><strong><?php esc_html_e( 'Licence B :', 'dame' ); ?></strong> <?php echo intval( $licence_counts['B'] ); ?></li>
									<li><strong><?php esc_html_e( 'Autres / Non précisé :', 'dame' ); ?></strong> <?php echo intval( $licence_counts['Autres'] ); ?></li>
								</ul>
							</div>
						</div>

						<!-- Derniers Adhérents -->
						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( '3 derniers adhérents enregistrés', 'dame' ); ?></span></h2>
							<div class="inside">
								<?php if ( empty( $latest_adherents ) ) : ?>
									<p><?php esc_html_e( 'Aucun adhérent trouvé.', 'dame' ); ?></p>
								<?php else : ?>
									<ul>
										<?php foreach ( $latest_adherents as $adherent ) : ?>
											<li>
												<a href="<?php echo esc_url( get_edit_post_link( $adherent->ID ) ); ?>">
													<?php echo esc_html( get_the_title( $adherent->ID ) ); ?>
												</a>
												- <span style="color: #666; font-size: 0.9em;"><?php echo esc_html( get_the_date( '', $adherent->ID ) ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adherent' ) ); ?>"><?php esc_html_e( 'Voir tous les adhérents', 'dame' ); ?></a></p>
							</div>
						</div>

					</div>

					<!-- Colonne Droite -->
					<div class="postbox-container" style="width: 49%; float: left;">

						<!-- Prochains événements -->
						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( '5 prochains événements (Agenda)', 'dame' ); ?></span></h2>
							<div class="inside">
								<?php if ( empty( $upcoming_events ) ) : ?>
									<p><?php esc_html_e( 'Aucun événement à venir.', 'dame' ); ?></p>
								<?php else : ?>
									<ul>
										<?php foreach ( $upcoming_events as $event ) :
											$start_date = get_post_meta( $event->ID, '_dame_start_date', true );
											$formatted_date = wp_date( get_option( 'date_format' ), strtotime( $start_date ) );
										?>
											<li>
												<strong><?php echo esc_html( $formatted_date ); ?></strong> :
												<a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>">
													<?php echo esc_html( get_the_title( $event->ID ) ); ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dame_agenda' ) ); ?>"><?php esc_html_e( 'Voir tout l\'agenda', 'dame' ); ?></a></p>
							</div>
						</div>

					</div>

					<div class="clear"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Force le menu parent "DAME" à rester ouvert.
	 */
	public function highlight_parent_menu( $parent_file ) {
		global $current_screen;
		$dame_taxonomies = [ 'dame_saison_adhesion', 'dame_group', 'dame_agenda_category' ];

		// Pour les taxonomies
		if ( isset( $current_screen->taxonomy ) && in_array( $current_screen->taxonomy, $dame_taxonomies ) ) {
			return 'dame-admin';
		}

		// Pour la page cachée du rapport de message
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'dame-message-report' ) {
			return 'dame-admin';
		}

		return $parent_file;
	}

	/**
	 * Force la mise en surbrillance (gras) du bon sous-menu.
	 */
	public function highlight_submenu( $submenu_file ) {
		global $current_screen;

		// Pour les taxonomies
		if ( isset( $current_screen->taxonomy ) ) {
			if ( $current_screen->taxonomy === 'dame_saison_adhesion' ) {
				return 'edit-tags.php?taxonomy=dame_saison_adhesion&post_type=adherent';
			}
			if ( $current_screen->taxonomy === 'dame_group' ) {
				return 'edit-tags.php?taxonomy=dame_group&post_type=adherent';
			}
			if ( $current_screen->taxonomy === 'dame_agenda_category' ) {
				return 'edit-tags.php?taxonomy=dame_agenda_category&post_type=dame_agenda';
			}
		}

		// Pour la page cachée du rapport de message (met en surbrillance "Tous les messages")
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'dame-message-report' ) {
			return 'edit.php?post_type=dame_message';
		}

		return $submenu_file;
	}
}
