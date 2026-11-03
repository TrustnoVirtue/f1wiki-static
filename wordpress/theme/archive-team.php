<?php
/**
 * Team archive.
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
					<h1>Teams</h1>
					<p>Constructors from the <?php echo esc_html( f1wiki_archive_range_label() ); ?> seasons, with championship years, win totals, driver line-ups and season-by-season records.</p>
				</div>
			</div>
			<div class="archive-meta">
				<span><?php echo esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) . ' constructors' ); ?></span>
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
						get_template_part( 'template-parts/card-team', null, array( 'post_id' => get_the_ID() ) );
					endwhile;
					?>
				</div>
				<?php f1wiki_pagination(); ?>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No constructors found', 'Import team data to populate this archive.' ); ?>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
