<?php
/**
 * Auto-generated article content for imported entities.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Join a list of strings naturally.
 *
 * @param array $items Items.
 * @return string
 */
function f1wiki_join_list( $items ) {
	$items = array_values( array_filter( array_map( 'trim', $items ) ) );
	$count = count( $items );

	if ( 0 === $count ) {
		return '';
	}
	if ( 1 === $count ) {
		return $items[0];
	}
	if ( 2 === $count ) {
		return $items[0] . ' and ' . $items[1];
	}

	$last = array_pop( $items );
	return implode( ', ', $items ) . ' and ' . $last;
}

/**
 * Archive range label such as 2000–2026.
 *
 * @return string
 */
function f1wiki_archive_range_label() {
	$from = (int) get_option( 'f1wiki_seed_from', 2000 );
	$to   = (int) get_option( 'f1wiki_seed_to', f1wiki_latest_season() );
	if ( ! $from ) {
		$from = 2000;
	}
	if ( ! $to ) {
		$to = f1wiki_latest_season();
	}
	return $from . '–' . $to;
}

/**
 * Pluralise a count.
 *
 * @param int    $count Count.
 * @param string $one   Singular noun.
 * @param string $many  Plural noun.
 * @return string
 */
function f1wiki_count_label( $count, $one, $many = '' ) {
	$count = (int) $count;
	if ( ! $many ) {
		$many = $one . 's';
	}
	return $count . ' ' . ( 1 === $count ? $one : $many );
}

/**
 * Store generated content, respecting manual edits.
 *
 * @param int    $post_id Post ID.
 * @param string $content HTML content.
 * @param bool   $force   Overwrite manual edits.
 * @return bool
 */
function f1wiki_set_auto_content( $post_id, $content, $force = false ) {
	$post_id = (int) $post_id;
	$content = trim( $content );
	if ( ! $post_id || '' === $content ) {
		return false;
	}

	$existing = (string) get_post_field( 'post_content', $post_id );
	$auto     = (string) get_post_meta( $post_id, '_f1wiki_auto_content', true );

	if ( ! $force && '' !== $existing && '1' !== $auto ) {
		return false;
	}

	$GLOBALS['f1wiki_auto_content_update'] = true;

	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $content,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 34, '…' ),
		)
	);

	unset( $GLOBALS['f1wiki_auto_content_update'] );

	update_post_meta( $post_id, '_f1wiki_auto_content', '1' );
	update_post_meta( $post_id, '_f1wiki_generated_hash', md5( $content ) );

	return true;
}

/**
 * Detect manual edits so imports do not overwrite them.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function f1wiki_watch_manual_edits( $post_id, $post, $update ) {
	if ( ! $update || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! in_array( $post->post_type, array( 'driver', 'team', 'circuit', 'race', 'season' ), true ) ) {
		return;
	}

	if ( ! empty( $GLOBALS['f1wiki_auto_content_update'] ) ) {
		return;
	}

	if ( '1' !== (string) get_post_meta( $post_id, '_f1wiki_auto_content', true ) ) {
		return;
	}

	$hash = (string) get_post_meta( $post_id, '_f1wiki_generated_hash', true );
	if ( $hash && md5( (string) $post->post_content ) !== $hash ) {
		update_post_meta( $post_id, '_f1wiki_auto_content', '0' );
	}
}
add_action( 'save_post', 'f1wiki_watch_manual_edits', 10, 3 );

/**
 * Generate a driver summary.
 *
 * @param int  $post_id Driver post ID.
 * @param bool $force   Overwrite manual content.
 * @return bool
 */
