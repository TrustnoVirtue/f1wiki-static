<?php
/**
 * Single season.
 *
 * @package F1Wiki
 */

get_header();

$season_id   = get_the_ID();
$year        = (int) get_post_meta( $season_id, '_f1wiki_season_year', true );
$status      = f1wiki_get_season_status( $year );
$drivers     = f1wiki_get_driver_standings( $year );
$teams       = f1wiki_get_team_standings( $year );
$races       = f1wiki_get_season_races( $year );
$champ_driver_id = (int) get_post_meta( $season_id, '_f1wiki_champion_driver_id', true );
$champ_team_id   = (int) get_post_meta( $season_id, '_f1wiki_champion_team_id', true );
$champ_driver    = $champ_driver_id ? get_the_title( $champ_driver_id ) : '';
$champ_team      = $champ_team_id ? get_the_title( $champ_team_id ) : '';
$champ_color     = $champ_team_id ? f1wiki_team_color( $champ_team_id ) : '#E10600';
$leader          = ! empty( $drivers ) ? $drivers[0] : null;
$team_leader     = ! empty( $teams ) ? $teams[0] : null;
$prev_id         = f1wiki_get_season_post( $year - 1 );
$next_id         = f1wiki_get_season_post( $year + 1 );
$is_complete     = 'completed' === $status['status'];
?>
<article class="single single--season" style="--team-color:<?php echo esc_attr( $champ_color ); ?>">
	<header class="entity-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="entity-hero__grid">
				<div class="entity-hero__copy">
					<p class="eyebrow">Season profile</p>
					<h1><?php the_title(); ?></h1>
					<div class="entity-meta">
						<span class="status-pill status-pill--<?php echo esc_attr( $status['status'] ); ?>"><?php echo esc_html( f1wiki_status_label( $status['status'] ) ); ?></span>
						<span class="entity-meta__item"><?php echo esc_html( $status['completed'] . ' of ' . $status['rounds'] . ' rounds completed' ); ?></span>
						<?php if ( $champ_driver ) : ?>
							<span class="entity-meta__item"><?php echo esc_html( ( $is_complete ? 'Champion: ' : 'Leader: ' ) . $champ_driver ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="entity-hero__watermark" aria-hidden="true"><?php echo esc_html( $year ); ?></div>
			</div>
		</div>
	</header>

	<div class="container article-layout">
		<div class="article-main">
			<div class="season-champions">
				<?php if ( $champ_driver_id ) : ?>
					<a class="champion-card" href="<?php echo esc_url( get_permalink( $champ_driver_id ) ); ?>">
						<span><?php echo $is_complete ? 'Drivers\' champion' : 'Championship leader'; ?></span>
						<strong><?php echo esc_html( $champ_driver ); ?></strong>
						<small><?php echo $leader ? esc_html( f1wiki_format_points( $leader->points ) . ' points' ) : ''; ?></small>
					</a>
				<?php endif; ?>
				<?php if ( $champ_team_id ) : ?>
					<a class="champion-card" href="<?php echo esc_url( get_permalink( $champ_team_id ) ); ?>">
						<span><?php echo $is_complete ? 'Constructors\' champion' : 'Constructors\' leader'; ?></span>
						<strong><?php echo esc_html( $champ_team ); ?></strong>
						<small><?php echo $team_leader ? esc_html( f1wiki_format_points( $team_leader->points ) . ' points' ) : ''; ?></small>
					</a>
				<?php endif; ?>
			</div>

			<div class="stat-strip">
				<div><strong><?php echo esc_html( number_format_i18n( $status['rounds'] ) ); ?></strong><span>Scheduled rounds</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $status['completed'] ) ); ?></strong><span>Completed</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( count( $drivers ) ) ); ?></strong><span>Drivers</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( count( $teams ) ) ); ?></strong><span>Constructors</span></div>
			</div>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<p class="data-note">Standings and statistics cover the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive.</p>

			<section class="section">
				<?php f1wiki_section_head( 'Championship', 'Drivers\' standings' ); ?>
				<?php get_template_part( 'template-parts/standings-table', null, array( 'rows' => $drivers, 'type' => 'driver' ) ); ?>
			</section>

			<section class="section">
				<?php f1wiki_section_head( 'Championship', 'Constructors\' standings' ); ?>
				<?php get_template_part( 'template-parts/standings-table', null, array( 'rows' => $teams, 'type' => 'team' ) ); ?>
			</section>

			<section class="section">
				<?php f1wiki_section_head( 'Calendar', 'Races' ); ?>
				<?php if ( $races ) : ?>
					<div class="table-wrap">
						<table class="data-table">
							<thead>
								<tr>
									<th class="pos" scope="col">Rd</th>
									<th scope="col">Date</th>
									<th scope="col">Race</th>
									<th scope="col">Circuit</th>
									<th scope="col">Winner</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $races as $race ) : ?>
									<?php
									$round   = (int) get_post_meta( $race->ID, '_f1wiki_round', true );
									$date    = (string) get_post_meta( $race->ID, '_f1wiki_race_date', true );
									$summary = f1wiki_get_race_summary( $race->ID );
									$has     = (int) get_post_meta( $race->ID, '_f1wiki_has_results', true );
									?>
									<tr class="<?php echo $has ? '' : 'is-upcoming'; ?>">
										<td class="pos"><?php echo esc_html( $round ); ?></td>
										<td><?php echo esc_html( $date ? f1wiki_format_date( $date, 'j M' ) : 'TBC' ); ?></td>
										<td><a href="<?php echo esc_url( get_permalink( $race ) ); ?>"><?php echo esc_html( preg_replace( '/^\d{4}\s+/', '', $race->post_title ) ); ?></a></td>
										<td><?php echo esc_html( $summary['circuit'] ); ?></td>
										<td>
											<?php if ( $summary['winner'] ) : ?>
												<span class="team-dot" style="--team-color:<?php echo esc_attr( $summary['team_color'] ); ?>" aria-hidden="true"></span>
												<a href="<?php echo esc_url( $summary['winner_url'] ); ?>"><?php echo esc_html( $summary['winner'] ); ?></a>
											<?php else : ?>
												<span class="muted">Upcoming</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php f1wiki_empty_state( 'No calendar available', 'Races for this season have not been imported yet.' ); ?>
				<?php endif; ?>
			</section>

			<?php if ( $prev_id || $next_id ) : ?>
				<nav class="race-pager" aria-label="Seasons">
					<?php if ( $prev_id ) : ?>
						<a class="race-pager__item race-pager__item--prev" href="<?php echo esc_url( get_permalink( $prev_id ) ); ?>">
							<span>Previous season</span>
							<strong><?php echo esc_html( ( $year - 1 ) . ' season' ); ?></strong>
						</a>
					<?php endif; ?>
					<?php if ( $next_id ) : ?>
						<a class="race-pager__item race-pager__item--next" href="<?php echo esc_url( get_permalink( $next_id ) ); ?>">
							<span>Next season</span>
							<strong><?php echo esc_html( ( $year + 1 ) . ' season' ); ?></strong>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>

		<aside class="article-aside">
			<div class="infobox">
				<h2>Season facts</h2>
				<dl class="infobox-list">
					<div><dt>Year</dt><dd><?php echo esc_html( $year ); ?></dd></div>
					<div><dt>Status</dt><dd><?php echo esc_html( f1wiki_status_label( $status['status'] ) ); ?></dd></div>
					<div><dt>Scheduled rounds</dt><dd><?php echo esc_html( $status['rounds'] ); ?></dd></div>
					<div><dt>Completed rounds</dt><dd><?php echo esc_html( $status['completed'] ); ?></dd></div>
					<?php if ( $champ_driver ) : ?>
						<div><dt><?php echo $is_complete ? 'Champion' : 'Leader'; ?></dt><dd><a href="<?php echo esc_url( get_permalink( $champ_driver_id ) ); ?>"><?php echo esc_html( $champ_driver ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $champ_team ) : ?>
						<div><dt><?php echo $is_complete ? 'Winning team' : 'Leading team'; ?></dt><dd><a href="<?php echo esc_url( get_permalink( $champ_team_id ) ); ?>"><?php echo esc_html( $champ_team ); ?></a></dd></div>
					<?php endif; ?>
				</dl>
			</div>
			<div class="aside-note">
				<p>This season sits inside the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive. Standings reflect imported results and may lag behind the latest race weekend.</p>
			</div>
		</aside>
	</div>
</article>
<?php
get_footer();
