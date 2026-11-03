<?php
/**
 * Shared data helpers used by the theme and the importer.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Most recent season in the archive.
 *
 * @return int
 */
function f1wiki_latest_season() {
	$year = (int) get_option( 'f1wiki_latest_season' );
	if ( $year > 0 ) {
		return $year;
	}

	global $wpdb;
	$table = f1wiki_driver_standings_table();
	$year  = (int) $wpdb->get_var( "SELECT MAX(season_year) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $year > 0 ? $year : (int) current_time( 'Y' );
}

/**
 * Archive counts used in the hero and dashboard.
 *
 * @return array
 */
function f1wiki_get_counts() {
	$counts = get_option( 'f1wiki_counts' );
	if ( is_array( $counts ) && ! empty( $counts ) ) {
		return $counts;
	}

	global $wpdb;
	$results = f1wiki_results_table();

	return array(
		'drivers'  => (int) wp_count_posts( 'driver' )->publish,
		'teams'    => (int) wp_count_posts( 'team' )->publish,
		'circuits' => (int) wp_count_posts( 'circuit' )->publish,
		'races'    => (int) wp_count_posts( 'race' )->publish,
		'seasons'  => (int) wp_count_posts( 'season' )->publish,
		'results'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$results}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	);
}

/**
 * Driver standings for a season.
 *
 * @param int $year  Season year.
 * @param int $limit Optional result limit.
 * @return array
 */
function f1wiki_get_driver_standings( $year, $limit = 0 ) {
	global $wpdb;

	$table = f1wiki_driver_standings_table();
	$posts = $wpdb->posts;

	$sql = "SELECT s.*, d.post_title AS driver_name, t.post_title AS team_name
		FROM {$table} s
		INNER JOIN {$posts} d ON d.ID = s.driver_id AND d.post_status = 'publish'
		LEFT JOIN {$posts} t ON t.ID = s.team_id
		WHERE s.season_year = %d
		ORDER BY s.position ASC, s.points DESC";

	$args = array( $year );

	if ( $limit > 0 ) {
		$sql   .= ' LIMIT %d';
		$args[] = $limit;
	}

	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	foreach ( $rows as $row ) {
		$row->team_color = $row->team_id ? f1wiki_team_color( (int) $row->team_id ) : '#666666';
		$row->driver_url = get_permalink( (int) $row->driver_id );
		$row->team_url   = $row->team_id ? get_permalink( (int) $row->team_id ) : '';
	}

	return $rows;
}

/**
 * Constructor standings for a season.
 *
 * @param int $year  Season year.
 * @param int $limit Optional result limit.
 * @return array
 */
function f1wiki_get_team_standings( $year, $limit = 0 ) {
	global $wpdb;

	$table = f1wiki_team_standings_table();
	$posts = $wpdb->posts;

	$sql = "SELECT s.*, t.post_title AS team_name
		FROM {$table} s
		INNER JOIN {$posts} t ON t.ID = s.team_id AND t.post_status = 'publish'
		WHERE s.season_year = %d
		ORDER BY s.position ASC, s.points DESC";

	$args = array( $year );

	if ( $limit > 0 ) {
		$sql   .= ' LIMIT %d';
		$args[] = $limit;
	}

	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	foreach ( $rows as $row ) {
		$row->team_color = f1wiki_team_color( (int) $row->team_id );
		$row->team_url   = get_permalink( (int) $row->team_id );
	}

	return $rows;
}

/**
 * Drivers' champions (standings leaders) across a season range.
 *
 * @param int $from First season.
 * @param int $to   Last season. Defaults to the latest season.
 * @return array
 */
function f1wiki_get_champions( $from = 2000, $to = 0 ) {
	global $wpdb;

	$table = f1wiki_driver_standings_table();
	$posts = $wpdb->posts;

	if ( ! $to ) {
		$to = f1wiki_latest_season();
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.season_year, s.driver_id, s.team_id, s.points,
				d.post_title AS driver_name, t.post_title AS team_name
			FROM {$table} s
			INNER JOIN {$posts} d ON d.ID = s.driver_id AND d.post_status = 'publish'
			LEFT JOIN {$posts} t ON t.ID = s.team_id
			WHERE s.position = 1 AND s.season_year BETWEEN %d AND %d
			ORDER BY s.season_year ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$from,
			$to
		)
	);

	foreach ( $rows as $row ) {
		$row->driver_url = get_permalink( (int) $row->driver_id );
		$row->team_url   = $row->team_id ? get_permalink( (int) $row->team_id ) : '';
		$row->team_color = f1wiki_team_color( (int) $row->team_id );
	}

	return $rows;
}

