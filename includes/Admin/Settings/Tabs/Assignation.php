<?php
/**
 * Assignation Tab for Settings.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

use WP_Query;

/**
 * Class Assignation
 */
class Assignation {

	/**
	 * Get the label for the tab.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( "Assignation des comptes", "dame" );
	}

	/**
	 * Register actions and settings.
	 */
	public function register() {
		add_action( 'admin_post_dame_assign_user', array( $this, 'handle_assignment' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		// Get all linked user IDs first.
		global $wpdb;
		$linked_user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
				'_dame_linked_wp_user'
			)
		);
		$linked_user_ids = array_map( 'intval', $linked_user_ids );

		// Get members who are not yet linked.
		$unlinked_members_args = array(
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_dame_linked_wp_user',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_dame_linked_wp_user',
					'value'   => '',
					'compare' => '=',
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		$unlinked_members      = new WP_Query( $unlinked_members_args );

		// Get WP Users who are not yet linked.
		$unlinked_users_args = array(
			'exclude' => $linked_user_ids,
			'fields'  => array( 'ID', 'display_name' ),
		);
		$unlinked_users      = get_users( $unlinked_users_args );

		// Get all editable roles.
		global $wp_roles;
		$roles = $wp_roles->get_names();

		?>
		<h2><?php esc_html_e( "Assignation des comptes WordPress", "dame" ); ?></h2>
		<p><?php esc_html_e( "Cette page vous permet d'assigner rapidement un compte WordPress et un rôle à un adhérent.", "dame" ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="dame_assign_user">
			<?php wp_nonce_field( 'dame_user_assignment_action', 'dame_user_assignment_nonce' ); ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" id="member" class="manage-column"><?php esc_html_e( "Adhérent", "dame" ); ?></th>
						<th scope="col" id="user" class="manage-column"><?php esc_html_e( "Compte WordPress", "dame" ); ?></th>
						<th scope="col" id="role" class="manage-column"><?php esc_html_e( "Rôle", "dame" ); ?></th>
						<th scope="col" id="action" class="manage-column"><?php esc_html_e( "Action", "dame" ); ?></th>
					</tr>
				</thead>
				<tbody id="the-list">
					<?php if ( $unlinked_members->have_posts() ) : ?>
						<?php while ( $unlinked_members->have_posts() ) : ?>
							<?php $unlinked_members->the_post(); ?>
							<tr>
								<td><strong><?php the_title(); ?></strong></td>
								<td>
									<?php if ( ! empty( $unlinked_users ) ) : ?>
										<select name="user_id[<?php echo esc_attr( get_the_ID() ); ?>]">
											<option value=""><?php esc_html_e( "Sélectionner un utilisateur", "dame" ); ?></option>
											<?php foreach ( $unlinked_users as $user ) : ?>
												<option value="<?php echo esc_attr( $user->ID ); ?>">
													<?php echo esc_html( $user->display_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php else : ?>
										<?php esc_html_e( "Aucun utilisateur non assigné disponible", "dame" ); ?>
									<?php endif; ?>
								</td>
								<td>
									<select name="role[<?php echo esc_attr( get_the_ID() ); ?>]">
										<?php foreach ( $roles as $role_value => $role_name ) : ?>
											<option value="<?php echo esc_attr( $role_value ); ?>" <?php selected( $role_value, 'membre' ); ?>>
												<?php echo esc_html( $role_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<button type="submit" name="assign_user" value="<?php echo esc_attr( get_the_ID() ); ?>" class="button button-primary">
										<?php esc_html_e( "Assigner", "dame" ); ?>
									</button>
								</td>
							</tr>
						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					<?php else : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( "Tous les adhérents ont déjà un compte WordPress assigné.", "dame" ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</form>
		<?php
	}

	/**
	 * Handle the assignment form submission.
	 */
	public function handle_assignment() {
		if ( ! isset( $_POST['dame_user_assignment_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dame_user_assignment_nonce'] ) ), 'dame_user_assignment_action' ) ) {
			wp_die( esc_html__( "La vérification de sécurité a échoué.", "dame" ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
		}

		if ( ! isset( $_POST['assign_user'] ) ) {
			return;
		}

		$member_id = absint( $_POST['assign_user'] );
		$user_id   = isset( $_POST['user_id'][ $member_id ] ) ? absint( $_POST['user_id'][ $member_id ] ) : 0;
		$role      = isset( $_POST['role'][ $member_id ] ) ? sanitize_text_field( $_POST['role'][ $member_id ] ) : '';

		if ( ! $member_id || ! get_post( $member_id ) ) {
			return;
		}

		if ( ! $user_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dame-settings&tab=assignation&message=missing_user' ) );
			exit();
		}

		// Check if user exists.
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dame-settings&tab=assignation&message=invalid_user' ) );
			exit();
		}

		// Check if role is valid.
		global $wp_roles;
		if ( ! in_array( $role, array_keys( $wp_roles->get_names() ), true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dame-settings&tab=assignation&message=invalid_role' ) );
			exit();
		}

		// Update member meta and user role.
		update_post_meta( $member_id, '_dame_linked_wp_user', $user_id );
		$user->set_role( $role );

		// Redirect to the same page with success message.
		wp_safe_redirect( admin_url( 'admin.php?page=dame-settings&tab=assignation&message=success' ) );
		exit();
	}

	/**
	 * Display admin notices.
	 */
	public function display_notices() {
		if ( ! isset( $_GET['page'] ) || 'dame-settings' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['tab'] ) || 'assignation' !== $_GET['tab'] ) {
			return;
		}

		if ( isset( $_GET['message'] ) ) {
			if ( 'success' === $_GET['message'] ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( "Compte assigné avec succès.", "dame" ); ?></p>
				</div>
				<?php
			} elseif ( 'missing_user' === $_GET['message'] ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p><?php esc_html_e( "Veuillez sélectionner un compte WordPress à assigner.", "dame" ); ?></p>
				</div>
				<?php
			} elseif ( 'invalid_user' === $_GET['message'] ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( "Le compte WordPress sélectionné n'existe pas.", "dame" ); ?></p>
				</div>
				<?php
			} elseif ( 'invalid_role' === $_GET['message'] ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( "Le rôle sélectionné est invalide.", "dame" ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Return existing options for this tab.
	 *
	 * @param array $input Input options.
	 * @param array $existing Existing options.
	 * @return array
	 */
	public function sanitize( $input, $existing ) {
		return $existing;
	}
}
