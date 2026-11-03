<?php
/**
 * Single team.
 *
 * @package F1Wiki
 */

get_header();

$team_id     = get_the_ID();
$stats       = f1wiki_get_stats( $team_id, 'team' );
$nat         = (string) get_post_meta( $team_id, '_f1wiki_nationality', true );
$base        = (string) get_post_meta( $team_id, '_f1wiki_base', true );
$principal   = (string) get_post_meta( $team_id, '_f1wiki_principal', true );
$wiki        = (string) get_post_meta( $team_id, '_f1wiki_wikipedia', true );
$seasons     = f1wiki_get_team_seasons( $team_id );
$latest_drivers = f1wiki_get_team_latest_drivers( $team_id );
$wins        = f1wiki_get_team_wins( $team_id, 8 );
$champ_years = isset( $stats['championship_years'] ) && is_array( $stats['championship_years'] ) ? array_map( 'intval', $stats['championship_years'] ) : array();
$color       = f1wiki_team_color( $team_id );
$latest      = f1wiki_latest_season();
$is_current  = (int) $stats['last_season'] === $latest;
$hero_mark   = strtoupper( substr( get_the_title(), 0, 3 ) );
?>
<article class="single single--team" style="--team-color:<?php echo esc_attr( $color ); ?>">
	<header class="entity-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="entity-hero__grid">
				<div class="entity-hero__copy">
					<p class="eyebrow">Constructor profile</p>
					<h1><?php the_title(); ?></h1>
					<div class="entity-meta">
						<?php if ( $nat ) : ?>
							<span class="entity-meta__item"><strong><?php echo esc_html( f1wiki_nationality_code( $nat ) ); ?></strong> <?php echo esc_html( $nat ); ?></span>
						<?php endif; ?>
						<span class="entity-meta__item"><?php echo $is_current ? 'On the current grid' : 'Historic constructor'; ?></span>
						<?php if ( $stats['last_season'] ) : ?>
							<span class="entity-meta__item">Latest season <?php echo esc_html( $stats['last_season'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( has_post_thumbnail( $team_id ) ) : ?>
					<figure class="entity-logo">
						<?php echo get_the_post_thumbnail( $team_id, 'medium', array( 'alt' => '', 'loading' => 'eager' ) ); ?>
						<?php echo f1wiki_photo_credit( $team_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</figure>
				<?php else : ?>
					<div class="entity-hero__watermark entity-hero__watermark--short" aria-hidden="true"><?php echo esc_html( $hero_mark ); ?></div>
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
				<div><strong><?php echo esc_html( number_format_i18n( $stats['starts'] ) ); ?></strong><span>Starts</span></div>
			</div>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<p class="data-note">Team totals cover the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive only.</p>

			<section class="section">
				<?php f1wiki_section_head( 'History', 'Season by season' ); ?>
				<?php if ( $seasons ) : ?>
					<div class="table-wrap">
						<table class="data-table">
							<thead>
								<tr>
									<th scope="col">Season</th>
									<th scope="col">Drivers</th>
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
											$driver_links = array();
											foreach ( $season->drivers as $season_driver ) {
												$driver_links[] = '<a href="' . esc_url( $season_driver['url'] ) . '">' . esc_html( $season_driver['name'] ) . '</a>';
											}
											echo implode( '<span class="sep">,</span> ', $driver_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
					<?php f1wiki_empty_state( 'No season record available', 'Results for this constructor have not been imported yet.' ); ?>
				<?php endif; ?>
			</section>

			<section class="section">
				<?php f1wiki_section_head( 'Results', 'Recent wins' ); ?>
				<?php if ( $wins ) : ?>
					<div class="table-wrap">
						<table class="data-table">
							<thead>
								<tr>
									<th scope="col">Season</th>
									<th scope="col">Race</th>
									<th scope="col">Driver</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $wins as $win ) : ?>
									<tr>
										<td><?php echo esc_html( $win->season_year ); ?></td>
										<td><a href="<?php echo esc_url( $win->race_url ); ?>"><?php echo esc_html( $win->race_name ); ?></a></td>
										<td><a href="<?php echo esc_url( $win->driver_url ); ?>"><?php echo esc_html( $win->driver_name ); ?></a></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php f1wiki_empty_state( 'No wins in the archive', 'This constructor has no recorded race wins in the covered seasons.' ); ?>
				<?php endif; ?>
			</section>
		</div>

		<aside class="article-aside">
			<div class="infobox">
				<h2>Team profile</h2>
				<dl class="infobox-list">
					<?php if ( $nat ) : ?>
						<div><dt>Nationality</dt><dd><?php echo esc_html( $nat ); ?></dd></div>
					<?php endif; ?>
					<div><dt>Archive span</dt><dd><?php echo esc_html( f1wiki_season_span( $stats ) ); ?></dd></div>
					<div><dt>Starts</dt><dd><?php echo esc_html( number_format_i18n( $stats['starts'] ) ); ?></dd></div>
					<div><dt>Wins</dt><dd><?php echo esc_html( number_format_i18n( $stats['wins'] ) ); ?></dd></div>
					<div><dt>Podiums</dt><dd><?php echo esc_html( number_format_i18n( $stats['podiums'] ) ); ?></dd></div>
					<div><dt>Pole positions</dt><dd><?php echo esc_html( number_format_i18n( $stats['poles'] ) ); ?></dd></div>
					<div><dt>Points</dt><dd><?php echo esc_html( f1wiki_format_points( $stats['points'] ) ); ?></dd></div>
					<?php if ( $champ_years ) : ?>
						<div><dt>Titles</dt><dd><?php echo esc_html( implode( ', ', $champ_years ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $base ) : ?>
						<div><dt>Base</dt><dd><?php echo esc_html( $base ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $principal ) : ?>
						<div><dt>Team principal</dt><dd><?php echo esc_html( $principal ); ?></dd></div>
					<?php endif; ?>
				</dl>
				<?php if ( $wiki ) : ?>
					<a class="button button--outline button--wide" href="<?php echo esc_url( $wiki ); ?>" target="_blank" rel="noopener noreferrer">Wikipedia <?php echo f1wiki_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $latest_drivers ) : ?>
				<div class="infobox infobox--plain">
					<h2>Latest line-up</h2>
					<ul class="lineup-list">
						<?php foreach ( $latest_drivers as $driver ) : ?>
							<li>
								<?php echo f1wiki_avatar( $driver['name'], $color, '', 'sm', $driver['id'] ); ?>
								<a href="<?php echo esc_url( $driver['url'] ); ?>"><?php echo esc_html( $driver['name'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="aside-note">
				<p>Statistics cover the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive and may not represent a constructor's full history.</p>
			</div>
		</aside>
	</div>
</article>
<?php
get_footer();
