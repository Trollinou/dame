<?php
/**
 * Actions Metabox.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Metaboxes\PreInscription;

use DAME\Services\Adherent_Matcher;
use DAME\Services\Data_Provider;
use DAME\Services\Document_Storage;

/**
 * Class Actions
 */
class Actions {

	/**
	 * Initialize the metabox.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_box' ) );
		add_action( 'save_post', array( $this, 'save' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts for the metabox.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'dame_pre_inscription' !== $screen->post_type ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'dame-admin-pre-inscription-actions',
			\DAME_PLUGIN_URL . 'assets/js/admin-pre-inscription-actions.js',
			array(),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_localize_script(
			'dame-admin-pre-inscription-actions',
			'dame_pre_inscription_actions_data',
			array(
				'confirm_delete' => __( 'Êtes-vous sûr de vouloir supprimer définitivement cette préinscription ? Cette action est irréversible.', 'dame' ),
			)
		);
	}

	/**
	 * Add the meta box.
	 */
	public function add_box(): void {
		$matched_id = Adherent_Matcher::find_match( (int) get_the_ID() );
		add_meta_box(
			'dame_pre_inscription_actions',
			__( 'Actions de Validation', 'dame' ),
			array( $this, 'render' ),
			'dame_pre_inscription',
			'side',
			'high',
			array( 'matched_id' => $matched_id )
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post             $post The post object.
	 * @param array<string, mixed> $metabox The metabox arguments.
	 */
	public function render( $post, $metabox ): void {
		$matched_id = $metabox['args']['matched_id'];
		wp_nonce_field( 'dame_pre_inscription_process_action', 'dame_pre_inscription_action_nonce' );

		// HelloAsso Payment check.
		global $wpdb;
		$ha_table = $wpdb->prefix . 'dame_ha_items';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ha_payment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT amount_cents, payment_method, order_date, tier_name FROM %i WHERE matched_pre_inscription_id = %d AND status = 'Processed' ORDER BY id DESC LIMIT 1",
				$ha_table,
				$post->ID
			),
			ARRAY_A
		);
		?>
		<div class="dame-actions-wrapper">
			<?php if ( ! empty( $ha_payment ) ) : ?>
				<div style="background: #edfaef; border-left: 4px solid #46b450; padding: 10px; margin-bottom: 15px; border-radius: 3px;">
					<strong style="color: #2e7d32;">💳 <?php esc_html_e( 'Paiement HelloAsso Reçu !', 'dame' ); ?></strong><br>
					<span style="font-size: 13px;">
						<?php
						printf(
							/* translators: 1: montant en euros, 2: date */
							esc_html__( '%1$s € le %2$s', 'dame' ),
							esc_html( number_format( ( (int) $ha_payment['amount_cents'] ) / 100, 2, ',', ' ' ) ),
							esc_html( (string) $ha_payment['order_date'] )
						);
						?>
						<?php if ( ! empty( $ha_payment['tier_name'] ) ) : ?>
							<br><em>(<?php echo esc_html( (string) $ha_payment['tier_name'] ); ?>)</em>
						<?php endif; ?>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $matched_id ) : ?>
				<input type="hidden" name="dame_matched_adherent_id" value="<?php echo esc_attr( (string) $matched_id ); ?>" />
				<p><strong><span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php esc_html_e( 'Adhérent existant trouvé !', 'dame' ); ?></strong></p>
				<p>
					<button type="submit" name="dame_pre_inscription_action" value="validate_update" class="button button-primary button-large"><?php esc_html_e( "Mettre à jour l'adhérent", 'dame' ); ?></button>
				</p>
			<?php else : ?>
				<p><strong><?php esc_html_e( 'Valider cette préinscription ?', 'dame' ); ?></strong></p>
				<p>
					<button type="submit" name="dame_pre_inscription_action" value="validate_new" class="button button-primary button-large"><?php esc_html_e( 'Valider et Créer Adhérent', 'dame' ); ?></button>
				</p>
			<?php endif; ?>
			<hr>
			<p>
				<button type="submit" name="dame_pre_inscription_action" value="delete" class="button button-secondary button-large dame-delete-button" formnovalidate><?php esc_html_e( 'Supprimer la Préinscription', 'dame' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ): void {
		$action_nonce = isset( $_POST['dame_pre_inscription_action_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_pre_inscription_action_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $action_nonce, 'dame_pre_inscription_process_action' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'dame_pre_inscription' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['dame_pre_inscription_action'] ) ) {
			$action = sanitize_key( wp_unslash( $_POST['dame_pre_inscription_action'] ) );

			switch ( $action ) {
				case 'delete':
					// Document cleanup is handled automatically via before_delete_post in PreInscription CPT.
					wp_delete_post( $post_id, true );
					wp_safe_redirect( admin_url( 'edit.php?post_type=dame_pre_inscription&message=101' ) );
					exit;

				case 'validate_new':
				case 'validate_update':
					$pre_inscription_meta  = get_post_meta( $post_id );
					$adherent_meta         = array();
					$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
					$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';

					foreach ( $pre_inscription_meta as $key => $value ) {
						if ( strpos( $key, '_dame_' ) === 0 ) {
							$adherent_meta[ $key ] = maybe_unserialize( $value[0] );
						}
					}

					// Duplicate documents physically so adherent has its own files.
					$last_name_adherent  = isset( $adherent_meta['_dame_last_name'] ) ? sanitize_file_name( (string) $adherent_meta['_dame_last_name'] ) : 'adherent';
					$first_name_adherent = isset( $adherent_meta['_dame_first_name'] ) ? sanitize_file_name( (string) $adherent_meta['_dame_first_name'] ) : '';

					if ( ! empty( $adherent_meta['_dame_doc_health_attestation_path'] ) ) {
						$source_health = (string) $adherent_meta['_dame_doc_health_attestation_path'];
						$target_name   = 'attestation_sante_' . $last_name_adherent . '_' . $first_name_adherent . $season_suffix . '.pdf';
						$copied_health = Document_Storage::duplicate_file( $source_health, $target_name );
						if ( $copied_health ) {
							$adherent_meta['_dame_doc_health_attestation_path'] = $copied_health;
							if ( $season_suffix ) {
								$adherent_meta[ '_dame_doc_health_attestation_path' . $season_suffix ] = $copied_health;
							}
						}
					}

					if ( ! empty( $adherent_meta['_dame_doc_parental_auth_path'] ) ) {
						$source_parental = (string) $adherent_meta['_dame_doc_parental_auth_path'];
						$target_name     = 'attestation_parentale_' . $last_name_adherent . '_' . $first_name_adherent . $season_suffix . '.pdf';
						$copied_parental = Document_Storage::duplicate_file( $source_parental, $target_name );
						if ( $copied_parental ) {
							$adherent_meta['_dame_doc_parental_auth_path'] = $copied_parental;
							if ( $season_suffix ) {
								$adherent_meta[ '_dame_doc_parental_auth_path' . $season_suffix ] = $copied_parental;
							}
						}
					}

					if ( isset( $adherent_meta['_dame_signature_date'] ) && $season_suffix ) {
						$adherent_meta[ '_dame_signature_date' . $season_suffix ] = $adherent_meta['_dame_signature_date'];
					}

					// Transfer matched HelloAsso payment if found.
					global $wpdb;
					$ha_table = $wpdb->prefix . 'dame_ha_items';
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$ha_payment = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT amount_cents, payment_method, order_date FROM %i WHERE matched_pre_inscription_id = %d AND status = 'Processed' ORDER BY id DESC LIMIT 1",
							$ha_table,
							$post_id
						),
						ARRAY_A
					);
					if ( is_array( $ha_payment ) && ! empty( $ha_payment['amount_cents'] ) ) {
						$p_amount = (float) ( (int) $ha_payment['amount_cents'] / 100 );
						$p_date   = ! empty( $ha_payment['order_date'] ) ? substr( (string) $ha_payment['order_date'], 0, 10 ) : wp_date( 'Y-m-d' );
						$p_method = ! empty( $ha_payment['payment_method'] ) ? (string) $ha_payment['payment_method'] : 'HelloAsso';

						$adherent_meta['_dame_payment_amount'] = $p_amount;
						$adherent_meta['_dame_payment_date']   = $p_date;
						$adherent_meta['_dame_payment_method'] = $p_method;

						if ( $season_suffix ) {
							$adherent_meta[ '_dame_payment_amount' . $season_suffix ] = $p_amount;
							$adherent_meta[ '_dame_payment_date' . $season_suffix ]   = $p_date;
							$adherent_meta[ '_dame_payment_method' . $season_suffix ] = $p_method;
						}
					}

					// Recalculate department and region based on postal code.
					$matched_adherent_id = isset( $_POST['dame_matched_adherent_id'] ) ? absint( $_POST['dame_matched_adherent_id'] ) : 0;
					$postal_code         = isset( $adherent_meta['_dame_postal_code'] ) ? (string) $adherent_meta['_dame_postal_code'] : '';
					if ( empty( $postal_code ) && $matched_adherent_id > 0 ) {
						$postal_code = (string) get_post_meta( $matched_adherent_id, '_dame_postal_code', true );
					}

					if ( ! empty( $postal_code ) ) {
						$dept_code = Data_Provider::get_department_from_postal_code( $postal_code );
						if ( $dept_code ) {
							$adherent_meta['_dame_department'] = $dept_code;
							$region_code                       = Data_Provider::get_region_for_department( $dept_code );
							if ( $region_code ) {
								$adherent_meta['_dame_region'] = $region_code;
							}
						}
					} elseif ( ! empty( $adherent_meta['_dame_department'] ) && empty( $adherent_meta['_dame_region'] ) ) {
						$region_code = Data_Provider::get_region_for_department( (string) $adherent_meta['_dame_department'] );
						if ( $region_code ) {
							$adherent_meta['_dame_region'] = $region_code;
						}
					}

					$post_title       = get_the_title( $post_id );
					$adherent_id      = 0;
					$redirect_message = 0;

					if ( 'validate_new' === $action ) {
						$adherent_post_data = array(
							'post_title'  => $post_title,
							'post_type'   => 'adherent',
							'post_status' => 'publish',
							'meta_input'  => $adherent_meta,
						);
						$adherent_id        = wp_insert_post( $adherent_post_data, true );
						$redirect_message   = 6; // Post published.
					} else { // validate_update.
						$adherent_id = isset( $_POST['dame_matched_adherent_id'] ) ? absint( $_POST['dame_matched_adherent_id'] ) : 0;
						if ( ! $adherent_id ) {
							$adherent_id      = wp_insert_post(
								array(
									'post_title'  => $post_title,
									'post_type'   => 'adherent',
									'post_status' => 'publish',
									'meta_input'  => $adherent_meta,
								)
							);
							$redirect_message = 6;
						} else {
							wp_update_post(
								array(
									'ID'         => $adherent_id,
									'post_title' => $post_title,
								)
							);

							global $wpdb;
							$meta_insert_values       = array();
							$meta_insert_placeholders = array();

							foreach ( $adherent_meta as $key => $value ) {
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
								$wpdb->delete(
									$wpdb->postmeta,
									array(
										'post_id'  => $adherent_id,
										'meta_key' => $key,
									)
								);

								$meta_insert_values[]       = $adherent_id;
								$meta_insert_values[]       = $key;
								$meta_insert_values[]       = maybe_serialize( $value );
								$meta_insert_placeholders[] = '(%d, %s, %s)';
							}

							if ( ! empty( $meta_insert_placeholders ) ) {
								$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_insert_placeholders );
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
								$wpdb->query( $wpdb->prepare( $query, $meta_insert_values ) );
							}
							$redirect_message = 1; // Post updated.
						}
					}

					if ( is_wp_error( $adherent_id ) ) {
						return;
					}

					// Set adherent to 'Active' for the current season.
					if ( $current_season_tag_id ) {
						wp_add_object_terms( $adherent_id, $current_season_tag_id, 'dame_saison_adhesion' );
					}

					// Delete pre-inscription post (its original documents will be cleaned up, adherent keeps its copies).
					wp_delete_post( $post_id, true );

					// Redirect to the adherent's edit page.
					$redirect_url = get_edit_post_link( $adherent_id, 'raw' );
					if ( $redirect_url ) {
						wp_safe_redirect( add_query_arg( 'message', $redirect_message, $redirect_url ) );
						exit;
					}
			}
		}
	}
}