/**
 * Full classification for a race.
 *
 * @param int $race_id Race post ID.
 * @return array
 */
function f1wiki_get_race_results( $race_id ) {
	global $wpdb;

	$table = f1wiki_results_table();
	$posts = $wpdb->posts;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT r.*, d.post_title AS driver_name, t.post_title AS team_name
			FROM {$table} r
			INNER JOIN {$posts} d ON d.ID = r.driver_id
			INNER JOIN {$posts} t ON t.ID = r.team_id
			WHERE r.race_id = %d
			ORDER BY r.result_order ASC, r.position ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$race_id
		)
	);

	foreach ( $rows as $row ) {
		$row->driver_url = get_permalink( (int) $row->driver_id );
		$row->team_url   = get_permalink( (int) $row->team_id );
		$row->team_color = f1wiki_team_color( (int) $row->team_id );
		$row->is_fastest = (int) $row->fastest_lap_rank === 1;
	}

	return $rows;
}

/**
 * Season-by-season record for a driver.
 *
 * @param int $driver_id Driver post ID.
 * @return array
 */
function f1wiki_get_driver_seasons( $driver_id ) {
	global $wpdb;

	$results = f1wiki_results_table();
	$standings = f1wiki_driver_standings_table();

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT season_year,
				COUNT(*) AS starts,
				SUM(points) AS results_points,
				SUM(CASE WHEN position_text = '1' THEN 1 ELSE 0 END) AS wins,
				GROUP_CONCAT(DISTINCT team_id ORDER BY team_id) AS team_ids
			FROM {$results}
			WHERE driver_id = %d
			GROUP BY season_year
			ORDER BY season_year DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$driver_id
		)
	);

	$positions = array();
	$standing_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT season_year, position, points FROM {$standings} WHERE driver_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$driver_id
		)
	);
	foreach ( $standing_rows as $standing ) {
		$positions[ (int) $standing->season_year ] = $standing;
	}

	foreach ( $rows as $row ) {
		$row->season_year   = (int) $row->season_year;
		$row->starts        = (int) $row->starts;
		$row->wins          = (int) $row->wins;
		$row->points        = isset( $positions[ $row->season_year ] ) ? (float) $positions[ $row->season_year ]->points : (float) $row->results_points;
		$row->position      = isset( $positions[ $row->season_year ] ) ? (int) $positions[ $row->season_year ]->position : 0;
		$row->season_url    = get_permalink( f1wiki_get_season_post( $row->season_year ) );
		$row->season_post   = f1wiki_get_season_post( $row->season_year );
		$row->teams         = array();

		foreach ( array_filter( array_map( 'intval', explode( ',', (string) $row->team_ids ) ) ) as $team_id ) {
			$row->teams[] = array(
				'id'    => $team_id,
				'name'  => get_the_title( $team_id ),
				'url'   => get_permalink( $team_id ),
				'color' => f1wiki_team_color( $team_id ),
			);
		}
	}

	return $rows;
}

/**
 * Season-by-season record for a team.
 *
 * @param int $team_id Team post ID.
 * @return array
 */
function f1wiki_get_team_seasons( $team_id ) {
	global $wpdb;

	$results   = f1wiki_results_table();
	$standings = f1wiki_team_standings_table();

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT season_year,
				COUNT(*) AS starts,
				SUM(points) AS results_points,
				SUM(CASE WHEN position_text = '1' THEN 1 ELSE 0 END) AS wins,
				GROUP_CONCAT(DISTINCT driver_id ORDER BY driver_id) AS driver_ids
			FROM {$results}
			WHERE team_id = %d
			GROUP BY season_year
			ORDER BY season_year DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$team_id
		)
	);

	$positions = array();
	$standing_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT season_year, position, points FROM {$standings} WHERE team_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$team_id
		)
	);
	foreach ( $standing_rows as $standing ) {
		$positions[ (int) $standing->season_year ] = $standing;
	}

	foreach ( $rows as $row ) {
		$row->season_year = (int) $row->season_year;
		$row->starts      = (int) $row->starts;
		$row->wins        = (int) $row->wins;
		$row->points      = isset( $positions[ $row->season_year ] ) ? (float) $positions[ $row->season_year ]->points : (float) $row->results_points;
		$row->position    = isset( $positions[ $row->season_year ] ) ? (int) $positions[ $row->season_year ]->position : 0;
		$row->season_url  = get_permalink( f1wiki_get_season_post( $row->season_year ) );
		$row->drivers     = array();

		foreach ( array_filter( array_map( 'intval', explode( ',', (string) $row->driver_ids ) ) ) as $driver_id ) {
			$row->drivers[] = array(
				'id'   => $driver_id,
				'name' => get_the_title( $driver_id ),
				'url'  => get_permalink( $driver_id ),
			);
		}
	}

	return $rows;
}