function f1wiki_generate_driver_content( $post_id, $force = false ) {
	global $wpdb;

	$post_id = (int) $post_id;
	$name    = get_the_title( $post_id );
	$stats   = f1wiki_get_stats( $post_id, 'driver' );
	$nat     = (string) get_post_meta( $post_id, '_f1wiki_nationality', true );

	$team_ids = $wpdb->get_col(
		$wpdb->prepare(
			'SELECT team_id FROM ' . f1wiki_results_table() . ' WHERE driver_id = %d GROUP BY team_id ORDER BY MIN(season_year) ASC', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		)
	);

	$teams = array();
	foreach ( $team_ids as $team_id ) {
		$title = get_the_title( (int) $team_id );
		if ( $title ) {
			$teams[] = $title;
		}
	}

	$champ_years = $wpdb->get_col(
		$wpdb->prepare(
			'SELECT season_year FROM ' . f1wiki_driver_standings_table() . ' WHERE driver_id = %d AND position = 1 ORDER BY season_year ASC', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		)
	);

	$sentences = array();

	if ( $nat ) {
		$sentences[] = sprintf( '%s is a %s racing driver.', $name, $nat );
	} else {
		$sentences[] = sprintf( '%s is a racing driver.', $name );
	}

	if ( $stats['first_season'] && $stats['last_season'] ) {
		if ( $stats['first_season'] === $stats['last_season'] ) {
			$sentences[] = sprintf( 'The archive records Formula One appearances in the %d season.', $stats['first_season'] );
		} else {
			$sentences[] = sprintf( 'The archive records Formula One appearances between %d and %d.', $stats['first_season'], $stats['last_season'] );
		}
	}

	if ( $stats['starts'] ) {
		$sentences[] = sprintf(
			'Across %s, %s recorded %s, %s, %s and %s, scoring %s championship points.',
			f1wiki_count_label( $stats['starts'], 'start' ),
			$name,
			f1wiki_count_label( $stats['wins'], 'win' ),
			f1wiki_count_label( $stats['podiums'], 'podium' ),
			f1wiki_count_label( $stats['poles'], 'pole position' ),
			f1wiki_count_label( $stats['fastest_laps'], 'fastest lap' ),
			f1wiki_format_points( $stats['points'] )
		);
	}

	if ( ! empty( $champ_years ) ) {
		$sentences[] = sprintf( '%s won the drivers\' championship in %s.', $name, f1wiki_join_list( array_map( 'intval', $champ_years ) ) );
	} elseif ( $stats['best_championship'] && $stats['best_championship_year'] ) {
		$sentences[] = sprintf( '%s\'s best championship finish in the archive is P%d in %d.', $name, $stats['best_championship'], $stats['best_championship_year'] );
	}

	if ( ! empty( $teams ) ) {
		$sentences[] = sprintf( '%s drove for %s during the period covered here.', $name, f1wiki_join_list( $teams ) );
	}

	$content = '<p>' . implode( ' ', array_map( 'esc_html', $sentences ) ) . '</p>';

	return f1wiki_set_auto_content( $post_id, $content, $force );
}

/**
 * Generate a team summary.
 *
 * @param int  $post_id Team post ID.
 * @param bool $force   Overwrite manual content.
 * @return bool
 */
function f1wiki_generate_team_content( $post_id, $force = false ) {
	$post_id = (int) $post_id;
	$name    = get_the_title( $post_id );
	$stats   = f1wiki_get_stats( $post_id, 'team' );
	$nat     = (string) get_post_meta( $post_id, '_f1wiki_nationality', true );

	$sentences = array();

	if ( $nat ) {
		$sentences[] = sprintf( '%s is a %s Formula One constructor.', $name, $nat );
	} else {
		$sentences[] = sprintf( '%s is a Formula One constructor.', $name );
	}

	if ( $stats['first_season'] && $stats['last_season'] ) {
		$sentences[] = sprintf(
			'The archive covers the team from %d to %d: %s, %s and %s.',
			$stats['first_season'],
			$stats['last_season'],
			f1wiki_count_label( $stats['starts'], 'start' ),
			f1wiki_count_label( $stats['wins'], 'win' ),
			f1wiki_format_points( $stats['points'] ) . ' points'
		);
	}

	$champ_years = $stats['championship_years'];
	if ( ! is_array( $champ_years ) ) {
		$champ_years = array_filter( array_map( 'intval', explode( ',', (string) $champ_years ) ) );
	}
	sort( $champ_years );

	if ( ! empty( $champ_years ) ) {
		$sentences[] = sprintf( 'The team won the constructors\' championship in %s.', f1wiki_join_list( $champ_years ) );
	}

	$drivers = f1wiki_get_team_latest_drivers( $post_id );
	if ( ! empty( $drivers ) ) {
		$names = wp_list_pluck( $drivers, 'name' );
		$sentences[] = sprintf( 'Its most recent drivers in the archive are %s.', f1wiki_join_list( $names ) );
	}

	$content = '<p>' . implode( ' ', array_map( 'esc_html', $sentences ) ) . '</p>';

	return f1wiki_set_auto_content( $post_id, $content, $force );
}

