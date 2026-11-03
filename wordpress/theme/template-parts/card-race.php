<?php
/**
 * Race card.
 *
 * @package F1Wiki
 */

$race_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $race_id ) {
	return;
}

$season  = (int) get_post_meta( $race_id, '_f1wiki_season_year', true );
$round   = (int) get_post_meta( $race_id, '_f1wiki_round', true );
$date    = (string) get_post_meta( $race_id, '_f1wiki_race_date', true );
$has     = (int) get_post_meta( $race_id, '_f1wiki_has_results', true );
$summary = f1wiki_get_race_summary( $race_id );
$name    = preg_replace( '/^\d{4}\s+/', '', get_the_title( $race_id ) );
?>
<article class="card card--race" style="--team-color:<?php echo esc_attr( $summary['team_color'] ); ?>">
	<a class="card__link" href="<?php echo esc_url( get_permalink( $race_id ) ); ?>">
		<div class="card__top">
			<span class="card__kicker"><?php echo esc_html( $season . ' · Round ' . $round ); ?></span>
			<span class="card__code"><?php echo esc_html( $date ? f1wiki_format_date( $date, 'j M' ) : 'TBC' ); ?></span>
		</div>
		<div class="card__body">
			<h3><?php echo esc_html( $name ); ?></h3>
			<p class="card__meta"><?php echo esc_html( $summary['circuit'] ? $summary['circuit'] : 'Circuit to be confirmed' ); ?></p>
		</div>
		<div class="card__foot">
			<span>
				<?php if ( $has && $summary['winner'] ) : ?>
					<span class="team-dot" style="--team-color:<?php echo esc_attr( $summary['team_color'] ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $summary['winner'] ); ?>
				<?php elseif ( $has ) : ?>
					Classification available
				<?php else : ?>
					Upcoming race
				<?php endif; ?>
			</span>
			<span class="card__arrow"><?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</a>
</article>
