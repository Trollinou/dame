<?php
/**
 * Gutenberg Blocks & Block Bindings Manager.
 *
 * @package DAME\Blocks
 */

declare(strict_types=1);

namespace DAME\Blocks;

use DateTime;
use DAME\Shortcodes\Agenda as AgendaShortcode;
use DAME\Shortcodes\Newsletter as NewsletterShortcode;
use DAME\Shortcodes\Benevolat as BenevolatShortcode;
use DAME\Shortcodes\Contact as ContactShortcode;
use DAME\Shortcodes\RegistrationForm as RegistrationShortcode;

/**
 * Class Manager
 * Handles block registration, Block Bindings API sources, and FSE template hooks.
 */
class Manager {

	/**
	 * Initialize block hooks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'init', array( $this, 'register_block_bindings' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Registers the custom DAME block category in the Gutenberg inserter.
	 *
	 * @param array<int, array<string, string>> $categories Existing block categories.
	 * @return array<int, array<string, string>> Filtered categories with DAME prepended.
	 */
	public function register_block_category( array $categories ): array {
		return array_merge(
			array(
				array(
					'slug'  => 'dame',
					'title' => __( 'DAME — Gestion Club Échecs', 'dame' ),
					'icon'  => 'groups',
				),
			),
			$categories
		);
	}

	/**
	 * Registers Gutenberg blocks using block.json declarations.
	 */
	public function register_blocks(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$blocks = array(
			'agenda'       => array( $this, 'render_agenda_block' ),
			'newsletter'   => array( $this, 'render_newsletter_block' ),
			'benevolat'    => array( $this, 'render_benevolat_block' ),
			'contact'      => array( $this, 'render_contact_block' ),
			'registration' => array( $this, 'render_registration_block' ),
		);

		foreach ( $blocks as $slug => $render_callback ) {
			$block_path = \DAME_PLUGIN_DIR . 'blocks/' . $slug;
			if ( file_exists( $block_path . '/block.json' ) ) {
				register_block_type(
					$block_path,
					array(
						'render_callback' => $render_callback,
					)
				);
			}
		}
	}