/**
 * Generate a circuit summary.
 *
 * @param int  $post_id Circuit post ID.
 * @param bool $force   Overwrite manual content.
 * @return bool
 */
function f1wiki_generate_circuit_content( $post_id, $force = false ) {
	$post_id = (int) $post_id;
	$name    = get_the_title( $post_id );
	$stats   = f1wiki_get_stats( $post_id, 'circuit' );
	$locality = (string) get_post_meta( $post_id, '_f1wiki_locality', true );
	$country  = (string) get_post_meta( $post_id, '_f1wiki_country', true );

	$location = trim( $locality . ( $locality && $country ? ', ' : '' ) . $country );

	$sentences = array();

	if ( $location ) {
		$sentences[] = sprintf( '%s is a motor racing circuit in %s.', $name, $location );
	} else {
		$sentences[] = sprintf( '%s is a motor racing circuit.', $name );
	}

	if ( $stats['races'] ) {
		$sentence = sprintf( 'The archive records %s at the circuit', f1wiki_count_label( $stats['races'], 'Formula One race' ) );
		if ( $stats['first_season'] && $stats['last_season'] ) {
			$sentence .= sprintf( ', held between %d and %d', $stats['first_season'], $stats['last_season'] );
		}
		$sentences[] = $sentence . '.';
	}

	if ( $stats['most_wins_driver'] ) {
		$sentences[] = sprintf(
			'%s has the most wins at the circuit in the archive, with %s.',
			$stats['most_wins_driver'],
			f1wiki_count_label( $stats['most_wins_driver_wins'], 'win' )
		);
	}

	if ( $stats['most_wins_team'] ) {
		$sentences[] = sprintf(
			'%s leads the constructors with %s.',
			$stats['most_wins_team'],
			f1wiki_count_label( $stats['most_wins_team_wins'], 'win' )
		);
	}

	$content = '<p>' . implode( ' ', array_map( 'esc_html', $sentences ) ) . '</p>';

	return f1wiki_set_auto_content( $post_id, $content, $force );
}

/**
 * Generate a season summary.
 *
 * @param int  $post_id Season post ID.
 * @param bool $force   Overwrite manual content.
 * @return bool
 */
function f1wiki_generate_season_content( $post_id, $force = false ) {
	$post_id = (int) $post_id;
	$year    = (int) get_post_meta( $post_id, '_f1wiki_season_year', true );
	$rounds  = (int) get_post_meta( $post_id, '_f1wiki_rounds', true );
	$done    = (int) get_post_meta( $post_id, '_f1wiki_completed_rounds', true );

	if ( ! $year ) {
		$year = (int) get_the_title( $post_id );
	}

	$standings = f1wiki_get_driver_standings( $year, 1 );
	$teams     = f1wiki_get_team_standings( $year, 1 );
	$leader    = $standings ? $standings[0] : null;
	$team      = $teams ? $teams[0] : null;
	$complete  = $rounds > 0 && $done >= $rounds;

	$sentences = array();

	if ( $complete ) {
		$sentences[] = sprintf( 'The %d Formula One season was contested over %d rounds.', $year, $rounds );
		if ( $leader ) {
			$sentences[] = sprintf(
				'%s won the drivers\' championship with %s points.',
				$leader->driver_name,
				f1wiki_format_points( $leader->points )
			);
		}
		if ( $team ) {
			$sentences[] = sprintf( '%s won the constructors\' championship.', $team->team_name );
		}
	} else {
		$sentences[] = sprintf( 'The %d Formula One season has completed %d of %d scheduled rounds in the archive.', $year, $done, $rounds );
		if ( $leader ) {
			$sentences[] = sprintf(
				'%s leads the drivers\' championship with %s points.',
				$leader->driver_name,
				f1wiki_format_points( $leader->points )
			);
		}
		if ( $team ) {
			$sentences[] = sprintf( '%s leads the constructors\' standings.', $team->team_name );
		}
	}

	$content = '<p>' . implode( ' ', array_map( 'esc_html', $sentences ) ) . '</p>';

	return f1wiki_set_auto_content( $post_id, $content, $force );
}

