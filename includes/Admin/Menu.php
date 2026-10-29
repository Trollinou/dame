<?php
/**
 * DAME Admin Menu class.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin;

use DAME\Admin\Pages\Mailing;
use DAME\Admin\Pages\Backups;
use DAME\Admin\Pages\ImportFFE;
use DAME\Admin\Pages\Ticketing;
use DAME\Admin\Settings\Main as SettingsMain;
use DAME\Admin\Pages\MessageReport;

/**
 * Class Menu
 */
class Menu {

	/**
	 * Initialize admin menu hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_menus' ), 10 );
		add_action( 'admin_menu', array( $this, 'reorder_dame_submenu' ), 999 );
		add_action( 'all_admin_notices', array( $this, 'render_taxonomies_and_feeds_tabs' ) );
		add_filter( 'parent_file', array( $this, 'highlight_parent_menu' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_submenu' ) );
		( new Backups() )->init();
		( new Mailing() )->init();
		( new Ticketing() )->init();
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_menus(): void {
		// 1. Menu Parent & Tableau de bord (slug: dame-admin).
		add_menu_page(
			__( 'DAME - Gestion', 'dame' ),
			__( 'DAME', 'dame' ),
			'edit_dame_messages',
			'dame-admin',
			array( $this, 'render_dashboard' ),
			'dashicons-groups',
			30.1
		);

		// Envoyer un message (Mailing - rattaché aux messages).
		add_submenu_page(
			'dame-admin',
			__( 'Envoyer un message', 'dame' ),
			__( 'Envoyer un message', 'dame' ),
			'edit_dame_messages',
			'dame-mailing',
			array( new Mailing(), 'render' )
		);

		// HelloAsso & Billetterie.
		add_submenu_page(
			'dame-admin',
			__( 'HelloAsso', 'dame' ),
			__( 'HelloAsso', 'dame' ),
			'manage_dame_ticketing',
			'dame-ticketing',
			array( new Ticketing(), 'render' )
		);

		// Imports & Sauvegardes (Administrateurs).
		add_submenu_page(
			'dame-admin',
			__( 'Imports & Sauvegardes', 'dame' ),
			__( 'Imports & Sauvegardes', 'dame' ),
			'manage_options',
			'dame-import-ffe',
			array( new ImportFFE(), 'render' )
		);

		// Sauvegardes (Sous-onglet d'Imports & Sauvegardes).
		add_submenu_page(
			'dame-admin',
			__( 'Sauvegardes et Restaurations', 'dame' ),
			__( 'Sauvegardes', 'dame' ),
			'manage_options',
			'dame-backups',
			array( new Backups(), 'render' )
		);

		// Réglages (Administrateurs).
		add_submenu_page(
			'dame-admin',
			__( 'Réglages DAME', 'dame' ),
			__( 'Réglages', 'dame' ),
			'manage_options',
			'dame-settings',
			array( new SettingsMain(), 'render_page' )
		);

		// Page cachée : Rapport détaillé d'un message.
		add_submenu_page(
			'',
			__( 'Rapport du message', 'dame' ),
			__( 'Rapport', 'dame' ),
			'edit_dame_messages',
			'dame-message-report',
			array( new MessageReport(), 'render' )
		);
	}

	/**
	 * Reorder submenus under the main DAME menu.
	 */
	public function reorder_dame_submenu(): void {
		global $submenu;

		if ( ! isset( $submenu['dame-admin'] ) ) {
			return;
		}

		$dame_submenu = $submenu['dame-admin'];
		$reordered    = array();

		// Target 11 clean items in exact requested order.
		$desired_order = array(
			'dame-admin'                              => array(
				'title' => __( 'Tableau de bord', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'edit.php?post_type=dame_pre_inscription' => array(
				'title' => __( 'Préinscriptions', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'edit.php?post_type=adherent'             => array(
				'title' => __( 'Adhérents', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'edit.php?post_type=dame_contact'         => array(
				'title' => __( 'Contacts', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'edit.php?post_type=dame_agenda'          => array(
				'title' => __( 'Évènements', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'dame-ticketing'                          => array(
				'title' => __( 'HelloAsso', 'dame' ),
				'cap'   => 'manage_dame_ticketing',
			),
			'edit.php?post_type=benevolat'            => array(
				'title' => __( 'Appels à bénévoles', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'edit.php?post_type=dame_message'         => array(
				'title' => __( 'Messages', 'dame' ),
				'cap'   => 'edit_dame_messages',
			),
			'edit-tags.php?taxonomy=dame_group&amp;post_type=adherent' => array(
				'title' => __( 'Catégories & Flux', 'dame' ),
				'cap'   => 'manage_categories',
			),
			'dame-import-ffe'                         => array(
				'title' => __( 'Imports & Sauvegardes', 'dame' ),
				'cap'   => 'manage_options',
			),
			'dame-settings'                           => array(
				'title' => __( 'Réglages', 'dame' ),
				'cap'   => 'manage_options',
			),
		);

		// Items to explicitly exclude from the sidebar menu (managed via tabs or actions).
		$excluded_slugs = array(
			'dame-mailing',
			'dame-backups',
			'edit.php?post_type=dame_ical_feed',
			'edit-tags.php?taxonomy=dame_contact_type&amp;post_type=dame_contact',
			'edit-tags.php?taxonomy=dame_contact_type&post_type=dame_contact',
			'edit-tags.php?taxonomy=dame_agenda_category&amp;post_type=dame_agenda',
			'edit-tags.php?taxonomy=dame_agenda_category&post_type=dame_agenda',
			'edit-tags.php?taxonomy=dame_saison_adhesion&amp;post_type=adherent',
			'edit-tags.php?taxonomy=dame_saison_adhesion&post_type=adherent',
		);

		foreach ( $desired_order as $url => $config ) {
			if ( ! current_user_can( $config['cap'] ) ) {
				continue;
			}

			$found = false;
			foreach ( $dame_submenu as $key => $item ) {
				$item_url   = str_replace( '&', '&amp;', $item[2] );
				$target_url = str_replace( '&', '&amp;', $url );

				if ( $item_url === $target_url || $item[2] === $url ) {
					$item[0] = $config['title'];
					$item[1] = $config['cap'];
					if ( ! isset( $item[3] ) ) {
						$item[3] = $config['title'];
					}
					$reordered[] = $item;
					unset( $dame_submenu[ $key ] );
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$clean_url   = str_replace( '&amp;', '&', $url );
				$reordered[] = array( $config['title'], $config['cap'], $clean_url, $config['title'] );
			}
		}

		// Keep any unexpected third-party submenu items while filtering out excluded items.
		foreach ( $dame_submenu as $item ) {
			$item_url = str_replace( '&', '&amp;', $item[2] );
			if ( in_array( $item[2], $excluded_slugs, true ) || in_array( $item_url, $excluded_slugs, true ) ) {
				continue;
			}
			if ( ! isset( $item[3] ) ) {
				$item[3] = $item[0];
			}
			$reordered[] = $item;
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu['dame-admin'] = $reordered;
	}

	/**
	 * Render the main DAME admin dashboard page.
	 */
	public function render_dashboard(): void {
		// 1. Saison en cours.
		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_term           = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
		$season_name           = ( $season_term && ! is_wp_error( $season_term ) ) ? $season_term->name : __( 'Non définie', 'dame' );

		// 2. Comptage Adhérents (Saison en cours).
		$adherents_args = array(
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);
		if ( $current_season_tag_id ) {
			$adherents_args['tax_query'] = array(
				array(
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => $current_season_tag_id,
				),
			);
		}
		$adherent_ids    = get_posts( $adherents_args );
		$total_adherents = count( $adherent_ids );

		$licence_counts = array(
			'A'      => 0,
			'B'      => 0,
			'Autres' => 0,
		);
		if ( $total_adherents > 0 ) {
			foreach ( $adherent_ids as $id ) {
				$licence_type = get_post_meta( $id, '_dame_license_type', true );
				if ( strpos( $licence_type, 'Licence A' ) !== false || $licence_type === 'A' ) {
					++$licence_counts['A'];
				} elseif ( strpos( $licence_type, 'Licence B' ) !== false || $licence_type === 'B' ) {
					++$licence_counts['B'];
				} else {
					++$licence_counts['Autres'];
				}
			}
		}

		// 3. Derniers Adhérents.
		$latest_adherents = get_posts(
			array(
				'post_type'      => 'adherent',
				'posts_per_page' => 5,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			)
		);

		// 4. Préinscriptions en attente.
		$pre_inscriptions_query  = new \WP_Query(
			array(
				'post_type'      => 'dame_pre_inscription',
				'post_status'    => array( 'pending', 'draft' ),
				'posts_per_page' => 1,
			)
		);
		$pending_preinscriptions = $pre_inscriptions_query->found_posts;

		// 5. Prochains événements Agenda.
		$today           = current_time( 'Y-m-d' );
		$upcoming_events = get_posts(
			array(
				'post_type'      => 'dame_agenda',
				'posts_per_page' => 5,
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
				'post_status'    => 'publish',
			)
		);

		// 6. Bénévolats en cours.
		$active_benevolats = array();
		$all_benevolats    = get_posts(
			array(
				'post_type'      => 'benevolat',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		if ( ! empty( $all_benevolats ) ) {
			foreach ( $all_benevolats as $benevolat ) {
				$benevolat_data = get_post_meta( $benevolat->ID, '_dame_benevolat_data', true );
				if ( ! empty( $benevolat_data ) ) {
					$dates = array_column( (array) $benevolat_data, 'date' );
					if ( empty( $dates ) ) {
						continue;
					}

					$first_date = min( $dates );
					$last_date  = max( $dates );

					if ( $last_date >= $today ) {
						$responses_query = new \WP_Query(
							array(
								'post_type'      => 'benevolat_reponse',
								'post_status'    => 'publish',
								'post_parent'    => $benevolat->ID,
								'posts_per_page' => 1,
							)
						);

						$active_benevolats[] = array(
							'ID'              => $benevolat->ID,
							'title'           => $benevolat->post_title,
							'first_date'      => $first_date,
							'responses_count' => $responses_query->found_posts,
						);
					}
				}
			}

			usort(
				$active_benevolats,
				function ( array $a, array $b ) {
					return strcmp( $a['first_date'], $b['first_date'] );
				}
			);
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tableau de Bord DAME', 'dame' ); ?></h1>
			<p><?php esc_html_e( 'Bienvenue dans l\'espace de gestion de votre club.', 'dame' ); ?></p>

			<div class="postbox dame-dashboard-summary-box">
				<h2><?php esc_html_e( 'Vue d\'ensemble - Saison active :', 'dame' ); ?> <strong><?php echo esc_html( $season_name ); ?></strong></h2>
				<div class="dame-dashboard-cards">
					<div class="dame-dashboard-card">
						<h3><?php esc_html_e( 'Total Adhérents', 'dame' ); ?></h3>
						<p class="dame-dashboard-card-value"><?php echo intval( $total_adherents ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adherent' ) ); ?>"><?php esc_html_e( 'Voir tout', 'dame' ); ?></a>
					</div>
					<div class="dame-dashboard-card dame-dashboard-card--alert">
						<h3><?php esc_html_e( 'Préinscriptions en attente', 'dame' ); ?></h3>
						<p class="dame-dashboard-card-value"><?php echo intval( $pending_preinscriptions ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dame_pre_inscription' ) ); ?>"><?php esc_html_e( 'Voir tout', 'dame' ); ?></a>
					</div>
				</div>
			</div>

			<div id="dashboard-widgets-wrap">
				<div id="dashboard-widgets" class="metabox-holder dame-dashboard-columns">

					<!-- Colonne Gauche -->
					<div class="postbox-container dame-dashboard-column">

						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( '5 derniers adhérents enregistrés', 'dame' ); ?></span></h2>
							<div class="inside">
								<?php if ( empty( $latest_adherents ) ) : ?>
									<p><?php esc_html_e( 'Aucun adhérent trouvé.', 'dame' ); ?></p>
								<?php else : ?>
									<ul>
										<?php foreach ( $latest_adherents as $adherent ) : ?>
											<li>
												<a href="<?php echo esc_url( (string) get_edit_post_link( $adherent->ID ) ); ?>">
													<?php echo esc_html( get_the_title( $adherent->ID ) ); ?>
												</a>
												- <span class="dame-dashboard-meta"><?php echo esc_html( (string) get_the_date( '', $adherent->ID ) ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adherent' ) ); ?>"><?php esc_html_e( 'Voir tous les adhérents', 'dame' ); ?></a></p>
							</div>
						</div>

						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( 'Répartition des licences', 'dame' ); ?></span></h2>
							<div class="inside">
								<ul>
									<li>
										<strong><?php esc_html_e( 'Licence A :', 'dame' ); ?></strong>
										<a href="
										<?php
										echo esc_url(
											add_query_arg(
												array(
													'post_type' => 'adherent',
													'dame_license_type_filter' => 'A',
													'dame_saison_filter' => $current_season_tag_id,
												),
												admin_url( 'edit.php' )
											)
										);
										?>
													">
											<?php echo intval( $licence_counts['A'] ); ?>
										</a>
									</li>
									<li>
										<strong><?php esc_html_e( 'Licence B :', 'dame' ); ?></strong>
										<a href="
										<?php
										echo esc_url(
											add_query_arg(
												array(
													'post_type' => 'adherent',
													'dame_license_type_filter' => 'B',
													'dame_saison_filter' => $current_season_tag_id,
												),
												admin_url( 'edit.php' )
											)
										);
										?>
													">
											<?php echo intval( $licence_counts['B'] ); ?>
										</a>
									</li>
									<li>
										<strong><?php esc_html_e( 'Autres / Non précisé :', 'dame' ); ?></strong>
										<a href="
										<?php
										echo esc_url(
											add_query_arg(
												array(
													'post_type' => 'adherent',
													'dame_license_type_filter' => 'Autres',
													'dame_saison_filter' => $current_season_tag_id,
												),
												admin_url( 'edit.php' )
											)
										);
										?>
													">
											<?php echo intval( $licence_counts['Autres'] ); ?>
										</a>
									</li>
								</ul>
							</div>
						</div>

					</div>

					<!-- Colonne Droite -->
					<div class="postbox-container dame-dashboard-column">

						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( '5 prochains événements (Agenda)', 'dame' ); ?></span></h2>
							<div class="inside">
								<?php if ( empty( $upcoming_events ) ) : ?>
									<p><?php esc_html_e( 'Aucun événement à venir.', 'dame' ); ?></p>
								<?php else : ?>
									<ul>
										<?php
										foreach ( $upcoming_events as $event ) :
											$start_date     = (string) get_post_meta( $event->ID, '_dame_start_date', true );
											$start_ts       = strtotime( $start_date );
											$formatted_date = '';
											if ( false !== $start_ts ) {
												$df_str = wp_date( (string) get_option( 'date_format' ), $start_ts );
												if ( false !== $df_str ) {
													$formatted_date = $df_str;
												}
											}
											?>
											<li>
												<strong><?php echo esc_html( $formatted_date ); ?></strong> :
												<a href="<?php echo esc_url( (string) get_edit_post_link( $event->ID ) ); ?>">
													<?php echo esc_html( get_the_title( $event->ID ) ); ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dame_agenda' ) ); ?>"><?php esc_html_e( 'Voir tout l\'agenda', 'dame' ); ?></a></p>
							</div>
						</div>

						<div class="postbox">
							<h2 class="hndle"><span><?php esc_html_e( 'Bénévolats en cours', 'dame' ); ?></span></h2>
							<div class="inside">
								<?php if ( empty( $active_benevolats ) ) : ?>
									<p><?php esc_html_e( 'Aucun appel en cours.', 'dame' ); ?></p>
								<?php else : ?>
									<ul>
										<?php
										foreach ( $active_benevolats as $benevolat ) :
											$bene_ts        = strtotime( (string) $benevolat['first_date'] );
											$formatted_date = '';
											if ( false !== $bene_ts ) {
												$df_str = wp_date( (string) get_option( 'date_format' ), $bene_ts );
												if ( false !== $df_str ) {
													$formatted_date = $df_str;
												}
											}
											?>
											<li>
												<strong><?php echo esc_html( $formatted_date ); ?></strong> :
												<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $benevolat['ID'] ) ); ?>">
													<?php echo esc_html( $benevolat['title'] ); ?>
												</a>
												- <span class="dame-dashboard-meta"><?php echo intval( $benevolat['responses_count'] ); ?> <?php echo esc_html( _n( 'réponse', 'réponses', $benevolat['responses_count'], 'dame' ) ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=benevolat' ) ); ?>"><?php esc_html_e( 'Appels à bénévoles', 'dame' ); ?></a></p>
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
	 * Highlight the parent menu for custom taxonomies and subpages.
	 *
	 * @param string $parent_file The parent file slug.
	 * @return string The modified parent file.
	 */
	public function highlight_parent_menu( string $parent_file ): string {
		global $current_screen;
		$dame_taxonomies = array( 'dame_saison_adhesion', 'dame_group', 'dame_agenda_category', 'dame_contact_type' );

		if ( $current_screen instanceof \WP_Screen ) {
			if ( in_array( $current_screen->taxonomy, $dame_taxonomies, true ) || 'dame_ical_feed' === $current_screen->post_type ) {
				return 'dame-admin';
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( in_array( $page, array( 'dame-message-report', 'dame-mailing', 'dame-backups' ), true ) ) {
			return 'dame-admin';
		}

		return $parent_file;
	}

	/**
	 * Highlight the active submenu item for custom taxonomies and subpages.
	 *
	 * @param string|null $submenu_file The active submenu file.
	 * @return string|null The modified submenu file.
	 */
	public function highlight_submenu( ?string $submenu_file ): ?string {
		global $current_screen;
		$dame_taxonomies = array( 'dame_saison_adhesion', 'dame_group', 'dame_agenda_category', 'dame_contact_type' );

		if ( $current_screen instanceof \WP_Screen ) {
			if ( in_array( $current_screen->taxonomy, $dame_taxonomies, true ) || 'dame_ical_feed' === $current_screen->post_type ) {
				return 'edit-tags.php?taxonomy=dame_group&post_type=adherent';
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'dame-message-report' === $page || 'dame-mailing' === $page ) {
			return 'edit.php?post_type=dame_message';
		}

		if ( 'dame-backups' === $page ) {
			return 'dame-import-ffe';
		}

		return $submenu_file;
	}

	/**
	 * Render horizontal tabs for taxonomies and iCal feeds.
	 */
	public function render_taxonomies_and_feeds_tabs(): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_dame_group   = 'dame_group' === $screen->taxonomy;
		$is_contact_type = 'dame_contact_type' === $screen->taxonomy;
		$is_agenda_cat   = 'dame_agenda_category' === $screen->taxonomy;
		$is_season       = 'dame_saison_adhesion' === $screen->taxonomy;
		$is_ical_feed    = 'dame_ical_feed' === $screen->post_type;

		if ( ! $is_dame_group && ! $is_contact_type && ! $is_agenda_cat && ! $is_season && ! $is_ical_feed ) {
			return;
		}

		$tabs = array(
			'dame_group'           => array(
				'title'  => __( "Groupes d'Adhérent", 'dame' ),
				'url'    => admin_url( 'edit-tags.php?taxonomy=dame_group&post_type=adherent' ),
				'active' => $is_dame_group,
			),
			'dame_contact_type'    => array(
				'title'  => __( 'Groupes de Contact', 'dame' ),
				'url'    => admin_url( 'edit-tags.php?taxonomy=dame_contact_type&post_type=dame_contact' ),
				'active' => $is_contact_type,
			),
			'dame_agenda_category' => array(
				'title'  => __( "Groupes d'Évènements", 'dame' ),
				'url'    => admin_url( 'edit-tags.php?taxonomy=dame_agenda_category&post_type=dame_agenda' ),
				'active' => $is_agenda_cat,
			),
			'dame_saison_adhesion' => array(
				'title'  => __( "Saisons d'adhésion", 'dame' ),
				'url'    => admin_url( 'edit-tags.php?taxonomy=dame_saison_adhesion&post_type=adherent' ),
				'active' => $is_season,
			),
			'dame_ical_feed'       => array(
				'title'  => __( "Flux d'agenda", 'dame' ),
				'url'    => admin_url( 'edit.php?post_type=dame_ical_feed' ),
				'active' => $is_ical_feed,
			),
		);

		?>
		<div class="wrap dame-taxonomies-tabs-wrap" style="margin-bottom: 20px;">
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab ) : ?>
					<a href="<?php echo esc_url( $tab['url'] ); ?>" class="nav-tab <?php echo $tab['active'] ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}
}
