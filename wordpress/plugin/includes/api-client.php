<?php
/**
 * Jolpica F1 API client with disk caching.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * API client.
 */
class F1Wiki_API_Client {

	/**
	 * API base URL.
	 */
	const BASE_URL = 'https://api.jolpi.ca/ergast/f1';

	/**
	 * Cache directory.
	 *
	 * @var string
	 */
	private $cache_dir = '';

	/**
	 * Whether to read the cache.
	 *
	 * @var bool
	 */
	private $use_cache = true;

	/**
	 * Whether to ignore existing cache entries.
	 *
	 * @var bool
	 */
	private $refresh = false;

	/**
	 * Delay between network requests.
	 *
	 * @var float
	 */
	private $sleep = 0.25;

	/**
	 * Cache lifetime in seconds.
	 *
	 * @var int
	 */
	private $ttl = 604800;

	/**
	 * Constructor.
	 *
	 * @param array $args Optional args: use_cache, refresh, sleep, ttl.
	 */
	public function __construct( $args = array() ) {
		$uploads = wp_upload_dir();
		$this->cache_dir = trailingslashit( $uploads['basedir'] ) . 'f1wiki-cache';

		if ( isset( $args['use_cache'] ) ) {
			$this->use_cache = (bool) $args['use_cache'];
		}
		if ( isset( $args['refresh'] ) ) {
			$this->refresh = (bool) $args['refresh'];
		}
		if ( isset( $args['sleep'] ) ) {
			$this->sleep = max( 0, (float) $args['sleep'] );
		}
		if ( isset( $args['ttl'] ) ) {
			$this->ttl = max( 0, (int) $args['ttl'] );
		}

		if ( $this->use_cache && ! is_dir( $this->cache_dir ) ) {
			wp_mkdir_p( $this->cache_dir );
		}
	}

	/**
	 * Fetch a season schedule.
	 *
	 * @param int $year Season.
	 * @return array
	 */
	public function schedule( $year ) {
		$data = $this->request( '/' . (int) $year . '.json', array( 'limit' => 100 ) );
		return isset( $data['MRData']['RaceTable']['Races'] ) ? $data['MRData']['RaceTable']['Races'] : array();
	}

	/**
	 * Fetch all race results for a season.
	 *
	 * @param int $year Season.
	 * @return array Races keyed by round, with merged Results arrays.
	 */
	public function results( $year ) {
		$pages  = $this->request_paginated( '/' . (int) $year . '/results.json' );
		$merged = array();

		foreach ( $pages as $page ) {
			$races = isset( $page['RaceTable']['Races'] ) ? $page['RaceTable']['Races'] : array();

			foreach ( $races as $race ) {
				$round = isset( $race['round'] ) ? (int) $race['round'] : 0;
				if ( ! $round ) {
					continue;
				}

				if ( ! isset( $merged[ $round ] ) ) {
					$merged[ $round ] = $race;
					continue;
				}

				if ( ! empty( $race['Results'] ) ) {
					if ( empty( $merged[ $round ]['Results'] ) ) {
						$merged[ $round ]['Results'] = array();
					}

					$seen = array();
					foreach ( $merged[ $round ]['Results'] as $existing ) {
						if ( isset( $existing['Driver']['driverId'] ) ) {
							$seen[ $existing['Driver']['driverId'] ] = true;
						}
					}

					foreach ( $race['Results'] as $result ) {
						$driver_id = isset( $result['Driver']['driverId'] ) ? $result['Driver']['driverId'] : '';
						if ( $driver_id && ! isset( $seen[ $driver_id ] ) ) {
							$merged[ $round ]['Results'][] = $result;
						}
					}
				}

				foreach ( array( 'Circuit', 'date', 'time', 'raceName', 'url' ) as $field ) {
					if ( empty( $merged[ $round ][ $field ] ) && ! empty( $race[ $field ] ) ) {
						$merged[ $round ][ $field ] = $race[ $field ];
					}
				}
			}
		}

		ksort( $merged );
		return array_values( $merged );
	}

