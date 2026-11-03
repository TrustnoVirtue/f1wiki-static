<?php
/**
 * Template helpers.
 *
 * @package F1Wiki
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return singular or plural label for a count.
 *
 * @param int    $count    Count.
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return string
 */
function f1wiki_plural( $count, $singular, $plural ) {
	return 1 === (int) $count ? $singular : $plural;
}

/**
 * Human label for a post type.
 *
 * @param string $type Post type.
 * @return string
 */
function f1wiki_post_type_label( $type ) {
	$labels = array(
		'driver'  => 'Driver',
		'team'    => 'Team',
		'circuit' => 'Circuit',
		'race'    => 'Race',
		'season'  => 'Season',
		'page'    => 'Page',
		'post'    => 'Article',
	);

	return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( (string) $type );
}

/**
 * Archive URL for a content type.
 *
 * @param string $type Post type.
 * @return string
 */
function f1wiki_archive_link( $type ) {
	$link = get_post_type_archive_link( $type );
	return $link ? $link : home_url( '/' );
}

/**
 * Initials from a name.
 *
 * @param string $name Name.
 * @return string
 */
function f1wiki_initials( $name ) {
	$name = trim( preg_replace( '/\s+/', ' ', (string) $name ) );
	if ( '' === $name ) {
		return '?';
	}

	$parts = explode( ' ', $name );
	if ( count( $parts ) === 1 ) {
		return strtoupper( substr( $parts[0], 0, 2 ) );
	}

	$first = substr( $parts[0], 0, 1 );
	$last  = substr( $parts[ count( $parts ) - 1 ], 0, 1 );

	return strtoupper( $first . $last );
}

/**
 * Latest team data for a driver.
 *
 * @param int $driver_id Driver post ID.
 * @return array
 */
function f1wiki_get_driver_team( $driver_id ) {
	$team_id = (int) get_post_meta( $driver_id, '_f1wiki_current_team', true );
	$year    = (int) get_post_meta( $driver_id, '_f1wiki_latest_team_year', true );

	if ( ! $team_id && function_exists( 'get_field' ) ) {
		$team_id = (int) get_field( 'current_team', $driver_id );
	}

	if ( ! $team_id ) {
		return array(
			'id'    => 0,
			'name'  => '',
			'url'   => '',
			'color' => '#E10600',
			'year'  => $year,
		);
	}

	return array(
		'id'    => $team_id,
		'name'  => get_the_title( $team_id ),
		'url'   => get_permalink( $team_id ),
		'color' => f1wiki_team_color( $team_id ),
		'year'  => $year,
	);
}

/**
 * Team dot plus optional linked name.
 *
 * @param int  $team_id   Team post ID.
 * @param bool $with_name Include the team name.
 * @param bool $linked    Wrap the name in a link.
 * @return string
 */
function f1wiki_team_mark( $team_id, $with_name = true, $linked = true ) {
	$team_id = (int) $team_id;
	if ( ! $team_id ) {
		return '';
	}

	$color = f1wiki_team_color( $team_id );
	$name  = get_the_title( $team_id );
	$url   = get_permalink( $team_id );

	$html  = '<span class="team-mark">';
	$html .= '<span class="team-dot" style="--team-color:' . esc_attr( $color ) . '" aria-hidden="true"></span>';

	if ( $with_name ) {
		if ( $linked && $url ) {
			$html .= '<a class="team-mark__name" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
		} else {
			$html .= '<span class="team-mark__name">' . esc_html( $name ) . '</span>';
		}
	}

	$html .= '</span>';

	return $html;
}

/**
 * Driver avatar: photo when available, monogram otherwise.
 *
 * @param string $name    Name.
 * @param string $color   Accent color.
 * @param string $number  Optional number.
 * @param string $size    Size class.
 * @param int    $post_id Optional driver post ID for the photo.
 * @return string
 */
function f1wiki_avatar( $name, $color = '#E10600', $number = '', $size = 'md', $post_id = 0 ) {
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail(
			$post_id,
			'f1wiki-portrait',
			array(
				'class'   => 'avatar avatar--' . $size . ' avatar--photo',
				'style'   => '--team-color:' . $color,
				'alt'     => '',
				'loading' => 'lazy',
			)
		);
	}

	$content = $number ? $number : f1wiki_initials( $name );
	$label   = $number ? 'Car number ' . $number : $name;

	return '<span class="avatar avatar--' . esc_attr( $size ) . '" style="--team-color:' . esc_attr( $color ) . '" aria-label="' . esc_attr( $label ) . '"><span aria-hidden="true">' . esc_html( $content ) . '</span></span>';
}

