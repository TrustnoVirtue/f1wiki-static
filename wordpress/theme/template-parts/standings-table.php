<?php
/**
 * Standings table.
 *
 * @package F1Wiki
 */

$rows = isset( $args['rows'] ) && is_array( $args['rows'] ) ? $args['rows'] : array();
$type = isset( $args['type'] ) && 'team' === $args['type'] ? 'team' : 'driver';
?>
<div class="table-wrap">
	<table class="data-table standings-table">
		<caption class="screen-reader-text"><?php echo esc_html( 'team' === $type ? 'Constructor standings' : 'Driver standings' ); ?></caption>
		<thead>
			<tr>
				<th class="pos" scope="col">Pos</th>
				<th scope="col"><?php echo esc_html( 'team' === $type ? 'Constructor' : 'Driver' ); ?></th>
				<?php if ( 'driver' === $type ) : ?>
					<th scope="col">Team</th>
				<?php endif; ?>
				<th class="num" scope="col">Wins</th>
				<th class="num" scope="col">Pts</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr>
					<td colspan="<?php echo esc_attr( 'driver' === $type ? 5 : 4 ); ?>">Standings are not available yet.</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$position = isset( $row->position ) ? (int) $row->position : 0;
					$name     = 'team' === $type ? $row->team_name : $row->driver_name;
					$url      = 'team' === $type ? $row->team_url : $row->driver_url;
					$color    = isset( $row->team_color ) ? $row->team_color : '#666666';
					?>
					<tr class="<?php echo 1 === $position ? 'is-leader' : ''; ?>">
						<td class="pos"><?php echo esc_html( $position ? $position : '—' ); ?></td>
						<td>
							<a class="table-person" href="<?php echo esc_url( $url ); ?>">
								<span class="team-dot" style="--team-color:<?php echo esc_attr( $color ); ?>" aria-hidden="true"></span>
								<?php echo esc_html( $name ); ?>
							</a>
						</td>
						<?php if ( 'driver' === $type ) : ?>
							<td>
								<?php
								if ( ! empty( $row->team_id ) ) {
									echo f1wiki_team_mark( (int) $row->team_id, true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo '—';
								}
								?>
							</td>
						<?php endif; ?>
						<td class="num"><?php echo esc_html( number_format_i18n( (int) $row->wins ) ); ?></td>
						<td class="num points"><?php echo esc_html( f1wiki_format_points( $row->points ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
