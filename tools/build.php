<?php
/**
 * Static site generator for the F1 Archive (GitHub Pages).
 *
 * Reads the local WordPress database and writes a fully static site:
 * JSON data, resized images and HTML shells (rendered client-side).
 *
 * Usage: php tools/build.php
 */

$root   = dirname( __DIR__ );
$dbHost = '127.0.0.1';
$dbUser = 'f1wiki';
$dbPass = 'F1w1kiL0cal2026';
$dbName = 'f1wiki';
$uploadDir = 'C:/xampp/htdocs/f1wiki/wp-content/uploads';

$db = new mysqli( $dbHost, $dbUser, $dbPass, $dbName );
if ( $db->connect_errno ) {
	fwrite( STDERR, 'DB connection failed: ' . $db->connect_error . "\n" );
	exit( 1 );
}
$db->set_charset( 'utf8mb4' );

foreach ( array( 'data', 'data/drivers', 'data/teams', 'data/seasons', 'data/races', 'assets', 'assets/img', 'assets/img/drivers', 'assets/img/teams', 'assets/fonts', 'assets/css', 'assets/js', 'tools' ) as $dir ) {
	if ( ! is_dir( $root . '/' . $dir ) ) {
		mkdir( $root . '/' . $dir, 0777, true );
	}
}

function rows( $db, $sql ) {
	$res = $db->query( $sql );
	if ( ! $res ) {
		fwrite( STDERR, 'SQL error: ' . $db->error . "\n" . $sql . "\n" );
		exit( 1 );
	}
	return $res->fetch_all( MYSQLI_ASSOC );
}

function meta_map( $db, $type ) {
	$sql = "SELECT m.post_id, m.meta_key, m.meta_value
		FROM wp_postmeta m
		INNER JOIN wp_posts p ON p.ID = m.post_id
		WHERE p.post_type = '" . $db->real_escape_string( $type ) . "'";
	$map = array();
	foreach ( rows( $db, $sql ) as $row ) {
		$map[ (int) $row['post_id'] ][ $row['meta_key'] ] = $row['meta_value'];
	}
	return $map;
}

function posts_by_type( $db, $type ) {
	$type = $db->real_escape_string( $type );
	return rows( $db, "SELECT ID, post_title, post_name, post_date FROM wp_posts WHERE post_type='{$type}' AND post_status='publish' ORDER BY post_title ASC" );
}

function unserialize_meta( $value, $default = array() ) {
	if ( ! is_string( $value ) || '' === $value ) {
		return $default;
	}
	$data = @unserialize( $value );
	return is_array( $data ) ? $data : $default;
}

function json_write( $root, $rel, $data ) {
	$json = json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	file_put_contents( $root . '/' . $rel, $json );
}

function export_image( $src, $root, $rel, $max_width, $jpeg = false ) {
	if ( ! $src || ! file_exists( $src ) ) {
		return '';
	}

	$info = @getimagesize( $src );
	if ( ! $info ) {
		return '';
	}

	if ( ! extension_loaded( 'gd' ) ) {
		copy( $src, $root . '/' . $rel );
		return $rel;
	}

	$image = false;
	if ( 'image/png' === $info['mime'] ) {
		$image = @imagecreatefrompng( $src );
	} elseif ( 'image/jpeg' === $info['mime'] ) {
		$image = @imagecreatefromjpeg( $src );
	} elseif ( 'image/webp' === $info['mime'] ) {
		$image = @imagecreatefromwebp( $src );
	}

	if ( ! $image ) {
		copy( $src, $root . '/' . $rel );
		return $rel;
	}

	$width  = imagesx( $image );
	$height = imagesy( $image );

	if ( $width > $max_width ) {
		$new_height = (int) round( $height * ( $max_width / $width ) );
		$resized    = imagescale( $image, $max_width, $new_height );
		if ( $resized ) {
			imagedestroy( $image );
			$image = $resized;
		}
	}

	if ( $jpeg ) {
		imagejpeg( $image, $root . '/' . $rel, 84 );
	} else {
		imagesavealpha( $image, true );
		imagepng( $image, $root . '/' . $rel, 8 );
	}

	imagedestroy( $image );

	return $rel;
}

