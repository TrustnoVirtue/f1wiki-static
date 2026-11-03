<?php
/**
 * F1 Wiki theme functions.
 *
 * @package F1Wiki
 */

defined( 'ABSPATH' ) || exit;

define( 'F1WIKI_THEME_VERSION', '1.2.0' );

/**
 * Theme setup.
 */
function f1wiki_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 220, 'flex-height' => true, 'flex-width' => true ) );

	register_nav_menus(
		array(
			'primary' => 'Primary',
			'footer'  => 'Footer',
		)
	);

	add_image_size( 'f1wiki-card', 720, 405, true );
	add_image_size( 'f1wiki-portrait', 560, 700, true );
	add_image_size( 'f1wiki-hero', 1600, 900, true );
}
add_action( 'after_setup_theme', 'f1wiki_theme_setup' );

/**
 * Enqueue styles and scripts.
 */
function f1wiki_enqueue_assets() {
	$css = get_template_directory() . '/assets/css/main.css';
	wp_enqueue_style(
		'f1wiki-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : F1WIKI_THEME_VERSION
	);

	$js = get_template_directory() . '/assets/js/main.js';
	wp_enqueue_script(
		'f1wiki-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		file_exists( $js ) ? (string) filemtime( $js ) : F1WIKI_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'f1wiki_enqueue_assets' );

/**
 * Excerpt settings.
 *
 * @param int $length Length.
 * @return int
 */
function f1wiki_excerpt_length( $length ) {
	return 30;
}
add_filter( 'excerpt_length', 'f1wiki_excerpt_length' );

/**
 * Excerpt more string.
 *
 * @param string $more More.
 * @return string
 */
function f1wiki_excerpt_more( $more ) {
	return '…';
}
add_filter( 'excerpt_more', 'f1wiki_excerpt_more' );

/**
 * Body classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function f1wiki_body_classes( $classes ) {
	$classes[] = 'f1wiki-theme';

	if ( is_singular() ) {
		$classes[] = 'is-entity';
	}
	if ( is_front_page() ) {
		$classes[] = 'is-home';
	}

	return $classes;
}
add_filter( 'body_class', 'f1wiki_body_classes' );

/**
 * Title separator.
 *
 * @return string
 */
function f1wiki_title_separator() {
	return '·';
}
add_filter( 'document_title_separator', 'f1wiki_title_separator' );

/**
 * Configure main archive queries.
 *
 * @param WP_Query $query Query.
 */
function f1wiki_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );

	if ( $query->is_post_type_archive( 'driver' ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );

		$meta_query = array();

		$team = isset( $_GET['driver_team'] ) ? absint( wp_unslash( $_GET['driver_team'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $team ) {
			global $wpdb;

			$results_table = f1wiki_results_table();
			$driver_ids    = $wpdb->get_col( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT DISTINCT driver_id FROM {$results_table} WHERE team_id = %d",
					$team
				)
			);
			$driver_ids = array_map( 'absint', $driver_ids );
			$query->set( 'post__in', $driver_ids ? $driver_ids : array( 0 ) );
		}

		$current = isset( $_GET['current'] ) && '1' === (string) wp_unslash( $_GET['current'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $current ) {
			$meta_query[] = array(
				'key'   => '_f1wiki_seasons_sort',
				'value' => f1wiki_latest_season(),
				'type'  => 'NUMERIC',
			);
		}

		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	if ( $query->is_post_type_archive( 'team' ) ) {
		$query->set( 'posts_per_page', 30 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}

	if ( $query->is_post_type_archive( 'circuit' ) ) {
		$query->set( 'posts_per_page', 30 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}

	if ( $query->is_post_type_archive( 'season' ) ) {
		$query->set( 'posts_per_page', -1 );
		$query->set( 'meta_key', '_f1wiki_year_sort' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'DESC' );
	}

	if ( $query->is_post_type_archive( 'race' ) ) {
		$query->set( 'posts_per_page', 30 );
		$query->set( 'meta_key', '_f1wiki_race_date' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'DESC' );

		$season = isset( $_GET['race_season'] ) ? absint( wp_unslash( $_GET['race_season'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $season ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'   => '_f1wiki_season_year',
						'value' => $season,
						'type'  => 'NUMERIC',
					),
				)
			);
		}
	}
}
add_action( 'pre_get_posts', 'f1wiki_pre_get_posts' );

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/seo.php';
