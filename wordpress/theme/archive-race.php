<?php
/**
 * Race archive.
 *
 * @package F1Wiki
 */

get_header();

$selected_season = isset( $_GET['race_season'] ) ? absint( wp_unslash( $_GET['race_season'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$season_posts     = get_posts(
	array(
		'post_type'      => 'season',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => '_f1wiki_year_sort',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	)
);
?>
<div class="archive">
	<section class="archive-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="archive-hero__inner">
				<div class="archive-hero__copy">
					<p class="eyebrow">Browse the archive</p>
					<h1>Races</h1>
					<p>Every Grand Prix in the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive. Open a race for the full classification, pole position, fastest lap and circuit details.</p>
				</div>
				<form class="filters" method="get" action="<?php echo esc_url( f1wiki_archive_link( 'race' ) ); ?>">
					<label class="filters__field">
						<span>Season</span>
						<select name="race_season" data-auto-submit>
							<option value="">All seasons</option>
							<?php foreach ( $season_posts as $season_post ) : ?>
								<?php $season_year = (int) get_post_meta( $season_post->ID, '_f1wiki_season_year', true ); ?>
								<option value="<?php echo esc_attr( $season_year ); ?>" <?php selected( $selected_season, $season_year ); ?>><?php echo esc_html( $season_year ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="filters__actions">
						<button class="button button--small" type="submit">Filter</button>
						<?php if ( $selected_season ) : ?>
							<a class="filters__reset" href="<?php echo esc_url( f1wiki_archive_link( 'race' ) ); ?>">Reset</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
			<div class="archive-meta">
				<span><?php echo esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) . ' races' ); ?></span>
				<span><?php echo esc_html( $selected_season ? $selected_season . ' season' : 'Newest first' ); ?></span>
			</div>
		</div>
	</section>

	<section class="section section--archive">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<?php $current_season = 0; ?>
				<?php
				while ( have_posts() ) :
					the_post();
					$season_year = (int) get_post_meta( get_the_ID(), '_f1wiki_season_year', true );

					if ( $season_year !== $current_season ) :
						if ( $current_season ) {
							echo '</div></div>';
						}
						$season_status = f1wiki_get_season_status( $season_year );
						$season_id     = f1wiki_get_season_post( $season_year );
						?>
						<div class="race-group">
							<div class="race-group__head">
								<h2><a href="<?php echo esc_url( get_permalink( $season_id ) ); ?>"><?php echo esc_html( $season_year ); ?> season</a></h2>
								<span><?php echo esc_html( $season_status['completed'] . ' of ' . $season_status['rounds'] . ' rounds completed' ); ?></span>
							</div>
							<div class="card-grid card-grid--4">
						<?php
						$current_season = $season_year;
					endif;

					get_template_part( 'template-parts/card-race', null, array( 'post_id' => get_the_ID() ) );
				endwhile;

				if ( $current_season ) {
					echo '</div></div>';
				}
				?>
				<?php f1wiki_pagination(); ?>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No races match that season', 'Try another season or reset the filter to browse the full race archive.' ); ?>
				<p class="center"><a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'race' ) ); ?>">Reset filter</a></p>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
