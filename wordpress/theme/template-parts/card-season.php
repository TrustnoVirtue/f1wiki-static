<?php
/**
 * Season card.
 *
 * @package F1Wiki
 */

$season_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
if ( ! $season_id ) {
	return;
}

$year       = (int) get_post_meta( $season_id, '_f1wiki_season_year', true );
$rounds     = (int) get_post_meta( $season_id, '_f1wiki_rounds', true );
$completed  = (int) get_post_meta( $season_id, '_f1wiki_completed_rounds', true );
$status     = (string) get_post_meta( $season_id, '_f1wiki_season_status', true );
$driver_id  = (int) get_post_meta( $season_id, '_f1wiki_champion_driver_id', true );
$team_id    = (int) get_post_meta( $season_id, '_f1wiki_champion_team_id', true );
$team_color = $team_id ? f1wiki_team_color( $team_id ) : '#E10600';
$champion   = $driver_id ? get_the_title( $driver_id ) : '';
$team_name  = $team_id ? get_the_title( $team_id ) : '';
?>
<article class="card card--season" style="--team-color:<?php echo esc_attr( $team_color ); ?>">
	<a class="card__link" href="<?php echo esc_url( get_permalink( $season_id ) ); ?>">
		<div class="card__top">
			<span class="card__kicker"><?php echo esc_html( f1wiki_status_label( $status ) ); ?></span>
			<span class="card__code"><?php echo esc_html( $completed . '/' . $rounds ); ?></span>
		</div>
		<div class="season-card__year" aria-hidden="true"><?php echo esc_html( $year ); ?></div>
		<div class="card__body">
			<h3><?php echo esc_html( $year . ' season' ); ?></h3>
			<p class="card__meta">
				<?php if ( $champion ) : ?>
					<span class="team-dot" style="--team-color:<?php echo esc_attr( $team_color ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( ( 'completed' === $status ? 'Champion: ' : 'Leader: ' ) . $champion ); ?>
				<?php else : ?>
					<?php echo esc_html( $completed . ' of ' . $rounds . ' rounds completed' ); ?>
				<?php endif; ?>
			</p>
		</div>
		<div class="card__foot">
			<span><?php echo esc_html( $team_name ? $team_name : 'Season overview' ); ?></span>
			<span class="card__arrow"><?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</a>
</article>
