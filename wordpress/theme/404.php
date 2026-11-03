<?php
/**
 * 404.
 *
 * @package F1Wiki
 */

get_header();
?>
<div class="not-found">
	<div class="container not-found__inner">
		<p class="eyebrow">404</p>
		<h1>That page is off the grid.</h1>
		<p>The page you were looking for does not exist, or it may have moved. Try the archive search or jump to a section below.</p>
		<form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="not-found-search">Search F1 Wiki</label>
			<input id="not-found-search" type="search" name="s" placeholder="Search drivers, teams, races…">
			<button class="button" type="submit"><?php echo f1wiki_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Search</button>
		</form>
		<div class="not-found__links">
			<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">Drivers</a>
			<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'team' ) ); ?>">Teams</a>
			<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'circuit' ) ); ?>">Circuits</a>
			<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'season' ) ); ?>">Seasons</a>
			<a class="button button--outline" href="<?php echo esc_url( f1wiki_archive_link( 'race' ) ); ?>">Races</a>
		</div>
	</div>
</div>
<?php
get_footer();
