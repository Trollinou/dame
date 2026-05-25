<?php
/**
 * FFE Synchronization Batch Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use WP_Query;
use DOMDocument;
use DOMXPath;
use WP_Error;

/**
 * Class FFESyncBatch
 */
class FFESyncBatch {

	/**
	 * Base URL for the FFE website.
	 */
	private const BASE_URL = 'https://www.echecs.asso.fr';

	/**
	 * User agent for requests.
	 */
	private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

	/**
	 * Sync members data from FFE.
	 */
	public function run(): void {
		$options = get_option( 'dame_options' );
		$club_id = isset( $options['assoc_ffe_id'] ) ? (string) $options['assoc_ffe_id'] : '';

		if ( empty( $club_id ) ) {
			error_log( 'DAME FFESyncBatch: Club ID is not configured. Skipping sync.' );
			return;
		}

		error_log( sprintf( 'DAME FFESyncBatch: Starting sync for club ID %s', $club_id ) );

		$players = $this->scrape_players( $club_id );

		if ( empty( $players ) ) {
			error_log( 'DAME FFESyncBatch: No players found on FFE.' );
			return;
		}

		$this->update_members( $players );
	}

	/**
	 * Scrape players list from FFE.
	 *
	 * @param string $club_id Club ID.
	 * @return array<array<string, string>>
	 */
	private function scrape_players( string $club_id ): array {
		$url = self::BASE_URL . '/ListeJoueurs.aspx?Action=JOUEURCLUBREF&ClubRef=' . $club_id;
		$all_players = [];
		$page = 1;
		$current_url = $url;
		$post_data = [];

		while ( true ) {
			$response = $this->fetch_page( $current_url, $post_data );
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				break;
			}

			$html = wp_remote_retrieve_body( $response );
			$dom = new DOMDocument();
			@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
			$xpath = new DOMXPath( $dom );

			// Extract players from table
			$new_players = $this->parse_player_table( $xpath );
			$all_players = array_merge( $all_players, $new_players );

			error_log( sprintf( 'DAME FFESyncBatch: Scraped page %d (%d players found so far)', $page, count( $all_players ) ) );

			// Check for next page
			$next_page_info = $this->get_next_page_info( $xpath, $page );
			if ( ! $next_page_info ) {
				break;
			}

			// Prepare POST data for next page (ASP.NET __doPostBack)
			$post_data = $this->get_aspnet_fields( $xpath );
			$post_data['__EVENTTARGET'] = $next_page_info['target'];
			$post_data['__EVENTARGUMENT'] = $next_page_info['argument'];

			$page++;
			$current_url = $url; // Always POST to the same URL
		}

