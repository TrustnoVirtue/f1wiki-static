<?php
/**
 * Aggregate statistics from imported results and standings.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recalculates career and archive statistics.
 */
class F1Wiki_Stats {

	/**
	 * Log a message when running under WP-CLI.
	 *
	 * @param string $message Message.
	 */
	private static function log( $message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
		}
	}

	/**
	 * Recalculate everything.
	 *
	 * @param bool $force_content Overwrite manually edited content.
	 */
	public static function recalculate_all( $force_content = false ) {
		self::recalculate_drivers();
		self::recalculate_teams();
		self::recalculate_circuits();
		f1wiki_refresh_site_options();
		f1wiki_generate_all_content( $force_content );
	}

	/**
	 * Driver career totals from results and standings.
	 */
	private static function recalculate_drivers() {
		global $wpdb;

		$results = f1wiki_results_table();
		$standings = f1wiki_driver_standings_table();

		$rows = $wpdb->get_results(
			"SELECT driver_id,
				COUNT(*) AS starts,
				SUM(CASE WHEN position_text = '1' THEN 1 ELSE 0 END) AS wins,
				SUM(CASE WHEN position_text IN ('1','2','3') THEN 1 ELSE 0 END) AS podiums,
				SUM(CASE WHEN fastest_lap_rank = 1 THEN 1 ELSE 0 END) AS fastest_laps,
				SUM(CASE WHEN grid = 1 THEN 1 ELSE 0 END) AS poles,
				SUM(points) AS points,
				MIN(season_year) AS first_season,
				MAX(season_year) AS last_season
			FROM {$results}
			GROUP BY driver_id" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$championships = array();
		$champ_rows    = $wpdb->get_results(
			"SELECT driver_id, season_year FROM {$standings} WHERE position = 1 ORDER BY season_year ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $champ_rows as $row ) {
			$championships[ (int) $row->driver_id ][] = (int) $row->season_year;
		}

		$best = array();
		$best_rows = $wpdb->get_results(
			"SELECT driver_id, position, season_year FROM {$standings} WHERE position > 0 ORDER BY position ASC, season_year ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $best_rows as $row ) {
			$driver_id = (int) $row->driver_id;
			if ( ! isset( $best[ $driver_id ] ) ) {
				$best[ $driver_id ] = array(
					'position' => (int) $row->position,
					'year'     => (int) $row->season_year,
				);
			}
		}

		$latest_teams = array();
		$team_rows    = $wpdb->get_results(
			"SELECT r.driver_id, r.team_id, r.season_year
			FROM {$results} r
			INNER JOIN (
				SELECT driver_id, MAX(season_year) AS latest_year
				FROM {$results}
				GROUP BY driver_id
			) latest ON latest.driver_id = r.driver_id AND latest.latest_year = r.season_year
			ORDER BY r.driver_id ASC, r.round DESC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $team_rows as $row ) {
			$driver_id = (int) $row->driver_id;
			if ( ! isset( $latest_teams[ $driver_id ] ) ) {
				$latest_teams[ $driver_id ] = array(
					'team_id' => (int) $row->team_id,
					'year'    => (int) $row->season_year,
				);
			}
		}

		foreach ( $rows as $row ) {
			$driver_id = (int) $row->driver_id;
			$stats     = array(
				'starts'                 => (int) $row->starts,
				'wins'                   => (int) $row->wins,
				'podiums'                => (int) $row->podiums,
				'poles'                  => (int) $row->poles,
				'fastest_laps'           => (int) $row->fastest_laps,
				'points'                 => (float) $row->points,
				'championships'          => isset( $championships[ $driver_id ] ) ? count( $championships[ $driver_id ] ) : 0,
				'first_season'           => (int) $row->first_season,
				'last_season'            => (int) $row->last_season,
				'best_championship'      => isset( $best[ $driver_id ] ) ? $best[ $driver_id ]['position'] : 0,
				'best_championship_year' => isset( $best[ $driver_id ] ) ? $best[ $driver_id ]['year'] : 0,
				'championship_years'     => isset( $championships[ $driver_id ] ) ? $championships[ $driver_id ] : array(),
			);

			update_post_meta( $driver_id, '_f1wiki_stats', $stats );
			update_post_meta( $driver_id, '_f1wiki_wins_sort', $stats['wins'] );
			update_post_meta( $driver_id, '_f1wiki_titles_sort', $stats['championships'] );
			update_post_meta( $driver_id, '_f1wiki_seasons_sort', $stats['last_season'] );
			update_post_meta( $driver_id, '_f1wiki_starts_sort', $stats['starts'] );

			if ( isset( $latest_teams[ $driver_id ] ) ) {
				update_post_meta( $driver_id, '_f1wiki_current_team', $latest_teams[ $driver_id ]['team_id'] );
				update_post_meta( $driver_id, '_f1wiki_latest_team_year', $latest_teams[ $driver_id ]['year'] );
				if ( function_exists( 'update_field' ) ) {
					update_field( 'current_team', $latest_teams[ $driver_id ]['team_id'], $driver_id );
				}
			}

			if ( function_exists( 'update_field' ) ) {
				update_field( 'career_stats', $stats, $driver_id );
			}
		}

		self::log( sprintf( '     updated stats for %d drivers', count( $rows ) ) );
	}

	/**
	 * Team archive totals.
	 */
	private static function recalculate_teams() {
		global $wpdb;

		$results = f1wiki_results_table();
		$standings = f1wiki_team_standings_table();

		$rows = $wpdb->get_results(
			"SELECT team_id,
				COUNT(*) AS starts,
				SUM(CASE WHEN position_text = '1' THEN 1 ELSE 0 END) AS wins,
				SUM(CASE WHEN position_text IN ('1','2','3') THEN 1 ELSE 0 END) AS podiums,
				SUM(CASE WHEN grid = 1 THEN 1 ELSE 0 END) AS poles,
				SUM(points) AS points,
				MIN(season_year) AS first_season,
				MAX(season_year) AS last_season
			FROM {$results}
			GROUP BY team_id" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$championships = array();
		$champ_rows    = $wpdb->get_results(
			"SELECT team_id, season_year FROM {$standings} WHERE position = 1 ORDER BY season_year ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $champ_rows as $row ) {
			$championships[ (int) $row->team_id ][] = (int) $row->season_year;
		}

		foreach ( $rows as $row ) {
			$team_id = (int) $row->team_id;
			$stats   = array(
				'starts'             => (int) $row->starts,
				'wins'               => (int) $row->wins,
				'podiums'            => (int) $row->podiums,
				'poles'              => (int) $row->poles,
				'points'             => (float) $row->points,
				'championships'      => isset( $championships[ $team_id ] ) ? count( $championships[ $team_id ] ) : 0,
				'first_season'       => (int) $row->first_season,
				'last_season'        => (int) $row->last_season,
				'championship_years' => isset( $championships[ $team_id ] ) ? $championships[ $team_id ] : array(),
			);

			update_post_meta( $team_id, '_f1wiki_stats', $stats );
			update_post_meta( $team_id, '_f1wiki_wins_sort', $stats['wins'] );
			update_post_meta( $team_id, '_f1wiki_titles_sort', $stats['championships'] );
			update_post_meta( $team_id, '_f1wiki_seasons_sort', $stats['last_season'] );

			if ( function_exists( 'update_field' ) ) {
				$acf_stats = $stats;
				$acf_stats['championship_years'] = implode( ', ', $stats['championship_years'] );
				update_field( 'team_stats', $acf_stats, $team_id );
			}
		}

		self::log( sprintf( '     updated stats for %d teams', count( $rows ) ) );
	}

	/**
	 * Circuit archive totals.
	 */
	private static function recalculate_circuits() {
		global $wpdb;

		$results = f1wiki_results_table();

		$rows = $wpdb->get_results(
			"SELECT circuit_id,
				COUNT(DISTINCT race_id) AS races,
				MIN(season_year) AS first_season,
				MAX(season_year) AS last_season
			FROM {$results}
			GROUP BY circuit_id" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$driver_wins = array();
		$driver_rows = $wpdb->get_results(
			"SELECT circuit_id, driver_id, COUNT(*) AS wins
			FROM {$results}
			WHERE position_text = '1'
			GROUP BY circuit_id, driver_id
			ORDER BY circuit_id ASC, wins DESC, driver_id ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $driver_rows as $row ) {
			$circuit_id = (int) $row->circuit_id;
			if ( ! isset( $driver_wins[ $circuit_id ] ) ) {
				$driver_wins[ $circuit_id ] = array(
					'id'   => (int) $row->driver_id,
					'name' => get_the_title( (int) $row->driver_id ),
					'wins' => (int) $row->wins,
				);
			}
		}

		$team_wins = array();
		$team_rows = $wpdb->get_results(
			"SELECT circuit_id, team_id, COUNT(*) AS wins
			FROM {$results}
			WHERE position_text = '1'
			GROUP BY circuit_id, team_id
			ORDER BY circuit_id ASC, wins DESC, team_id ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $team_rows as $row ) {
			$circuit_id = (int) $row->circuit_id;
			if ( ! isset( $team_wins[ $circuit_id ] ) ) {
				$team_wins[ $circuit_id ] = array(
					'id'   => (int) $row->team_id,
					'name' => get_the_title( (int) $row->team_id ),
					'wins' => (int) $row->wins,
				);
			}
		}

		foreach ( $rows as $row ) {
			$circuit_id = (int) $row->circuit_id;
			$stats      = array(
				'races'                 => (int) $row->races,
				'first_season'          => (int) $row->first_season,
				'last_season'           => (int) $row->last_season,
				'most_wins_driver'      => isset( $driver_wins[ $circuit_id ] ) ? $driver_wins[ $circuit_id ]['name'] : '',
				'most_wins_driver_wins' => isset( $driver_wins[ $circuit_id ] ) ? $driver_wins[ $circuit_id ]['wins'] : 0,
				'most_wins_team'        => isset( $team_wins[ $circuit_id ] ) ? $team_wins[ $circuit_id ]['name'] : '',
				'most_wins_team_wins'   => isset( $team_wins[ $circuit_id ] ) ? $team_wins[ $circuit_id ]['wins'] : 0,
			);

			update_post_meta( $circuit_id, '_f1wiki_stats', $stats );
			update_post_meta( $circuit_id, '_f1wiki_races_sort', $stats['races'] );

			if ( function_exists( 'update_field' ) ) {
				update_field( 'circuit_stats', $stats, $circuit_id );
			}
		}

		self::log( sprintf( '     updated stats for %d circuits', count( $rows ) ) );
	}
}

/**
 * Refresh site-wide options and counts.
 */
function f1wiki_refresh_site_options() {
	global $wpdb;

	$results   = f1wiki_results_table();
	$standings = f1wiki_driver_standings_table();

	$latest = (int) $wpdb->get_var( "SELECT MAX(season_year) FROM {$standings}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! $latest ) {
		$latest = (int) $wpdb->get_var( "SELECT MAX(season_year) FROM {$results}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	if ( ! $latest ) {
		$latest = (int) current_time( 'Y' );
	}

	$first = (int) $wpdb->get_var( "SELECT MIN(season_year) FROM {$results}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( $first && ! get_option( 'f1wiki_seed_from' ) ) {
		update_option( 'f1wiki_seed_from', $first );
	}
	if ( ! get_option( 'f1wiki_seed_to' ) ) {
		update_option( 'f1wiki_seed_to', $latest );
	}

	update_option( 'f1wiki_latest_season', $latest );
	update_option(
		'f1wiki_counts',
		array(
			'drivers'  => (int) wp_count_posts( 'driver' )->publish,
			'teams'    => (int) wp_count_posts( 'team' )->publish,
			'circuits' => (int) wp_count_posts( 'circuit' )->publish,
			'races'    => (int) wp_count_posts( 'race' )->publish,
			'seasons'  => (int) wp_count_posts( 'season' )->publish,
			'results'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$results}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		)
	);
}
