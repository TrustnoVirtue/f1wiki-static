<?php
/**
 * Season archive.
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
					<h1>Seasons</h1>
					<p>Championship standings, calendars and winners for every season in the archive. Open a year for the full drivers' and constructors' tables and a round-by-round record.</p>
				</div>
			</div>
			<div class="archive-meta">
				<span><?php echo esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) . ' seasons' ); ?></span>
				<span><?php echo esc_html( f1wiki_archive_range_label() ); ?></span>
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
						get_template_part( 'template-parts/card-season', null, array( 'post_id' => get_the_ID() ) );
					endwhile;
					?>
				</div>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No seasons found', 'Import season data to populate this archive.' ); ?>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
