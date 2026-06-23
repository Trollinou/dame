<?php
/**
 * Register blocks for the plugin.
 *
 * @package DAME\Blocks
 */

declare(strict_types=1);

namespace DAME\Blocks;

use DAME\Shortcodes\Agenda as AgendaShortcode;
use DAME\Shortcodes\Benevolat as BenevolatShortcode;
use DAME\Shortcodes\RegistrationForm;
use DAME\Shortcodes\Contact as ContactShortcode;

/**
 * Class Register
 */
class Register {

	/**
	 * Agenda shortcode instance.
	 *
	 * @var AgendaShortcode
	 */
	private $agenda_shortcode;

	/**
	 * Benevolat shortcode instance.
	 *
	 * @var BenevolatShortcode
	 */
	private $benevolat_shortcode;

	/**
	 * RegistrationForm shortcode instance.
	 *
	 * @var RegistrationForm
	 */
	private $registration_form;

	/**
	 * Contact shortcode instance.
	 *
	 * @var ContactShortcode
	 */
	private $contact_shortcode;

	/**
	 * Constructor.
	 *
	 * @param AgendaShortcode    $agenda_shortcode    Agenda shortcode.
	 * @param BenevolatShortcode $benevolat_shortcode Benevolat shortcode.
	 * @param RegistrationForm   $registration_form   Registration form.
	 * @param ContactShortcode   $contact_shortcode   Contact shortcode.
	 */
	public function __construct(
		AgendaShortcode $agenda_shortcode,
		BenevolatShortcode $benevolat_shortcode,
		RegistrationForm $registration_form,
		ContactShortcode $contact_shortcode
	) {
		$this->agenda_shortcode    = $agenda_shortcode;
		$this->benevolat_shortcode = $benevolat_shortcode;
		$this->registration_form   = $registration_form;
		$this->contact_shortcode   = $contact_shortcode;
	}

	/**
	 * Initialize block registration.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Register the PHP-only blocks.
	 */
	public function register_blocks(): void {
		// Register Agenda Block
		register_block_type( 'dame/agenda', [
			'title'           => __( 'DAME Agenda', 'dame' ),
			'category'        => 'widgets',
			'icon'            => 'calendar-alt',
			'attributes'      => [],
			'render_callback' => function( $attributes ) {
				return $this->agenda_shortcode->render_agenda( $attributes );
			},
			'supports'        => [
				'autoRegister'  => true,
				'interactivity' => true,
			],
		] );

		// Register Benevolat Block
		register_block_type( 'dame/benevolat', [
			'title'           => __( 'DAME Bénévolat', 'dame' ),
			'category'        => 'widgets',
			'icon'            => 'groups',
			'attributes'      => [
				'slug' => [
					'type'    => 'string',
					'default' => '',
				],
			],
			'render_callback' => function( $attributes ) {
				return $this->benevolat_shortcode->render( $attributes );
			},
			'supports'        => [
				'autoRegister'  => true,
				'interactivity' => true,
			],
		] );

		// Register Registration Form Block
		register_block_type( 'dame/registration', [
			'title'           => __( 'DAME Inscription', 'dame' ),
			'category'        => 'widgets',
			'icon'            => 'id-alt',
			'attributes'      => [],
			'render_callback' => function( $attributes ) {
				return $this->registration_form->render( $attributes );
			},
			'supports'        => [
				'autoRegister' => true,
			],
		] );

		// Register Contact Block
		register_block_type( 'dame/contact', [
			'title'           => __( 'DAME Contact', 'dame' ),
			'category'        => 'widgets',
			'icon'            => 'email',
			'attributes'      => [],
			'render_callback' => function( $attributes ) {
				return $this->contact_shortcode->render( $attributes );
			},
			'supports'        => [
				'autoRegister' => true,
			],
		] );
	}
}
