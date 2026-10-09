<?php
/**
 * Saisons Tab.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Saisons
 */
class Saisons {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Saisons', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Registers actions/hooks logic.
		// If called during admin_init (via Main::register_settings), execute directly.
		if ( doing_action( 'admin_init' ) ) {
			$this->handle_actions();
		} else {
			add_action( 'admin_init', array( $this, 'handle_actions' ) );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin settings tab check.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		if ( false === strpos( $hook, 'dame-settings' ) || 'saisons' !== $tab ) {
			return;
		}

		wp_enqueue_script( 'dame-admin-saisons', \DAME_PLUGIN_URL . 'assets/js/admin-saisons.js', array(), \DAME_VERSION, true );
		wp_localize_script(
			'dame-admin-saisons',
			'dame_saisons_data',
			array(
				'confirm_reset' => __( 'Êtes-vous sûr de vouloir initialiser la nouvelle saison ? Cela créera un nouveau tag et le définira comme saison active.', 'dame' ),
			)
		);
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		// Custom UI for Seasons.
		$this->render_ui();
	}

	/**
	 * Sanitize options.
	 *
	 * @param array<string, mixed> $input New input.
	 * @param array<string, mixed> $existing_options Existing options.
	 * @return array<string, mixed> Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		// Saisons tab doesn't use standard settings API submission.
		return $existing_options;
	}

	/**
	 * Helper to get next season name.
	 *
	 * @return string
	 */
	private function get_next_season_name() {
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

		if ( $current_season_tag_id ) {
			$current_season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
			if ( $current_season_term && ! is_wp_error( $current_season_term ) ) {
				if ( preg_match( '/(\d{4})\/(\d{4})/', $current_season_term->name, $matches ) ) {
					$end_year               = (int) $matches[2];
					$next_season_start_year = $end_year;
					$next_season_end_year   = $next_season_start_year + 1;
					return sprintf( 'Saison %d/%d', $next_season_start_year, $next_season_end_year );
				}
			}
		}

		$current_month     = (int) wp_date( 'n' );
		$current_year      = (int) wp_date( 'Y' );
		$season_start_year = ( $current_month >= 9 ) ? $current_year + 1 : $current_year;
		$season_end_year   = $season_start_year + 1;

		return sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );
	}

	/**
	 * Handle actions.
	 */
	public function handle_actions(): void {
		$nonce = isset( $_POST['dame_season_management_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_season_management_nonce_field'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'dame_season_management_nonce' ) ) {
			$action = isset( $_POST['dame_action'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_action'] ) ) : '';
			if ( 'annual_reset' === $action ) {
				$new_season_name = $this->get_next_season_name();

				if ( term_exists( $new_season_name, 'dame_saison_adhesion' ) ) {
					add_action(
						'admin_notices',
						function () use ( $new_season_name ) {
							echo '<div class="error"><p>' . sprintf(
								/* translators: %s: Nom de la saison */
								esc_html__( 'L\'opération ne peut être effectuée car la saison "%s" a déjà été créée.', 'dame' ),
								esc_html( $new_season_name )
							) . '</p></div>';
						}
					);
					return;
				}

				$new_season_term = wp_insert_term( $new_season_name, 'dame_saison_adhesion' );

				if ( is_wp_error( $new_season_term ) ) {
					add_action(
						'admin_notices',
						function () use ( $new_season_term ) {
							echo '<div class="error"><p>' . sprintf(
								/* translators: %s: Message d'erreur */
								esc_html__( 'Erreur lors de la création de la saison : %s', 'dame' ),
								esc_html( $new_season_term->get_error_message() )
							) . '</p></div>';
						}
					);
					return;
				}

				update_option( 'dame_current_season_tag_id', $new_season_term['term_id'] );
				add_action(
					'admin_notices',
					function () use ( $new_season_name ) {
						echo '<div class="updated"><p>' . sprintf(
							/* translators: %s: Nom de la saison */
							esc_html__( 'Nouvelle saison initialisée avec succès. La saison active est maintenant : %s', 'dame' ),
							'<strong>' . esc_html( $new_season_name ) . '</strong>'
						) . '</p></div>';
					}
				);
			}

			if ( 'update_current_season' === $action ) {
				if ( isset( $_POST['dame_current_season_selector'] ) ) {
					$selected_season_id = (int) $_POST['dame_current_season_selector'];
					$term               = get_term( $selected_season_id, 'dame_saison_adhesion' );

					if ( $term && ! is_wp_error( $term ) ) {
						update_option( 'dame_current_season_tag_id', $selected_season_id );
						add_action(
							'admin_notices',
							function () use ( $term ) {
								echo '<div class="updated"><p>' . sprintf(
									/* translators: %s: Nom de la saison */
									esc_html__( 'La saison active a été mise à jour : %s', 'dame' ),
									'<strong>' . esc_html( $term->name ) . '</strong>'
								) . '</p></div>';
							}
						);
					}
				}
			}

			if ( 'update_season_pricing' === $action && isset( $_POST['dame_save_pricing'] ) ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				$pricing_season_id = isset( $_POST['dame_pricing_season_id'] ) ? absint( $_POST['dame_pricing_season_id'] ) : 0;
				if ( $pricing_season_id > 0 ) {
					$pricing = array(
						'price_licence_a'       => isset( $_POST['dame_price_licence_a'] ) ? (float) $_POST['dame_price_licence_a'] : 140.0,
						'price_licence_b'       => isset( $_POST['dame_price_licence_b'] ) ? (float) $_POST['dame_price_licence_b'] : 70.0,
						'discount_female_a'     => isset( $_POST['dame_discount_female_a'] ) ? (float) $_POST['dame_discount_female_a'] : 10.0,
						'surcharge_first_reg_a' => isset( $_POST['dame_surcharge_first_reg_a'] ) ? (float) $_POST['dame_surcharge_first_reg_a'] : 30.0,
					);
					\DAME\Services\Data_Provider::save_season_pricing( $pricing_season_id, $pricing );
					$term        = get_term( $pricing_season_id, 'dame_saison_adhesion' );
					$season_name = ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
					add_action(
						'admin_notices',
						function () use ( $season_name ) {
							echo '<div class="updated"><p>' . sprintf(
								/* translators: %s: Nom de la saison */
								esc_html__( 'Les tarifs pour la %s ont été mis à jour avec succès.', 'dame' ),
								'<strong>' . esc_html( $season_name ) . '</strong>'
							) . '</p></div>';
						}
					);
				}
			}
		}
	}

	/**
	 * Render UI.
	 */
	public function render_ui(): void {
		$seasons = get_terms(
			array(
				'taxonomy'   => 'dame_saison_adhesion',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'DESC',
			)
		);

		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
		?>
		<div>
			<div>
				<h3><?php esc_html_e( 'Saison Active', 'dame' ); ?></h3>
				<p><?php esc_html_e( "Sélectionnez la saison d'adhésion à utiliser comme saison active sur l'ensemble du site.", 'dame' ); ?></p>
				<form method="post">
					<input type="hidden" name="dame_action" value="update_current_season">
					<?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>

					<label for="dame_current_season_selector"><strong><?php esc_html_e( 'Saison active :', 'dame' ); ?></strong></label>
					<select id="dame_current_season_selector" name="dame_current_season_selector">
						<?php if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) : ?>
							<?php foreach ( $seasons as $season ) : ?>
								<option value="<?php echo esc_attr( (string) $season->term_id ); ?>" <?php selected( $season->term_id, $current_season_tag_id ); ?>>
									<?php echo esc_html( $season->name ); ?>
								</option>
							<?php endforeach; ?>
						<?php else : ?>
							<option value=""><?php esc_html_e( 'Aucune saison trouvée', 'dame' ); ?></option>
						<?php endif; ?>
					</select>
					<?php submit_button( __( 'Changer la saison active', 'dame' ), 'secondary', 'dame_update_season', false ); ?>
				</form>
			</div>

			<hr class="wp-header-end">

			<div>
				<h3><?php esc_html_e( 'Nouvelle Saison', 'dame' ); ?></h3>
				<p><?php esc_html_e( 'Cette action prépare le système pour la prochaine saison d\'adhésion en créant le nouveau tag.', 'dame' ); ?></p>
				<?php
				$next_season_name = $this->get_next_season_name();
				$disabled         = term_exists( $next_season_name, 'dame_saison_adhesion' ) ? 'disabled' : '';
				?>
				<form method="post">
					<input type="hidden" name="dame_action" value="annual_reset" />
					<?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>
					<?php submit_button( __( 'Initialiser la nouvelle saison', 'dame' ), 'primary', 'dame_annual_reset', false, $disabled ); ?>
					<p class="description">
						<?php
						if ( $disabled ) {
							printf(
								/* translators: %s: Nom de la saison */
								esc_html__( 'La saison "%s" a déjà été créée.', 'dame' ),
								esc_html( $next_season_name )
							);
						} else {
							printf(
								/* translators: %s: Nom de la saison */
								esc_html__( 'Cette action créera et activera la saison "%s".', 'dame' ),
								esc_html( $next_season_name )
							);
						}
						?>
					</p>
				</form>
			</div>

			<hr class="wp-header-end">

			<div>
				<h3><?php esc_html_e( 'Tarifs des cotisations par saison', 'dame' ); ?></h3>
				<p><?php esc_html_e( 'Configurez les tarifs d\'adhésion et les règles d\'ajustement pour chaque saison. Ces montants sont utilisés pour le calcul automatique de la cotisation et la génération des attestations de paiement.', 'dame' ); ?></p>

				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$pricing_season_id = isset( $_GET['pricing_season_id'] ) ? absint( $_GET['pricing_season_id'] ) : (int) $current_season_tag_id;
				if ( ! $pricing_season_id && ! empty( $seasons ) && ! is_wp_error( $seasons ) ) {
					$pricing_season_id = (int) $seasons[0]->term_id;
				}

				$current_pricing = \DAME\Services\Data_Provider::get_season_pricing( $pricing_season_id );
				?>

				<div style="margin-bottom: 15px;">
					<label for="dame_pricing_season_id"><strong><?php esc_html_e( 'Sélectionner la saison à configurer :', 'dame' ); ?></strong></label><br>
					<select id="dame_pricing_season_id" style="min-width: 250px; margin-top: 5px;" onchange="window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=dame-settings&tab=saisons' ) ); ?>&pricing_season_id=' + this.value;">
						<?php if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) : ?>
							<?php foreach ( $seasons as $season ) : ?>
								<option value="<?php echo esc_attr( (string) $season->term_id ); ?>" <?php selected( $season->term_id, $pricing_season_id ); ?>>
									<?php echo esc_html( $season->name ); ?><?php echo ( (int) $season->term_id === (int) $current_season_tag_id ) ? ' ' . esc_html__( '(Saison active)', 'dame' ) : ''; ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<span class="description" style="margin-left: 10px;"><?php esc_html_e( 'Change la saison visualisée et charge ses tarifs.', 'dame' ); ?></span>
				</div>

				<form method="post" style="max-width: 750px; background: #fff; border: 1px solid #ccd0d4; padding: 15px 20px; border-radius: 4px;">
					<input type="hidden" name="dame_action" value="update_season_pricing" />
					<input type="hidden" name="dame_pricing_season_id" value="<?php echo esc_attr( (string) $pricing_season_id ); ?>" />
					<?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>

					<table class="form-table" style="margin-top: 0;">
						<tr>
							<th scope="row"><label for="dame_price_licence_a"><?php esc_html_e( 'Tarif de base Licence A (€)', 'dame' ); ?></label></th>
							<td>
								<input type="number" step="0.5" min="0" id="dame_price_licence_a" name="dame_price_licence_a" value="<?php echo esc_attr( (string) $current_pricing['price_licence_a'] ); ?>" class="small-text" required="required" />
								<p class="description"><?php esc_html_e( 'Cours + Compétition (tarif plein adulte homme en renouvellement).', 'dame' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="dame_price_licence_b"><?php esc_html_e( 'Tarif de base Licence B (€)', 'dame' ); ?></label></th>
							<td>
								<input type="number" step="0.5" min="0" id="dame_price_licence_b" name="dame_price_licence_b" value="<?php echo esc_attr( (string) $current_pricing['price_licence_b'] ); ?>" class="small-text" required="required" />
								<p class="description"><?php esc_html_e( 'Jeu libre (tarif fixe appliqué pour tous en Licence B).', 'dame' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="dame_discount_female_a"><?php esc_html_e( 'Remise féminine sur Licence A (€)', 'dame' ); ?></label></th>
							<td>
								<input type="number" step="0.5" min="0" id="dame_discount_female_a" name="dame_discount_female_a" value="<?php echo esc_attr( (string) $current_pricing['discount_female_a'] ); ?>" class="small-text" required="required" />
								<p class="description"><?php esc_html_e( 'Montant déduit de la cotisation Licence A pour les féminines (ex: 10 €).', 'dame' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="dame_surcharge_first_reg_a"><?php esc_html_e( 'Surcoût 1ère adhésion sur Licence A (€)', 'dame' ); ?></label></th>
							<td>
								<input type="number" step="0.5" min="0" id="dame_surcharge_first_reg_a" name="dame_surcharge_first_reg_a" value="<?php echo esc_attr( (string) $current_pricing['surcharge_first_reg_a'] ); ?>" class="small-text" required="required" />
								<p class="description"><?php esc_html_e( 'Montant ajouté à la 1ère adhésion Licence A pour financer le polo floqué (ex: 30 €).', 'dame' ); ?></p>
							</td>
						</tr>
					</table>

					<div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 10px 15px; margin: 15px 0;">
						<p style="margin: 0;"><strong><?php esc_html_e( 'Exemples calculés pour cette saison :', 'dame' ); ?></strong></p>
						<ul style="margin: 5px 0 0 20px; list-style-type: disc;">
							<li><?php esc_html_e( 'Licence A — Renouvellement Homme :', 'dame' ); ?> <strong><?php echo esc_html( number_format( $current_pricing['price_licence_a'], 2, ',', ' ' ) ); ?> €</strong></li>
							<li><?php esc_html_e( 'Licence A — Renouvellement Femme :', 'dame' ); ?> <strong><?php echo esc_html( number_format( max( 0.0, $current_pricing['price_licence_a'] - $current_pricing['discount_female_a'] ), 2, ',', ' ' ) ); ?> €</strong></li>
							<li><?php esc_html_e( 'Licence A — 1ère adhésion Homme (avec polo) :', 'dame' ); ?> <strong><?php echo esc_html( number_format( $current_pricing['price_licence_a'] + $current_pricing['surcharge_first_reg_a'], 2, ',', ' ' ) ); ?> €</strong></li>
							<li><?php esc_html_e( 'Licence A — 1ère adhésion Femme (avec polo) :', 'dame' ); ?> <strong><?php echo esc_html( number_format( max( 0.0, $current_pricing['price_licence_a'] - $current_pricing['discount_female_a'] + $current_pricing['surcharge_first_reg_a'] ), 2, ',', ' ' ) ); ?> €</strong></li>
							<li><?php esc_html_e( 'Licence B — Tout public :', 'dame' ); ?> <strong><?php echo esc_html( number_format( $current_pricing['price_licence_b'], 2, ',', ' ' ) ); ?> €</strong></li>
						</ul>
					</div>

					<?php submit_button( __( 'Enregistrer les tarifs de cette saison', 'dame' ), 'primary', 'dame_save_pricing', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
