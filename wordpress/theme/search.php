<?php
/**
 * Search results.
 *
 * @package F1Wiki
 */

get_header();

global $wp_query;
$total = (int) $wp_query->found_posts;
?>
<div class="search-page">
	<section class="archive-hero search-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<div class="search-hero__inner">
				<p class="eyebrow">Search</p>
				<h1><?php echo $total ? 'Results for “' . esc_html( get_search_query() ) . '”' : 'Search the archive'; ?></h1>
				<p><?php echo $total ? esc_html( number_format_i18n( $total ) . ' matches across drivers, teams, circuits, races and seasons.' ) : 'Look up a driver, team, circuit, race or season.'; ?></p>
				<form class="search-form search-form--large" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="search-page-field">Search F1 Wiki</label>
					<input id="search-page-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Try “Senna”, “Monza” or “2021”">
					<button class="button" type="submit"><?php echo f1wiki_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Search</button>
				</form>
			</div>
		</div>
	</section>

	<section class="section section--archive">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="search-results">
					<?php
					while ( have_posts() ) :
						the_post();
						$type = get_post_type();
						?>
						<article class="search-result">
							<a class="search-result__link" href="<?php the_permalink(); ?>">
								<span class="search-result__type"><?php echo esc_html( f1wiki_post_type_label( $type ) ); ?></span>
								<div class="search-result__body">
									<h2><?php the_title(); ?></h2>
									<p><?php echo esc_html( wp_html_excerpt( wp_strip_all_tags( get_the_excerpt() ), 220, '…' ) ); ?></p>
								</div>
								<span class="search-result__arrow"><?php echo f1wiki_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</a>
						</article>
					<?php endwhile; ?>
				</div>
				<?php f1wiki_pagination(); ?>
			<?php else : ?>
				<?php f1wiki_empty_state( 'No matches found', 'Try a broader term, check the spelling, or browse an archive below.' ); ?>
				<div class="search-suggestions">
					<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">Drivers</a>
					<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'team' ) ); ?>">Teams</a>
					<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'circuit' ) ); ?>">Circuits</a>
					<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'season' ) ); ?>">Seasons</a>
					<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'race' ) ); ?>">Races</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
