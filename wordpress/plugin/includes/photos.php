<?php
/**
 * Driver photo imports from Wikipedia and Wikimedia Commons.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fetches freely licensed driver portraits and stores attribution.
 */
class F1Wiki_Photos {

	/**
	 * Fetch portraits for drivers missing one.
	 *
	 * @param array $args Options: season, all, limit, refresh.
	 * @return array Summary counters.
	 */
	public static function fetch( $args = array() ) {
		$season  = isset( $args['season'] ) ? absint( $args['season'] ) : 0;
		$all     = ! empty( $args['all'] );
		$limit   = isset( $args['limit'] ) ? absint( $args['limit'] ) : 0;
		$refresh = ! empty( $args['refresh'] );
		$type    = isset( $args['type'] ) && 'team' === $args['type'] ? 'team' : 'driver';

		if ( ! $all && ! $season ) {
			$season = f1wiki_latest_season();
		}

		$query = array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		if ( ! $all ) {
			$query['meta_query'] = array(
				array(
					'key'   => '_f1wiki_seasons_sort',
					'value' => $season,
				),
			);
		}

		$ids = get_posts( $query );

		$summary = array(
			'total'   => count( $ids ),
			'fetched' => 0,
			'skipped' => 0,
			'failed'  => 0,
		);

		foreach ( $ids as $post_id ) {
			if ( ! $refresh && has_post_thumbnail( $post_id ) ) {
				$summary['skipped']++;
				self::log( sprintf( 'skip   %s (already has a file)', get_the_title( $post_id ) ) );
				continue;
			}

			$result = self::fetch_one( $post_id, $type );

			if ( is_wp_error( $result ) ) {
				$summary['failed']++;
				self::log( sprintf( 'failed %s: %s', get_the_title( $post_id ), $result->get_error_message() ) );
			} else {
				$summary['fetched']++;
				self::log( sprintf( 'ok     %s — %s', get_the_title( $post_id ), $result ) );
			}

			sleep( 1 );
		}

		return $summary;
	}

	/**
	 * Fetch and attach one portrait or logo.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    driver or team.
	 * @return string|WP_Error Credit line on success.
	 */
	protected static function fetch_one( $post_id, $type = 'driver' ) {
		$title = get_the_title( $post_id );

		if ( ! $title ) {
			return new WP_Error( 'no_title', 'Missing post title.' );
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( 'team' === $type ) {
			$pageimage = self::wikipedia_pageimage( $title, self::team_wikipedia_titles( $title ), $title . ' Formula One team' );
		} else {
			$lookup = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
			$lookup = str_replace( array( '’', '‘' ), "'", $lookup );

			$pageimage = self::wikipedia_pageimage( $lookup, array(), $lookup . ' Formula One driver' );
		}

		if ( is_wp_error( $pageimage ) ) {
			return $pageimage;
		}

		$info = self::commons_file_info( $pageimage );
		if ( is_wp_error( $info ) ) {
			return $info;
		}

		$tmp = self::download( $info['thumb'] );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$path      = (string) wp_parse_url( $info['thumb'], PHP_URL_PATH );
		$extension = pathinfo( $path, PATHINFO_EXTENSION );
		$extension = $extension ? $extension : 'jpg';

		$file_array = array(
			'name'     => sanitize_title( $title ) . '.' . $extension,
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id, $title );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return $attachment_id;
		}

		set_post_thumbnail( $post_id, $attachment_id );

		$credit = ( 'team' === $type ? 'Logo: ' : 'Photo: ' ) . $info['artist'] . ' · ' . $info['license'] . ' · Wikimedia Commons';
		update_post_meta( $post_id, '_f1wiki_photo_credit', $credit );
		update_post_meta( $post_id, '_f1wiki_photo_source', $info['source'] );
		update_post_meta( $post_id, '_f1wiki_photo_file', $info['title'] );

		return $credit;
	}

