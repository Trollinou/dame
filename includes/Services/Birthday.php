<?php

namespace DAME\Services;

use WP_Query;
use DateTime;

class Birthday {

	public function send_wishes() {
		$options = get_option( 'dame_options' );
		if ( empty( $options['birthday_emails_enabled'] ) ) return;

		$article_slug = $options['birthday_article_slug'] ?? '';
		if ( empty( $article_slug ) ) return;

		$posts = get_posts( [ 'name' => $article_slug, 'post_type' => 'post', 'post_status' => [ 'publish', 'private' ], 'posts_per_page' => 1 ] );
		if ( ! $posts ) return;
		$article = $posts[0];

		// Season Logic
		$season_id = get_option( 'dame_current_season_tag_id' );
		if ( ! $season_id ) return;
		$season_ids = [ $season_id ];

		// If Sept, add previous season
		if ( (int) date( 'n' ) === 9 ) {
			$term = get_term( $season_id );
			if ( $term && preg_match( '/(\d{4})\/(\d{4})/', $term->name, $matches ) ) {
				$prev_name = sprintf( 'Saison %d/%d', $matches[1] - 1, $matches[1] );
				$prev_term = get_term_by( 'name', $prev_name, 'dame_saison_adhesion' );
				if ( $prev_term ) $season_ids[] = $prev_term->term_id;
			}
		}

		// Query Adherents
		$query = new WP_Query( [
			'post_type' => 'adherent', 'posts_per_page' => -1,
			'meta_query' => [ [ 'key' => '_dame_birth_date', 'value' => '-' . date( 'm-d' ) . '$', 'compare' => 'REGEXP' ] ],
			'tax_query' => [ [ 'taxonomy' => 'dame_saison_adhesion', 'field' => 'term_id', 'terms' => $season_ids, 'operator' => 'IN' ] ]
		] );

		$sent_list = [];
		$sender_email = $options['sender_email'] ?? get_option( 'admin_email' );
		$headers = [ 'Content-Type: text/html; charset=UTF-8', 'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>' ];

		while ( $query->have_posts() ) {
			$query->the_post();
			$pid = get_the_ID();
			$nom = get_post_meta( $pid, '_dame_last_name', true );
			$prenom = get_post_meta( $pid, '_dame_first_name', true );
			$birth = get_post_meta( $pid, '_dame_birth_date', true );

			if ( empty( $prenom ) || empty( $birth ) ) continue;

			try {
				$age = ( new DateTime( $birth ) )->diff( new DateTime() )->y;
			} catch ( \Exception $e ) { continue; }

			$subject = str_replace( [ '[NOM]', '[PRENOM]', '[AGE]' ], [ mb_strtoupper( $nom ), mb_convert_case( $prenom, MB_CASE_TITLE ), $age ], $article->post_title );
			$content = str_replace( [ '[NOM]', '[PRENOM]', '[AGE]' ], [ mb_strtoupper( $nom ), mb_convert_case( $prenom, MB_CASE_TITLE ), $age ], apply_filters( 'the_content', $article->post_content ) );

			$emails = \DAME\Core\Utils::get_emails_for_adherent( $pid );
			if ( $emails ) {
				foreach ( $emails as $email ) wp_mail( $email, $subject, $content, $headers );
				$sent_list[] = "$prenom $nom ($age ans)";
			}
		}
		wp_reset_postdata();

		// Report to Admin
		if ( ! empty( $sent_list ) ) {
			wp_mail( $sender_email, "Rapport Anniversaires", "Joyeux anniversaire envoyé à :\n" . implode( "\n", $sent_list ), $headers );
		}
	}
}
