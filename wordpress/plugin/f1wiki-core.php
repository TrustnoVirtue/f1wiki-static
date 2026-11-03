<?php
/**
 * Plugin Name: F1 Wiki Core
 * Description: Formula 1 wiki content types, race data tables, Jolpica import and statistics.
 * Version: 1.0.0
 * Author: F1 Wiki
 * Text Domain: f1wiki
 */

defined( 'ABSPATH' ) || exit;

define( 'F1WIKI_CORE_VERSION', '1.0.0' );
define( 'F1WIKI_CORE_FILE', __FILE__ );
define( 'F1WIKI_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'F1WIKI_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once F1WIKI_CORE_DIR . 'includes/post-types.php';
require_once F1WIKI_CORE_DIR . 'includes/tables.php';
require_once F1WIKI_CORE_DIR . 'includes/helpers.php';
require_once F1WIKI_CORE_DIR . 'includes/acf-fields.php';
require_once F1WIKI_CORE_DIR . 'includes/content.php';

/**
 * Activation: install tables and register content types.
 */
function f1wiki_core_activate() {
	f1wiki_install_tables();
	f1wiki_register_content_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'f1wiki_core_activate' );

/**
 * Deactivation: flush rewrite rules.
 */
function f1wiki_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'f1wiki_core_deactivate' );

/**
 * Bootstrap the plugin.
 */
function f1wiki_core_bootstrap() {
	if ( get_option( 'f1wiki_core_db_version' ) !== F1WIKI_CORE_VERSION ) {
		f1wiki_install_tables();
	}

	if ( is_admin() ) {
		require_once F1WIKI_CORE_DIR . 'includes/admin.php';
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once F1WIKI_CORE_DIR . 'includes/api-client.php';
		require_once F1WIKI_CORE_DIR . 'includes/stats.php';
		require_once F1WIKI_CORE_DIR . 'includes/seeder.php';
		require_once F1WIKI_CORE_DIR . 'includes/setup.php';
		require_once F1WIKI_CORE_DIR . 'includes/photos.php';
		require_once F1WIKI_CORE_DIR . 'includes/cli.php';
	}
}
add_action( 'plugins_loaded', 'f1wiki_core_bootstrap' );
