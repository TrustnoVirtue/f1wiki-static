<?php
/**
 * Single driver.
 *
 * @package F1Wiki
 */

get_header();

$driver_id   = get_the_ID();
$stats       = f1wiki_get_stats( $driver_id, 'driver' );
$team        = f1wiki_get_driver_team( $driver_id );
$nat         = (string) get_post_meta( $driver_id, '_f1wiki_nationality', true );
$nat_code    = $nat ? f1wiki_nationality_code( $nat ) : '';
$code        = (string) get_post_meta( $driver_id, '_f1wiki_driver_code', true );
$number      = f1wiki_driver_number( $driver_id );
$dob         = (string) get_post_meta( $driver_id, '_f1wiki_dob', true );
$wiki        = (string) get_post_meta( $driver_id, '_f1wiki_wikipedia', true );
$seasons     = f1wiki_get_driver_seasons( $driver_id );
$recent      = f1wiki_get_driver_recent_results( $driver_id, 8 );
$champ_years = f1wiki_championship_years( $stats );
$latest      = f1wiki_latest_season();
$is_active   = (int) $stats['last_season'] === $latest;
$team_label  = $is_active ? 'Current team' : 'Last team';
$hero_number = $number ? $number : f1wiki_initials( get_the_title() );
$related     = $team['id'] ? get_posts(
	array(
		'post_type'      => 'driver',
		'posts_per_page' => 4,
		'post_status'    => 'publish',
		'post__not_in'   => array( $driver_id ),
		'meta_key'       => '_f1wiki_current_team',
		'meta_value'     => $team['id'],
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
) : array();
?>
<article class="single single--driver" style="--team-color:<?php echo esc_attr( $team['color'] ); ?>">
	<header class="entity-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="entity-hero__grid">
				<div class="entity-hero__copy">
					<p class="eyebrow">Driver profile</p>
					<h1><?php the_title(); ?></h1>
					<div class="entity-meta">
						<?php if ( $nat ) : ?>
							<span class="entity-meta__item"><strong><?php echo esc_html( $nat_code ); ?></strong> <?php echo esc_html( $nat ); ?></span>
						<?php endif; ?>
						<?php if ( $team['name'] ) : ?>
							<span class="entity-meta__item"><?php echo f1wiki_team_mark( $team['id'], true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php if ( $number ) : ?>
							<span class="entity-meta__item">Car <?php echo esc_html( $number ); ?></span>
						<?php endif; ?>
						<?php if ( $code ) : ?>
							<span class="entity-meta__item"><?php echo esc_html( $code ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( has_post_thumbnail( $driver_id ) ) : ?>
					<figure class="entity-portrait">
						<?php echo get_the_post_thumbnail( $driver_id, 'f1wiki-portrait', array( 'alt' => '', 'loading' => 'eager' ) ); ?>
						<?php echo f1wiki_photo_credit( $driver_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</figure>
				<?php else : ?>
					<div class="entity-hero__watermark" aria-hidden="true"><?php echo esc_html( $hero_number ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<div class="container article-layout">
		<div class="article-main">
			<div class="stat-strip">
				<div><strong><?php echo esc_html( number_format_i18n( $stats['championships'] ) ); ?></strong><span>Championships</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $stats['wins'] ) ); ?></strong><span>Wins</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $stats['podiums'] ) ); ?></strong><span>Podiums</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $stats['poles'] ) ); ?></strong><span>Pole positions</span></div>
			</div>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<p class="data-note">Statistics cover the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive. <?php echo esc_html( f1wiki_fastest_lap_note() ); ?></p>

			<section class="section">
				<?php f1wiki_section_head( 'Career', 'Season by season' ); ?>
				<?php if ( $seasons ) : ?>
					<div class="table-wrap">
						<table class="data-table">
							<thead>
								<tr>
									<th scope="col">Season</th>
									<th scope="col">Team</th>
									<th class="pos" scope="col">Pos</th>
									<th class="num" scope="col">Wins</th>
									<th class="num" scope="col">Points</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $seasons as $season ) : ?>
									<tr>
										<td><a href="<?php echo esc_url( $season->season_url ); ?>"><?php echo esc_html( $season->season_year ); ?></a></td>
										<td>
											<?php
											$team_marks = array();
											foreach ( $season->teams as $season_team ) {
												$team_marks[] = f1wiki_team_mark( $season_team['id'], true, true );
											}
											echo implode( '<span class="sep">/</span>', $team_marks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</td>
										<td class="pos"><?php echo esc_html( $season->position ? 'P' . $season->position : '—' ); ?></td>
										<td class="num"><?php echo esc_html( number_format_i18n( $season->wins ) ); ?></td>
										<td class="num points"><?php echo esc_html( f1wiki_format_points( $season->points ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php f1wiki_empty_state( 'No season record available', 'Results for this driver have not been imported yet.' ); ?>
				<?php endif; ?>
			</section>

			<section class="section">
				<?php f1wiki_section_head( 'Form', 'Recent results' ); ?>
				<?php if ( $recent ) : ?>
					<div class="table-wrap">
						<table class="data-table">
							<thead>
								<tr>
									<th scope="col">Race</th>
									<th scope="col">Team</th>
									<th class="pos" scope="col">Grid</th>
									<th class="pos" scope="col">Result</th>
									<th class="num" scope="col">Points</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent as $row ) : ?>
									<tr>
										<td><a href="<?php echo esc_url( $row->race_url ); ?>"><?php echo esc_html( $row->race_name ); ?></a></td>
										<td><?php echo f1wiki_team_mark( (int) $row->team_id, true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="pos"><?php echo esc_html( $row->grid ? 'P' . (int) $row->grid : '—' ); ?></td>
										<td class="pos">
											<?php echo esc_html( f1wiki_result_position( $row->position, $row->position_text ) ); ?>
											<?php if ( (int) $row->fastest_lap_rank === 1 ) : ?>
												<span class="fl-badge" title="Fastest lap">FL</span>
											<?php endif; ?>
										</td>
										<td class="num points"><?php echo esc_html( f1wiki_format_points( $row->points ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php f1wiki_empty_state( 'No recent results', 'This driver has no race classifications in the archive.' ); ?>
				<?php endif; ?>
			</section>

			<?php if ( $related ) : ?>
				<section class="section">
					<?php f1wiki_section_head( 'Team-mates', 'More from ' . $team['name'], $team['url'], 'View team' ); ?>
					<div class="card-grid card-grid--4">
						<?php foreach ( $related as $related_driver ) : ?>
							<?php get_template_part( 'template-parts/card-driver', null, array( 'post_id' => $related_driver->ID ) ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>

		<aside class="article-aside">
			<div class="infobox">
				<h2>Driver profile</h2>
				<dl class="infobox-list">
					<?php if ( $nat ) : ?>
						<div><dt>Nationality</dt><dd><?php echo esc_html( $nat ); ?> <span class="muted"><?php echo esc_html( $nat_code ); ?></span></dd></div>
					<?php endif; ?>
					<?php if ( $dob ) : ?>
						<div><dt>Date of birth</dt><dd><?php echo esc_html( f1wiki_format_date( $dob, 'j M Y' ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $number ) : ?>
						<div><dt>Car number</dt><dd><?php echo esc_html( $number ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $code ) : ?>
						<div><dt>Code</dt><dd><?php echo esc_html( $code ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $team['name'] ) : ?>
						<div><dt><?php echo esc_html( $team_label ); ?></dt><dd><?php echo f1wiki_team_mark( $team['id'], true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd></div>
					<?php endif; ?>
					<div><dt>Archive span</dt><dd><?php echo esc_html( f1wiki_season_span( $stats ) ); ?></dd></div>
					<div><dt>Starts</dt><dd><?php echo esc_html( number_format_i18n( $stats['starts'] ) ); ?></dd></div>
					<div><dt>Career points</dt><dd><?php echo esc_html( f1wiki_format_points( $stats['points'] ) ); ?></dd></div>
					<div><dt>Fastest laps</dt><dd><?php echo esc_html( number_format_i18n( $stats['fastest_laps'] ) ); ?></dd></div>
					<?php if ( $stats['best_championship'] ) : ?>
						<div><dt>Best championship</dt><dd>P<?php echo esc_html( $stats['best_championship'] ); ?><?php echo $stats['best_championship_year'] ? ' · ' . esc_html( $stats['best_championship_year'] ) : ''; ?></dd></div>
					<?php endif; ?>
					<?php if ( $champ_years ) : ?>
						<div><dt>Titles</dt><dd><?php echo esc_html( implode( ', ', $champ_years ) ); ?></dd></div>
					<?php endif; ?>
				</dl>
				<?php if ( $wiki ) : ?>
					<a class="button button--outline button--wide" href="<?php echo esc_url( $wiki ); ?>" target="_blank" rel="noopener noreferrer">Wikipedia <?php echo f1wiki_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php endif; ?>
			</div>
			<div class="aside-note">
				<p>Career totals are calculated from the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive, not from complete career records.</p>
				<p><?php echo esc_html( f1wiki_fastest_lap_note() ); ?></p>
			</div>
		</aside>
	</div>
</article>
<?php
get_footer();
