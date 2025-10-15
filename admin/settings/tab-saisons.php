<?php
/**
 * Settings for the Saisons tab.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Determines the name of the next season based on the current active season.
 *
 * @return string The name of the next season (e.g., "Saison 2025/2026").
 */
function dame_get_next_season_name() {
    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );

    if ( $current_season_tag_id ) {
        $current_season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
        if ( $current_season_term && ! is_wp_error( $current_season_term ) ) {
            // Extract the years from the current season name, e.g., "Saison 2024/2025"
            if ( preg_match( '/(\d{4})\/(\d{4})/', $current_season_term->name, $matches ) ) {
                $end_year = (int) $matches[2];
                $next_season_start_year = $end_year;
                $next_season_end_year   = $next_season_start_year + 1;
                return sprintf( 'Saison %d/%d', $next_season_start_year, $next_season_end_year );
            }
        }
    }

    // Fallback for the very first season or if the current season name is in an unexpected format.
    // A new season is considered to start in September.
    $current_month     = (int) date( 'n' );
    $current_year      = (int) date( 'Y' );
    $season_start_year = ( $current_month >= 9 ) ? $current_year + 1 : $current_year;
    $season_end_year   = $season_start_year + 1;

    return sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );
}

/**
 * Handle actions related to season management (creation and selection).
 */
function dame_handle_season_actions() {
    // Check for nonce presence and validity for all actions in this section
    if ( isset( $_POST['dame_season_management_nonce_field'] ) && wp_verify_nonce( $_POST['dame_season_management_nonce_field'], 'dame_season_management_nonce' ) ) {

        // Handle creation of a new season
        if ( isset( $_POST['dame_action'] ) && 'annual_reset' === $_POST['dame_action'] ) {
            $new_season_name = dame_get_next_season_name();

            if ( term_exists( $new_season_name, 'dame_saison_adhesion' ) ) {
                add_action(
                    'admin_notices',
                    function() use ( $new_season_name ) {
                        $message = sprintf(
                            esc_html__( 'L\'opération ne peut être effectuée car la saison "%s" a déjà été créée.', 'dame' ),
                            esc_html( $new_season_name )
                        );
                        echo '<div class="error"><p>' . $message . '</p></div>';
                    }
                );
                return;
            }

            $new_season_term = wp_insert_term( $new_season_name, 'dame_saison_adhesion' );

            if ( is_wp_error( $new_season_term ) ) {
                add_action(
                    'admin_notices',
                    function() use ( $new_season_term ) {
                        $message = sprintf(
                            esc_html__( 'Erreur lors de la création de la saison : %s', 'dame' ),
                            $new_season_term->get_error_message()
                        );
                        echo '<div class="error"><p>' . $message . '</p></div>';
                    }
                );
                return;
            }

            update_option( 'dame_current_season_tag_id', $new_season_term['term_id'] );

            add_action(
                'admin_notices',
                function() use ( $new_season_name ) {
                    $message = sprintf(
                        esc_html__( 'Nouvelle saison initialisée avec succès. La saison active est maintenant : %s', 'dame' ),
                        '<strong>' . esc_html( $new_season_name ) . '</strong>'
                    );
                    echo '<div class="updated"><p>' . $message . '</p></div>';
                }
            );
        }

        // Handle updating the current season from the dropdown
        if ( isset( $_POST['dame_action'] ) && 'update_current_season' === $_POST['dame_action'] ) {
            if ( isset( $_POST['dame_current_season_selector'] ) ) {
                $selected_season_id = (int) $_POST['dame_current_season_selector'];
                $term = get_term( $selected_season_id, 'dame_saison_adhesion' );

                if ( $term && ! is_wp_error( $term ) ) {
                    update_option( 'dame_current_season_tag_id', $selected_season_id );

                    add_action(
                        'admin_notices',
                        function() use ( $term ) {
                            $message = sprintf(
                                esc_html__( 'La saison active a été mise à jour : %s', 'dame' ),
                                '<strong>' . esc_html( $term->name ) . '</strong>'
                            );
                            echo '<div class="updated"><p>' . $message . '</p></div>';
                        }
                    );
                }
            }
        }
    } elseif ( isset( $_POST['dame_action'] ) && ( 'annual_reset' === $_POST['dame_action'] || 'update_current_season' === $_POST['dame_action'] ) ) {
        // Handle nonce failure
        wp_die( 'Security check failed.' );
    }
}
add_action( 'admin_init', 'dame_handle_season_actions' );

/**
 * Renders the entire UI for the annual season management section.
 */
function dame_annual_reset_section_ui() {
    // Get all available seasons
    $seasons = get_terms( array(
        'taxonomy'   => 'dame_saison_adhesion',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'DESC',
    ) );

    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    ?>
    <div>
        <!-- Top section: Season Selection -->
        <div>
            <h3><?php esc_html_e( "Saison Active", 'dame' ); ?></h3>
            <p><?php esc_html_e( "Sélectionnez la saison d'adhésion à utiliser comme saison active sur l'ensemble du site.", 'dame' ); ?></p>
            <form method="post">
                <input type="hidden" name="dame_action" value="update_current_season">
                <?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>

                <label for="dame_current_season_selector" style="font-weight: bold;"><?php esc_html_e( 'Saison active :', 'dame' ); ?></label>
                <select id="dame_current_season_selector" name="dame_current_season_selector" style="margin-right: 10px;">
                    <?php if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) : ?>
                        <?php foreach ( $seasons as $season ) : ?>
                            <option value="<?php echo esc_attr( $season->term_id ); ?>" <?php selected( $season->term_id, $current_season_tag_id ); ?>>
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

        <hr style="margin: 20px 0;">

        <!-- Bottom section: Create New Season -->
        <div>
            <h3><?php esc_html_e( "Nouvelle Saison", 'dame' ); ?></h3>
            <p><?php esc_html_e( 'Cette action prépare le système pour la prochaine saison d\'adhésion en créant le nouveau tag.', 'dame' ); ?></p>
            <?php
            $next_season_name = dame_get_next_season_name();
            $disabled = term_exists( $next_season_name, 'dame_saison_adhesion' ) ? 'disabled' : '';
            ?>
            <form method="post">
                <input type="hidden" name="dame_action" value="annual_reset" />
                <?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>
                <?php submit_button( __( 'Initialiser la nouvelle saison', 'dame' ), 'primary', 'dame_annual_reset', false, $disabled ); ?>
                <p class="description">
                    <?php
                    if ( $disabled ) {
                        echo esc_html( sprintf( __( 'La saison "%s" a déjà été créée.', 'dame' ), $next_season_name ) );
                    } else {
                        echo esc_html( sprintf( __( 'Cette action créera et activera la saison "%s".', 'dame' ), $next_season_name ) );
                    }
                    ?>
                </p>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetButton = document.getElementById('dame_annual_reset');
            if (resetButton) {
                resetButton.addEventListener('click', function(e) {
                    if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir initialiser la nouvelle saison ? Cela créera un nouveau tag et le définira comme saison active.', 'dame' ) ); ?>")) {
                        e.preventDefault();
                    } else {
                        setTimeout(function() { resetButton.disabled = true; }, 0);
                    }
                });
            }
        });
    </script>
    <?php
}
