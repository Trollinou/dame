<?php
/**
 * Reconciliation Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\PreInscription;

use DAME\Services\Adherent_Matcher;
use DAME\Services\Data_Provider;
use DateTime;

/**
 * Class Reconciliation
 */
class Reconciliation {

	/**
	 * Initialize the metabox.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
	}

	/**
	 * Add the meta box.
	 */
	public function add_box() {
		$matched_id = Adherent_Matcher::find_match( get_the_ID() );
		if ( $matched_id ) {
			add_meta_box(
				'dame_pre_inscription_reconciliation',
				__( 'Rapprochement avec un adhérent existant', 'dame' ),
				[ $this, 'render' ],
				'dame_pre_inscription',
				'normal',
				'high',
				array( 'matched_id' => $matched_id )
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 * @param array    $metabox The metabox arguments.
	 */
	public function render( $post, $metabox ) {
		$pre_inscription_id = $post->ID;
		$matched_id         = $metabox['args']['matched_id'];

		// Define all possible fields to ensure a comprehensive comparison.
		$all_fields = array(
			'Informations Principales' => array(
				'Nom de naissance'    => 'dame_birth_name',
				'Nom d\'usage'        => 'dame_last_name',
				'Prénom'              => 'dame_first_name',
				'Sexe'                => 'dame_sexe',
				'Date de naissance'   => 'dame_birth_date',
				'Lieu de naissance'   => 'dame_birth_city',
				'Numéro de téléphone' => 'dame_phone_number',
				'Email'               => 'dame_email',
				'Profession'          => 'dame_profession',
				'Adresse'             => 'dame_address_1',
				'Complément'          => 'dame_address_2',
				'Code Postal'         => 'dame_postal_code',
				'Ville'               => 'dame_city',
				'Taille de vêtements' => 'dame_taille_vetements',
				'Type de licence'     => 'dame_license_type',
				'Document de santé'   => 'dame_health_document',
			),
			'Représentant Légal 1'     => array(
				'Rep. 1 - Nom de naissance'        => 'dame_legal_rep_1_last_name',
				'Rep. 1 - Prénom'                  => 'dame_legal_rep_1_first_name',
				'Rep. 1 - Date de naissance'       => 'dame_legal_rep_1_date_naissance',
				'Rep. 1 - Lieu de naissance'       => 'dame_legal_rep_1_commune_naissance',
				'Rep. 1 - Contrôle d\'honorabilité' => 'dame_legal_rep_1_honorabilite',
				'Rep. 1 - Numéro de téléphone'     => 'dame_legal_rep_1_phone',
				'Rep. 1 - Email'                   => 'dame_legal_rep_1_email',
				'Rep. 1 - Profession'              => 'dame_legal_rep_1_profession',
				'Rep. 1 - Adresse'                 => 'dame_legal_rep_1_address_1',
				'Rep. 1 - Complément'              => 'dame_legal_rep_1_address_2',
				'Rep. 1 - Code Postal'             => 'dame_legal_rep_1_postal_code',
				'Rep. 1 - Ville'                   => 'dame_legal_rep_1_city',
			),
			'Représentant Légal 2'     => array(
				'Rep. 2 - Nom de naissance'        => 'dame_legal_rep_2_last_name',
				'Rep. 2 - Prénom'                  => 'dame_legal_rep_2_first_name',
				'Rep. 2 - Date de naissance'       => 'dame_legal_rep_2_date_naissance',
				'Rep. 2 - Lieu de naissance'       => 'dame_legal_rep_2_commune_naissance',
				'Rep. 2 - Contrôle d\'honorabilité' => 'dame_legal_rep_2_honorabilite',
				'Rep. 2 - Numéro de téléphone'     => 'dame_legal_rep_2_phone',
				'Rep. 2 - Email'                   => 'dame_legal_rep_2_email',
				'Rep. 2 - Profession'              => 'dame_legal_rep_2_profession',
				'Rep. 2 - Adresse'                 => 'dame_legal_rep_2_address_1',
				'Rep. 2 - Complément'              => 'dame_legal_rep_2_address_2',
				'Rep. 2 - Code Postal'             => 'dame_legal_rep_2_postal_code',
				'Rep. 2 - Ville'                   => 'dame_legal_rep_2_city',
			),
		);
		?>
		<p><?php printf( __( 'Correspondance trouvée avec l\'adhérent <a href="%s" target="_blank">#%d</a>.', 'dame' ), esc_url( get_edit_post_link( $matched_id ) ), (int) $matched_id ); ?></p>
		<table class="wp-list-table widefat fixed striped dame-reconciliation-table">
			<thead>
				<tr>
					<th style="width: 25%;"><?php _e( 'Champ', 'dame' ); ?></th>
					<th style="width: 37.5%;"><?php _e( 'Donnée de la Préinscription', 'dame' ); ?></th>
					<th style="width: 37.5%;"><?php _e( 'Donnée de l\'Adhérent Existant', 'dame' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $all_fields as $group_label => $fields ) {
					$has_data_in_group = false;
					foreach ( $fields as $key_suffix ) {
						if ( ! empty( get_post_meta( $pre_inscription_id, '_' . $key_suffix, true ) ) ) {
							$has_data_in_group = true;
							break;
						}
					}

					if ( ! $has_data_in_group ) {
						continue;
					}

					?>
					<tr class="heading">
						<th colspan="3"><strong><?php echo esc_html( $group_label ); ?></strong></th>
					</tr>
					<?php
					foreach ( $fields as $label => $key_suffix ) {
						$pre_inscription_value = get_post_meta( $pre_inscription_id, '_' . $key_suffix, true );
						$adherent_value        = get_post_meta( $matched_id, '_' . $key_suffix, true );

						// Special display formatting for certain fields
						if ( 'dame_birth_date' === $key_suffix ) {
							if ( $pre_inscription_value ) {
								$date = DateTime::createFromFormat( 'Y-m-d', $pre_inscription_value );
								if ( $date ) {
									$pre_inscription_value = $date->format( 'd/m/Y' );
								}
							}
							if ( $adherent_value ) {
								$date = DateTime::createFromFormat( 'Y-m-d', $adherent_value );
								if ( $date ) {
									$adherent_value = $date->format( 'd/m/Y' );
								}
							}
						} elseif ( 'dame_health_document' === $key_suffix ) {
							$options = Data_Provider::get_health_document_options();
							$pre_inscription_value = isset( $options[ $pre_inscription_value ] ) ? $options[ $pre_inscription_value ] : $pre_inscription_value;
							$adherent_value        = isset( $options[ $adherent_value ] ) ? $options[ $adherent_value ] : $adherent_value;
						}


						// Show all fields from the pre-inscription, even if empty, to be comprehensive.
						$highlight_class = ( (string) $pre_inscription_value !== (string) $adherent_value ) ? 'dame-highlight-diff' : '';
						?>
						<tr class="<?php echo esc_attr( $highlight_class ); ?>">
							<td><strong><?php echo esc_html( $label ); ?></strong></td>
							<td><?php echo esc_html( $pre_inscription_value ); ?></td>
							<td><?php echo esc_html( $adherent_value ); ?></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}
}
