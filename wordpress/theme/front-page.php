<?php
/**
 * Front page.
 *
 * @package F1Wiki
 */

get_header();

$year       = f1wiki_latest_season();
$status     = f1wiki_get_season_status( $year );
$drivers    = f1wiki_get_driver_standings( $year, 5 );
$teams      = f1wiki_get_team_standings( $year, 5 );
$all_teams  = f1wiki_get_team_standings( $year );
$counts     = f1wiki_get_counts();
$season_url = get_permalink( f1wiki_get_season_post( $year ) );
$leader     = ! empty( $drivers ) ? $drivers[0] : null;
$team_leader = ! empty( $teams ) ? $teams[0] : null;
$next_race   = f1wiki_get_next_race();
$last_race   = f1wiki_get_latest_completed_race();
$from        = (int) get_option( 'f1wiki_seed_from', 2000 );
$team_color  = $leader ? $leader->team_color : '#E10600';
$champions   = f1wiki_get_champions( $from, $year );

$latest_races = new WP_Query(
	array(
		'post_type'      => 'race',
		'posts_per_page' => 4,
		'post_status'    => 'publish',
		'meta_key'       => '_f1wiki_race_date',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'   => '_f1wiki_has_results',
				'value' => '1',
			),
		),
		'no_found_rows'  => true,
	)
);

$circuits = get_posts(
	array(
		'post_type'      => 'circuit',
		'posts_per_page' => 4,
		'post_status'    => 'publish',
		'meta_key'       => '_f1wiki_races_sort',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	)
);

$current_teams = array_slice( $all_teams, 0, 8 );
?>