function pick_color( $team_slug, $meta ) {
	foreach ( array( 'team_color', '_f1wiki_team_color' ) as $key ) {
		if ( ! empty( $meta[ $key ] ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $meta[ $key ] ) ) {
			return strtoupper( $meta[ $key ] );
		}
	}

	$map = array(
		'ferrari'      => '#E8002D',
		'red_bull'     => '#3671C6',
		'mercedes'     => '#27F4D2',
		'mclaren'      => '#FF8000',
		'aston_martin' => '#229971',
		'alpine'       => '#FF87BC',
		'williams'     => '#64C4FF',
		'rb'           => '#6692FF',
		'haas'         => '#B6BABD',
		'sauber'       => '#52E252',
		'audi'         => '#C8102E',
		'renault'      => '#FFF500',
	);

	$key = str_replace( '-', '_', strtolower( $team_slug ) );
	if ( isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}

	$palette = array( '#E8002D', '#3671C6', '#27F4D2', '#FF8000', '#229971', '#FF87BC', '#64C4FF', '#B6BABD', '#FFF500', '#C8102E', '#52E252', '#6692FF' );

	return $palette[ abs( crc32( $key ) ) % count( $palette ) ];
}

function nat_code( $nationality ) {
	$map = array(
		'British' => 'GBR', 'German' => 'GER', 'Spanish' => 'ESP', 'Finnish' => 'FIN', 'Italian' => 'ITA',
		'French' => 'FRA', 'Brazilian' => 'BRA', 'Dutch' => 'NED', 'Australian' => 'AUS', 'Austrian' => 'AUT',
		'Mexican' => 'MEX', 'Canadian' => 'CAN', 'Japanese' => 'JPN', 'Monegasque' => 'MON', 'Thai' => 'THA',
		'Swiss' => 'SUI', 'Belgian' => 'BEL', 'Danish' => 'DEN', 'Swedish' => 'SWE', 'Russian' => 'RUS',
		'Polish' => 'POL', 'Argentine' => 'ARG', 'Argentinian' => 'ARG', 'Colombian' => 'COL', 'Venezuelan' => 'VEN',
		'New Zealander' => 'NZL', 'South African' => 'RSA', 'Portuguese' => 'POR', 'Irish' => 'IRL', 'Hungarian' => 'HUN',
		'Czech' => 'CZE', 'Indian' => 'IND', 'Indonesian' => 'INA', 'Malaysian' => 'MAS', 'Chinese' => 'CHN',
		'American' => 'USA', 'Liechtensteiner' => 'LIE', 'Uruguayan' => 'URU', 'Chilean' => 'CHI', 'Dutch' => 'NED',
		'Emirati' => 'UAE', 'Saudi' => 'KSA', 'Israeli' => 'ISR', 'Korean' => 'KOR', 'Romanian' => 'ROU',
	);

	return isset( $map[ $nationality ] ) ? $map[ $nationality ] : strtoupper( substr( $nationality, 0, 3 ) );
}

/* ------------------------------------------------------------------ */
/* Load entities                                                       */
/* ------------------------------------------------------------------ */

echo "Loading database...\n";

$team_posts   = posts_by_type( $db, 'team' );
$driver_posts = posts_by_type( $db, 'driver' );
$circuit_posts = posts_by_type( $db, 'circuit' );
$race_posts   = posts_by_type( $db, 'race' );

$team_meta    = meta_map( $db, 'team' );
$driver_meta  = meta_map( $db, 'driver' );
$circuit_meta = meta_map( $db, 'circuit' );
$race_meta    = meta_map( $db, 'race' );

$attachments = array();
foreach ( rows( $db, "SELECT ID, post_mime_type FROM wp_posts WHERE post_type='attachment'" ) as $row ) {
	$attachments[ (int) $row['ID'] ] = $row['post_mime_type'];
}
$attachment_files = array();
foreach ( rows( $db, "SELECT post_id, meta_value FROM wp_postmeta WHERE meta_key='_wp_attached_file'" ) as $row ) {
	$attachment_files[ (int) $row['post_id'] ] = $row['meta_value'];
}

