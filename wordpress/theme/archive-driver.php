<?php
/**
 * Driver archive.
 *
 * @package F1Wiki
 */

get_header();

$filter_teams  = get_posts(
	array(
		'post_type'      => 'team',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);
$selected_team = isset( $_GET['driver_team'] ) ? absint( wp_unslash( $_GET['driver_team'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_only  = isset( $_GET['current'] ) && '1' === (string) wp_unslash( $_GET['current'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$has_filters   = $selected_team || $current_only;
?>
<div class="archive">
	<section class="archive-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="archive-hero__inner">
				<div class="archive-hero__copy">
					<p class="eyebrow">Browse the archive</p>
					<h1>Drivers</h1>
					<p>Every driver in the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive, from world champions to one-off entries. Open a profile for career totals, season-by-season records and recent results.</p>
				</div>
				<form class="filters" method="get" action="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">
					<label class="filters__field">
						<span>Team</span>
						<select name="driver_team" data-auto-submit>
							<option value="">All teams</option>
							<?php foreach ( $filter_teams as $team_post ) : ?>
								<option value="<?php echo esc_attr( $team_post->ID ); ?>" <?php selected( $selected_team, (int) $team_post->ID ); ?>><?php echo esc_html( get_the_title( $team_post ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="filters__check">
						<input type="checkbox" name="current" value="1" <?php checked( $current_only ); ?> data-auto-submit>
						<span>Current grid only</span>
					</label>
					<div class="filters__actions">
						<button class="button button--small" type="submit">Filter</button>
						<?php if ( $has_filters ) : ?>
							<a class="filters__reset" href="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">Reset</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
			<div class="archive-meta">
				<span><?php echo esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) . ' drivers' ); ?></span>
				<span><?php echo esc_html( $current_only ? 'Current grid' : ( $selected_team ? 'Filtered by team' : 'Archive ' . f1wiki_archive_range_label() ) ); ?></span>
			</div>
		</div>
	</section>

	<section class="section section--archive">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="card-grid card-grid--4">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card-driver', null, array( 'post_id' => get_the_ID() ) );
					endwhile;
					?>
				</div>
				<?php f1wiki_pagination(); ?>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No drivers match those filters', 'Try another team or reset the filter to see the full driver archive.' ); ?>
				<p class="center"><a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">Reset filters</a></p>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
