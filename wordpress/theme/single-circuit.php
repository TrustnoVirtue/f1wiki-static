<?php
/**
 * Single circuit.
 *
 * @package F1Wiki
 */

get_header();

$circuit_id = get_the_ID();
$stats      = f1wiki_get_stats( $circuit_id, 'circuit' );
$locality   = (string) get_post_meta( $circuit_id, '_f1wiki_locality', true );
$country    = (string) get_post_meta( $circuit_id, '_f1wiki_country', true );
$lat        = (string) get_post_meta( $circuit_id, '_f1wiki_latitude', true );
$lng        = (string) get_post_meta( $circuit_id, '_f1wiki_longitude', true );
$wiki       = (string) get_post_meta( $circuit_id, '_f1wiki_wikipedia', true );
$races      = f1wiki_get_circuit_races( $circuit_id );
$place      = trim( $locality . ( $locality && $country ? ', ' : '' ) . $country );
$map_url    = f1wiki_map_url( $lat, $lng );
$length     = (string) get_post_meta( $circuit_id, '_f1wiki_circuit_length_km', true );
$corners    = (string) get_post_meta( $circuit_id, '_f1wiki_corners', true );
$lap_record = (string) get_post_meta( $circuit_id, '_f1wiki_lap_record', true );
$hero_mark  = $country ? f1wiki_nationality_code( $country ) : 'CIR';
?>
<article class="single single--circuit" style="--team-color:<?php echo esc_attr( '#E10600' ); ?>">
	<header class="entity-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="entity-hero__grid">
				<div class="entity-hero__copy">
					<p class="eyebrow">Circuit profile</p>
					<h1><?php the_title(); ?></h1>
					<div class="entity-meta">
						<?php if ( $place ) : ?>
							<span class="entity-meta__item"><?php echo f1wiki_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $place ); ?></span>
						<?php endif; ?>
						<?php if ( $stats['races'] ) : ?>
							<span class="entity-meta__item"><?php echo esc_html( f1wiki_count_label( $stats['races'], 'race' ) ); ?> in the archive</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="entity-hero__watermark entity-hero__watermark--short" aria-hidden="true"><?php echo esc_html( $hero_mark ); ?></div>
			</div>
		</div>
	</header>

	<div class="container article-layout">
		<div class="article-main">
			<div class="stat-strip">
				<div><strong><?php echo esc_html( number_format_i18n( $stats['races'] ) ); ?></strong><span>Races</span></div>
				<div><strong><?php echo esc_html( $stats['first_season'] ? $stats['first_season'] : '—' ); ?></strong><span>First season</span></div>
				<div><strong><?php echo esc_html( $stats['last_season'] ? $stats['last_season'] : '—' ); ?></strong><span>Latest season</span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $stats['most_wins_driver_wins'] ) ); ?></strong><span>Most wins</span></div>
			</div>

			<div class="prose">
				<?php the_content(); ?>
			</div>

			<p class="data-note">Race history covers the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive only.</p>

			<section class="section">
				<?php f1wiki_section_head( 'History', 'Race winners at the circuit' ); ?>
				<?php if ( $races ) : ?>
					<div class="table-wrap">
						<table class="data-table">
							<thead>
								<tr>
									<th scope="col">Date</th>
									<th scope="col">Race</th>
									<th scope="col">Winner</th>
									<th scope="col">Team</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $races as $race ) : ?>
									<?php
									$summary = f1wiki_get_race_summary( $race->ID );
									$date    = (string) get_post_meta( $race->ID, '_f1wiki_race_date', true );
									?>
									<tr>
										<td><?php echo esc_html( $date ? f1wiki_format_date( $date, 'j M Y' ) : '—' ); ?></td>
										<td><a href="<?php echo esc_url( get_permalink( $race ) ); ?>"><?php echo esc_html( $race->post_title ); ?></a></td>
										<td>
											<?php if ( $summary['winner'] ) : ?>
												<span class="team-dot" style="--team-color:<?php echo esc_attr( $summary['team_color'] ); ?>" aria-hidden="true"></span>
												<a href="<?php echo esc_url( $summary['winner_url'] ); ?>"><?php echo esc_html( $summary['winner'] ); ?></a>
											<?php else : ?>
												—
											<?php endif; ?>
										</td>
										<td>
											<?php
											if ( $summary['team'] ) {
												echo f1wiki_team_mark( (int) $summary['team_id'], true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											} else {
												echo '—';
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php f1wiki_empty_state( 'No race history available', 'This circuit has no imported race results.' ); ?>
				<?php endif; ?>
			</section>
		</div>

		<aside class="article-aside">
			<div class="infobox">
				<h2>Circuit facts</h2>
				<dl class="infobox-list">
					<?php if ( $locality ) : ?>
						<div><dt>Locality</dt><dd><?php echo esc_html( $locality ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $country ) : ?>
						<div><dt>Country</dt><dd><?php echo esc_html( $country ); ?></dd></div>
					<?php endif; ?>
					<div><dt>Archive span</dt><dd><?php echo esc_html( f1wiki_season_span( $stats ) ); ?></dd></div>
					<div><dt>Races</dt><dd><?php echo esc_html( number_format_i18n( $stats['races'] ) ); ?></dd></div>
					<?php if ( $stats['most_wins_driver'] ) : ?>
						<div><dt>Most wins</dt><dd><?php echo esc_html( $stats['most_wins_driver'] ); ?> <span class="muted"><?php echo esc_html( f1wiki_count_label( $stats['most_wins_driver_wins'], 'win' ) ); ?></span></dd></div>
					<?php endif; ?>
					<?php if ( $stats['most_wins_team'] ) : ?>
						<div><dt>Top team</dt><dd><?php echo esc_html( $stats['most_wins_team'] ); ?> <span class="muted"><?php echo esc_html( f1wiki_count_label( $stats['most_wins_team_wins'], 'win' ) ); ?></span></dd></div>
					<?php endif; ?>
					<?php if ( $length ) : ?>
						<div><dt>Length</dt><dd><?php echo esc_html( $length ); ?> km</dd></div>
					<?php endif; ?>
					<?php if ( $corners ) : ?>
						<div><dt>Corners</dt><dd><?php echo esc_html( $corners ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $lap_record ) : ?>
						<div><dt>Lap record</dt><dd><?php echo esc_html( $lap_record ); ?></dd></div>
					<?php endif; ?>
				</dl>
				<div class="infobox__actions">
					<?php if ( $map_url ) : ?>
						<a class="button button--outline button--wide" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener noreferrer">Open map <?php echo f1wiki_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
					<?php if ( $wiki ) : ?>
						<a class="button button--outline button--wide" href="<?php echo esc_url( $wiki ); ?>" target="_blank" rel="noopener noreferrer">Wikipedia <?php echo f1wiki_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="aside-note">
				<p>Race history is limited to the <?php echo esc_html( f1wiki_archive_range_label() ); ?> seasons. The circuit may have hosted Grands Prix outside this range.</p>
			</div>
		</aside>
	</div>
</article>
<?php
get_footer();