/**
 * Generate a race report line.
 *
 * @param int  $post_id Race post ID.
 * @param bool $force   Overwrite manual content.
 * @return bool
 */
function f1wiki_generate_race_content( $post_id, $force = false ) {
	global $wpdb;

	$post_id  = (int) $post_id;
	$title    = get_the_title( $post_id );
	$date     = (string) get_post_meta( $post_id, '_f1wiki_race_date', true );
	$season   = (int) get_post_meta( $post_id, '_f1wiki_season_year', true );
	$has      = (int) get_post_meta( $post_id, '_f1wiki_has_results', true );
	$summary  = f1wiki_get_race_summary( $post_id );
	$formatted_date = f1wiki_format_date( $date );
	$circuit  = $summary['circuit'];
	$circuit_id = $summary['circuit_id'];
	$locality = $circuit_id ? (string) get_post_meta( $circuit_id, '_f1wiki_locality', true ) : '';
	$country  = $circuit_id ? (string) get_post_meta( $circuit_id, '_f1wiki_country', true ) : '';
	$place    = trim( $locality . ( $locality && $country ? ', ' : '' ) . $country );

	$sentences = array();

	if ( $has && $summary['winner'] ) {
		$sentences[] = sprintf(
			'The %s took place on %s at %s%s.',
			$title,
			$formatted_date ? $formatted_date : 'the scheduled date',
			$circuit,
			$place ? ' in ' . $place : ''
		);

		$grid = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT grid FROM ' . f1wiki_results_table() . ' WHERE race_id = %d AND driver_id = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_id,
				$summary['winner_id']
			)
		);

		$winner_sentence = sprintf( '%s won for %s', $summary['winner'], $summary['team'] );
		if ( $grid > 0 ) {
			$winner_sentence .= sprintf( ' from P%d on the grid', $grid );
		}
		$sentences[] = $winner_sentence . '.';

		if ( $summary['pole'] && $summary['pole_id'] !== $summary['winner_id'] ) {
			$sentences[] = sprintf( '%s started from pole position.', $summary['pole'] );
		}
		if ( $summary['fastest'] ) {
			$sentences[] = sprintf( '%s set the fastest lap.', $summary['fastest'] );
		}
	} else {
		$sentences[] = sprintf(
			'The %s is scheduled for %s at %s%s.',
			$title,
			$formatted_date ? $formatted_date : 'a date to be confirmed',
			$circuit,
			$place ? ' in ' . $place : ''
		);
		$sentences[] = 'A full classification will appear here once results are available.';
	}

	$content = '<p>' . implode( ' ', array_map( 'esc_html', $sentences ) ) . '</p>';

	return f1wiki_set_auto_content( $post_id, $content, $force );
}

/**
 * Regenerate summaries for all entities.
 *
 * @param bool $force Overwrite manual edits.
 */
function f1wiki_generate_all_content( $force = false ) {
	$types = array( 'driver', 'team', 'circuit', 'season', 'race' );

	foreach ( $types as $type ) {
		$ids = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $ids as $id ) {
			switch ( $type ) {
				case 'driver':
					f1wiki_generate_driver_content( $id, $force );
					break;
				case 'team':
					f1wiki_generate_team_content( $id, $force );
					break;
				case 'circuit':
					f1wiki_generate_circuit_content( $id, $force );
					break;
				case 'season':
					f1wiki_generate_season_content( $id, $force );
					break;
				case 'race':
					f1wiki_generate_race_content( $id, $force );
					break;
			}
		}
	}
}