/**
 * Drivers who raced for a team in its latest season.
 *
 * @param int $team_id Team post ID.
 * @return array
 */
function f1wiki_get_team_latest_drivers( $team_id ) {
	global $wpdb;

	$table = f1wiki_results_table();
	$year  = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT MAX(season_year) FROM {$table} WHERE team_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$team_id
		)
	);

	if ( ! $year ) {
		return array();
	}

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT driver_id, COUNT(*) AS starts, MAX(round) AS last_round
			FROM {$table}
			WHERE team_id = %d AND season_year = %d
			GROUP BY driver_id
			ORDER BY last_round DESC, starts DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$team_id,
			$year
		)
	);

	$drivers = array();
	foreach ( $ids as $driver_id ) {
		$drivers[] = array(
			'id'   => (int) $driver_id,
			'name' => get_the_title( (int) $driver_id ),
			'url'  => get_permalink( (int) $driver_id ),
		);
	}

	return $drivers;
}

/**
 * Recent results for a driver.
 *
 * @param int $driver_id Driver post ID.
 * @param int $limit     Number of rows.
 * @return array
 */
function f1wiki_get_driver_recent_results( $driver_id, $limit = 10 ) {
	global $wpdb;

	$table = f1wiki_results_table();
	$posts = $wpdb->posts;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT r.*, rp.post_title AS race_name, tp.post_title AS team_name
			FROM {$table} r
			INNER JOIN {$posts} rp ON rp.ID = r.race_id
			INNER JOIN {$posts} tp ON tp.ID = r.team_id
			WHERE r.driver_id = %d
			ORDER BY r.season_year DESC, r.round DESC
			LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$driver_id,
			$limit
		)
	);

	foreach ( $rows as $row ) {
		$row->race_url   = get_permalink( (int) $row->race_id );
		$row->team_url   = get_permalink( (int) $row->team_id );
		$row->team_color = f1wiki_team_color( (int) $row->team_id );
	}

	return $rows;
}

/**
 * Race wins for a team.
 *
 * @param int $team_id Team post ID.
 * @param int $limit   Number of rows.
 * @return array
 */
function f1wiki_get_team_wins( $team_id, $limit = 8 ) {
	global $wpdb;

	$table = f1wiki_results_table();
	$posts = $wpdb->posts;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT r.*, rp.post_title AS race_name, dp.post_title AS driver_name
			FROM {$table} r
			INNER JOIN {$posts} rp ON rp.ID = r.race_id
			INNER JOIN {$posts} dp ON dp.ID = r.driver_id
			WHERE r.team_id = %d AND r.position_text = '1'
			ORDER BY r.season_year DESC, r.round DESC
			LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$team_id,
			$limit
		)
	);

	foreach ( $rows as $row ) {
		$row->race_url   = get_permalink( (int) $row->race_id );
		$row->driver_url = get_permalink( (int) $row->driver_id );
	}

	return $rows;
}

/**
 * All races held at a circuit, newest first.
 *
 * @param int $circuit_id Circuit post ID.
 * @return WP_Post[]
 */
function f1wiki_get_circuit_races( $circuit_id ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'race',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_f1wiki_circuit_id',
					'value' => (int) $circuit_id,
				),
			),
			'meta_key'       => '_f1wiki_race_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	return $query->posts;
}

/**
 * All races in a season, ordered by round.
 *
 * @param int $year Season year.
 * @return WP_Post[]
 */
