<?php
/**
 * Custom database tables for results and standings.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Results table name.
 *
 * @return string
 */
function f1wiki_results_table() {
	global $wpdb;
	return $wpdb->prefix . 'f1_results';
}

/**
 * Driver standings table name.
 *
 * @return string
 */
function f1wiki_driver_standings_table() {
	global $wpdb;
	return $wpdb->prefix . 'f1_driver_standings';
}

/**
 * Constructor standings table name.
 *
 * @return string
 */
function f1wiki_team_standings_table() {
	global $wpdb;
	return $wpdb->prefix . 'f1_team_standings';
}

/**
 * Create or update the custom tables.
 */
function f1wiki_install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	$results           = f1wiki_results_table();
	$driver_standings  = f1wiki_driver_standings_table();
	$team_standings    = f1wiki_team_standings_table();

	$sql = array();

	$sql[] = "CREATE TABLE {$results} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		race_id bigint(20) unsigned NOT NULL DEFAULT 0,
		season_year smallint(5) unsigned NOT NULL DEFAULT 0,
		round tinyint(4) unsigned NOT NULL DEFAULT 0,
		circuit_id bigint(20) unsigned NOT NULL DEFAULT 0,
		driver_id bigint(20) unsigned NOT NULL DEFAULT 0,
		team_id bigint(20) unsigned NOT NULL DEFAULT 0,
		result_order smallint(5) unsigned NOT NULL DEFAULT 0,
		grid smallint(5) unsigned NOT NULL DEFAULT 0,
		position smallint(5) unsigned DEFAULT NULL,
		position_text varchar(10) NOT NULL DEFAULT '',
		points decimal(6,2) NOT NULL DEFAULT 0.00,
		laps smallint(5) unsigned NOT NULL DEFAULT 0,
		status varchar(100) NOT NULL DEFAULT '',
		car_number varchar(10) NOT NULL DEFAULT '',
		fastest_lap_rank tinyint(4) unsigned DEFAULT NULL,
		fastest_lap_time varchar(20) NOT NULL DEFAULT '',
		fastest_lap_speed varchar(20) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		UNIQUE KEY race_driver (race_id,driver_id),
		KEY driver_season (driver_id,season_year),
		KEY team_season (team_id,season_year),
		KEY season_round (season_year,round),
		KEY circuit_id (circuit_id)
	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$driver_standings} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		season_year smallint(5) unsigned NOT NULL DEFAULT 0,
		driver_id bigint(20) unsigned NOT NULL DEFAULT 0,
		team_id bigint(20) unsigned NOT NULL DEFAULT 0,
		position smallint(5) unsigned NOT NULL DEFAULT 0,
		points decimal(7,2) NOT NULL DEFAULT 0.00,
		wins smallint(5) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY season_driver (season_year,driver_id),
		KEY driver_id (driver_id),
		KEY season_position (season_year,position)
	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$team_standings} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		season_year smallint(5) unsigned NOT NULL DEFAULT 0,
		team_id bigint(20) unsigned NOT NULL DEFAULT 0,
		position smallint(5) unsigned NOT NULL DEFAULT 0,
		points decimal(7,2) NOT NULL DEFAULT 0.00,
		wins smallint(5) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY season_team (season_year,team_id),
		KEY team_id (team_id),
		KEY season_position (season_year,position)
	) {$charset_collate};";

	foreach ( $sql as $statement ) {
		dbDelta( $statement );
	}

	update_option( 'f1wiki_core_db_version', F1WIKI_CORE_VERSION );
}
