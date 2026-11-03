<?php
/**
 * Circuit archive.
 *
 * @package F1Wiki
 */

get_header();
?>
<div class="archive">
	<section class="archive-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="archive-hero__inner">
				<div class="archive-hero__copy">
					<p class="eyebrow">Browse the archive</p>
					<h1>Circuits</h1>
					<p>Formula 1 venues from the <?php echo esc_html( f1wiki_archive_range_label() ); ?> archive. Each profile collects race history, location and the most successful drivers and teams at the circuit.</p>
				</div>
			</div>
			<div class="archive-meta">
				<span><?php echo esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) . ' circuits' ); ?></span>
				<span>Archive <?php echo esc_html( f1wiki_archive_range_label() ); ?></span>
			</div>
		</div>
	</section>

	<section class="section section--archive">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="card-grid card-grid--3">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card-circuit', null, array( 'post_id' => get_the_ID() ) );
					endwhile;
					?>
				</div>
				<?php f1wiki_pagination(); ?>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No circuits found', 'Import race schedules to populate this archive.' ); ?>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