<div class="home">
	<section class="home-hero">
		<div class="container home-hero__grid">
			<div class="home-hero__copy">
				<p class="eyebrow">Formula 1 encyclopedia · <?php echo esc_html( f1wiki_archive_range_label() ); ?></p>
				<h1>Every driver.<br><em>Every era.</em><br>One archive.</h1>
				<p class="home-hero__lede">From the 2000 season to the current grid, F1 Wiki connects the people, teams and circuits that make up modern Formula 1 — with career records, race results and championship history in one place.</p>
				<div class="hero-actions">
					<a class="button" href="<?php echo esc_url( $season_url ); ?>">Explore <?php echo esc_html( $year ); ?></a>
					<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">Browse drivers</a>
				</div>
				<div class="hero-stats">
					<div><strong><?php echo esc_html( number_format_i18n( $counts['races'] ) ); ?></strong><span>races</span></div>
					<div><strong><?php echo esc_html( number_format_i18n( $counts['drivers'] ) ); ?></strong><span>drivers</span></div>
					<div><strong><?php echo esc_html( number_format_i18n( $counts['seasons'] ) ); ?></strong><span>seasons</span></div>
					<div><strong><?php echo esc_html( number_format_i18n( $counts['results'] ) ); ?></strong><span>results</span></div>
				</div>
			</div>

			<aside class="season-snapshot" style="--team-color:<?php echo esc_attr( $team_color ); ?>">
				<div class="season-snapshot__top">
					<p class="eyebrow">Season snapshot</p>
					<span class="status-pill status-pill--<?php echo esc_attr( $status['status'] ); ?>"><?php echo esc_html( f1wiki_status_label( $status['status'] ) ); ?></span>
				</div>
				<div class="season-snapshot__title">
					<h2><a href="<?php echo esc_url( $season_url ); ?>"><?php echo esc_html( $year ); ?></a></h2>
					<p>Round <?php echo esc_html( $status['completed'] ); ?> of <?php echo esc_html( $status['rounds'] ); ?> completed</p>
				</div>

				<?php if ( $leader ) : ?>
					<div class="snapshot-leader">
						<?php echo f1wiki_avatar( $leader->driver_name, $leader->team_color, f1wiki_driver_number( $leader->driver_id ), 'md', $leader->driver_id ); ?>
						<div class="snapshot-leader__body">
							<span><?php echo 'completed' === $status['status'] ? 'Champion' : 'Championship leader'; ?></span>
							<a href="<?php echo esc_url( $leader->driver_url ); ?>"><?php echo esc_html( $leader->driver_name ); ?></a>
							<small><?php echo f1wiki_team_mark( (int) $leader->team_id, true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
						</div>
						<strong class="snapshot-leader__points"><?php echo esc_html( f1wiki_format_points( $leader->points ) ); ?><small>pts</small></strong>
					</div>
				<?php endif; ?>

				<div class="snapshot-rows">
					<?php if ( $team_leader ) : ?>
						<div class="snapshot-row">
							<span>Constructors</span>
							<span><?php echo f1wiki_team_mark( (int) $team_leader->team_id, true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <strong><?php echo esc_html( f1wiki_format_points( $team_leader->points ) ); ?> pts</strong></span>
						</div>
					<?php endif; ?>
					<div class="snapshot-row">
						<span><?php echo $next_race ? 'Next race' : 'Latest race'; ?></span>
						<?php if ( $next_race ) : ?>
							<span><a href="<?php echo esc_url( get_permalink( $next_race ) ); ?>"><?php echo esc_html( preg_replace( '/^\d{4}\s+/', '', get_the_title( $next_race ) ) ); ?></a> <small><?php echo esc_html( f1wiki_format_date( (string) get_post_meta( $next_race->ID, '_f1wiki_race_date', true ), 'j M' ) ); ?></small></span>
						<?php elseif ( $last_race ) : ?>
							<span><a href="<?php echo esc_url( get_permalink( $last_race ) ); ?>"><?php echo esc_html( preg_replace( '/^\d{4}\s+/', '', get_the_title( $last_race ) ) ); ?></a> <small><?php echo esc_html( f1wiki_format_date( (string) get_post_meta( $last_race->ID, '_f1wiki_race_date', true ), 'j M' ) ); ?></small></span>
						<?php else : ?>
							<span>Calendar to be confirmed</span>
						<?php endif; ?>
					</div>
				</div>

				<a class="button button--wide" href="<?php echo esc_url( $season_url ); ?>">Full <?php echo esc_html( $year ); ?> season</a>
			</aside>
		</div>

		<div class="container timeline-wrap">
			<div class="timeline-head">
				<span>Seasons</span>
				<span><?php echo esc_html( f1wiki_archive_range_label() ); ?></span>
			</div>
			<nav class="timeline" aria-label="Seasons">
				<?php for ( $y = $from; $y <= $year; $y++ ) : ?>
					<?php $season_id = f1wiki_get_season_post( $y ); ?>
					<?php if ( $season_id ) : ?>
						<a class="<?php echo $y === $year ? 'is-current' : ''; ?>" href="<?php echo esc_url( get_permalink( $season_id ) ); ?>" <?php echo $y === $year ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $y ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>
			</nav>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<?php f1wiki_section_head( 'Championship', $year . ' standings', $season_url, 'Full standings' ); ?>
			<div class="grid grid--2 grid--gap">
				<div class="panel">
					<div class="panel__head">
						<h3>Drivers</h3>
						<span>Top five</span>
					</div>
					<?php get_template_part( 'template-parts/standings-table', null, array( 'rows' => $drivers, 'type' => 'driver' ) ); ?>
				</div>
				<div class="panel">
					<div class="panel__head">
						<h3>Constructors</h3>
						<span>Top five</span>
					</div>
					<?php get_template_part( 'template-parts/standings-table', null, array( 'rows' => $teams, 'type' => 'team' ) ); ?>
				</div>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<?php f1wiki_section_head( 'Results', 'Latest races', f1wiki_archive_link( 'race' ), 'All races' ); ?>
			<?php if ( $latest_races->have_posts() ) : ?>
				<div class="card-grid card-grid--4">
					<?php
					while ( $latest_races->have_posts() ) :
						$latest_races->the_post();
						get_template_part( 'template-parts/card-race', null, array( 'post_id' => get_the_ID() ) );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No race classifications yet', 'Import race data to populate this section.' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section--champions">
		<div class="container">
			<?php f1wiki_section_head( 'Roll of honour', 'World champions ' . $from . '-' . $year, f1wiki_archive_link( 'season' ), 'All seasons' ); ?>
			<?php if ( $champions ) : ?>
				<ol class="champions-list">
					<?php foreach ( $champions as $champion ) : ?>
						<?php $in_progress = (int) $champion->season_year === (int) $year && 'completed' !== $status['status']; ?>
						<li class="champion">
							<span class="champion__year"><?php echo esc_html( $champion->season_year ); ?></span>
							<span class="champion__name"><a href="<?php echo esc_url( $champion->driver_url ); ?>"><?php echo esc_html( $champion->driver_name ); ?></a></span>
							<span class="champion__team">
								<span class="team-dot" style="--team-color:<?php echo esc_attr( $champion->team_color ); ?>" aria-hidden="true"></span>
								<?php if ( $champion->team_url ) : ?>
									<a href="<?php echo esc_url( $champion->team_url ); ?>"><?php echo esc_html( $champion->team_name ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $champion->team_name ); ?>
								<?php endif; ?>
							</span>
							<?php if ( $in_progress ) : ?>
								<span class="champion__flag">Season in progress — current leader</span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No champions yet', 'Import season standings to populate the roll of honour.' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<?php f1wiki_section_head( 'The grid', 'Current constructors', f1wiki_archive_link( 'team' ), 'All teams' ); ?>
			<?php if ( $current_teams ) : ?>
				<div class="card-grid card-grid--4">
					<?php foreach ( $current_teams as $team_row ) : ?>
						<?php get_template_part( 'template-parts/card-team', null, array( 'post_id' => (int) $team_row->team_id ) ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No constructors yet', 'Import season standings to populate the grid.' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<?php f1wiki_section_head( 'Venues', 'Circuits in focus', f1wiki_archive_link( 'circuit' ), 'All circuits' ); ?>
			<?php if ( $circuits ) : ?>
				<div class="card-grid card-grid--4">
					<?php foreach ( $circuits as $circuit ) : ?>
						<?php get_template_part( 'template-parts/card-circuit', null, array( 'post_id' => $circuit->ID ) ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No circuits yet', 'Import race schedules to populate the circuit archive.' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<section class="about-strip">
		<div class="container about-strip__inner">
			<div>
				<p class="eyebrow">About the archive</p>
				<h2>A connected record of modern Formula 1.</h2>
				<p>Every result links a driver, a team, a circuit and a season. Browse the connections, follow a career, or start with a single race weekend.</p>
			</div>
			<div class="about-strip__links">
				<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About F1 Wiki <?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<a href="<?php echo esc_url( home_url( '/data-sources/' ) ); ?>">Data sources &amp; coverage <?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>
		</div>
	</section>
</div>

<?php
get_footer();
