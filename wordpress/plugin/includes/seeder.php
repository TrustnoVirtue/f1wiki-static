<?php
/**
 * Jolpica F1 data importer.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seeds drivers, teams, circuits, races and seasons from the Jolpica API.
 */
class F1Wiki_Seeder {

	/**
	 * API client.
	 *
	 * @var F1Wiki_API_Client
	 */
	private $api;

	/**
	 * First season.
	 *
	 * @var int
	 */
	private $from;

	/**
	 * Last season.
	 *
	 * @var int
	 */
	private $to;

	/**
	 * Force content regeneration.
	 *
	 * @var bool
	 */
	private $force_content = false;

	/**
	 * Current year being imported.
	 *
	 * @var int
	 */
	private $working_year = 0;

	/**
	 * Driver post IDs keyed by Jolpica driver ID.
	 *
	 * @var array
	 */
	private $driver_ids = array();

	/**
	 * Team post IDs keyed by Jolpica constructor ID.
	 *
	 * @var array
	 */
	private $team_ids = array();

	/**
	 * Circuit post IDs keyed by Jolpica circuit ID.
	 *
	 * @var array
	 */
	private $circuit_ids = array();

	/**
	 * Driver metadata last updated year.
	 *
	 * @var array
	 */
	private $driver_year = array();

	/**
	 * Team metadata last updated year.
	 *
	 * @var array
	 */
	private $team_year = array();

	/**
	 * Constructor.
	 *
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function __construct( $assoc_args = array() ) {
		$this->from = isset( $assoc_args['from'] ) ? (int) $assoc_args['from'] : 2000;
		$this->to   = isset( $assoc_args['to'] ) ? (int) $assoc_args['to'] : (int) current_time( 'Y' );

		if ( $this->from < 1950 ) {
			$this->from = 1950;
		}
		if ( $this->to < $this->from ) {
			$this->to = $this->from;
		}

		$this->force_content = ! empty( $assoc_args['force-content'] );

		$this->api = new F1Wiki_API_Client(
			array(
				'use_cache' => ! isset( $assoc_args['no-cache'] ),
				'refresh'   => ! empty( $assoc_args['refresh'] ),
				'sleep'     => isset( $assoc_args['sleep'] ) ? (float) $assoc_args['sleep'] : 0.25,
			)
		);
	}

	/**
	 * Run the import.
	 */
	public function run() {
		WP_CLI::log( sprintf( 'Importing Formula 1 data from %d to %d.', $this->from, $this->to ) );

		update_option( 'f1wiki_seed_from', $this->from );
		update_option( 'f1wiki_seed_to', $this->to );

		for ( $year = $this->from; $year <= $this->to; $year++ ) {
			$this->working_year = $year;

			try {
				$this->seed_season( $year );
			} catch ( Exception $e ) {
				WP_CLI::warning( sprintf( 'Season %d skipped: %s', $year, $e->getMessage() ) );
			}
		}

		WP_CLI::log( 'Recalculating statistics and generated summaries...' );
		F1Wiki_Stats::recalculate_all( $this->force_content );
		f1wiki_refresh_site_options();

		$counts = f1wiki_get_counts();
		WP_CLI::success(
			sprintf(
				'Import complete: %d drivers, %d teams, %d circuits, %d races, %d seasons, %d result rows.',
				$counts['drivers'],
				$counts['teams'],
				$counts['circuits'],
				$counts['races'],
				$counts['seasons'],
				$counts['results']
			)
		);
	}

