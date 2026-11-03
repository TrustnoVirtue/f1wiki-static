<?php
/**
 * Fallback index.
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
					<p class="eyebrow">F1 Wiki</p>
					<h1><?php echo is_home() && ! is_front_page() ? 'Latest' : 'Archive'; ?></h1>
					<p>Articles and updates from the F1 Wiki archive.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="section section--archive">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="post-list">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<article class="post-list__item">
							<p class="eyebrow"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></p>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html( wp_html_excerpt( wp_strip_all_tags( get_the_excerpt() ), 220, '…' ) ); ?></p>
						</article>
					<?php endwhile; ?>
				</div>
				<?php f1wiki_pagination(); ?>
			<?php else : ?>
				<?php f1wiki_empty_state( 'Nothing published yet', 'There are no articles in this archive.' ); ?>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