$teams   = array();
$teams_by_id = array();
foreach ( $team_posts as $post ) {
	$id   = (int) $post['ID'];
	$meta = isset( $team_meta[ $id ] ) ? $team_meta[ $id ] : array();
	$stats = unserialize_meta( isset( $meta['_f1wiki_stats'] ) ? $meta['_f1wiki_stats'] : '' );

	$logo = '';
	$credit = isset( $meta['_f1wiki_photo_credit'] ) ? $meta['_f1wiki_photo_credit'] : '';
	$credit_url = isset( $meta['_f1wiki_photo_source'] ) ? $meta['_f1wiki_photo_source'] : '';
	if ( ! empty( $meta['_thumbnail_id'] ) ) {
		$att = (int) $meta['_thumbnail_id'];
		$file = isset( $attachment_files[ $att ] ) ? $attachment_files[ $att ] : '';
		if ( $file && file_exists( $uploadDir . '/' . $file ) ) {
			$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			$ext = in_array( $ext, array( 'png', 'jpg', 'jpeg', 'webp' ), true ) ? $ext : 'png';
			if ( 'jpg' === $ext || 'jpeg' === $ext ) {
				$ext = 'jpg';
			}
			$logo = export_image( $uploadDir . '/' . $file, $root, 'assets/img/teams/' . $id . '.' . $ext, 520, false );
		}
	}

	$team = array(
		'id'         => $id,
		'slug'       => $post['post_name'],
		'name'       => $post['post_title'],
		'nat'        => isset( $meta['_f1wiki_nationality'] ) ? $meta['_f1wiki_nationality'] : '',
		'color'      => pick_color( $post['post_name'], $meta ),
		'logo'       => $logo,
		'credit'     => $credit,
		'creditUrl'  => $credit_url,
		'stats'      => array(
			'starts'        => isset( $stats['starts'] ) ? (int) $stats['starts'] : 0,
			'wins'          => isset( $stats['wins'] ) ? (int) $stats['wins'] : 0,
			'podiums'       => isset( $stats['podiums'] ) ? (int) $stats['podiums'] : 0,
			'poles'         => isset( $stats['poles'] ) ? (int) $stats['poles'] : 0,
			'points'        => isset( $stats['points'] ) ? (float) $stats['points'] : 0,
			'championships' => isset( $stats['championships'] ) ? (int) $stats['championships'] : 0,
		),
		'firstSeason' => isset( $stats['first_season'] ) ? (int) $stats['first_season'] : 0,
		'lastSeason'  => isset( $stats['last_season'] ) ? (int) $stats['last_season'] : 0,
	);

	$teams[] = $team;
	$teams_by_id[ $id ] = $team;
}

$drivers = array();
$drivers_by_id = array();
foreach ( $driver_posts as $post ) {
	$id   = (int) $post['ID'];
	$meta = isset( $driver_meta[ $id ] ) ? $driver_meta[ $id ] : array();
	$stats = unserialize_meta( isset( $meta['_f1wiki_stats'] ) ? $meta['_f1wiki_stats'] : '' );

	$team_id = isset( $meta['_f1wiki_current_team'] ) ? (int) $meta['_f1wiki_current_team'] : 0;
	$team    = isset( $teams_by_id[ $team_id ] ) ? $teams_by_id[ $team_id ] : null;

	$photo = '';
	$credit = isset( $meta['_f1wiki_photo_credit'] ) ? $meta['_f1wiki_photo_credit'] : '';
	$credit_url = isset( $meta['_f1wiki_photo_source'] ) ? $meta['_f1wiki_photo_source'] : '';
	if ( ! empty( $meta['_thumbnail_id'] ) ) {
		$att = (int) $meta['_thumbnail_id'];
		$file = isset( $attachment_files[ $att ] ) ? $attachment_files[ $att ] : '';
		if ( $file && file_exists( $uploadDir . '/' . $file ) ) {
			$photo = export_image( $uploadDir . '/' . $file, $root, 'assets/img/drivers/' . $id . '.jpg', 460, true );
		}
	}

	$driver = array(
		'id'        => $id,
		'slug'      => $post['post_name'],
		'name'      => $post['post_title'],
		'nat'       => isset( $meta['_f1wiki_nationality'] ) ? $meta['_f1wiki_nationality'] : '',
		'code'      => isset( $meta['_f1wiki_driver_code'] ) ? $meta['_f1wiki_driver_code'] : '',
		'number'    => isset( $meta['_f1wiki_number'] ) ? $meta['_f1wiki_number'] : '',
		'dob'       => isset( $meta['_f1wiki_dob'] ) ? $meta['_f1wiki_dob'] : '',
		'photo'     => $photo,
		'credit'    => $credit,
		'creditUrl' => $credit_url,
		'teamId'    => $team ? $team['id'] : 0,
		'teamName'  => $team ? $team['name'] : '',
		'color'     => $team ? $team['color'] : '#E10600',
		'stats'     => array(
			'starts'        => isset( $stats['starts'] ) ? (int) $stats['starts'] : 0,
			'wins'          => isset( $stats['wins'] ) ? (int) $stats['wins'] : 0,
			'podiums'       => isset( $stats['podiums'] ) ? (int) $stats['podiums'] : 0,
			'poles'         => isset( $stats['poles'] ) ? (int) $stats['poles'] : 0,
			'fastestLaps'   => isset( $stats['fastest_laps'] ) ? (int) $stats['fastest_laps'] : 0,
			'points'        => isset( $stats['points'] ) ? (float) $stats['points'] : 0,
			'championships' => isset( $stats['championships'] ) ? (int) $stats['championships'] : 0,
			'best'          => isset( $stats['best_championship'] ) ? (int) $stats['best_championship'] : 0,
			'bestYear'      => isset( $stats['best_championship_year'] ) ? (int) $stats['best_championship_year'] : 0,
		),
		'firstSeason' => isset( $stats['first_season'] ) ? (int) $stats['first_season'] : 0,
		'lastSeason'  => isset( $stats['last_season'] ) ? (int) $stats['last_season'] : 0,
	);

	$drivers[] = $driver;
	$drivers_by_id[ $id ] = $driver;
}