		return $all_players;
	}

	/**
	 * Fetch a page using WP Remote.
	 *
	 * @param string $url URL.
	 * @param array<string, string> $post_data Optional POST data.
	 * @return array<string, mixed>|WP_Error
	 */
	private function fetch_page( string $url, array $post_data = [] ): array|WP_Error {
		$args = [
			'timeout'    => 30,
			'user-agent' => self::USER_AGENT,
		];

		if ( ! empty( $post_data ) ) {
			$args['method'] = 'POST';
			$args['body']   = $post_data;
			$args['headers'] = [ 'Referer' => $url ];
		}

		return wp_remote_request( $url, $args );
	}

	/**
	 * Parse player table rows.
	 *
	 * @param DOMXPath $xpath XPath.
	 * @return array<array<string, string>>
	 */
	private function parse_player_table( DOMXPath $xpath ): array {
		$players = [];
		$rows = $xpath->query( "//div[contains(@class, 'page-mid')]//tr" );

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$cols = $xpath->query( "td", $row );
				if ( $cols->length >= 8 ) {
					$licence_ffe = trim( $cols->item(0)->textContent );
					$nom_complet = trim( $cols->item(1)->textContent );
					$licence_type = trim( $cols->item(2)->textContent );
					
					// Extraction id_ffe depuis le lien
					$link_node = $xpath->query( "td[4]/a", $row );
					$id_ffe = '';
					if ( $link_node->length > 0 ) {
						$href = $link_node->item(0)->getAttribute('href');
						if ( preg_match( '/Id=(.+)/', $href, $matches ) ) {
							$id_ffe = $matches[1];
						}
					}

					$elo_standard = trim( str_replace( "\xc2\xa0", ' ', $cols->item(4)->textContent ) );
					$elo_rapide   = trim( str_replace( "\xc2\xa0", ' ', $cols->item(5)->textContent ) );
					$elo_blitz    = trim( str_replace( "\xc2\xa0", ' ', $cols->item(6)->textContent ) );
					$categorie    = trim( $cols->item(7)->textContent );

					if ( ! empty( $id_ffe ) && ! empty( $nom_complet ) ) {
						$players[] = [
							'id_ffe'       => $id_ffe,
							'nom_complet'  => $nom_complet,
							'licence_num'  => $licence_ffe,
							'licence_type' => $licence_type,
							'categorie'    => $categorie,
							'elo_standard' => $elo_standard ?: '0',
							'elo_rapide'   => $elo_rapide ?: '0',
							'elo_blitz'    => $elo_blitz ?: '0',
						];
					}
				}
			}
		}

		return $players;
	}

	/**
	 * Get ASP.NET hidden fields.
	 *
	 * @param DOMXPath $xpath XPath.
	 * @return array<string, string>
	 */
	private function get_aspnet_fields( DOMXPath $xpath ): array {
		$fields = [];
		$inputs = $xpath->query( "//input[@type='hidden']" );
		if ( $inputs ) {
			foreach ( $inputs as $input ) {
				$name = $input->getAttribute('name');
				$value = $input->getAttribute('value');
				if ( $name ) {
					$fields[ $name ] = $value;
				}
			}
		}
		return $fields;
	}

	/**
	 * Get next page info from pager.
	 *
	 * @param DOMXPath $xpath XPath.
	 * @param int $current_page Current page number.
	 * @return array{target: string, argument: string}|null
	 */
	private function get_next_page_info( DOMXPath $xpath, int $current_page ): ?array {
		$next_page_num = (string) ( $current_page + 1 );
		$links = $xpath->query( "//div[contains(@class, 'pager')]//a" );

		if ( $links ) {
			foreach ( $links as $link ) {
				if ( trim( $link->textContent ) === $next_page_num ) {
					$href = $link->getAttribute('href');
					if ( preg_match( "/__doPostBack\('(.*?)','(.*?)'\)/", $href, $matches ) ) {
						return [ 'target' => $matches[1], 'argument' => $matches[2] ];
					}
				}
			}
		}

		return null;
	}

	/**
	 * Update members in WordPress.
	 *
	 * @param array<array<string, string>> $players Scraped players.
	 */
	private function update_members( array $players ): void {
		$active_adherents = $this->get_active_adherents();
		$members_by_license = [];
		$members_by_name    = [];

		foreach ( $active_adherents as $adherent ) {
			$license = get_post_meta( $adherent->ID, '_dame_license_number', true );
			$license_clean = strtoupper( str_replace( ' ', '', (string) $license ) );
			if ( ! empty( $license_clean ) ) {
				$members_by_license[ $license_clean ] = $adherent->ID;
			}
			$normalized_name = $this->normalize_name( $adherent->post_title );
			$members_by_name[ $normalized_name ] = $adherent->ID;
		}

		$updated_count = 0;
		$fide_lookups  = 0;
		$fide_limit    = 10;

		foreach ( $players as $player ) {
			$licence_clean = strtoupper( str_replace( ' ', '', $player['licence_num'] ) );
			$nom_normalized = $this->normalize_name( $player['nom_complet'] );

			$post_id = 0;
			if ( ! empty( $licence_clean ) && isset( $members_by_license[ $licence_clean ] ) ) {
				$post_id = $members_by_license[ $licence_clean ];
			} elseif ( ! empty( $nom_normalized ) && isset( $members_by_name[ $nom_normalized ] ) ) {
				$post_id = $members_by_name[ $nom_normalized ];
			}

			if ( $post_id ) {
				update_post_meta( $post_id, '_dame_license_number', $player['licence_num'] );
				update_post_meta( $post_id, '_dame_ffe_id', $player['id_ffe'] );
				update_post_meta( $post_id, '_dame_elo_standard', $player['elo_standard'] );
				update_post_meta( $post_id, '_dame_elo_rapide', $player['elo_rapide'] );
				update_post_meta( $post_id, '_dame_elo_blitz', $player['elo_blitz'] );

				// FIDE ID retrieval with safety limit
				$fide_id = get_post_meta( $post_id, '_dame_fide_id', true );
				if ( ( empty( $fide_id ) || 'NC' === $fide_id ) && $fide_lookups < $fide_limit ) {
					$fide_id = $this->fetch_fide_id( $player['id_ffe'] );
					if ( $fide_id ) {
						update_post_meta( $post_id, '_dame_fide_id', $fide_id );
					}
					$fide_lookups++;
				}

				$updated_count++;
			}
		}

		error_log( sprintf( 'DAME FFESyncBatch: Sync completed. %d adherents updated (%d FIDE lookups performed).', $updated_count, $fide_lookups ) );
	}

	/**
	 * Fetch FIDE ID from player profile page.
	 *
	 * @param string $id_ffe FFE Player ID.
	 * @return string|null
	 */
	private function fetch_fide_id( string $id_ffe ): ?string {
		$url = self::BASE_URL . '/FicheJoueur.aspx?Id=' . $id_ffe;
		$response = $this->fetch_page( $url );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$html = wp_remote_retrieve_body( $response );
		if ( preg_match( '/ratings\.fide\.com\/profile\/([0-9]+)/', $html, $matches ) ) {
			return str_pad( $matches[1], 8, '0', STR_PAD_LEFT );
		}

		return null;
	}

	/**
	 * Get all active adherents.
	 */
	private function get_active_adherents(): array {
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
		$args = [
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];
		if ( $current_season_tag_id ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => (int) $current_season_tag_id,
				],
			];
		}
		return get_posts( $args );
	}

	/**
	 * Normalize a name for matching.
	 */
	private function normalize_name( string $name ): string {
		$name = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $name );
		$name = strtolower( (string) $name );
		$name = preg_replace( '/[^a-z0-9 ]/', '', $name );
		$name = preg_replace( '/\s+/', '', trim( (string) $name ) );
		return (string) $name;
	}
}
