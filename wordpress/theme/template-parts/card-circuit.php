<?php
/**
 * Circuit card.
 *
 * @package F1Wiki
 */

$circuit_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $circuit_id ) {
	return;
}

$stats   = f1wiki_get_stats( $circuit_id, 'circuit' );
$locality = (string) get_post_meta( $circuit_id, '_f1wiki_locality', true );
$country  = (string) get_post_meta( $circuit_id, '_f1wiki_country', true );
$place    = trim( $locality . ( $locality && $country ? ', ' : '' ) . $country );
$span     = f1wiki_season_span( $stats );
?>
<article class="card card--circuit">
	<a class="card__link" href="<?php echo esc_url( get_permalink( $circuit_id ) ); ?>">
		<div class="card__top">
			<span class="card__kicker">Circuit</span>
			<span class="card__code"><?php echo esc_html( $country ? f1wiki_nationality_code( $country ) : 'F1' ); ?></span>
		</div>
		<div class="card__body">
			<h3><?php echo esc_html( get_the_title( $circuit_id ) ); ?></h3>
			<p class="card__meta"><?php echo esc_html( $place ? $place : 'Formula One venue' ); ?></p>
		</div>
		<div class="card__metrics">
			<span><strong><?php echo esc_html( number_format_i18n( $stats['races'] ) ); ?></strong> races</span>
			<span><strong><?php echo esc_html( $span ); ?></strong> archive span</span>
		</div>
		<div class="card__foot">
			<span>
				<?php
				if ( $stats['most_wins_driver'] ) {
					echo esc_html( $stats['most_wins_driver'] . ' · ' . f1wiki_count_label( $stats['most_wins_driver_wins'], 'win' ) );
				} else {
					echo 'Race history';
				}
				?>
			</span>
			<span class="card__arrow"><?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</a>
</article>