/**
 * Team logo image, or an original wordmark mark when no free logo exists.
 *
 * @param int $team_id Team post ID.
 * @param int $height  Rendered height in pixels for images.
 * @return string
 */
function f1wiki_team_logo( $team_id, $height = 56 ) {
	$team_id = (int) $team_id;

	if ( $team_id && has_post_thumbnail( $team_id ) ) {
		return get_the_post_thumbnail(
			$team_id,
			'medium',
			array(
				'class'   => 'team-logo team-logo--image',
				'alt'     => '',
				'loading' => 'lazy',
				'style'   => 'height:' . (int) $height . 'px',
			)
		);
	}

	$name = get_the_title( $team_id );
	$map  = array(
		'alfa romeo'     => 'AR',
		'alphatauri'     => 'AT',
		'alpine f1 team' => 'ALP',
		'aston martin'   => 'AM',
		'bmw sauber'     => 'BMW',
		'force india'    => 'FI',
		'lotus f1'       => 'LOT',
		'manor marussia' => 'MRN',
		'mf1'            => 'MF1',
		'racing point'   => 'RP',
		'rb f1 team'     => 'RB',
		'red bull'       => 'RBR',
		'spyker mf1'     => 'SPK',
		'super aguri'    => 'SA',
		'toro rosso'     => 'STR',
		'virgin'         => 'VRG',
	);

	$key  = strtolower( trim( $name ) );
	$code = isset( $map[ $key ] ) ? $map[ $key ] : strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $name ), 0, 3 ) );
	$color = f1wiki_team_color( $team_id );

	return '<span class="team-logo team-logo--mark" style="--team-color:' . esc_attr( $color ) . ';min-height:' . (int) $height . 'px" aria-hidden="true">' . esc_html( $code ) . '</span>';
}

/**
 * Photo attribution for a driver portrait.
 *
 * @param int $post_id Driver post ID.
 * @return string
 */
function f1wiki_photo_credit( $post_id ) {
	$credit = (string) get_post_meta( $post_id, '_f1wiki_photo_credit', true );

	if ( ! $credit ) {
		return '';
	}

	$source = (string) get_post_meta( $post_id, '_f1wiki_photo_source', true );

	if ( $source ) {
		$credit = '<a href="' . esc_url( $source ) . '" rel="noopener noreferrer">' . esc_html( $credit ) . '</a>';
	} else {
		$credit = esc_html( $credit );
	}

	return '<figcaption class="entity-portrait__credit">' . $credit . '</figcaption>';
}

/**
 * Site logo or wordmark.
 *
 * @return void
 */
function f1wiki_site_logo() {
	if ( has_custom_logo() ) {
		echo '<div class="brand brand--custom">';
		the_custom_logo();
		echo '</div>';
		return;
	}
	?>
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) . ' — home' ); ?>">
		<span class="brand__mark" aria-hidden="true">F1</span>
		<span class="brand__word">Wiki</span>
	</a>
	<?php
}

/**
 * Fallback primary navigation.
 *
 * @return void
 */
