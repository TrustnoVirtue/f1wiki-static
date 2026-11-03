<?php
/**
 * Admin list tables and dashboard widget.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add custom columns.
 *
 * @param array  $columns Existing columns.
 * @param string $type    Post type.
 * @return array
 */
function f1wiki_admin_columns( $columns, $type ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;

		if ( 'title' !== $key ) {
			continue;
		}

		switch ( $type ) {
			case 'driver':
				$new['f1_number']      = 'No.';
				$new['f1_nationality'] = 'Nationality';
				$new['f1_team']        = 'Latest team';
				$new['f1_seasons']     = 'Seasons';
				$new['f1_titles']      = 'Titles';
				$new['f1_wins']        = 'Wins';
				break;
			case 'team':
				$new['f1_nationality'] = 'Nationality';
				$new['f1_seasons']     = 'Seasons';
				$new['f1_titles']      = 'Titles';
				$new['f1_wins']        = 'Wins';
				break;
			case 'circuit':
				$new['f1_country'] = 'Country';
				$new['f1_races']   = 'Races';
				$new['f1_years']   = 'Years';
				break;
			case 'race':
				$new['f1_season'] = 'Season';
				$new['f1_round']  = 'Round';
				$new['f1_date']   = 'Date';
				$new['f1_winner'] = 'Winner';
				break;
			case 'season':
				$new['f1_year']      = 'Year';
				$new['f1_champion']  = 'Champion';
				$new['f1_rounds']    = 'Rounds';
				$new['f1_status']    = 'Status';
				break;
		}
	}

	return $new;
}
add_filter( 'manage_driver_posts_columns', 'f1wiki_admin_columns', 10, 2 );
add_filter( 'manage_team_posts_columns', 'f1wiki_admin_columns', 10, 2 );
add_filter( 'manage_circuit_posts_columns', 'f1wiki_admin_columns', 10, 2 );
add_filter( 'manage_race_posts_columns', 'f1wiki_admin_columns', 10, 2 );
add_filter( 'manage_season_posts_columns', 'f1wiki_admin_columns', 10, 2 );

/**
 * Render custom column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function f1wiki_admin_column_content( $column, $post_id ) {
	$stats = get_post_meta( $post_id, '_f1wiki_stats', true );
	$stats = is_array( $stats ) ? $stats : array();

	switch ( $column ) {
		case 'f1_number':
			echo esc_html( get_post_meta( $post_id, '_f1wiki_driver_number', true ) ?: '—' );
			break;
		case 'f1_nationality':
			$nat = get_post_meta( $post_id, '_f1wiki_nationality', true );
			echo $nat ? esc_html( $nat ) : '—';
			break;
		case 'f1_team':
			$team_id = (int) get_post_meta( $post_id, '_f1wiki_current_team', true );
			echo $team_id ? esc_html( get_the_title( $team_id ) ) : '—';
			break;
		case 'f1_seasons':
			$first = isset( $stats['first_season'] ) ? (int) $stats['first_season'] : 0;
			$last  = isset( $stats['last_season'] ) ? (int) $stats['last_season'] : 0;
			echo $first ? esc_html( $first === $last ? (string) $first : $first . '–' . $last ) : '—';
			break;
		case 'f1_titles':
			echo esc_html( isset( $stats['championships'] ) ? (string) $stats['championships'] : '—' );
			break;
		case 'f1_wins':
			echo esc_html( isset( $stats['wins'] ) ? (string) $stats['wins'] : '—' );
			break;
		case 'f1_country':
			echo esc_html( get_post_meta( $post_id, '_f1wiki_country', true ) ?: '—' );
			break;
		case 'f1_races':
			echo esc_html( isset( $stats['races'] ) ? (string) $stats['races'] : '—' );
			break;
		case 'f1_years':
			$first = isset( $stats['first_season'] ) ? (int) $stats['first_season'] : 0;
			$last  = isset( $stats['last_season'] ) ? (int) $stats['last_season'] : 0;
			echo $first ? esc_html( $first === $last ? (string) $first : $first . '–' . $last ) : '—';
			break;
		case 'f1_season':
			echo esc_html( get_post_meta( $post_id, '_f1wiki_season_year', true ) ?: '—' );
			break;
		case 'f1_round':
			echo esc_html( get_post_meta( $post_id, '_f1wiki_round', true ) ?: '—' );
			break;
		case 'f1_date':
			echo esc_html( f1wiki_format_date( get_post_meta( $post_id, '_f1wiki_race_date', true ) ) ?: '—' );
			break;
		case 'f1_winner':
			$winner = (int) get_post_meta( $post_id, '_f1wiki_winner_driver_id', true );
			echo $winner ? esc_html( get_the_title( $winner ) ) : '—';
			break;
		case 'f1_year':
			echo esc_html( get_post_meta( $post_id, '_f1wiki_season_year', true ) ?: '—' );
			break;
		case 'f1_champion':
			$champion = (int) get_post_meta( $post_id, '_f1wiki_champion_driver_id', true );
			echo $champion ? esc_html( get_the_title( $champion ) ) : '—';
			break;
		case 'f1_rounds':
			$rounds = (int) get_post_meta( $post_id, '_f1wiki_rounds', true );
			$done   = (int) get_post_meta( $post_id, '_f1wiki_completed_rounds', true );
			echo $rounds ? esc_html( $done . ' / ' . $rounds ) : '—';
			break;
		case 'f1_status':
			$status = get_post_meta( $post_id, '_f1wiki_season_status', true );
			echo esc_html( 'in_progress' === $status ? 'In progress' : ( $status ? ucfirst( $status ) : '—' ) );
			break;
	}
}
add_action( 'manage_driver_posts_custom_column', 'f1wiki_admin_column_content', 10, 2 );
add_action( 'manage_team_posts_custom_column', 'f1wiki_admin_column_content', 10, 2 );
add_action( 'manage_circuit_posts_custom_column', 'f1wiki_admin_column_content', 10, 2 );
add_action( 'manage_race_posts_custom_column', 'f1wiki_admin_column_content', 10, 2 );
add_action( 'manage_season_posts_custom_column', 'f1wiki_admin_column_content', 10, 2 );

/**
 * Sortable admin columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function f1wiki_sortable_columns( $columns ) {
	$columns['f1_number']  = 'f1_number';
	$columns['f1_team']    = 'f1_team';
	$columns['f1_seasons'] = 'f1_seasons';
	$columns['f1_titles']  = 'f1_titles';
	$columns['f1_wins']    = 'f1_wins';
	$columns['f1_country'] = 'f1_country';
	$columns['f1_races']   = 'f1_races';
	$columns['f1_year']    = 'f1_year';
	$columns['f1_date']    = 'f1_date';
	$columns['f1_round']   = 'f1_round';
	return $columns;
}
add_filter( 'manage_edit-driver_sortable_columns', 'f1wiki_sortable_columns' );
add_filter( 'manage_edit-team_sortable_columns', 'f1wiki_sortable_columns' );
add_filter( 'manage_edit-circuit_sortable_columns', 'f1wiki_sortable_columns' );
add_filter( 'manage_edit-race_sortable_columns', 'f1wiki_sortable_columns' );
add_filter( 'manage_edit-season_sortable_columns', 'f1wiki_sortable_columns' );

/**
 * Map sortable column keys to meta keys.
 *
 * @param WP_Query $query Query.
 */
