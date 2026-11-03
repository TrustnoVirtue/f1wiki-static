<?php
/**
 * Single race.
 *
 * @package F1Wiki
 */

get_header();

$race_id     = get_the_ID();
$season      = (int) get_post_meta( $race_id, '_f1wiki_season_year', true );
$round       = (int) get_post_meta( $race_id, '_f1wiki_round', true );
$date        = (string) get_post_meta( $race_id, '_f1wiki_race_date', true );
$has_results = (int) get_post_meta( $race_id, '_f1wiki_has_results', true );
$summary     = f1wiki_get_race_summary( $race_id );
$results     = $has_results ? f1wiki_get_race_results( $race_id ) : array();
$season_id   = f1wiki_get_season_post( $season );
$season_url  = $season_id ? get_permalink( $season_id ) : '';
$wiki        = (string) get_post_meta( $race_id, '_f1wiki_wikipedia', true );
$season_races = f1wiki_get_season_races( $season );
$prev_race    = null;
$next_race    = null;

foreach ( $season_races as $index => $season_race ) {
	if ( (int) $season_race->ID === $race_id ) {
		$prev_race = isset( $season_races[ $index - 1 ] ) ? $season_races[ $index - 1 ] : null;
		$next_race = isset( $season_races[ $index + 1 ] ) ? $season_races[ $index + 1 ] : null;
		break;
	}
}
?>
<article class="single single--race" style="--team-color:<?php echo esc_attr( $summary['team_color'] ); ?>">
	<header class="entity-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="entity-hero__grid">
				<div class="entity-hero__copy">
					<p class="eyebrow"><?php echo esc_html( $season . ' season · Round ' . $round ); ?></p>
					<h1><?php the_title(); ?></h1>
					<div class="entity-meta">
						<?php if ( $date ) : ?>
							<span class="entity-meta__item"><?php echo f1wiki_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( f1wiki_format_date( $date, 'j F Y' ) ); ?></span>
						<?php endif; ?>
						<?php if ( $summary['circuit'] ) : ?>
							<span class="entity-meta__item"><?php echo f1wiki_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <a href="<?php echo esc_url( $summary['circuit_url'] ); ?>"><?php echo esc_html( $summary['circuit'] ); ?></a></span>
						<?php endif; ?>
						<span class="entity-meta__item"><?php echo $has_results ? 'Classification available' : 'Upcoming race'; ?></span>
					</div>
				</div>
				<div class="entity-hero__watermark" aria-hidden="true"><?php echo esc_html( $round ? str_pad( (string) $round, 2, '0', STR_PAD_LEFT ) : 'R' ); ?></div>
			</div>
		</div>
	</header>

	<div class="container article-layout">
		<div class="article-main">
			<div class="race-facts">
				<div>
					<span>Winner</span>
					<?php if ( $summary['winner'] ) : ?>
						<strong><a href="<?php echo esc_url( $summary['winner_url'] ); ?>"><?php echo esc_html( $summary['winner'] ); ?></a></strong>
						<small><?php echo esc_html( $summary['team'] ); ?></small>
					<?php else : ?>
						<strong>—</strong>
						<small>Awaiting result</small>
					<?php endif; ?>
				</div>
				<div>
					<span>Pole position</span>
					<?php if ( $summary['pole'] ) : ?>
						<strong><a href="<?php echo esc_url( $summary['pole_url'] ); ?>"><?php echo esc_html( $summary['pole'] ); ?></a></strong>
					<?php else : ?>
						<strong>—</strong>
					<?php endif; ?>
				</div>
				<div>
					<span>Fastest lap</span>
					<?php if ( $summary['fastest'] ) : ?>
						<strong><a href="<?php echo esc_url( $summary['fastest_url'] ); ?>"><?php echo esc_html( $summary['fastest'] ); ?></a></strong>
					<?php else : ?>
						<strong>—</strong>
						<small><?php echo $season < 2004 ? 'Not recorded in source' : 'No fastest lap recorded'; ?></small>
					<?php endif; ?>
				</div>
				<div>
					<span>Circuit</span>
					<?php if ( $summary['circuit'] ) : ?>
						<strong><a href="<?php echo esc_url( $summary['circuit_url'] ); ?>"><?php echo esc_html( $summary['circuit'] ); ?></a></strong>
					<?php else : ?>
						<strong>—</strong>
					<?php endif; ?>
				</div>
			</div>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<?php if ( $season < 2004 && ! $has_results ) : ?>
				<p class="data-note"><?php echo esc_html( f1wiki_fastest_lap_note() ); ?></p>
			<?php endif; ?>

			<section class="section">
				<?php f1wiki_section_head( 'Classification', $has_results ? 'Race result' : 'Entry list' ); ?>
				<?php if ( $results ) : ?>
					<div class="table-wrap">
						<table class="data-table race-results">
							<thead>
								<tr>
									<th class="pos" scope="col">Pos</th>
									<th class="num" scope="col">No</th>
									<th scope="col">Driver</th>
									<th scope="col">Team</th>
									<th class="pos" scope="col">Grid</th>
									<th class="num" scope="col">Laps</th>
									<th scope="col">Status</th>
									<th class="num" scope="col">Pts</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $results as $row ) : ?>
									<?php
									$position = f1wiki_result_position( $row->position, $row->position_text );
									$is_dnf   = ! is_numeric( $row->position ) || (int) $row->position < 1;
									$is_win   = '1' === (string) $row->position_text || 1 === (int) $row->position;
									$classes  = array();
									if ( $is_dnf ) {
										$classes[] = 'is-dnf';
									}
									if ( $is_win ) {
										$classes[] = 'is-winner';
									}
									if ( (int) $row->position >= 1 && (int) $row->position <= 3 ) {
										$classes[] = 'is-podium';
									}
									?>
									<tr class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
										<td class="pos"><?php echo esc_html( $position ); ?></td>
										<td class="num"><?php echo esc_html( $row->car_number ? $row->car_number : '—' ); ?></td>
										<td>
											<a class="table-person" href="<?php echo esc_url( $row->driver_url ); ?>">
												<span class="team-dot" style="--team-color:<?php echo esc_attr( $row->team_color ); ?>" aria-hidden="true"></span>
												<?php echo esc_html( $row->driver_name ); ?>
											</a>
											<?php if ( $row->is_fastest ) : ?>
												<span class="fl-badge" title="<?php echo esc_attr( 'Fastest lap' . ( $row->fastest_lap_time ? ': ' . $row->fastest_lap_time : '' ) ); ?>">FL</span>
											<?php endif; ?>
										</td>
										<td><?php echo f1wiki_team_mark( (int) $row->team_id, true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="pos"><?php echo esc_html( $row->grid ? 'P' . (int) $row->grid : '—' ); ?></td>
										<td class="num"><?php echo esc_html( number_format_i18n( (int) $row->laps ) ); ?></td>
										<td class="status"><?php echo esc_html( $row->status ? $row->status : '—' ); ?></td>
										<td class="num points"><?php echo esc_html( f1wiki_format_points( $row->points ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php f1wiki_empty_state( 'Classification not available', 'This race has not been run or results have not been imported yet.' ); ?>
				<?php endif; ?>
			</section>

			<?php if ( $prev_race || $next_race ) : ?>
				<nav class="race-pager" aria-label="Season races">
					<?php if ( $prev_race ) : ?>
						<a class="race-pager__item race-pager__item--prev" href="<?php echo esc_url( get_permalink( $prev_race ) ); ?>">
							<span>Previous round</span>
							<strong><?php echo esc_html( preg_replace( '/^\d{4}\s+/', '', get_the_title( $prev_race ) ) ); ?></strong>
						</a>
					<?php endif; ?>
					<?php if ( $next_race ) : ?>
						<a class="race-pager__item race-pager__item--next" href="<?php echo esc_url( get_permalink( $next_race ) ); ?>">
							<span>Next round</span>
							<strong><?php echo esc_html( preg_replace( '/^\d{4}\s+/', '', get_the_title( $next_race ) ) ); ?></strong>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>

		<aside class="article-aside">
			<div class="infobox">
				<h2>Race facts</h2>
				<dl class="infobox-list">
					<?php if ( $season_url ) : ?>
						<div><dt>Season</dt><dd><a href="<?php echo esc_url( $season_url ); ?>"><?php echo esc_html( $season ); ?></a></dd></div>
					<?php endif; ?>
					<div><dt>Round</dt><dd><?php echo esc_html( $round ); ?></dd></div>
					<?php if ( $date ) : ?>
						<div><dt>Date</dt><dd><?php echo esc_html( f1wiki_format_date( $date, 'j M Y' ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $summary['circuit'] ) : ?>
						<div><dt>Circuit</dt><dd><a href="<?php echo esc_url( $summary['circuit_url'] ); ?>"><?php echo esc_html( $summary['circuit'] ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $summary['winner'] ) : ?>
						<div><dt>Winner</dt><dd><a href="<?php echo esc_url( $summary['winner_url'] ); ?>"><?php echo esc_html( $summary['winner'] ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $summary['pole'] ) : ?>
						<div><dt>Pole</dt><dd><a href="<?php echo esc_url( $summary['pole_url'] ); ?>"><?php echo esc_html( $summary['pole'] ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $summary['fastest'] ) : ?>
						<div><dt>Fastest lap</dt><dd><a href="<?php echo esc_url( $summary['fastest_url'] ); ?>"><?php echo esc_html( $summary['fastest'] ); ?></a></dd></div>
					<?php endif; ?>
				</dl>
				<?php if ( $wiki ) : ?>
					<a class="button button--outline button--wide" href="<?php echo esc_url( $wiki ); ?>" target="_blank" rel="noopener noreferrer">Wikipedia <?php echo f1wiki_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $season_url ) : ?>
				<div class="infobox infobox--plain">
					<h2>Season</h2>
					<p class="infobox__season"><a href="<?php echo esc_url( $season_url ); ?>"><?php echo esc_html( $season . ' season overview' ); ?></a></p>
					<p class="muted">Standings, calendar and every round.</p>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</article>
<?php
get_footer();