	/**
	 * Import one season.
	 *
	 * @param int $year Season year.
	 */
	private function seed_season( $year ) {
		WP_CLI::log( sprintf( '  %d: schedule', $year ) );

		$schedule = $this->api->schedule( $year );
		$results  = array();
		$drivers  = array();
		$teams    = array();

		try {
			$results = $this->api->results( $year );
		} catch ( Exception $e ) {
			WP_CLI::warning( sprintf( '%d results unavailable: %s', $year, $e->getMessage() ) );
		}

		try {
			$drivers = $this->api->driver_standings( $year );
		} catch ( Exception $e ) {
			WP_CLI::warning( sprintf( '%d driver standings unavailable: %s', $year, $e->getMessage() ) );
		}

		try {
			$teams = $this->api->constructor_standings( $year );
		} catch ( Exception $e ) {
			WP_CLI::warning( sprintf( '%d constructor standings unavailable: %s', $year, $e->getMessage() ) );
		}

		if ( empty( $schedule ) && empty( $results ) ) {
			WP_CLI::warning( sprintf( 'No schedule or results for %d.', $year ) );
			return;
		}

		$season_id = $this->ensure_season( $year );

		// Schedule races (including future rounds).
		$scheduled_rounds = array();
		foreach ( $schedule as $race ) {
			$round = isset( $race['round'] ) ? (int) $race['round'] : 0;
			if ( ! $round ) {
				continue;
			}
			$scheduled_rounds[ $round ] = $race;
			$this->ensure_race( $race, $year );
		}

		// Results races may include rounds missing from the schedule.
		$result_rounds = array();
		foreach ( $results as $race ) {
			$round = isset( $race['round'] ) ? (int) $race['round'] : 0;
			if ( ! $round ) {
				continue;
			}
			$result_rounds[ $round ] = $race;

			$race_id = $this->ensure_race( $race, $year );

			if ( ! empty( $race['Results'] ) ) {
				foreach ( $race['Results'] as $result ) {
					if ( ! empty( $result['Driver'] ) ) {
						$this->ensure_driver( $result['Driver'] );
					}
					if ( ! empty( $result['Constructor'] ) ) {
						$this->ensure_team( $result['Constructor'] );
					}
				}
			}

			$this->save_results( $race_id, $race, $year );
		}

		// Ensure standings entities exist before writing standings.
		foreach ( $drivers as $standing ) {
			if ( ! empty( $standing['Driver'] ) ) {
				$this->ensure_driver( $standing['Driver'] );
			}
			if ( ! empty( $standing['Constructors'] ) ) {
				foreach ( $standing['Constructors'] as $constructor ) {
					$this->ensure_team( $constructor );
				}
			}
		}
		foreach ( $teams as $standing ) {
			if ( ! empty( $standing['Constructor'] ) ) {
				$this->ensure_team( $standing['Constructor'] );
			}
		}

		$champions = $this->save_standings( $year, $drivers, $teams );

		$rounds_count = count( $scheduled_rounds );
		if ( ! $rounds_count && ! empty( $result_rounds ) ) {
			$rounds_count = max( array_keys( $result_rounds ) );
		}

		$completed = 0;
		foreach ( $results as $race ) {
			if ( ! empty( $race['Results'] ) ) {
				$completed++;
			}
		}

		$status = ( $rounds_count > 0 && $completed >= $rounds_count ) ? 'completed' : 'in_progress';

		update_post_meta( $season_id, '_f1wiki_rounds', $rounds_count );
		update_post_meta( $season_id, '_f1wiki_completed_rounds', $completed );
		update_post_meta( $season_id, '_f1wiki_season_status', $status );
		update_post_meta( $season_id, '_f1wiki_stats', array( 'rounds' => $rounds_count, 'completed' => $completed ) );

		if ( $champions['driver_id'] ) {
			update_post_meta( $season_id, '_f1wiki_champion_driver_id', $champions['driver_id'] );
		}
		if ( $champions['team_id'] ) {
			update_post_meta( $season_id, '_f1wiki_champion_team_id', $champions['team_id'] );
		}

		if ( function_exists( 'update_field' ) ) {
			update_field( 'season_year', $year, $season_id );
			update_field( 'rounds_count', $rounds_count, $season_id );
			update_field( 'completed_rounds', $completed, $season_id );
			update_field( 'season_status', $status, $season_id );
			update_field( 'champion_driver', $champions['driver_id'], $season_id );
			update_field( 'champion_team', $champions['team_id'], $season_id );
		}

		f1wiki_generate_season_content( $season_id, $this->force_content );

		WP_CLI::log(
			sprintf(
				'  %d: %d rounds, %d completed, %d result races',
				$year,
				$rounds_count,
				$completed,
				count( $results )
			)
		);
	}