	/**
	 * Lead image file name from Wikipedia.
	 *
	 * @param string $title        Page title.
	 * @param array  $extra        Extra candidate titles to try first.
	 * @param string $search_query Optional search fallback query.
	 * @return string|WP_Error
	 */
	protected static function wikipedia_pageimage( $title, $extra = array(), $search_query = '' ) {
		$candidates = array_merge( $extra, array( $title ) );

		if ( $search_query ) {
			$search = self::wikipedia_search_title( $search_query );
			if ( ! is_wp_error( $search ) && $search ) {
				$candidates[] = $search;
			}
		}

		$candidates[] = $title . ' (racing driver)';

		foreach ( array_unique( $candidates ) as $candidate ) {
			$url = add_query_arg(
				array(
					'action'      => 'query',
					'format'      => 'json',
					'prop'        => 'pageimages',
					'piprop'      => 'name|thumbnail',
					'pithumbsize' => 900,
					'redirects'   => 1,
					'titles'      => rawurlencode( $candidate ),
				),
				'https://en.wikipedia.org/w/api.php'
			);

			$response = self::request( $url );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$pages = isset( $response['query']['pages'] ) ? $response['query']['pages'] : array();

			foreach ( $pages as $page ) {
				if ( ! empty( $page['pageimage'] ) ) {
					return (string) $page['pageimage'];
				}
			}
		}

		return new WP_Error( 'no_image', 'No lead image found on Wikipedia.' );
	}

	/**
	 * Wikipedia article candidates for a constructor name.
	 *
	 * @param string $name Team name.
	 * @return array
	 */
	protected static function team_wikipedia_titles( $name ) {
		$map = array(
			'alfa romeo'       => array( 'Alfa Romeo in Formula One', 'Alfa Romeo Racing' ),
			'alphatauri'       => array( 'Scuderia AlphaTauri' ),
			'alpine f1 team'   => array( 'Alpine F1 Team', 'Alpine (Formula One team)' ),
			'arrows'           => array( 'Arrows Grand Prix International' ),
			'aston martin'     => array( 'Aston Martin in Formula One' ),
			'audi'             => array( 'Audi in Formula One', 'Audi F1 Team' ),
			'bar'              => array( 'British American Racing' ),
			'benetton'         => array( 'Benetton Formula' ),
			'bmw sauber'       => array( 'BMW Sauber' ),
			'brawn'            => array( 'Brawn GP' ),
			'cadillac f1 team' => array( 'Cadillac F1 Team' ),
			'caterham'         => array( 'Caterham F1' ),
			'ferrari'          => array( 'Scuderia Ferrari' ),
			'force india'      => array( 'Force India' ),
			'haas f1 team'     => array( 'Haas F1 Team' ),
			'honda'            => array( 'Honda in Formula One' ),
			'hrt'              => array( 'HRT Formula 1 Team' ),
			'jaguar'           => array( 'Jaguar Racing' ),
			'jordan'           => array( 'Jordan Grand Prix' ),
			'lotus'            => array( 'Lotus F1', 'Team Lotus (2010–2011)', 'Lotus Racing' ),
			'lotus f1'         => array( 'Lotus F1' ),
			'manor marussia'   => array( 'Manor Motorsport', 'Marussia F1' ),
			'marussia'         => array( 'Marussia F1' ),
			'mclaren'          => array( 'McLaren' ),
			'mercedes'         => array( 'Mercedes-Benz in Formula One' ),
			'mf1'              => array( 'Midland F1 Racing' ),
			'minardi'          => array( 'Minardi' ),
			'prost'            => array( 'Prost Grand Prix' ),
			'racing point'     => array( 'Racing Point F1 Team' ),
			'rb f1 team'       => array( 'RB Formula One Team' ),
			'red bull'         => array( 'Red Bull Racing' ),
			'renault'          => array( 'Renault in Formula One' ),
			'sauber'           => array( 'Sauber Motorsport' ),
			'spyker'           => array( 'Spyker F1' ),
			'spyker mf1'       => array( 'Spyker F1' ),
			'super aguri'      => array( 'Super Aguri F1' ),
			'toro rosso'       => array( 'Scuderia Toro Rosso' ),
			'toyota'           => array( 'Toyota in Formula One' ),
			'virgin'           => array( 'Virgin Racing' ),
			'williams'         => array( 'Williams Racing' ),
		);

		$key = strtolower( trim( $name ) );

		return isset( $map[ $key ] ) ? $map[ $key ] : array();
	}