$circuits = array();
foreach ( $circuit_posts as $post ) {
	$id   = (int) $post['ID'];
	$meta = isset( $circuit_meta[ $id ] ) ? $circuit_meta[ $id ] : array();
	$stats = unserialize_meta( isset( $meta['_f1wiki_stats'] ) ? $meta['_f1wiki_stats'] : '' );

	$circuits[] = array(
		'id'          => $id,
		'slug'        => $post['post_name'],
		'name'        => $post['post_title'],
		'locality'    => isset( $meta['_f1wiki_locality'] ) ? $meta['_f1wiki_locality'] : '',
		'country'     => isset( $meta['_f1wiki_country'] ) ? $meta['_f1wiki_country'] : '',
		'races'       => isset( $stats['races'] ) ? (int) $stats['races'] : 0,
		'firstSeason' => isset( $stats['first_season'] ) ? (int) $stats['first_season'] : 0,
		'lastSeason'  => isset( $stats['last_season'] ) ? (int) $stats['last_season'] : 0,
		'mostWins'    => isset( $stats['most_wins'] ) ? (int) $stats['most_wins'] : 0,
	);
}

/* ------------------------------------------------------------------ */
/* Standings, champions, races                                          */
/* ------------------------------------------------------------------ */

echo "Building standings and calendars...\n";

$latest = (int) $db->query( "SELECT MAX(season_year) FROM wp_f1_driver_standings" )->fetch_row()[0];
$first  = (int) $db->query( "SELECT MIN(season_year) FROM wp_f1_driver_standings" )->fetch_row()[0];

$driver_standings_rows = rows( $db, "SELECT * FROM wp_f1_driver_standings ORDER BY season_year ASC, position ASC" );
$team_standings_rows   = rows( $db, "SELECT * FROM wp_f1_team_standings ORDER BY season_year ASC, position ASC" );

$driver_standings = array();
foreach ( $driver_standings_rows as $row ) {
	$driver_standings[ (int) $row['season_year'] ][] = array(
		'pos'      => (int) $row['position'],
		'driverId' => (int) $row['driver_id'],
		'teamId'   => (int) $row['team_id'],
		'points'   => (float) $row['points'],
		'wins'     => (int) $row['wins'],
	);
}

$team_standings = array();
foreach ( $team_standings_rows as $row ) {
	$team_standings[ (int) $row['season_year'] ][] = array(
		'pos'    => (int) $row['position'],
		'teamId' => (int) $row['team_id'],
		'points' => (float) $row['points'],
		'wins'   => (int) $row['wins'],
	);
}

$champions = array();
foreach ( $driver_standings as $year => $standing ) {
	$leader = $standing[0];
	$team_leader = isset( $team_standings[ $year ][0] ) ? $team_standings[ $year ][0] : null;
	$driver = isset( $drivers_by_id[ $leader['driverId'] ] ) ? $drivers_by_id[ $leader['driverId'] ] : null;
	$team   = $team_leader && isset( $teams_by_id[ $team_leader['teamId'] ] ) ? $teams_by_id[ $team_leader['teamId'] ] : null;

	$champions[] = array(
		'year'      => (int) $year,
		'driverId'  => $driver ? $driver['id'] : 0,
		'driver'    => $driver ? $driver['name'] : '',
		'teamId'    => $team ? $team['id'] : 0,
		'team'      => $team ? $team['name'] : '',
		'color'     => $team ? $team['color'] : '#E10600',
		'points'    => $leader['points'],
	);
}