function f1wiki_admin_sort_queries( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	$map     = array(
		'f1_number'  => '_f1wiki_driver_number',
		'f1_team'    => '_f1wiki_current_team',
		'f1_seasons' => '_f1wiki_seasons_sort',
		'f1_titles'  => '_f1wiki_titles_sort',
		'f1_wins'    => '_f1wiki_wins_sort',
		'f1_country' => '_f1wiki_country',
		'f1_races'   => '_f1wiki_races_sort',
		'f1_year'    => '_f1wiki_season_year',
		'f1_date'    => '_f1wiki_race_date',
		'f1_round'   => '_f1wiki_round',
	);

	if ( isset( $map[ $orderby ] ) ) {
		$query->set( 'meta_key', $map[ $orderby ] );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'f1wiki_admin_sort_queries' );

/**
 * Dashboard overview widget.
 */
function f1wiki_dashboard_widget() {
	wp_add_dashboard_widget(
		'f1wiki_dashboard',
		'F1 Wiki',
		'f1wiki_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'f1wiki_dashboard_widget' );

/**
 * Render dashboard widget.
 */
function f1wiki_render_dashboard_widget() {
	global $wpdb;

	$counts = f1wiki_get_counts();
	$latest = f1wiki_latest_season();
	$table  = f1wiki_results_table();
	$rows   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	?>
	<p style="margin-top:0">Last imported season: <strong><?php echo esc_html( $latest ); ?></strong></p>
	<table style="width:100%;border-collapse:collapse">
		<tr><td>Drivers</td><td style="text-align:right"><strong><?php echo esc_html( $counts['drivers'] ); ?></strong></td></tr>
		<tr><td>Teams</td><td style="text-align:right"><strong><?php echo esc_html( $counts['teams'] ); ?></strong></td></tr>
		<tr><td>Circuits</td><td style="text-align:right"><strong><?php echo esc_html( $counts['circuits'] ); ?></strong></td></tr>
		<tr><td>Races</td><td style="text-align:right"><strong><?php echo esc_html( $counts['races'] ); ?></strong></td></tr>
		<tr><td>Seasons</td><td style="text-align:right"><strong><?php echo esc_html( $counts['seasons'] ); ?></strong></td></tr>
		<tr><td>Result rows</td><td style="text-align:right"><strong><?php echo esc_html( $rows ); ?></strong></td></tr>
	</table>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=driver' ) ); ?>">Manage drivers</a>
		<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">View site</a>
	</p>
	<p style="color:#646970;margin-bottom:0">Refresh data with <code>wp f1wiki seed</code>, then <code>wp f1wiki stats</code>.</p>
	<?php
}
