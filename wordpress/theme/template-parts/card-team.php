<?php
/**
 * Team card.
 *
 * @package F1Wiki
 */

$team_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $team_id ) {
	return;
}

$stats = f1wiki_get_stats( $team_id, 'team' );
$color = f1wiki_team_color( $team_id );
$nat   = (string) get_post_meta( $team_id, '_f1wiki_nationality', true );
$code  = f1wiki_nationality_code( $nat );
$span  = f1wiki_season_span( $stats );
$current = (int) $stats['last_season'] === f1wiki_latest_season();
?>
<article class="card card--team" style="--team-color:<?php echo esc_attr( $color ); ?>">
	<a class="card__link" href="<?php echo esc_url( get_permalink( $team_id ) ); ?>">
		<div class="card__top">
			<span class="card__kicker"><?php echo $current ? 'Current constructor' : 'Constructor'; ?></span>
			<?php if ( $nat ) : ?>
				<span class="card__code"><?php echo esc_html( $code ); ?></span>
			<?php endif; ?>
		</div>
		<div class="card__logo">
			<?php echo f1wiki_team_logo( $team_id, 56 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<div class="card__body">
			<h3><?php echo esc_html( get_the_title( $team_id ) ); ?></h3>
			<p class="card__meta"><?php echo esc_html( $span ); ?> in the archive</p>
		</div>
		<div class="card__metrics">
			<span><strong><?php echo esc_html( number_format_i18n( $stats['championships'] ) ); ?></strong> <?php echo esc_html( f1wiki_plural( $stats['championships'], 'title', 'titles' ) ); ?></span>
			<span><strong><?php echo esc_html( number_format_i18n( $stats['wins'] ) ); ?></strong> <?php echo esc_html( f1wiki_plural( $stats['wins'], 'win', 'wins' ) ); ?></span>
			<span><strong><?php echo esc_html( number_format_i18n( $stats['starts'] ) ); ?></strong> <?php echo esc_html( f1wiki_plural( $stats['starts'], 'start', 'starts' ) ); ?></span>
		</div>
		<div class="card__foot">
			<span><?php echo esc_html( $stats['last_season'] ? 'Latest season ' . $stats['last_season'] : 'Constructor profile' ); ?></span>
			<span class="card__arrow"><?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</a>
</article>