$races = array();
$races_by_id = array();
$races_by_year = array();
foreach ( $race_posts as $post ) {
	$id   = (int) $post['ID'];
	$meta = isset( $race_meta[ $id ] ) ? $race_meta[ $id ] : array();
	$year = isset( $meta['_f1wiki_season_year'] ) ? (int) $meta['_f1wiki_season_year'] : 0;
	$circuit_id = isset( $meta['_f1wiki_circuit_id'] ) ? (int) $meta['_f1wiki_circuit_id'] : 0;
	$winner_id  = isset( $meta['_f1wiki_winner_driver_id'] ) ? (int) $meta['_f1wiki_winner_driver_id'] : 0;
	$winner_team_id = isset( $meta['_f1wiki_winner_team_id'] ) ? (int) $meta['_f1wiki_winner_team_id'] : 0;

	$circuit = null;
	foreach ( $circuits as $c ) {
		if ( $c['id'] === $circuit_id ) {
			$circuit = $c;
			break;
		}
	}

	$pole_id     = isset( $meta['_f1wiki_pole_driver_id'] ) ? (int) $meta['_f1wiki_pole_driver_id'] : 0;
	$fastest_id  = isset( $meta['_f1wiki_fastest_lap_driver_id'] ) ? (int) $meta['_f1wiki_fastest_lap_driver_id'] : 0;

	$race = array(
		'id'         => $id,
		'name'       => $post['post_title'],
		'year'       => $year,
		'round'      => isset( $meta['_f1wiki_round'] ) ? (int) $meta['_f1wiki_round'] : 0,
		'date'       => isset( $meta['_f1wiki_race_date'] ) ? $meta['_f1wiki_race_date'] : '',
		'hasResults' => ! empty( $meta['_f1wiki_has_results'] ),
		'circuitId'  => $circuit ? $circuit['id'] : 0,
		'circuit'    => $circuit ? $circuit['name'] : '',
		'country'    => $circuit ? $circuit['country'] : '',
		'winnerId'   => $winner_id,
		'winner'     => isset( $drivers_by_id[ $winner_id ] ) ? $drivers_by_id[ $winner_id ]['name'] : '',
		'winnerTeam' => isset( $teams_by_id[ $winner_team_id ] ) ? $teams_by_id[ $winner_team_id ]['name'] : '',
		'winnerColor' => isset( $teams_by_id[ $winner_team_id ] ) ? $teams_by_id[ $winner_team_id ]['color'] : '#E10600',
		'poleId'     => $pole_id,
		'pole'       => isset( $drivers_by_id[ $pole_id ] ) ? $drivers_by_id[ $pole_id ]['name'] : '',
		'fastestId'  => $fastest_id,
		'fastest'    => isset( $drivers_by_id[ $fastest_id ] ) ? $drivers_by_id[ $fastest_id ]['name'] : '',
	);

	$races[] = $race;
	$races_by_id[ $id ] = $race;
	$races_by_year[ $year ][] = $race;
}

foreach ( $races_by_year as $year => &$list ) {
	usort( $list, function ( $a, $b ) {
		return $a['round'] <=> $b['round'];
	} );
}
unset( $list );

/* ------------------------------------------------------------------ */
/* Season files                                                        */
/* ------------------------------------------------------------------ */

echo "Writing season data...\n";

$seasons_meta = array();
foreach ( array_keys( $driver_standings ) as $year ) {
	$dstanding = $driver_standings[ $year ];
	$tstanding = isset( $team_standings[ $year ] ) ? $team_standings[ $year ] : array();
	$calendar  = isset( $races_by_year[ $year ] ) ? $races_by_year[ $year ] : array();

	$completed = 0;
	foreach ( $calendar as $race ) {
		if ( $race['hasResults'] ) {
			$completed++;
		}
	}

	$champion = $champions[ $year - $first ];
	$team_champion = null;
	if ( ! empty( $tstanding ) ) {
		$team_champion = isset( $teams_by_id[ $tstanding[0]['teamId'] ] ) ? $teams_by_id[ $tstanding[0]['teamId'] ] : null;
	}

	$driver_rows = array();
	foreach ( $dstanding as $row ) {
		$driver = isset( $drivers_by_id[ $row['driverId'] ] ) ? $drivers_by_id[ $row['driverId'] ] : null;
		$team   = isset( $teams_by_id[ $row['teamId'] ] ) ? $teams_by_id[ $row['teamId'] ] : null;
		$driver_rows[] = array(
			'pos'     => $row['pos'],
			'driverId' => $row['driverId'],
			'name'    => $driver ? $driver['name'] : '',
			'photo'   => $driver ? $driver['photo'] : '',
			'teamId'  => $row['teamId'],
			'team'    => $team ? $team['name'] : '',
			'color'   => $team ? $team['color'] : '#999999',
			'points'  => $row['points'],
			'wins'    => $row['wins'],
		);
	}

	$team_rows = array();
	foreach ( $tstanding as $row ) {
		$team = isset( $teams_by_id[ $row['teamId'] ] ) ? $teams_by_id[ $row['teamId'] ] : null;
		$team_rows[] = array(
			'pos'    => $row['pos'],
			'teamId' => $row['teamId'],
			'name'   => $team ? $team['name'] : '',
			'logo'   => $team ? $team['logo'] : '',
			'color'  => $team ? $team['color'] : '#999999',
			'points' => $row['points'],
			'wins'   => $row['wins'],
		);
	}

	$season = array(
		'year'          => (int) $year,
		'rounds'        => count( $calendar ),
		'completed'     => $completed,
		'champion'      => $champion,
		'teamChampion'  => $team_champion ? array( 'id' => $team_champion['id'], 'name' => $team_champion['name'], 'color' => $team_champion['color'], 'logo' => $team_champion['logo'] ) : null,
		'drivers'       => $driver_rows,
		'teams'         => $team_rows,
		'races'         => $calendar,
	);

	json_write( $root, 'data/seasons/' . $year . '.json', $season );

	$seasons_meta[] = array(
		'year'         => (int) $year,
		'rounds'       => count( $calendar ),
		'completed'    => $completed,
		'champion'     => $champion,
		'teamChampion' => $team_champion ? array( 'name' => $team_champion['name'], 'color' => $team_champion['color'], 'logo' => $team_champion['logo'] ) : null,
		'driverCount'  => count( $driver_rows ),
		'teamCount'    => count( $team_rows ),
	);
}