function f1wiki_nav_fallback() {
	$items = array(
		'drivers'  => 'Drivers',
		'teams'    => 'Teams',
		'circuits' => 'Circuits',
		'seasons'  => 'Seasons',
		'races'    => 'Races',
	);

	echo '<ul class="menu">';
	foreach ( $items as $type => $label ) {
		echo '<li class="menu-item"><a href="' . esc_url( f1wiki_archive_link( $type ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Breadcrumb trail.
 *
 * @return void
 */
function f1wiki_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	$trail = array(
		array(
			'name' => 'Home',
			'url'  => home_url( '/' ),
		),
	);

	if ( is_singular() ) {
		$post_type = get_post_type();
		if ( in_array( $post_type, array( 'driver', 'team', 'circuit', 'race', 'season' ), true ) ) {
			$trail[] = array(
				'name' => f1wiki_post_type_label( $post_type ) . 's',
				'url'  => f1wiki_archive_link( $post_type ),
			);
		}
		$trail[] = array(
			'name' => get_the_title(),
			'url'  => '',
		);
	} elseif ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$trail[]   = array(
			'name' => f1wiki_post_type_label( is_array( $post_type ) ? reset( $post_type ) : $post_type ) . 's',
			'url'  => '',
		);
	} elseif ( is_search() ) {
		$trail[] = array(
			'name' => 'Search',
			'url'  => '',
		);
	} elseif ( is_404() ) {
		$trail[] = array(
			'name' => 'Not found',
			'url'  => '',
		);
	} elseif ( is_page() ) {
		$trail[] = array(
			'name' => get_the_title(),
			'url'  => '',
		);
	}

	echo '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>';
	foreach ( $trail as $crumb ) {
		if ( $crumb['url'] ) {
			echo '<li><a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['name'] ) . '</a></li>';
		} else {
			echo '<li aria-current="page">' . esc_html( $crumb['name'] ) . '</li>';
		}
	}
	echo '</ol></nav>';
}

/**
 * Inline SVG icon.
 *
 * @param string $name Icon name.
 * @return string
 */
function f1wiki_icon( $name ) {
	$icons = array(
		'arrow'    => '<path d="M4 12h15M13 6l6 6-6 6"/>',
		'chevron'  => '<path d="M9 18l6-6-6-6"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4.2-4.2"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
		'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/>',
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
		'pin'      => '<path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return '<svg class="icon icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
}

/**
 * Pagination wrapper.
 *
 * @return void
 */
function f1wiki_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => 'Prev',
			'next_text'          => 'Next',
			'screen_reader_text' => 'Posts navigation',
			'class'              => 'pagination',
		)
	);
}

/**
 * Section heading with optional archive link.
 *
 * @param string $eyebrow  Small label.
 * @param string $title    Heading.
 * @param string $link_url Link URL.
 * @param string $link_text Link text.
 * @return void
 */
function f1wiki_section_head( $eyebrow, $title, $link_url = '', $link_text = '' ) {
	?>
	<div class="section-head">
		<div>
			<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h2><?php echo esc_html( $title ); ?></h2>
		</div>
		<?php if ( $link_url && $link_text ) : ?>
			<a class="section-head__link" href="<?php echo esc_url( $link_url ); ?>">
				<?php echo esc_html( $link_text ); ?>
				<?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Status label for a season.
 *
 * @param string $status Status key.
 * @return string
 */
function f1wiki_status_label( $status ) {
	return 'in_progress' === $status ? 'In progress' : 'Season complete';
}

/**
 * OpenStreetMap URL for coordinates.
 *
 * @param string|float $lat Latitude.
 * @param string|float $lng Longitude.
 * @return string
 */
function f1wiki_map_url( $lat, $lng ) {
	if ( '' === (string) $lat || '' === (string) $lng ) {
		return '';
	}

	return 'https://www.openstreetmap.org/?mlat=' . rawurlencode( (string) $lat ) . '&mlon=' . rawurlencode( (string) $lng ) . '#map=13/' . rawurlencode( (string) $lat ) . '/' . rawurlencode( (string) $lng );
}

/**
 * Empty state message.
 *
 * @param string $title Title.
 * @param string $text  Body.
 * @return void
 */
function f1wiki_empty_state( $title, $text ) {
	?>
	<div class="empty-state">
		<p class="eyebrow">Nothing here yet</p>
		<h2><?php echo esc_html( $title ); ?></h2>
		<p><?php echo esc_html( $text ); ?></p>
	</div>
	<?php
}

/**
 * Championship years from driver stats.
 *
 * @param array $stats Stats.
 * @return int[]
 */
function f1wiki_championship_years( $stats ) {
	if ( empty( $stats['championship_years'] ) || ! is_array( $stats['championship_years'] ) ) {
		return array();
	}

	return array_map( 'intval', $stats['championship_years'] );
}

/**
 * First and last season label.
 *
 * @param array $stats Stats.
 * @return string
 */
function f1wiki_season_span( $stats ) {
	$first = isset( $stats['first_season'] ) ? (int) $stats['first_season'] : 0;
	$last  = isset( $stats['last_season'] ) ? (int) $stats['last_season'] : 0;

	if ( ! $first && ! $last ) {
		return '—';
	}
	if ( $first === $last ) {
		return (string) $first;
	}

	return $first . '–' . $last;
}

/**
 * Fastest-lap availability note.
 *
 * @return string
 */
function f1wiki_fastest_lap_note() {
	return 'The source archive does not record fastest laps for 2000–2003, so those totals start in 2004.';
}