function f1wiki_get_season_races( $year ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'race',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_f1wiki_season_year',
					'value' => (int) $year,
				),
			),
			'meta_key'       => '_f1wiki_round',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	return $query->posts;
}

/**
 * First upcoming race from today.
 *
 * @return WP_Post|null
 */
function f1wiki_get_next_race() {
	$query = new WP_Query(
		array(
			'post_type'      => 'race',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_f1wiki_race_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'meta_key'       => '_f1wiki_race_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	return $query->posts ? $query->posts[0] : null;
}

/**
 * Most recent race with a classification.
 *
 * @return WP_Post|null
 */
function f1wiki_get_latest_completed_race() {
	$query = new WP_Query(
		array(
			'post_type'      => 'race',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_f1wiki_has_results',
					'value' => '1',
				),
				array(
					'key'     => '_f1wiki_race_date',
					'compare' => 'EXISTS',
				),
			),
			'meta_key'       => '_f1wiki_race_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	return $query->posts ? $query->posts[0] : null;
}

/**
 * Season post for a year.
 *
 * @param int $year Season year.
 * @return int
 */
function f1wiki_get_season_post( $year ) {
	return f1wiki_get_post_by_source( 'season:' . (int) $year );
}

/**
 * Find a post by its imported source identifier.
 *
 * @param string $source_id Source key.
 * @return int
 */
function f1wiki_get_post_by_source( $source_id ) {
	$ids = get_posts(
		array(
			'post_type'        => array( 'driver', 'team', 'circuit', 'race', 'season' ),
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'meta_key'         => '_f1wiki_source_id',
			'meta_value'       => $source_id,
			'suppress_filters' => false,
			'no_found_rows'    => true,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

/**
 * Season progress information.
 *
 * @param int $year Season year.
 * @return array
 */
function f1wiki_get_season_status( $year ) {
	$season_id = f1wiki_get_season_post( $year );
	$status    = array(
		'rounds'    => 0,
		'completed' => 0,
		'status'    => 'unknown',
	);

	if ( ! $season_id ) {
		return $status;
	}

	$status['rounds']    = (int) get_post_meta( $season_id, '_f1wiki_rounds', true );
	$status['completed'] = (int) get_post_meta( $season_id, '_f1wiki_completed_rounds', true );
	$status['status']    = (string) get_post_meta( $season_id, '_f1wiki_season_status', true );

	return $status;
}

/**
 * Stats stored on a post.
 *
 * @param int    $post_id Post ID.
 * @param string $type    driver|team|circuit|season.
 * @return array
 */
function f1wiki_get_stats( $post_id, $type = 'driver' ) {
	$defaults = array(
		'driver'  => array(
			'starts'                 => 0,
			'wins'                   => 0,
			'podiums'                => 0,
			'poles'                  => 0,
			'fastest_laps'           => 0,
			'points'                 => 0,
			'championships'          => 0,
			'first_season'           => 0,
			'last_season'            => 0,
			'best_championship'      => 0,
			'best_championship_year' => 0,
		),
		'team'    => array(
			'starts'             => 0,
			'wins'               => 0,
			'podiums'            => 0,
			'poles'              => 0,
			'points'             => 0,
			'championships'      => 0,
			'championship_years' => array(),
			'first_season'       => 0,
			'last_season'        => 0,
		),
		'circuit' => array(
			'races'                 => 0,
			'first_season'          => 0,
			'last_season'           => 0,
			'most_wins_driver'      => '',
			'most_wins_driver_wins' => 0,
			'most_wins_team'        => '',
			'most_wins_team_wins'   => 0,
		),
		'season'  => array(
			'rounds'    => 0,
			'completed' => 0,
		),
	);

	$stored = get_post_meta( $post_id, '_f1wiki_stats', true );
	$stored = is_array( $stored ) ? $stored : array();
	$base   = isset( $defaults[ $type ] ) ? $defaults[ $type ] : array();

	return array_merge( $base, $stored );
}

/**
 * Race summary fields (winner, pole, fastest lap, circuit).
 *
 * @param int $race_id Race post ID.
 * @return array
 */
function f1wiki_get_race_summary( $race_id ) {
	$winner_id = (int) get_post_meta( $race_id, '_f1wiki_winner_driver_id', true );
	$team_id   = (int) get_post_meta( $race_id, '_f1wiki_winner_team_id', true );
	$pole_id   = (int) get_post_meta( $race_id, '_f1wiki_pole_driver_id', true );
	$fastest_id = (int) get_post_meta( $race_id, '_f1wiki_fastest_lap_driver_id', true );
	$circuit_id = (int) get_post_meta( $race_id, '_f1wiki_circuit_id', true );

	return array(
		'winner_id'  => $winner_id,
		'winner'     => $winner_id ? get_the_title( $winner_id ) : '',
		'winner_url' => $winner_id ? get_permalink( $winner_id ) : '',
		'team_id'    => $team_id,
		'team'       => $team_id ? get_the_title( $team_id ) : '',
		'team_url'   => $team_id ? get_permalink( $team_id ) : '',
		'team_color' => $team_id ? f1wiki_team_color( $team_id ) : '#666666',
		'pole_id'    => $pole_id,
		'pole'       => $pole_id ? get_the_title( $pole_id ) : '',
		'pole_url'   => $pole_id ? get_permalink( $pole_id ) : '',
		'fastest_id' => $fastest_id,
		'fastest'    => $fastest_id ? get_the_title( $fastest_id ) : '',
		'fastest_url' => $fastest_id ? get_permalink( $fastest_id ) : '',
		'circuit_id' => $circuit_id,
		'circuit'    => $circuit_id ? get_the_title( $circuit_id ) : '',
		'circuit_url' => $circuit_id ? get_permalink( $circuit_id ) : '',
	);
}

/**
 * Team accent color.
 *
 * @param int $team_id Team post ID.
 * @return string
 */
function f1wiki_team_color( $team_id ) {
	$team_id = (int) $team_id;
	if ( ! $team_id ) {
		return '#E10600';
	}

	$color = function_exists( 'get_field' ) ? get_field( 'team_color', $team_id ) : '';
	if ( ! $color ) {
		$color = get_post_meta( $team_id, '_f1wiki_team_color', true );
	}

	$color = f1wiki_sanitize_hex( $color );
	if ( $color ) {
		return $color;
	}

	return f1wiki_fallback_color( (string) $team_id );
}

/**
 * Validate a hex color.
 *
 * @param string $color Color value.
 * @return string
 */
function f1wiki_sanitize_hex( $color ) {
	if ( is_string( $color ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
		return strtolower( $color );
	}
	return '';
}

/**
 * Stable fallback color for a key.
 *
 * @param string $key Key.
 * @return string
 */
function f1wiki_fallback_color( $key ) {
	$palette = array( '#3671C6', '#FF8000', '#229971', '#FF87BC', '#64C4FF', '#F5C518', '#8C6BC8', '#E10600', '#009B77', '#C0C0C0' );
	$index   = abs( crc32( $key ) ) % count( $palette );
	return $palette[ $index ];
}

/**
 * Known constructor colors, keyed by Jolpica constructor ID.
 *
 * @return array
 */
function f1wiki_team_color_map() {
	return array(
		'ferrari'      => '#E8002D',
		'red_bull'     => '#3671C6',
		'mercedes'     => '#27F4D2',
		'mclaren'      => '#FF8000',
		'aston_martin' => '#229971',
		'alpine'       => '#FF87BC',
		'williams'     => '#64C4FF',
		'rb'           => '#6692FF',
		'racing_bulls' => '#6692FF',
		'alphatauri'   => '#5E8FAA',
		'toro_rosso'   => '#4E7C9B',
		'sauber'       => '#52E252',
		'audi'         => '#C8102E',
		'cadillac'     => '#C0C0C0',
		'haas'         => '#B6BABD',
		'renault'      => '#FFF500',
		'lotus_f1'     => '#FFB800',
		'force_india'  => '#F596C8',
		'racing_point' => '#F596C8',
		'jordan'       => '#F7D117',
		'toyota'       => '#CC0000',
		'bmw_sauber'   => '#293B6A',
		'brawn'        => '#B8FD36',
		'honda'        => '#E40521',
		'jaguar'       => '#0B5C2E',
		'minardi'      => '#111111',
		'prost'        => '#1B3D6D',
		'arrows'       => '#FF6A00',
		'bar'          => '#A7A9AC',
		'benetton'     => '#00A19C',
		'stewart'      => '#005A9C',
		'ligier'       => '#1B3D6D',
		'marussia'     => '#6E6E6E',
		'manor'        => '#6E6E6E',
		'caterham'     => '#009B48',
		'virgin'       => '#E4002B',
		'hispania'     => '#A7A9AC',
		'super_aguri'  => '#E60012',
		'spyker'       => '#F26522',
		'midland'      => '#E60012',
	);
}

/**
 * Three-letter nationality code.
 *
 * @param string $nationality Full nationality.
 * @return string
 */
function f1wiki_nationality_code( $nationality ) {
	$map = array(
		'British'        => 'GBR',
		'Italian'        => 'ITA',
		'German'         => 'GER',
		'French'         => 'FRA',
		'Dutch'          => 'NED',
		'Spanish'        => 'ESP',
		'Finnish'        => 'FIN',
		'Brazilian'      => 'BRA',
		'Australian'     => 'AUS',
		'Austrian'       => 'AUT',
		'Canadian'       => 'CAN',
		'Japanese'       => 'JPN',
		'Mexican'        => 'MEX',
		'Monegasque'     => 'MON',
		'Belgian'        => 'BEL',
		'Swiss'          => 'SUI',
		'Swedish'        => 'SWE',
		'Danish'         => 'DEN',
		'Thai'           => 'THA',
		'Chinese'        => 'CHN',
		'American'       => 'USA',
		'New Zealander'  => 'NZL',
		'South African'  => 'RSA',
		'Argentine'      => 'ARG',
		'Portuguese'     => 'POR',
		'Russian'        => 'RUS',
		'Polish'         => 'POL',
		'Czech'          => 'CZE',
		'Colombian'      => 'COL',
		'Venezuelan'     => 'VEN',
		'Indonesian'     => 'IDN',
		'Indian'         => 'IND',
		'Malaysian'      => 'MAS',
		'Hungarian'      => 'HUN',
		'Irish'          => 'IRL',
		'Uruguayan'      => 'URU',
		'Chilean'        => 'CHI',
		'Saudi'          => 'KSA',
		'Emirati'        => 'UAE',
		'Liechtensteiner' => 'LIE',
		'Rhodesian'      => 'RHO',
		'East German'    => 'GDR',
	);

	if ( isset( $map[ $nationality ] ) ) {
		return $map[ $nationality ];
	}

	return strtoupper( substr( (string) $nationality, 0, 3 ) );
}

/**
 * Format championship points.
 *
 * @param float|int $points Points.
 * @return string
 */
function f1wiki_format_points( $points ) {
	$points = (float) $points;
	if ( $points === floor( $points ) ) {
		return number_format_i18n( (int) $points );
	}
	return number_format_i18n( $points, 1 );
}

/**
 * Format a date string.
 *
 * @param string $date   Date.
 * @param string $format PHP date format.
 * @return string
 */
function f1wiki_format_date( $date, $format = 'j M Y' ) {
	if ( ! $date ) {
		return '';
	}
	$timestamp = strtotime( $date );
	if ( ! $timestamp ) {
		return (string) $date;
	}
	return date_i18n( $format, $timestamp );
}

/**
 * Human classification label.
 *
 * @param int|string $position Position.
 * @param string     $text     Position text.
 * @return string
 */
function f1wiki_result_position( $position, $text = '' ) {
	$text = strtoupper( (string) $text );
	if ( 'R' === $text ) {
		return 'DNF';
	}
	if ( 'D' === $text ) {
		return 'DSQ';
	}
	if ( 'E' === $text ) {
		return 'EXC';
	}
	if ( 'W' === $text ) {
		return 'WD';
	}
	if ( 'F' === $text ) {
		return 'DNQ';
	}
	if ( 'N' === $text ) {
		return 'NC';
	}
	if ( is_numeric( $position ) && (int) $position > 0 ) {
		return 'P' . (int) $position;
	}
	return '—';
}

/**
 * Driver number for display.
 *
 * @param int $driver_id Driver post ID.
 * @return string
 */
function f1wiki_driver_number( $driver_id ) {
	$number = get_post_meta( $driver_id, '_f1wiki_driver_number', true );
	if ( $number ) {
		return (string) $number;
	}
	if ( function_exists( 'get_field' ) ) {
		$number = get_field( 'driver_number', $driver_id );
	}
	return $number ? (string) $number : '';
}