usort( $seasons_meta, function ( $a, $b ) {
	return $b['year'] <=> $a['year'];
} );

json_write( $root, 'data/seasons.json', $seasons_meta );
json_write( $root, 'data/champions.json', $champions );
json_write( $root, 'data/circuits.json', $circuits );

/* ------------------------------------------------------------------ */
/* Driver detail files                                                 */
/* ------------------------------------------------------------------ */

echo "Writing driver profiles...\n";

$driver_seasons = array();
foreach ( $driver_standings_rows as $row ) {
	$driver_seasons[ (int) $row['driver_id'] ][] = array(
		'year'   => (int) $row['season_year'],
		'teamId' => (int) $row['team_id'],
		'pos'    => (int) $row['position'],
		'points' => (float) $row['points'],
		'wins'   => (int) $row['wins'],
	);
}

$driver_results = array();
$sql = "SELECT r.driver_id, r.race_id, r.position, r.position_text, r.points, r.team_id, r.grid, r.laps, r.status,
		p.post_title AS race_name, t.post_title AS team_name
	FROM wp_f1_results r
	INNER JOIN wp_posts p ON p.ID = r.race_id
	INNER JOIN wp_posts t ON t.ID = r.team_id
	ORDER BY r.season_year DESC, r.round DESC";
foreach ( rows( $db, $sql ) as $row ) {
	$driver_results[ (int) $row['driver_id'] ][] = $row;
}

foreach ( $drivers as $driver ) {
	$id = $driver['id'];
	$seasons = isset( $driver_seasons[ $id ] ) ? $driver_seasons[ $id ] : array();
	usort( $seasons, function ( $a, $b ) {
		return $b['year'] <=> $a['year'];
	} );

	$season_rows = array();
	foreach ( $seasons as $season ) {
		$team = isset( $teams_by_id[ $season['teamId'] ] ) ? $teams_by_id[ $season['teamId'] ] : null;
		$season_rows[] = array(
			'year'   => $season['year'],
			'teamId' => $season['teamId'],
			'team'   => $team ? $team['name'] : '',
			'color'  => $team ? $team['color'] : '#999999',
			'pos'    => $season['pos'],
			'points' => $season['points'],
			'wins'   => $season['wins'],
		);
	}

	$recent = array();
	$results = isset( $driver_results[ $id ] ) ? $driver_results[ $id ] : array();
	foreach ( array_slice( $results, 0, 14 ) as $row ) {
		$race = isset( $races_by_id[ (int) $row['race_id'] ] ) ? $races_by_id[ (int) $row['race_id'] ] : null;
		$recent[] = array(
			'raceId' => (int) $row['race_id'],
			'race'   => $race ? $race['name'] : $row['race_name'],
			'year'   => $race ? $race['year'] : 0,
			'round'  => $race ? $race['round'] : 0,
			'pos'    => $row['position_text'],
			'points' => (float) $row['points'],
			'team'   => $row['team_name'],
			'status' => $row['status'],
		);
	}

	json_write( $root, 'data/drivers/' . $id . '.json', array( 'driver' => $driver, 'seasons' => $season_rows, 'recent' => $recent ) );
}

json_write( $root, 'data/drivers.json', $drivers );

/* ------------------------------------------------------------------ */
/* Team detail files                                                   */
/* ------------------------------------------------------------------ */

echo "Writing team profiles...\n";

$team_seasons_all = array();
foreach ( $team_standings_rows as $row ) {
	$team_seasons_all[ (int) $row['team_id'] ][] = array(
		'year'   => (int) $row['season_year'],
		'pos'    => (int) $row['position'],
		'points' => (float) $row['points'],
		'wins'   => (int) $row['wins'],
	);
}