	/**
	 * Top search result title for a query.
	 *
	 * @param string $query Search query.
	 * @return string|WP_Error
	 */
	protected static function wikipedia_search_title( $query ) {
		$url = add_query_arg(
			array(
				'action'  => 'query',
				'format'  => 'json',
				'list'    => 'search',
				'srlimit' => 1,
				'srsearch' => rawurlencode( $query ),
			),
			'https://en.wikipedia.org/w/api.php'
		);

		$response = self::request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response['query']['search'][0]['title'] ) ) {
			return (string) $response['query']['search'][0]['title'];
		}

		return new WP_Error( 'no_search_result', 'No search result.' );
	}

	/**
	 * License and thumbnail details for a Commons file.
	 *
	 * @param string $file_name File name without namespace.
	 * @return array|WP_Error
	 */
	protected static function commons_file_info( $file_name ) {
		$url = add_query_arg(
			array(
				'action'     => 'query',
				'format'     => 'json',
				'prop'       => 'imageinfo',
				'iiprop'     => 'url|extmetadata',
				'iiurlwidth' => 900,
				'titles'     => rawurlencode( 'File:' . $file_name ),
			),
			'https://commons.wikimedia.org/w/api.php'
		);

		$response = self::request( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$pages = isset( $response['query']['pages'] ) ? $response['query']['pages'] : array();

		foreach ( $pages as $page ) {
			if ( isset( $page['missing'] ) || empty( $page['imageinfo'][0] ) ) {
				continue;
			}

			$info = $page['imageinfo'][0];
			$meta = isset( $info['extmetadata'] ) ? $info['extmetadata'] : array();

			$artist  = isset( $meta['Artist']['value'] ) ? html_entity_decode( wp_strip_all_tags( $meta['Artist']['value'] ), ENT_QUOTES, 'UTF-8' ) : '';
			$artist  = trim( preg_replace( '/\s+/', ' ', $artist ) );
			$license = isset( $meta['LicenseShortName']['value'] ) ? trim( wp_strip_all_tags( $meta['LicenseShortName']['value'] ) ) : '';
			$thumb   = ! empty( $info['thumburl'] ) ? (string) $info['thumburl'] : (string) $info['url'];
			$source  = ! empty( $info['descriptionurl'] ) ? (string) $info['descriptionurl'] : '';

			return array(
				'title'   => 'File:' . $file_name,
				'artist'  => $artist ? $artist : 'Wikimedia contributor',
				'license' => $license ? $license : 'Free license',
				'thumb'   => $thumb,
				'source'  => $source,
			);
		}

		return new WP_Error( 'not_on_commons', 'Lead image is not hosted on Wikimedia Commons.' );
	}

	/**
	 * JSON request helper with rate-limit backoff.
	 *
	 * @param string $url Endpoint.
	 * @return array|WP_Error
	 */
	protected static function request( $url ) {
		$url = add_query_arg( 'maxlag', 5, $url );

		for ( $attempt = 0; $attempt < 4; $attempt++ ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'    => 30,
					'user-agent' => 'F1Wiki/1.0 (local WordPress fan wiki)',
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( 429 === $code || 503 === $code ) {
				sleep( 4 * ( $attempt + 1 ) );
				continue;
			}

			if ( 200 !== $code ) {
				return new WP_Error( 'http_error', 'Request failed with status ' . $code );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $data ) ) {
				return new WP_Error( 'bad_json', 'Invalid JSON response.' );
			}

			return $data;
		}

		return new WP_Error( 'rate_limited', 'Rate limited by the Wikimedia API.' );
	}

	/**
	 * Download helper with rate-limit backoff.
	 *
	 * @param string $url File URL.
	 * @return string|WP_Error Local tmp path.
	 */
	protected static function download( $url ) {
		for ( $attempt = 0; $attempt < 4; $attempt++ ) {
			$tmp = download_url( $url, 60 );

			if ( is_wp_error( $tmp ) ) {
				$code = $tmp->get_error_data();
				if ( in_array( (int) $code, array( 429, 503 ), true ) ) {
					sleep( 4 * ( $attempt + 1 ) );
					continue;
				}

				return $tmp;
			}

			return $tmp;
		}

		return new WP_Error( 'rate_limited', 'Rate limited while downloading the image.' );
	}

	/**
	 * Log a line when running under WP-CLI.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	protected static function log( $message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
		}
	}
}
