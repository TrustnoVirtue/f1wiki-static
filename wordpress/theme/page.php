<?php
/**
 * Page.
 *
 * @package F1Wiki
 */

get_header();
?>
<article class="page">
	<header class="page-hero">
		<div class="container">
			<?php f1wiki_breadcrumbs(); ?>
			<p class="eyebrow">F1 Wiki</p>
			<h1><?php the_title(); ?></h1>
		</div>
	</header>

	<div class="container page-layout">
		<div class="prose prose--page">
			<?php the_content(); ?>
		</div>
	</div>
</article>
<?php
get_footer();