	/**
	 * Driver standings for a season.
	 *
	 * @param int $year Season.
	 * @return array
	 */
	public function driver_standings( $year ) {
		$data = $this->request( '/' . (int) $year . '/driverStandings.json', array( 'limit' => 100 ) );

		if ( empty( $data['MRData']['StandingsTable']['StandingsLists'][0] ) ) {
			return array();
		}

		$list = $data['MRData']['StandingsTable']['StandingsLists'][0];
		return isset( $list['DriverStandings'] ) ? $list['DriverStandings'] : array();
	}

	/**
	 * Constructor standings for a season.
	 *
	 * @param int $year Season.
	 * @return array
	 */
	public function constructor_standings( $year ) {
		$data = $this->request( '/' . (int) $year . '/constructorStandings.json', array( 'limit' => 100 ) );

		if ( empty( $data['MRData']['StandingsTable']['StandingsLists'][0] ) ) {
			return array();
		}

		$list = $data['MRData']['StandingsTable']['StandingsLists'][0];
		return isset( $list['ConstructorStandings'] ) ? $list['ConstructorStandings'] : array();
	}

	/**
	 * Fetch a paginated endpoint.
	 *
	 * @param string $path Endpoint path.
	 * @return array List of MRData arrays.
	 */
	private function request_paginated( $path ) {
		$pages  = array();
		$offset = 0;
		$total  = 0;
		$limit  = 100;

		do {
			$data = $this->request(
				$path,
				array(
					'limit'  => $limit,
					'offset' => $offset,
				)
			);

			if ( empty( $data['MRData'] ) ) {
				break;
			}

			$meta  = $data['MRData'];
			$total = isset( $meta['total'] ) ? (int) $meta['total'] : 0;
			$pages[] = $meta;

			$offset += $limit;

			if ( count( $pages ) >= 100 ) {
				break;
			}
		} while ( $offset < $total );

		return $pages;
	}

	/**
	 * Fetch JSON with caching and retries.
	 *
	 * @param string $path   Endpoint path.
	 * @param array  $params Query parameters.
	 * @return array
	 * @throws RuntimeException On unrecoverable error.
	 */
	private function request( $path, $params = array() ) {
		$url = self::BASE_URL . $path;
		if ( $params ) {
			$url = add_query_arg( $params, $url );
		}

		$cache_file = trailingslashit( $this->cache_dir ) . md5( $url ) . '.json';

		if ( $this->use_cache && ! $this->refresh && is_readable( $cache_file ) ) {
			$age = time() - (int) filemtime( $cache_file );
			if ( $age <= $this->ttl ) {
				$cached = json_decode( (string) file_get_contents( $cache_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( is_array( $cached ) ) {
					return $cached;
				}
			}
		}

		$body = '';
		$code = 0;

		for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 60,
					'headers' => array(
						'Accept'     => 'application/json',
						'User-Agent' => 'F1Wiki/1.0; ' . home_url( '/' ),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				$body = $response->get_error_message();
				$code = 0;
			} else {
				$code = (int) wp_remote_retrieve_response_code( $response );
				$body = (string) wp_remote_retrieve_body( $response );
			}

			if ( 200 === $code ) {
				break;
			}

			if ( 429 !== $code && $code < 500 ) {
				break;
			}

			sleep( $attempt * 2 );
		}

		if ( $this->sleep > 0 ) {
			usleep( (int) ( $this->sleep * 1000000 ) );
		}

		if ( 200 !== $code ) {
			throw new RuntimeException( sprintf( 'Jolpica API error %d for %s: %s', $code, $url, substr( $body, 0, 300 ) ) );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['MRData'] ) ) {
			throw new RuntimeException( sprintf( 'Invalid JSON from %s', $url ) );
		}

		if ( $this->use_cache ) {
			file_put_contents( $cache_file, wp_json_encode( $data ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return $data;
	}
}