	/**
	 * Registers custom Block Bindings sources (WordPress 6.5+ / 7.x).
	 */
	public function register_block_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		// 1. Source Agenda : dame/agenda-data
		register_block_bindings_source(
			'dame/agenda-data',
			array(
				'label'              => __( 'Données Agenda DAME', 'dame' ),
				'get_value_callback' => array( $this, 'get_agenda_binding_value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);

		// 2. Source Adhérent : dame/adherent-data
		register_block_bindings_source(
			'dame/adherent-data',
			array(
				'label'              => __( 'Données Adhérent DAME', 'dame' ),
				'get_value_callback' => array( $this, 'get_adherent_binding_value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	/**
	 * Resolves dynamic values for the dame/agenda-data block binding source.
	 *
	 * @param array<string, mixed> $source_args Arguments defined on the binding.
	 * @param \WP_Block             $block_instance Current block instance.
	 * @return string|null Formatted string or null.
	 */
	public function get_agenda_binding_value( array $source_args, $block_instance ): ?string {
		$post_id = $block_instance->context['postId'] ?? get_the_ID();
		if ( ! $post_id || 'dame_agenda' !== get_post_type( $post_id ) ) {
			return null;
		}

		$key = $source_args['key'] ?? '';

		switch ( $key ) {
			case 'formatted_date':
				$start = (string) get_post_meta( $post_id, '_dame_start_date', true );
				$end   = (string) get_post_meta( $post_id, '_dame_end_date', true );
				if ( empty( $start ) ) {
					return '';
				}
				$start_dt = new DateTime( $start );
				if ( $start === $end || empty( $end ) ) {
					return date_i18n( get_option( 'date_format' ), $start_dt->getTimestamp() );
				}
				$end_dt = new DateTime( $end );
				return sprintf(
					/* translators: 1: start date, 2: end date */
					__( 'Du %1$s au %2$s', 'dame' ),
					date_i18n( get_option( 'date_format' ), $start_dt->getTimestamp() ),
					date_i18n( get_option( 'date_format' ), $end_dt->getTimestamp() )
				);

			case 'time_range':
				$all_day = get_post_meta( $post_id, '_dame_all_day', true );
				if ( $all_day ) {
					return __( 'Toute la journée', 'dame' );
				}
				$start_time = (string) get_post_meta( $post_id, '_dame_start_time', true );
				$end_time   = (string) get_post_meta( $post_id, '_dame_end_time', true );
				return $start_time . ( ! empty( $end_time ) ? ' - ' . $end_time : '' );

			case 'location':
				$name = (string) get_post_meta( $post_id, '_dame_location_name', true );
				$addr = (string) get_post_meta( $post_id, '_dame_location_address', true );
				return ! empty( $name ) ? $name . ( ! empty( $addr ) ? ' (' . $addr . ')' : '' ) : $addr;

			case 'event_url':
				return (string) get_post_meta( $post_id, '_dame_event_url', true );

			case 'registration_url':
				return (string) get_post_meta( $post_id, '_dame_registration_url', true );

			case 'contact_name':
				return (string) get_post_meta( $post_id, '_dame_contact_name', true );

			case 'competition_badge':
				$comp = (string) get_post_meta( $post_id, '_dame_competition_type', true );
				if ( ! $comp || 'non' === $comp ) {
					return '';
				}
				return 'individuelle' === $comp ? __( 'Compétition Individuelle', 'dame' ) : __( 'Compétition par Équipe', 'dame' );

			default:
				return (string) get_post_meta( $post_id, '_' . $key, true );
		}
	}

	/**
	 * Resolves dynamic values for the dame/adherent-data block binding source.
	 *
	 * @param array<string, mixed> $source_args Arguments defined on the binding.
	 * @param \WP_Block             $block_instance Current block instance.
	 * @return string|null Formatted string or null.
	 */
	public function get_adherent_binding_value( array $source_args, $block_instance ): ?string {
		$post_id = $block_instance->context['postId'] ?? get_the_ID();
		if ( ! $post_id || 'adherent' !== get_post_type( $post_id ) ) {
			return null;
		}

		$key = $source_args['key'] ?? '';

		switch ( $key ) {
			case 'full_name':
				$first = (string) get_post_meta( $post_id, '_dame_first_name', true );
				$last  = (string) get_post_meta( $post_id, '_dame_last_name', true );
				return trim( $first . ' ' . $last );

			case 'ffe_licence':
				return (string) get_post_meta( $post_id, '_dame_ffe_licence', true );

			case 'category':
				return (string) get_post_meta( $post_id, '_dame_category', true );

			case 'rating_elo':
				return (string) get_post_meta( $post_id, '_dame_rating_elo', true );

			default:
				return (string) get_post_meta( $post_id, '_' . $key, true );
		}
	}

	/**
	 * Render callback for the Agenda block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string HTML rendered output.
	 */
	public function render_agenda_block( array $attributes ): string {
		return ( new AgendaShortcode() )->render_agenda( $attributes );
	}

	/**
	 * Render callback for the Newsletter block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string HTML rendered output.
	 */
	public function render_newsletter_block( array $attributes ): string {
		return ( new NewsletterShortcode() )->render( $attributes );
	}

	/**
	 * Render callback for the Benevolat block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string HTML rendered output.
	 */
	public function render_benevolat_block( array $attributes ): string {
		return ( new BenevolatShortcode() )->render( $attributes );
	}

	/**
	 * Render callback for the Contact block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string HTML rendered output.
	 */
	public function render_contact_block( array $attributes ): string {
		return (string) ( new ContactShortcode() )->render( $attributes );
	}

	/**
	 * Render callback for the Registration block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string HTML rendered output.
	 */
	public function render_registration_block( array $attributes ): string {
		return ( new RegistrationShortcode() )->render( $attributes );
	}
}