	/**
	 * Upsert the season post.
	 *
	 * @param int $year Season year.
	 * @return int
	 */
	private function ensure_season( $year ) {
		$source_id = 'season:' . (int) $year;
		$title     = sprintf( '%d Formula One season', $year );

		$post_id = f1wiki_get_post_by_source( $source_id );

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'season',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => (string) $year,
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				throw new RuntimeException( $post_id->get_error_message() );
			}
		} else {
			$current = get_the_title( $post_id );
			if ( $current !== $title ) {
				wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
			}
		}

		update_post_meta( $post_id, '_f1wiki_source_id', $source_id );
		update_post_meta( $post_id, '_f1wiki_season_year', (int) $year );
		update_post_meta( $post_id, '_f1wiki_year_sort', (int) $year );

		if ( function_exists( 'update_field' ) ) {
			update_field( 'season_year', (int) $year, $post_id );
		}

		return (int) $post_id;
	}

	/**
	 * Upsert a circuit.
	 *
	 * @param array $data Circuit data from the API.
	 * @return int
	 */
	private function ensure_circuit( $data ) {
		if ( empty( $data['circuitId'] ) ) {
			return 0;
		}

		$circuit_id = $data['circuitId'];

		if ( isset( $this->circuit_ids[ $circuit_id ] ) ) {
			return $this->circuit_ids[ $circuit_id ];
		}

		$source_id = 'circuit:' . $circuit_id;
		$title     = isset( $data['circuitName'] ) ? $data['circuitName'] : $circuit_id;
		$post_id   = f1wiki_get_post_by_source( $source_id );

		$location = isset( $data['Location'] ) && is_array( $data['Location'] ) ? $data['Location'] : array();

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'circuit',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => sanitize_title( $circuit_id ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				throw new RuntimeException( $post_id->get_error_message() );
			}
		} elseif ( get_the_title( $post_id ) !== $title ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
		}

		update_post_meta( $post_id, '_f1wiki_source_id', $source_id );
		update_post_meta( $post_id, '_f1wiki_locality', isset( $location['locality'] ) ? $location['locality'] : '' );
		update_post_meta( $post_id, '_f1wiki_country', isset( $location['country'] ) ? $location['country'] : '' );
		update_post_meta( $post_id, '_f1wiki_latitude', isset( $location['lat'] ) ? $location['lat'] : '' );
		update_post_meta( $post_id, '_f1wiki_longitude', isset( $location['long'] ) ? $location['long'] : '' );
		update_post_meta( $post_id, '_f1wiki_wikipedia', isset( $data['url'] ) ? $data['url'] : '' );

		if ( function_exists( 'update_field' ) ) {
			update_field( 'locality', isset( $location['locality'] ) ? $location['locality'] : '', $post_id );
			update_field( 'country', isset( $location['country'] ) ? $location['country'] : '', $post_id );
			update_field( 'latitude', isset( $location['lat'] ) ? (float) $location['lat'] : '', $post_id );
			update_field( 'longitude', isset( $location['long'] ) ? (float) $location['long'] : '', $post_id );
			update_field( 'wikipedia_url', isset( $data['url'] ) ? $data['url'] : '', $post_id );
		}

		$this->circuit_ids[ $circuit_id ] = (int) $post_id;
		return (int) $post_id;
	}

	/**
	 * Upsert a driver.
	 *
	 * @param array $data Driver data from the API.
	 * @return int
	 */
	private function ensure_driver( $data ) {
		if ( empty( $data['driverId'] ) ) {
			return 0;
		}

		$driver_id = $data['driverId'];

		if ( isset( $this->driver_ids[ $driver_id ] ) && isset( $this->driver_year[ $driver_id ] ) && $this->driver_year[ $driver_id ] >= $this->working_year ) {
			return $this->driver_ids[ $driver_id ];
		}

		$given   = isset( $data['givenName'] ) ? $data['givenName'] : '';
		$family  = isset( $data['familyName'] ) ? $data['familyName'] : '';
		$title   = trim( $given . ' ' . $family );
		$title   = $title ? $title : $driver_id;
		$post_id = f1wiki_get_post_by_source( 'driver:' . $driver_id );

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'driver',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => sanitize_title( $title ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				throw new RuntimeException( $post_id->get_error_message() );
			}
		} elseif ( get_the_title( $post_id ) !== $title ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
		}

		$code   = isset( $data['code'] ) ? $data['code'] : '';
		$number = isset( $data['permanentNumber'] ) ? $data['permanentNumber'] : '';
		$dob    = isset( $data['dateOfBirth'] ) ? $data['dateOfBirth'] : '';
		$nat    = isset( $data['nationality'] ) ? $data['nationality'] : '';
		$url    = isset( $data['url'] ) ? $data['url'] : '';

		update_post_meta( $post_id, '_f1wiki_source_id', 'driver:' . $driver_id );
		update_post_meta( $post_id, '_f1wiki_nationality', $nat );
		if ( '' !== (string) $number ) {
			update_post_meta( $post_id, '_f1wiki_driver_number', (string) $number );
		}
		if ( '' !== (string) $code ) {
			update_post_meta( $post_id, '_f1wiki_driver_code', (string) $code );
		}
		update_post_meta( $post_id, '_f1wiki_dob', $dob );
		update_post_meta( $post_id, '_f1wiki_wikipedia', $url );

		if ( function_exists( 'update_field' ) ) {
			if ( '' !== (string) $code ) {
				update_field( 'driver_code', (string) $code, $post_id );
			}
			if ( '' !== (string) $number ) {
				update_field( 'driver_number', (int) $number, $post_id );
			}
			if ( $dob ) {
				update_field( 'date_of_birth', $dob, $post_id );
			}
			update_field( 'nationality', $nat, $post_id );
			update_field( 'wikipedia_url', $url, $post_id );
		}

		$this->driver_ids[ $driver_id ] = (int) $post_id;
		$this->driver_year[ $driver_id ] = $this->working_year;

		return (int) $post_id;
	}

	/**
	 * Upsert a team.
	 *
	 * @param array $data Constructor data from the API.
	 * @return int
	 */
	private function ensure_team( $data ) {
		if ( empty( $data['constructorId'] ) ) {
			return 0;
		}

		$constructor_id = $data['constructorId'];
		$title          = isset( $data['name'] ) ? $data['name'] : $constructor_id;
		$post_id        = isset( $this->team_ids[ $constructor_id ] ) ? $this->team_ids[ $constructor_id ] : 0;

		if ( ! $post_id ) {
			$post_id = f1wiki_get_post_by_source( 'team:' . $constructor_id );
		}

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'team',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => sanitize_title( $constructor_id ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				throw new RuntimeException( $post_id->get_error_message() );
			}
		} else {
			$current = get_the_title( $post_id );
			if ( $current && $current !== $title && ( ! isset( $this->team_year[ $constructor_id ] ) || $this->team_year[ $constructor_id ] < $this->working_year ) ) {
				wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
			}
		}

		$updated_year = isset( $this->team_year[ $constructor_id ] ) ? $this->team_year[ $constructor_id ] : 0;

		if ( $updated_year < $this->working_year ) {
			$colors = f1wiki_team_color_map();
			$color  = isset( $colors[ $constructor_id ] ) ? $colors[ $constructor_id ] : f1wiki_fallback_color( $constructor_id );
			$existing_color = get_post_meta( $post_id, '_f1wiki_team_color', true );

			update_post_meta( $post_id, '_f1wiki_source_id', 'team:' . $constructor_id );
			update_post_meta( $post_id, '_f1wiki_nationality', isset( $data['nationality'] ) ? $data['nationality'] : '' );
			update_post_meta( $post_id, '_f1wiki_wikipedia', isset( $data['url'] ) ? $data['url'] : '' );

			if ( ! $existing_color ) {
				update_post_meta( $post_id, '_f1wiki_team_color', $color );
			}

			if ( function_exists( 'update_field' ) ) {
				if ( ! $existing_color ) {
					update_field( 'team_color', $color, $post_id );
				}
				update_field( 'nationality', isset( $data['nationality'] ) ? $data['nationality'] : '', $post_id );
				update_field( 'wikipedia_url', isset( $data['url'] ) ? $data['url'] : '', $post_id );
			}

			$this->team_year[ $constructor_id ] = $this->working_year;
		}

		$this->team_ids[ $constructor_id ] = (int) $post_id;

		return (int) $post_id;
	}

	/**
	 * Upsert a race.
	 *
	 * @param array $race Race data.
	 * @param int   $year Season.
	 * @return int
	 */
	private function ensure_race( $race, $year ) {
		$round = isset( $race['round'] ) ? (int) $race['round'] : 0;
		if ( ! $round ) {
			return 0;
		}

		$source_id  = 'race:' . (int) $year . ':' . $round;
		$race_name  = isset( $race['raceName'] ) ? $race['raceName'] : 'Grand Prix';
		$title      = sprintf( '%d %s', $year, $race_name );
		$date       = isset( $race['date'] ) ? $race['date'] : '';
		$circuit_id = 0;

		if ( ! empty( $race['Circuit'] ) ) {
			$circuit_id = $this->ensure_circuit( $race['Circuit'] );
		}

		$post_id = f1wiki_get_post_by_source( $source_id );

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'race',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => sanitize_title( $title ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				throw new RuntimeException( $post_id->get_error_message() );
			}
		} else {
			$current = get_the_title( $post_id );
			if ( $current !== $title ) {
				wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
			}
		}

		update_post_meta( $post_id, '_f1wiki_source_id', $source_id );
		update_post_meta( $post_id, '_f1wiki_season_year', (int) $year );
		update_post_meta( $post_id, '_f1wiki_round', $round );
		update_post_meta( $post_id, '_f1wiki_race_date', $date );
		update_post_meta( $post_id, '_f1wiki_circuit_id', $circuit_id );
		update_post_meta( $post_id, '_f1wiki_wikipedia', isset( $race['url'] ) ? $race['url'] : '' );

		if ( function_exists( 'update_field' ) ) {
			update_field( 'season_year', (int) $year, $post_id );
			update_field( 'round', $round, $post_id );
			if ( $date ) {
				update_field( 'race_date', $date, $post_id );
			}
			update_field( 'circuit', $circuit_id, $post_id );
			update_field( 'wikipedia_url', isset( $race['url'] ) ? $race['url'] : '', $post_id );
		}

		return (int) $post_id;
	}

	/**
	 * Replace a race classification and update its summary metadata.
	 *
	 * @param int   $race_id Race post ID.
	 * @param array $race    Race data with Results.
	 * @param int   $year    Season.
	 */
	private function save_results( $race_id, $race, $year ) {
		global $wpdb;

		$race_id = (int) $race_id;
		if ( ! $race_id ) {
			return;
		}

		$table  = f1wiki_results_table();
		$round  = isset( $race['round'] ) ? (int) $race['round'] : (int) get_post_meta( $race_id, '_f1wiki_round', true );
		$circuit_id = (int) get_post_meta( $race_id, '_f1wiki_circuit_id', true );

		$wpdb->delete( $table, array( 'race_id' => $race_id ), array( '%d' ) );

		$results = isset( $race['Results'] ) && is_array( $race['Results'] ) ? $race['Results'] : array();

		if ( empty( $results ) ) {
			update_post_meta( $race_id, '_f1wiki_has_results', '0' );
			delete_post_meta( $race_id, '_f1wiki_winner_driver_id' );
			delete_post_meta( $race_id, '_f1wiki_winner_team_id' );
			delete_post_meta( $race_id, '_f1wiki_pole_driver_id' );
			delete_post_meta( $race_id, '_f1wiki_fastest_lap_driver_id' );

			if ( function_exists( 'update_field' ) ) {
				update_field( 'has_results', 0, $race_id );
			}

			f1wiki_generate_race_content( $race_id, $this->force_content );
			return;
		}

		$winner_driver = 0;
		$winner_team   = 0;
		$pole_driver   = 0;
		$fastest_driver = 0;
		$order         = 0;

		foreach ( $results as $result ) {
			$order++;

			$driver_id = ! empty( $result['Driver']['driverId'] ) ? $this->ensure_driver( $result['Driver'] ) : 0;
			$team_id   = ! empty( $result['Constructor']['constructorId'] ) ? $this->ensure_team( $result['Constructor'] ) : 0;

			if ( ! $driver_id || ! $team_id ) {
				continue;
			}

			$position      = isset( $result['position'] ) && is_numeric( $result['position'] ) ? (int) $result['position'] : null;
			$position_text = isset( $result['positionText'] ) ? (string) $result['positionText'] : ( $position ? (string) $position : '' );
			$grid          = isset( $result['grid'] ) ? (int) $result['grid'] : 0;
			$fastest       = isset( $result['FastestLap'] ) && is_array( $result['FastestLap'] ) ? $result['FastestLap'] : array();
			$fastest_rank  = isset( $fastest['rank'] ) ? (int) $fastest['rank'] : null;
			$fastest_time  = isset( $fastest['Time']['time'] ) ? (string) $fastest['Time']['time'] : '';
			$fastest_speed = '';
			if ( isset( $fastest['AverageSpeed'] ) ) {
				$unit = isset( $fastest['AverageSpeed']['units'] ) ? $fastest['AverageSpeed']['units'] : '';
				$val  = isset( $fastest['AverageSpeed']['speed'] ) ? $fastest['AverageSpeed']['speed'] : '';
				$fastest_speed = trim( $val . ( $val && $unit ? ' ' . $unit : '' ) );
			}

			$inserted = $wpdb->insert(
				$table,
				array(
					'race_id'          => $race_id,
					'season_year'      => (int) $year,
					'round'            => $round,
					'circuit_id'       => $circuit_id,
					'driver_id'        => $driver_id,
					'team_id'          => $team_id,
					'result_order'     => $order,
					'grid'             => $grid,
					'position'         => $position,
					'position_text'    => $position_text,
					'points'           => isset( $result['points'] ) ? (float) $result['points'] : 0,
					'laps'             => isset( $result['laps'] ) ? (int) $result['laps'] : 0,
					'status'           => isset( $result['status'] ) ? (string) $result['status'] : '',
					'car_number'       => isset( $result['number'] ) ? (string) $result['number'] : '',
					'fastest_lap_rank' => $fastest_rank,
					'fastest_lap_time' => $fastest_time,
					'fastest_lap_speed' => $fastest_speed,
				),
				array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%f', '%d', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( ! $inserted ) {
				WP_CLI::warning( sprintf( 'Could not insert result for race %d driver %d: %s', $race_id, $driver_id, $wpdb->last_error ) );
			}

			if ( ( '1' === $position_text || 1 === $position ) && ! $winner_driver ) {
				$winner_driver = $driver_id;
				$winner_team   = $team_id;
			}
			if ( 1 === $grid && ! $pole_driver ) {
				$pole_driver = $driver_id;
			}
			if ( 1 === $fastest_rank && ! $fastest_driver ) {
				$fastest_driver = $driver_id;
			}
		}

		update_post_meta( $race_id, '_f1wiki_has_results', '1' );
		update_post_meta( $race_id, '_f1wiki_winner_driver_id', $winner_driver );
		update_post_meta( $race_id, '_f1wiki_winner_team_id', $winner_team );
		update_post_meta( $race_id, '_f1wiki_pole_driver_id', $pole_driver );
		update_post_meta( $race_id, '_f1wiki_fastest_lap_driver_id', $fastest_driver );

		if ( function_exists( 'update_field' ) ) {
			update_field( 'has_results', 1, $race_id );
			update_field( 'winner_driver', $winner_driver, $race_id );
			update_field( 'winner_team', $winner_team, $race_id );
			update_field( 'pole_driver', $pole_driver, $race_id );
			update_field( 'fastest_lap_driver', $fastest_driver, $race_id );
		}

		f1wiki_generate_race_content( $race_id, $this->force_content );
	}

	/**
	 * Replace season standings.
	 *
	 * @param int   $year        Season.
	 * @param array $drivers     Driver standings.
	 * @param array $teams       Constructor standings.
	 * @return array Champion/leader IDs.
	 */
	private function save_standings( $year, $drivers, $teams ) {
		global $wpdb;

		$driver_table = f1wiki_driver_standings_table();
		$team_table   = f1wiki_team_standings_table();

		$wpdb->delete( $driver_table, array( 'season_year' => (int) $year ), array( '%d' ) );
		$wpdb->delete( $team_table, array( 'season_year' => (int) $year ), array( '%d' ) );

		$champion_driver = 0;
		$champion_team   = 0;

		foreach ( $drivers as $standing ) {
			$driver_id = ! empty( $standing['Driver']['driverId'] ) ? $this->ensure_driver( $standing['Driver'] ) : 0;
			if ( ! $driver_id ) {
				continue;
			}

			$team_id = 0;
			if ( ! empty( $standing['Constructors'] ) && is_array( $standing['Constructors'] ) ) {
				foreach ( $standing['Constructors'] as $constructor ) {
					$candidate = $this->ensure_team( $constructor );
					if ( $candidate ) {
						$team_id = $candidate;
					}
				}
			}

			$position = isset( $standing['position'] ) && is_numeric( $standing['position'] ) ? (int) $standing['position'] : 0;

			$wpdb->insert(
				$driver_table,
				array(
					'season_year' => (int) $year,
					'driver_id'   => $driver_id,
					'team_id'     => $team_id,
					'position'    => $position,
					'points'      => isset( $standing['points'] ) ? (float) $standing['points'] : 0,
					'wins'        => isset( $standing['wins'] ) ? (int) $standing['wins'] : 0,
				),
				array( '%d', '%d', '%d', '%d', '%f', '%d' )
			);

			if ( 1 === $position && ! $champion_driver ) {
				$champion_driver = $driver_id;
			}
		}

		foreach ( $teams as $standing ) {
			$team_id = ! empty( $standing['Constructor']['constructorId'] ) ? $this->ensure_team( $standing['Constructor'] ) : 0;
			if ( ! $team_id ) {
				continue;
			}

			$position = isset( $standing['position'] ) && is_numeric( $standing['position'] ) ? (int) $standing['position'] : 0;

			$wpdb->insert(
				$team_table,
				array(
					'season_year' => (int) $year,
					'team_id'     => $team_id,
					'position'    => $position,
					'points'      => isset( $standing['points'] ) ? (float) $standing['points'] : 0,
					'wins'        => isset( $standing['wins'] ) ? (int) $standing['wins'] : 0,
				),
				array( '%d', '%d', '%d', '%f', '%d' )
			);

			if ( 1 === $position && ! $champion_team ) {
				$champion_team = $team_id;
			}
		}

		return array(
			'driver_id' => $champion_driver,
			'team_id'   => $champion_team,
		);
	}
}
