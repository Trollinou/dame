<?php
/**
 * DAME Admin Menu class.
 *
 * @package DAME
 */

namespace DAME\Admin;

use DAME\Admin\Pages\UserAssignment;
use DAME\Admin\Pages\Backups;
use DAME\Admin\Settings\Main as SettingsMain;

class Menu {

	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menus' ], 10 );
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'reorder_menus' ] );
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

		// Note : Les sous-menus des CPT (Adhérents, Agenda, Sondages) et Taxonomies
		// s'ajouteront automatiquement ici grâce au 'show_in_menu' défini dans leurs classes.

		// 2. Sous-menu : Assignation des comptes
		add_submenu_page(
			'dame-admin',
			__( "Assignation des comptes", "dame" ),
			__( "Assigner les comptes", "dame" ),
			'manage_options',
			'dame-assignation',
			[ new UserAssignment(), 'render' ]
		);

		// 3. Sous-menu : Flux d'agenda (ICS)
		// Le CPT Flux iCalendar s'ajoute automatiquement sous Agenda si 'show_in_menu' = 'edit.php?post_type=dame_agenda'.
		// Mais les instructions demandent explicitement ce sous-menu ici (près de l'Agenda).
		// Puisque WordPress trie les sous-menus ajoutés via add_submenu_page à la fin (ou selon l'ordre d'appel),
		// ce menu sera dans le bloc DAME.
		// En fait, l'instruction demandait de modifier les CPT. Nous allons utiliser la vue liste du CPT.
		add_submenu_page(
			'dame-admin',
			__( "Flux d'agenda (ICS)", "dame" ),
			__( "Flux d'agenda", "dame" ),
			'manage_options',
			'edit.php?post_type=dame_ical_feed',
			'__return_false'
		);

		// 4. Sous-menu : Messages (Extraction)
		// We add the Message list as a sub-menu.
		add_submenu_page(
			'dame-admin',
			__( "Historique des Messages", "dame" ),
			__( "Messages", "dame" ),
			'edit_dame_messages',
			'edit.php?post_type=dame_message',
			'__return_false'
		);

		// 5. Sous-menu : Sauvegardes
		add_submenu_page(
			'dame-admin',
			__( "Sauvegardes et Import", "dame" ),
			__( "Sauvegardes", "dame" ),
			'manage_options',
			'dame-backups',
			[ new Backups(), 'render' ]
		);

		// 6. Sous-menu : Réglages
		add_submenu_page(
			'dame-admin',
			__( "Réglages DAME", "dame" ),
			__( "Réglages", "dame" ),
			'manage_options',
			'dame-settings',
			[ new SettingsMain(), 'render_page' ]
		);
	}

	public function reorder_menus( $menu_order ) {
		global $submenu;

		if ( isset( $submenu['dame-admin'] ) ) {
			$dame_submenu = $submenu['dame-admin'];

			// Define desired order of slugs
			$desired_order = [
				'dame-admin', // Dashboard
				'edit.php?post_type=adherent',
				'dame-assignation',
				'edit.php?post_type=dame_agenda',
				'edit.php?post_type=dame_ical_feed',
				'edit.php?post_type=sondage',
				'edit.php?post_type=dame_message',
				'dame-backups',
				'dame-settings',
			];

			$reordered = [];

			// Extract known items in desired order
			foreach ( $desired_order as $slug ) {
				foreach ( $dame_submenu as $key => $item ) {
					if ( $item[2] === $slug ) {
						$reordered[] = $item;
						unset( $dame_submenu[$key] );
					}
				}
			}

			// Append any remaining items
			foreach ( $dame_submenu as $item ) {
				$reordered[] = $item;
			}

			// Assign back
			$submenu['dame-admin'] = $reordered;
		}

		return $menu_order;
	}

	public function render_dashboard() {
		echo '<div class="wrap"><h1>' . esc_html__( "Tableau de Bord DAME", "dame" ) . '</h1><p>' . esc_html__( "Bienvenue dans l'espace de gestion de votre club.", "dame" ) . '</p></div>';
	}
}