$team_wins = array();
$sql = "SELECT r.team_id, r.race_id, r.driver_id, p.post_title AS race_name
	FROM wp_f1_results r
	INNER JOIN wp_posts p ON p.ID = r.race_id
	WHERE r.position_text = '1'
	ORDER BY r.season_year DESC, r.round DESC";
foreach ( rows( $db, $sql ) as $row ) {
	$team_wins[ (int) $row['team_id'] ][] = $row;
}

foreach ( $teams as $team ) {
	$id = $team['id'];
	$seasons = isset( $team_seasons_all[ $id ] ) ? $team_seasons_all[ $id ] : array();
	usort( $seasons, function ( $a, $b ) {
		return $b['year'] <=> $a['year'];
	} );

	$wins = array();
	$win_rows = isset( $team_wins[ $id ] ) ? $team_wins[ $id ] : array();
	foreach ( array_slice( $win_rows, 0, 10 ) as $row ) {
		$race = isset( $races_by_id[ (int) $row['race_id'] ] ) ? $races_by_id[ (int) $row['race_id'] ] : null;
		$wins[] = array(
			'raceId' => (int) $row['race_id'],
			'race'   => $race ? $race['name'] : $row['race_name'],
			'year'   => $race ? $race['year'] : 0,
			'driver' => isset( $drivers_by_id[ (int) $row['driver_id'] ] ) ? $drivers_by_id[ (int) $row['driver_id'] ]['name'] : '',
			'driverId' => (int) $row['driver_id'],
		);
	}

	$lineup = array();
	foreach ( $drivers as $driver ) {
		if ( $driver['teamId'] === $id ) {
			$lineup[] = array( 'id' => $driver['id'], 'name' => $driver['name'], 'photo' => $driver['photo'] );
		}
	}

	json_write( $root, 'data/teams/' . $id . '.json', array( 'team' => $team, 'seasons' => $seasons, 'wins' => $wins, 'lineup' => $lineup ) );
}

json_write( $root, 'data/teams.json', $teams );

/* ------------------------------------------------------------------ */
/* Race detail files                                                   */
/* ------------------------------------------------------------------ */

echo "Writing race results...\n";

$results_by_race = array();
$sql = "SELECT r.*, d.post_title AS driver_name, t.post_title AS team_name
	FROM wp_f1_results r
	INNER JOIN wp_posts d ON d.ID = r.driver_id
	INNER JOIN wp_posts t ON t.ID = r.team_id
	ORDER BY r.race_id ASC, r.result_order ASC, r.position ASC";
foreach ( rows( $db, $sql ) as $row ) {
	$results_by_race[ (int) $row['race_id'] ][] = array(
		'driverId' => (int) $row['driver_id'],
		'driver'   => $row['driver_name'],
		'teamId'   => (int) $row['team_id'],
		'team'     => $row['team_name'],
		'color'    => isset( $teams_by_id[ (int) $row['team_id'] ] ) ? $teams_by_id[ (int) $row['team_id'] ]['color'] : '#999999',
		'car'      => $row['car_number'],
		'pos'      => $row['position_text'],
		'grid'     => (int) $row['grid'],
		'laps'     => (int) $row['laps'],
		'status'   => $row['status'],
		'points'   => (float) $row['points'],
		'fastest'  => 1 === (int) $row['fastest_lap_rank'],
	);
}

foreach ( $races as $race ) {
	$race['results'] = isset( $results_by_race[ $race['id'] ] ) ? $results_by_race[ $race['id'] ] : array();
	json_write( $root, 'data/races/' . $race['id'] . '.json', $race );
}

/* ------------------------------------------------------------------ */
/* Site summary                                                        */
/* ------------------------------------------------------------------ */

$counts = array(
	'drivers'  => count( $drivers ),
	'teams'    => count( $teams ),
	'circuits' => count( $circuits ),
	'races'    => count( $races ),
	'seasons'  => count( $seasons_meta ),
	'results'  => (int) $db->query( 'SELECT COUNT(*) FROM wp_f1_results' )->fetch_row()[0],
);

json_write(
	$root,
	'data/site.json',
	array(
		'generated'    => gmdate( 'Y-m-d' ),
		'from'         => $first,
		'to'           => $latest,
		'counts'       => $counts,
		'latestSeason' => $latest,
		'leader'       => $champions ? end( $champions ) : null,
	)
);

echo "Data written.\n";

/* ------------------------------------------------------------------ */
/* Shared assets                                                       */
/* ------------------------------------------------------------------ */

echo "Copying fonts and icons...\n";

