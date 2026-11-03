<?php
/**
 * One-time site setup: pages, menus and options.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site setup runner.
 */
class F1Wiki_Setup {

	/**
	 * Run setup.
	 */
	public static function run() {
		self::configure_options();
		self::delete_default_content();

		$home_id  = self::ensure_page( 'home', 'Home', 'The Formula 1 encyclopedia: drivers, teams, circuits, races and seasons from 2000 to today.' );
		$about_id = self::ensure_page( 'about', 'About F1 Wiki', self::about_content() );
		$data_id  = self::ensure_page( 'data-sources', 'Data sources', self::data_content() );

		if ( $home_id ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home_id );
		}

		self::build_menus( $about_id, $data_id );
		self::build_index();

		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}

		WP_CLI::success( 'F1 Wiki setup complete.' );
	}

	/**
	 * Set core options.
	 */
	private static function configure_options() {
		update_option( 'blogname', 'F1 Wiki' );
		update_option( 'blogdescription', 'The Formula 1 encyclopedia.' );
		update_option( 'timezone_string', 'Europe/London' );
		update_option( 'date_format', 'j M Y' );
		update_option( 'time_format', 'H:i' );
		update_option( 'permalink_structure', '/%postname%/' );
		update_option( 'posts_per_page', 12 );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );
		update_option( 'blog_public', 1 );
	}

	/**
	 * Remove the WordPress starter content.
	 */
	private static function delete_default_content() {
		foreach ( array( 'Hello world!', 'Sample Page' ) as $title ) {
			$posts = get_posts(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'any',
					'title'          => $title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);

			if ( $posts ) {
				wp_delete_post( (int) $posts[0], true );
			}
		}
	}

	/**
	 * Create a page if missing and update its content.
	 *
	 * @param string $slug    Page slug.
	 * @param string $title   Page title.
	 * @param string $content Page content.
	 * @return int
	 */
	private static function ensure_page( $slug, $title, $content ) {
		$page = get_page_by_path( $slug );

		if ( $page ) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_title'   => $title,
					'post_content' => $content,
				)
			);
			return (int) $page->ID;
		}

		return (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);
	}

	/**
	 * About page content.
	 *
	 * @return string
	 */
	private static function about_content() {
		$range = f1wiki_archive_range_label();
		$counts = f1wiki_get_counts();

		ob_start();
		?>
		<h2>Every driver. Every team. Every circuit.</h2>
		<p>F1 Wiki is an independent, editorially styled encyclopedia of Formula 1. It brings the sport's people, machines and places together in one browsable archive built on WordPress.</p>
		<p>The archive currently covers the <strong><?php echo esc_html( $range ); ?></strong> seasons: <?php echo esc_html( number_format_i18n( $counts['races'] ) ); ?> races, <?php echo esc_html( number_format_i18n( $counts['drivers'] ) ); ?> drivers, <?php echo esc_html( number_format_i18n( $counts['teams'] ) ); ?> constructors and <?php echo esc_html( number_format_i18n( $counts['circuits'] ) ); ?> circuits, with <?php echo esc_html( number_format_i18n( $counts['results'] ) ); ?> individual race results.</p>
		<h2>How to use the archive</h2>
		<ul>
			<li><strong>Drivers</strong> — career records, season-by-season results and team history.</li>
			<li><strong>Teams</strong> — constructors' records, championship years and driver line-ups.</li>
			<li><strong>Circuits</strong> — locations, race history and the most successful drivers and teams at each venue.</li>
			<li><strong>Races</strong> — full classifications, poles and fastest laps.</li>
			<li><strong>Seasons</strong> — championship standings, calendars and winners.</li>
		</ul>
		<p>Use the search field in the header for a quick lookup, or start from any archive in the main navigation.</p>
		<h2>Editing</h2>
		<p>All content lives in the WordPress admin. Seeded summaries can be edited like any other article; the importer will preserve manual edits on subsequent imports. New drivers, teams, circuits, races and seasons can be added through the corresponding admin screens.</p>
		<h2>Independence</h2>
		<p>F1 Wiki is a fan-made project and is not affiliated with Formula 1, the FIA or any team. Team names and marks belong to their respective owners. No official logos or photography are bundled with the site.</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Data sources page content.
	 *
	 * @return string
	 */
	private static function data_content() {
		$range = f1wiki_archive_range_label();

		ob_start();
		?>
		<h2>Race data</h2>
		<p>Race schedules, classifications and championship standings are imported from the <a href="https://api.jolpi.ca/ergast/f1/" target="_blank" rel="noopener noreferrer">Jolpica F1 API</a>, an open, Ergast-compatible API for Formula 1 data.</p>
		<h2>Coverage</h2>
		<p>The current archive covers <strong><?php echo esc_html( $range ); ?></strong>. Career and team statistics shown on this site are calculated from that archive, so they deliberately describe the covered seasons rather than complete all-time totals.</p>
		<p>Races from future rounds of the current season appear in the calendar as soon as they are published by the API. Classifications and statistics update after data is re-imported.</p>
		<p>The source does not include fastest-lap details for the 2000–2003 seasons. Fastest-lap totals therefore begin in 2004; race pages from the earlier seasons are labelled accordingly.</p>
		<h2>Photography</h2>
		<p>Driver portraits are imported from Wikipedia and Wikimedia Commons and used under their free licenses (CC BY, CC BY-SA, CC0, OGL and similar). Photographer and license details are shown with each portrait, linked to the source file.</p>
		<h2>Attribution</h2>
		<p>Driver and team encyclopedia links point to Wikipedia for further reading. Wikipedia content is licensed under CC BY-SA; F1 Wiki does not republish it. This site is not affiliated with Formula 1, the FIA or any team.</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Recreate the primary and footer menus.
	 *
	 * @param int $about_id About page ID.
	 * @param int $data_id  Data page ID.
	 */
	private static function build_menus( $about_id, $data_id ) {
		$primary = self::create_menu(
			'Primary',
			array(
				array( 'title' => 'Drivers', 'url' => home_url( '/drivers/' ) ),
				array( 'title' => 'Teams', 'url' => home_url( '/teams/' ) ),
				array( 'title' => 'Circuits', 'url' => home_url( '/circuits/' ) ),
				array( 'title' => 'Seasons', 'url' => home_url( '/seasons/' ) ),
				array( 'title' => 'Races', 'url' => home_url( '/races/' ) ),
			)
		);

		$footer_items = array(
			array( 'title' => 'About', 'url' => get_permalink( $about_id ) ),
			array( 'title' => 'Data sources', 'url' => get_permalink( $data_id ) ),
			array( 'title' => 'Search', 'url' => home_url( '/?s=' ) ),
		);

		$footer = self::create_menu( 'Footer', $footer_items );

		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( ! is_array( $locations ) ) {
			$locations = array();
		}
		if ( $primary ) {
			$locations['primary'] = $primary;
		}
		if ( $footer ) {
			$locations['footer'] = $footer;
		}
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Delete and rebuild a menu.
	 *
	 * @param string $name  Menu name.
	 * @param array  $items Menu items.
	 * @return int
	 */
	private static function create_menu( $name, $items ) {
		$existing = wp_get_nav_menu_object( $name );
		if ( $existing ) {
			wp_delete_nav_menu( $existing->term_id );
		}

		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			WP_CLI::warning( sprintf( 'Could not create menu %s: %s', $name, $menu_id->get_error_message() ) );
			return 0;
		}

		foreach ( $items as $item ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => $item['url'],
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);
		}

		return (int) $menu_id;
	}

	/**
	 * Rebuild the Relevanssi index.
	 */
	public static function build_index() {
		$types = get_option( 'relevanssi_index_post_types', array() );
		$types = is_array( $types ) ? $types : array();
		$types = array_values( array_unique( array_merge( $types, array( 'driver', 'team', 'circuit', 'race', 'season' ) ) ) );
		update_option( 'relevanssi_index_post_types', $types );

		if ( function_exists( 'relevanssi_truncate_index' ) ) {
			relevanssi_truncate_index();
		}
		if ( function_exists( 'relevanssi_build_index' ) ) {
			relevanssi_build_index( false, false );
			WP_CLI::log( 'Rebuilt the Relevanssi search index.' );
		}
	}
}
