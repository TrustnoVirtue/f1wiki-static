<?php
/**
 * Metadata and structured data.
 *
 * @package F1Wiki
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current canonical URL.
 *
 * @return string
 */
function f1wiki_canonical_url() {
	if ( is_singular() ) {
		return (string) get_permalink();
	}

	if ( is_post_type_archive() ) {
		$type = get_query_var( 'post_type' );
		$type = is_array( $type ) ? reset( $type ) : $type;
		$url  = get_post_type_archive_link( $type );
		$page = max( 1, (int) get_query_var( 'paged' ) );

		if ( $url && $page > 1 ) {
			return trailingslashit( $url ) . 'page/' . $page . '/';
		}
		return $url ? (string) $url : home_url( '/' );
	}

	if ( is_search() ) {
		return (string) get_search_link();
	}

	return home_url( '/' );
}

/**
 * Meta description.
 *
 * @return string
 */
function f1wiki_meta_description() {
	if ( is_front_page() ) {
		return 'An independent Formula 1 encyclopedia covering every driver, team, circuit, race and season from 2000 to today.';
	}

	if ( is_post_type_archive( 'driver' ) ) {
		return 'Browse every Formula 1 driver in the F1 Wiki archive, with career statistics, teams, championships and season-by-season results.';
	}
	if ( is_post_type_archive( 'team' ) ) {
		return 'Browse Formula 1 constructors in the F1 Wiki archive: championships, wins, driver line-ups and season-by-season records.';
	}
	if ( is_post_type_archive( 'circuit' ) ) {
		return 'Explore Formula 1 circuits in the F1 Wiki archive, with locations, race history and the most successful drivers and teams.';
	}
	if ( is_post_type_archive( 'race' ) ) {
		return 'Every Formula 1 race in the F1 Wiki archive, with full classifications, poles, fastest laps and circuit details.';
	}
	if ( is_post_type_archive( 'season' ) ) {
		return 'Formula 1 seasons from 2000 to today: championship standings, calendars, winners and season summaries.';
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( has_excerpt( $post ) ) {
				return wp_html_excerpt( wp_strip_all_tags( get_the_excerpt( $post ) ), 158, '…' );
			}
			$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			if ( $text ) {
				return wp_html_excerpt( $text, 158, '…' );
			}
			return wp_html_excerpt( $post->post_title . ' on F1 Wiki.', 158, '…' );
		}
	}

	if ( is_search() ) {
		return 'Search results for “' . get_search_query() . '” on F1 Wiki.';
	}

	if ( is_404() ) {
		return 'The page you requested could not be found on F1 Wiki.';
	}

	return get_bloginfo( 'description' );
}

/**
 * Social image URL.
 *
 * @return string
 */
function f1wiki_social_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'f1wiki-hero' );
		if ( $image ) {
			return $image[0];
		}
	}

	$fallback = get_template_directory() . '/assets/img/og-default.png';
	if ( file_exists( $fallback ) ) {
		return get_template_directory_uri() . '/assets/img/og-default.png';
	}

	return '';
}

/**
 * Print meta tags.
 */
function f1wiki_print_meta() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = f1wiki_meta_description();
	$url         = f1wiki_canonical_url();
	$image       = f1wiki_social_image();
	$type        = is_singular() ? 'article' : 'website';

	echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";

	if ( ! is_404() && $url ) {
		if ( ! is_singular() ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	}

	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'f1wiki_print_meta', 5 );

/**
 * Structured data for the current view.
 *
 * @return array|null
 */
function f1wiki_json_ld() {
	$url = f1wiki_canonical_url();

	if ( is_front_page() ) {
		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => get_bloginfo( 'name' ),
			'description'     => f1wiki_meta_description(),
			'url'             => home_url( '/' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$base = array(
			'@context' => 'https://schema.org',
			'name'     => get_the_title( $post ),
			'url'      => $url,
		);

		if ( 'driver' === $post->post_type ) {
			$base['@type']      = 'Person';
			$base['jobTitle']   = 'Formula One racing driver';
			$base['nationality'] = (string) get_post_meta( $post->ID, '_f1wiki_nationality', true );
			$dob                = (string) get_post_meta( $post->ID, '_f1wiki_dob', true );
			if ( $dob ) {
				$base['birthDate'] = $dob;
			}
			$team = f1wiki_get_driver_team( $post->ID );
			if ( $team['name'] ) {
				$base['memberOf'] = array(
					'@type' => 'SportsTeam',
					'name'  => $team['name'],
					'url'   => $team['url'],
				);
			}
			return $base;
		}

		if ( 'team' === $post->post_type ) {
			$base['@type'] = 'SportsTeam';
			$base['sport'] = 'Formula One';
			$nat           = (string) get_post_meta( $post->ID, '_f1wiki_nationality', true );
			if ( $nat ) {
				$base['nationality'] = $nat;
			}
			return $base;
		}

		if ( 'circuit' === $post->post_type ) {
			$base['@type'] = 'Place';
			$lat          = (string) get_post_meta( $post->ID, '_f1wiki_latitude', true );
			$lng          = (string) get_post_meta( $post->ID, '_f1wiki_longitude', true );
			if ( '' !== $lat && '' !== $lng ) {
				$base['geo'] = array(
					'@type'     => 'GeoCoordinates',
					'latitude'  => (float) $lat,
					'longitude' => (float) $lng,
				);
			}
			$country = (string) get_post_meta( $post->ID, '_f1wiki_country', true );
			if ( $country ) {
				$base['address'] = array(
					'@type'          => 'PostalAddress',
					'addressCountry' => $country,
				);
			}
			return $base;
		}

		if ( 'race' === $post->post_type ) {
			$base['@type']    = 'SportsEvent';
			$base['sport']    = 'Formula One';
			$base['startDate'] = (string) get_post_meta( $post->ID, '_f1wiki_race_date', true );
			$summary          = f1wiki_get_race_summary( $post->ID );
			if ( $summary['circuit'] ) {
				$base['location'] = array(
					'@type' => 'Place',
					'name'  => $summary['circuit'],
					'url'   => $summary['circuit_url'],
				);
			}
			return $base;
		}

		if ( 'season' === $post->post_type ) {
			$base['@type'] = 'Article';
			$base['about'] = get_the_title( $post );
			return $base;
		}

		$base['@type'] = 'WebPage';
		return $base;
	}

	if ( is_post_type_archive() || is_search() || is_404() ) {
		return array(
			'@context'    => 'https://schema.org',
			'@type'       => 'WebPage',
			'name'        => wp_get_document_title(),
			'description' => f1wiki_meta_description(),
			'url'         => $url,
		);
	}

	return null;
}

/**
 * Print JSON-LD.
 */
function f1wiki_print_json_ld() {
	$schema = f1wiki_json_ld();
	if ( empty( $schema ) ) {
		return;
	}

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'f1wiki_print_json_ld', 20 );