$themeAssets = 'C:/xampp/htdocs/f1wiki/wp-content/themes/f1wiki/assets';
foreach ( array( 'JetBrainsMonoNerdFont-Regular.ttf', 'JetBrainsMonoNerdFont-Bold.ttf', 'JetBrainsMonoNerdFont-Italic.ttf', 'JetBrainsMonoNerdFont-BoldItalic.ttf' ) as $font ) {
	if ( file_exists( $themeAssets . '/fonts/' . $font ) ) {
		copy( $themeAssets . '/fonts/' . $font, $root . '/assets/fonts/' . $font );
	}
}
if ( file_exists( $themeAssets . '/img/favicon.svg' ) ) {
	copy( $themeAssets . '/img/favicon.svg', $root . '/assets/img/favicon.svg' );
}

/* ------------------------------------------------------------------ */
/* HTML shells                                                         */
/* ------------------------------------------------------------------ */

$nav = array(
	'index'     => array( 'index.html', 'Dashboard' ),
	'champions' => array( 'champions.html', 'Champions' ),
	'drivers'   => array( 'drivers.html', 'Drivers' ),
	'teams'     => array( 'teams.html', 'Teams' ),
	'seasons'   => array( 'seasons.html', 'Seasons' ),
	'races'     => array( 'races.html', 'Races' ),
	'circuits'  => array( 'circuits.html', 'Circuits' ),
);

$pages = array(
	'index'     => array( 'F1 Archive 2000-' . $latest, 'Formula 1 results, standings, champions and driver profiles, 2000 to ' . $latest . '.' ),
	'champions' => array( 'World Champions', 'Every Formula 1 drivers championship leader from ' . $first . ' to ' . $latest . '.' ),
	'drivers'   => array( 'Drivers', 'Every driver in the archive with career totals and profiles.' ),
	'driver'    => array( 'Driver Profile', 'Career statistics and season-by-season record.' ),
	'teams'     => array( 'Teams', 'Constructors with championship years, wins and line-ups.' ),
	'team'      => array( 'Team Profile', 'Constructor statistics and season-by-season record.' ),
	'seasons'   => array( 'Seasons', 'Season-by-season championship results.' ),
	'season'    => array( 'Season', 'Championship standings and calendar.' ),
	'races'     => array( 'Races', 'Race calendar and full classifications.' ),
	'race'      => array( 'Race Result', 'Full race classification.' ),
	'circuits'  => array( 'Circuits', 'Every circuit in the archive.' ),
	'404'       => array( 'Not Found', 'Page not found.' ),
);

function shell( $root, $file, $nav, $active, $title, $description, $page, $site_name ) {
	$links = '';
	foreach ( $nav as $key => $item ) {
		$class = $key === $active ? ' class="is-active"' : '';
		$links .= '        <a href="' . $item[0] . '"' . $class . '>' . htmlspecialchars( $item[1], ENT_QUOTES, 'UTF-8' ) . "</a>\n";
	}

	$title_full = htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' );
	$desc       = htmlspecialchars( $description, ENT_QUOTES, 'UTF-8' );
	$brand      = htmlspecialchars( $site_name, ENT_QUOTES, 'UTF-8' );

	$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title_full} · {$brand}</title>
<meta name="description" content="{$desc}">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/css/site.css">
</head>
<body data-page="{$page}">
<a class="skip" href="#main">Skip to content</a>
<div class="shell">
	<aside class="sidebar" id="sidebar">
		<a class="brand" href="index.html"><span class="brand__mark">F1</span><span class="brand__word">Archive</span></a>
		<nav class="nav" aria-label="Sections">
{$links}
		</nav>
		<p class="sidebar__foot">Data 2000-{$GLOBALS['latest']}<br>Jolpica F1 API</p>
	</aside>
	<div class="content">
		<header class="topbar">
			<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="sidebar"><span></span><span></span><span></span><span class="sr">Menu</span></button>
			<div class="topbar__leader" id="top-leader"></div>
		</header>
		<main class="main" id="main">
			<p class="loading">Loading archive…</p>
		</main>
		<footer class="foot">
			<p>An independent Formula 1 archive. Data from the Jolpica F1 API. Driver portraits and team logos from Wikimedia Commons under their free licenses.</p>
			<p>Not affiliated with Formula 1, the FIA or any team.</p>
		</footer>
	</div>
</div>
<script src="assets/js/site.js" defer></script>
</body>
</html>
HTML;

	file_put_contents( $root . '/' . $file, $html );
}

echo "Writing HTML shells...\n";

$GLOBALS['latest'] = $latest;
$site_name = 'F1 Archive';

foreach ( $pages as $key => $meta ) {
	$file   = 'index' === $key ? 'index.html' : $key . '.html';
	$active = isset( $nav[ $key ] ) ? $key : '';
	shell( $root, $file, $nav, $active, $meta[0], $meta[1], $key, $site_name );
}

echo "Done. Static site written to {$root}\n";
