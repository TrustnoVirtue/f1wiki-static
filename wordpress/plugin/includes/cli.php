<?php
/**
 * WP-CLI commands.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * F1 Wiki commands.
 */
class F1Wiki_CLI_Command {

	/**
	 * Import F1 data from the Jolpica API.
	 *
	 * ## OPTIONS
	 *
	 * [--from=<year>]
	 * : First season to import. Default 2000.
	 *
	 * [--to=<year>]
	 * : Last season to import. Default current year.
	 *
	 * [--refresh]
	 * : Ignore cached API responses.
	 *
	 * [--no-cache]
	 * : Do not read or write the API cache.
	 *
	 * [--force-content]
	 * : Overwrite manually edited summaries.
	 *
	 * [--sleep=<seconds>]
	 * : Delay between API requests. Default 0.25.
	 *
	 * ## EXAMPLES
	 *
	 *     wp f1wiki seed --from=2000 --to=2026
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function seed( $args, $assoc_args ) {
		$seeder = new F1Wiki_Seeder( $assoc_args );
		$seeder->run();
	}

	/**
	 * Recalculate statistics and summaries.
	 *
	 * ## OPTIONS
	 *
	 * [--force-content]
	 * : Overwrite manually edited summaries.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function stats( $args, $assoc_args ) {
		F1Wiki_Stats::recalculate_all( ! empty( $assoc_args['force-content'] ) );
		f1wiki_refresh_site_options();
		WP_CLI::success( 'Statistics recalculated.' );
	}

	/**
	 * Create pages, menus and site options.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function setup( $args, $assoc_args ) {
		F1Wiki_Setup::run();
	}

	/**
	 * Import freely licensed portraits and logos from Wikimedia Commons.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<driver|team>]
	 * : Import driver portraits (default) or team logos.
	 *
	 * [--season=<year>]
	 * : Only entities active in this season. Defaults to the latest season.
	 *
	 * [--all]
	 * : Every item in the archive, not just one season.
	 *
	 * [--limit=<number>]
	 * : Stop after this many items.
	 *
	 * [--refresh]
	 * : Replace existing images.
	 *
	 * ## EXAMPLES
	 *
	 *     wp f1wiki photos --season=2026
	 *     wp f1wiki photos --all
	 *     wp f1wiki photos --type=team --all
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function photos( $args, $assoc_args ) {
		$summary = F1Wiki_Photos::fetch( $assoc_args );
		WP_CLI::success(
			sprintf(
				'%d fetched, %d skipped, %d failed (of %d items).',
				$summary['fetched'],
				$summary['skipped'],
				$summary['failed'],
				$summary['total']
			)
		);
	}

	/**
	 * Rebuild the Relevanssi search index.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function index( $args, $assoc_args ) {
		F1Wiki_Setup::build_index();
		WP_CLI::success( 'Search index rebuilt.' );
	}

	/**
	 * Show archive information.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function info( $args, $assoc_args ) {
		global $wpdb;

		$counts = f1wiki_get_counts();
		$table  = f1wiki_results_table();

		WP_CLI::log( 'F1 Wiki archive' );
		WP_CLI::log( 'Latest season: ' . f1wiki_latest_season() );
		WP_CLI::log( 'Drivers: ' . $counts['drivers'] );
		WP_CLI::log( 'Teams: ' . $counts['teams'] );
		WP_CLI::log( 'Circuits: ' . $counts['circuits'] );
		WP_CLI::log( 'Races: ' . $counts['races'] );
		WP_CLI::log( 'Seasons: ' . $counts['seasons'] );
		WP_CLI::log( 'Result rows: ' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

WP_CLI::add_command( 'f1wiki', 'F1Wiki_CLI_Command' );
