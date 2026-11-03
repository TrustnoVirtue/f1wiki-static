<?php
/**
 * ACF field groups for F1 wiki content.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Small helper for numeric fields.
 *
 * @param string $key   Field key.
 * @param string $label Label.
 * @param string $name  Field name.
 * @return array
 */
function f1wiki_acf_number_field( $key, $label, $name ) {
	return array(
		'key'   => $key,
		'label' => $label,
		'name'  => $name,
		'type'  => 'number',
	);
}

/**
 * Register all field groups.
 */
function f1wiki_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_f1wiki_driver',
			'title'    => 'Driver details',
			'fields'   => array(
				array(
					'key'   => 'field_f1wiki_driver_code',
					'label' => 'Three-letter code',
					'name'  => 'driver_code',
					'type'  => 'text',
					'maxlength' => 3,
				),
				f1wiki_acf_number_field( 'field_f1wiki_driver_number', 'Car number', 'driver_number' ),
				array(
					'key'            => 'field_f1wiki_driver_dob',
					'label'          => 'Date of birth',
					'name'           => 'date_of_birth',
					'type'           => 'date_picker',
					'display_format' => 'j M Y',
					'return_format'  => 'Y-m-d',
					'first_day'      => 1,
				),
				array(
					'key'   => 'field_f1wiki_driver_nationality',
					'label' => 'Nationality',
					'name'  => 'nationality',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_f1wiki_driver_wikipedia',
					'label' => 'Wikipedia URL',
					'name'  => 'wikipedia_url',
					'type'  => 'url',
				),
				array(
					'key'          => 'field_f1wiki_driver_current_team',
					'label'        => 'Latest team',
					'name'         => 'current_team',
					'type'         => 'post_object',
					'post_type'    => array( 'team' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'        => 'field_f1wiki_driver_stats',
					'label'      => 'Career statistics',
					'name'       => 'career_stats',
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_starts', 'Starts', 'starts' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_wins', 'Wins', 'wins' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_podiums', 'Podiums', 'podiums' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_poles', 'Pole positions', 'poles' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_fastest_laps', 'Fastest laps', 'fastest_laps' ),
						array(
							'key'   => 'field_f1wiki_driver_stats_points',
							'label' => 'Points',
							'name'  => 'points',
							'type'  => 'number',
							'step'  => '0.01',
						),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_championships', 'Championships', 'championships' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_first_season', 'First season in archive', 'first_season' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_last_season', 'Last season in archive', 'last_season' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_best_championship', 'Best championship position', 'best_championship' ),
						f1wiki_acf_number_field( 'field_f1wiki_driver_stats_best_year', 'Best championship year', 'best_championship_year' ),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'driver',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_f1wiki_team',
			'title'    => 'Team details',
			'fields'   => array(
				array(
					'key'     => 'field_f1wiki_team_color',
					'label'   => 'Accent color',
					'name'    => 'team_color',
					'type'    => 'color_picker',
					'default_value' => '#3671C6',
				),
				array(
					'key'   => 'field_f1wiki_team_nationality',
					'label' => 'Nationality',
					'name'  => 'nationality',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_f1wiki_team_wikipedia',
					'label' => 'Wikipedia URL',
					'name'  => 'wikipedia_url',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_f1wiki_team_base',
					'label' => 'Base',
					'name'  => 'base',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_f1wiki_team_principal',
					'label' => 'Team principal',
					'name'  => 'team_principal',
					'type'  => 'text',
				),
				array(
					'key'        => 'field_f1wiki_team_stats',
					'label'      => 'Team statistics',
					'name'       => 'team_stats',
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_first_season', 'First season in archive', 'first_season' ),
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_last_season', 'Last season in archive', 'last_season' ),
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_starts', 'Starts', 'starts' ),
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_wins', 'Wins', 'wins' ),
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_podiums', 'Podiums', 'podiums' ),
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_poles', 'Pole positions', 'poles' ),
						array(
							'key'   => 'field_f1wiki_team_stats_points',
							'label' => 'Points',
							'name'  => 'points',
							'type'  => 'number',
							'step'  => '0.01',
						),
						f1wiki_acf_number_field( 'field_f1wiki_team_stats_championships', 'Championships', 'championships' ),
						array(
							'key'   => 'field_f1wiki_team_stats_champ_years',
							'label' => 'Championship years',
							'name'  => 'championship_years',
							'type'  => 'text',
							'instructions' => 'Comma-separated years.',
						),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'team',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_f1wiki_circuit',
			'title'    => 'Circuit details',
			'fields'   => array(
				array(
					'key'   => 'field_f1wiki_circuit_locality',
					'label' => 'Locality',
					'name'  => 'locality',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_f1wiki_circuit_country',
					'label' => 'Country',
					'name'  => 'country',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_f1wiki_circuit_latitude',
					'label' => 'Latitude',
					'name'  => 'latitude',
					'type'  => 'number',
					'step'  => 'any',
				),
				array(
					'key'   => 'field_f1wiki_circuit_longitude',
					'label' => 'Longitude',
					'name'  => 'longitude',
					'type'  => 'number',
					'step'  => 'any',
				),
				array(
					'key'   => 'field_f1wiki_circuit_wikipedia',
					'label' => 'Wikipedia URL',
					'name'  => 'wikipedia_url',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_f1wiki_circuit_length',
					'label' => 'Circuit length (km)',
					'name'  => 'circuit_length_km',
					'type'  => 'number',
					'step'  => '0.001',
				),
				array(
					'key'   => 'field_f1wiki_circuit_corners',
					'label' => 'Corners',
					'name'  => 'corners',
					'type'  => 'number',
				),
				array(
					'key'   => 'field_f1wiki_circuit_lap_record',
					'label' => 'Lap record',
					'name'  => 'lap_record',
					'type'  => 'text',
				),
				array(
					'key'        => 'field_f1wiki_circuit_stats',
					'label'      => 'Circuit statistics',
					'name'       => 'circuit_stats',
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						f1wiki_acf_number_field( 'field_f1wiki_circuit_stats_races', 'Races in archive', 'races' ),
						f1wiki_acf_number_field( 'field_f1wiki_circuit_stats_first', 'First season in archive', 'first_season' ),
						f1wiki_acf_number_field( 'field_f1wiki_circuit_stats_last', 'Last season in archive', 'last_season' ),
						array(
							'key'   => 'field_f1wiki_circuit_stats_driver',
							'label' => 'Most wins (driver)',
							'name'  => 'most_wins_driver',
							'type'  => 'text',
						),
						f1wiki_acf_number_field( 'field_f1wiki_circuit_stats_driver_wins', 'Driver win count', 'most_wins_driver_wins' ),
						array(
							'key'   => 'field_f1wiki_circuit_stats_team',
							'label' => 'Most wins (team)',
							'name'  => 'most_wins_team',
							'type'  => 'text',
						),
						f1wiki_acf_number_field( 'field_f1wiki_circuit_stats_team_wins', 'Team win count', 'most_wins_team_wins' ),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'circuit',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_f1wiki_season',
			'title'    => 'Season details',
			'fields'   => array(
				f1wiki_acf_number_field( 'field_f1wiki_season_year', 'Year', 'season_year' ),
				array(
					'key'          => 'field_f1wiki_season_champion_driver',
					'label'        => 'Champion / leader (driver)',
					'name'         => 'champion_driver',
					'type'         => 'post_object',
					'post_type'    => array( 'driver' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'          => 'field_f1wiki_season_champion_team',
					'label'        => 'Champion / leader (team)',
					'name'         => 'champion_team',
					'type'         => 'post_object',
					'post_type'    => array( 'team' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				f1wiki_acf_number_field( 'field_f1wiki_season_rounds', 'Scheduled rounds', 'rounds_count' ),
				f1wiki_acf_number_field( 'field_f1wiki_season_completed', 'Completed rounds', 'completed_rounds' ),
				array(
					'key'   => 'field_f1wiki_season_status',
					'label' => 'Status',
					'name'  => 'season_status',
					'type'  => 'select',
					'choices' => array(
						'completed'   => 'Completed',
						'in_progress' => 'In progress',
					),
					'default_value' => 'completed',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'season',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_f1wiki_race',
			'title'    => 'Race details',
			'fields'   => array(
				f1wiki_acf_number_field( 'field_f1wiki_race_season', 'Season', 'season_year' ),
				f1wiki_acf_number_field( 'field_f1wiki_race_round', 'Round', 'round' ),
				array(
					'key'            => 'field_f1wiki_race_date',
					'label'          => 'Race date',
					'name'           => 'race_date',
					'type'           => 'date_picker',
					'display_format' => 'j M Y',
					'return_format'  => 'Y-m-d',
					'first_day'      => 1,
				),
				array(
					'key'          => 'field_f1wiki_race_circuit',
					'label'        => 'Circuit',
					'name'         => 'circuit',
					'type'         => 'post_object',
					'post_type'    => array( 'circuit' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'          => 'field_f1wiki_race_winner',
					'label'        => 'Winner',
					'name'         => 'winner_driver',
					'type'         => 'post_object',
					'post_type'    => array( 'driver' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'          => 'field_f1wiki_race_winner_team',
					'label'        => 'Winning team',
					'name'         => 'winner_team',
					'type'         => 'post_object',
					'post_type'    => array( 'team' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'          => 'field_f1wiki_race_pole',
					'label'        => 'Pole position',
					'name'         => 'pole_driver',
					'type'         => 'post_object',
					'post_type'    => array( 'driver' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'          => 'field_f1wiki_race_fastest',
					'label'        => 'Fastest lap',
					'name'         => 'fastest_lap_driver',
					'type'         => 'post_object',
					'post_type'    => array( 'driver' ),
					'return_format' => 'id',
					'ui'           => 1,
					'allow_null'   => 1,
				),
				array(
					'key'   => 'field_f1wiki_race_wikipedia',
					'label' => 'Wikipedia URL',
					'name'  => 'wikipedia_url',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_f1wiki_race_has_results',
					'label' => 'Classification available',
					'name'  => 'has_results',
					'type'  => 'true_false',
					'ui'    => 1,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'race',
					),
				),
			),
		)
	);
}
add_action( 'acf/init', 'f1wiki_register_acf_fields' );
