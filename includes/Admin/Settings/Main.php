<?php
/**
 * Main Settings Controller.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings;

use DAME\Admin\Settings\Tabs\Association;
use DAME\Admin\Settings\Tabs\Saisons;
use DAME\Admin\Settings\Tabs\Anniversaires;
use DAME\Admin\Settings\Tabs\Paiements;
use DAME\Admin\Settings\Tabs\Sauvegarde;
use DAME\Admin\Settings\Tabs\Emails;
use DAME\Admin\Settings\Tabs\Desinstallation;

/**
 * Class Main
 */
class Main {

	/**
	 * Array of tab instances.
	 *
	 * @var array
	 */
	private $tabs = [];

	/**
	 * Initialize the settings page.
	 */
	public function init() {
		// Instantiate Tabs
		$this->tabs['association']    = new Association();
		$this->tabs['saisons']        = new Saisons();
		$this->tabs['anniversaires']  = new Anniversaires();
		$this->tabs['paiements']      = new Paiements();
		$this->tabs['sauvegarde']     = new Sauvegarde();
		$this->tabs['emails']         = new Emails();
		$this->tabs['desinstallation'] = new Desinstallation();

		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings for all tabs.
	 */
	public function register_settings() {
		// Register the main option group once.
		register_setting( 'dame_options_group', 'dame_options', [ $this, 'sanitize_options' ] );

		// Let each tab register its sections and fields.
		foreach ( $this->tabs as $tab ) {
			if ( method_exists( $tab, 'register' ) ) {
				$tab->register();
			}
		}
	}

	/**
	 * Add the options page.
	 */
	public function add_menu() {
		add_options_page(
			__( 'Options DAME', 'dame' ),
			__( 'Options DAME', 'dame' ),
			'manage_options',
			'dame-settings',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render the options page.
	 */
	public function render_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'association';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $slug => $tab_obj ) : ?>
					<a href="?page=dame-settings&tab=<?php echo esc_attr( $slug ); ?>" class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_obj->get_label() ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'dame_options_group' );

				echo '<input type="hidden" name="dame_active_tab" value="' . esc_attr( $active_tab ) . '" />';

				if ( isset( $this->tabs[ $active_tab ] ) ) {
					$this->tabs[ $active_tab ]->render();
				}

				// Saisons tab handles its own forms/actions
				if ( 'saisons' !== $active_tab ) {
					submit_button( __( 'Enregistrer les modifications', 'dame' ) );
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input The input array.
	 * @return array The sanitized array.
	 */
	public function sanitize_options( $input ) {
		$options = get_option( 'dame_options', [] );
		$active_tab = isset( $_POST['dame_active_tab'] ) ? sanitize_key( $_POST['dame_active_tab'] ) : '';

		if ( isset( $this->tabs[ $active_tab ] ) && method_exists( $this->tabs[ $active_tab ], 'sanitize' ) ) {
			$options = $this->tabs[ $active_tab ]->sanitize( $input, $options );
		}

		return $options;
	}
}
