<?php
/**
 * Driver card.
 *
 * @package F1Wiki
 */

$driver_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $driver_id ) {
	return;
}

$stats   = f1wiki_get_stats( $driver_id, 'driver' );
$team    = f1wiki_get_driver_team( $driver_id );
$number  = f1wiki_driver_number( $driver_id );
$code    = (string) get_post_meta( $driver_id, '_f1wiki_driver_code', true );
$display = $number ? $number : f1wiki_initials( get_the_title( $driver_id ) );
$latest  = f1wiki_latest_season();
$is_current = ! empty( $team['year'] ) && (int) $team['year'] === $latest;
?>
<article class="card card--driver" style="--team-color:<?php echo esc_attr( $team['color'] ); ?>">
	<a class="card__link" href="<?php echo esc_url( get_permalink( $driver_id ) ); ?>">
		<div class="card__top">
			<span class="card__kicker"><?php echo $is_current ? 'Current driver' : 'Driver'; ?></span>
			<?php if ( $code ) : ?>
				<span class="card__code"><?php echo esc_html( $code ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( has_post_thumbnail( $driver_id ) ) : ?>
			<div class="card__portrait">
				<?php echo get_the_post_thumbnail( $driver_id, 'f1wiki-portrait', array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
			</div>
		<?php else : ?>
			<div class="card__number" aria-hidden="true"><?php echo esc_html( $display ); ?></div>
		<?php endif; ?>
		<div class="card__body">
			<h3><?php echo esc_html( get_the_title( $driver_id ) ); ?></h3>
			<p class="card__meta">
				<?php if ( $team['name'] ) : ?>
					<span class="team-dot" style="--team-color:<?php echo esc_attr( $team['color'] ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $team['name'] ); ?>
				<?php else : ?>
					<span>Formula One driver</span>
				<?php endif; ?>
			</p>
		</div>
		<div class="card__foot">
			<span><strong><?php echo esc_html( number_format_i18n( $stats['wins'] ) ); ?></strong> <?php echo esc_html( f1wiki_plural( $stats['wins'], 'win', 'wins' ) ); ?></span>
			<span><strong><?php echo esc_html( number_format_i18n( $stats['championships'] ) ); ?></strong> <?php echo esc_html( f1wiki_plural( $stats['championships'], 'title', 'titles' ) ); ?></span>
			<span class="card__arrow"><?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</a>
</article>
