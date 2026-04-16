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
			'dame-hidden', // <-- Remplace null pour compatibilité PHP 8.1
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

		// Define the strict exact order of URLs and their expected correct names
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

		// We will extract known items, rename them as specified, and push them to $reordered
		foreach ( $desired_order as $url => $new_title ) {
			$found = false;
			foreach ( $dame_submenu as $key => $item ) {
				// WP sometimes adds &amp; instead of & in URLs in the menu array, or vice versa.
				$item_url = str_replace( '&', '&amp;', $item[2] );
				$target_url = str_replace( '&', '&amp;', $url );

				if ( $item_url === $target_url || $item[2] === $url ) {
					// Rename the menu item
					$item[0] = $new_title;
					$reordered[] = $item;
					unset( $dame_submenu[$key] );
					$found = true;
					break; // Found it, move to next desired item
				}
			}

			// INJECTION FORCÉE : Si WordPress n'a pas inclus cet élément (ex: taxonomies), on le crée de force.
			if ( ! $found ) {
				// Définir la capacité requise ('manage_categories' pour les taxonomies, 'manage_options' par défaut)
				$cap = ( strpos( $url, 'edit-tags.php' ) !== false ) ? 'manage_categories' : 'manage_options';
				// On s'assure d'utiliser l'URL propre (sans &amp;) pour que le lien HTML fonctionne correctement
				$clean_url = str_replace( '&amp;', '&', $url );
				$reordered[] = [ $new_title, $cap, $clean_url ];
			}
		}

		// Append any remaining items that weren't in our strict list to the end
		foreach ( $dame_submenu as $item ) {
			$reordered[] = $item;
		}

		$submenu['dame-admin'] = $reordered;
	}

	public function render_dashboard() {
		echo '<div class="wrap"><h1>' . esc_html__( "Tableau de Bord DAME", "dame" ) . '</h1><p>' . esc_html__( "Bienvenue dans l'espace de gestion de votre club.", "dame" ) . '</p></div>';
	}

	/**
	 * Force le menu parent "DAME" à rester ouvert pour les taxonomies.
	 */
	public function highlight_parent_menu( $parent_file ) {
		global $current_screen;
		$dame_taxonomies = [ 'dame_saison_adhesion', 'dame_group', 'dame_agenda_category' ];

		if ( isset( $current_screen->taxonomy ) && in_array( $current_screen->taxonomy, $dame_taxonomies ) ) {
			return 'dame-admin';
		}

		return $parent_file;
	}

	/**
	 * Force la mise en surbrillance (gras) du bon sous-menu.
	 */
	public function highlight_submenu( $submenu_file ) {
		global $current_screen;

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

		return $submenu_file;
	}
}
