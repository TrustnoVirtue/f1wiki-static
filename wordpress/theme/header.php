<?php
/**
 * Header.
 *
 * @package F1Wiki
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon.svg' ); ?>" type="image/svg+xml">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">Skip to content</a>

<div class="site" id="top">
	<header class="site-header" data-header>
		<div class="utility-bar">
			<div class="container utility-bar__inner">
				<span>Independent Formula 1 encyclopedia</span>
				<span>Archive <?php echo esc_html( f1wiki_archive_range_label() ); ?></span>
			</div>
		</div>

		<div class="masthead">
			<div class="container masthead__inner">
				<?php f1wiki_site_logo(); ?>

				<nav class="primary-nav" id="primary-nav" aria-label="Primary">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'menu',
							'fallback_cb'    => 'f1wiki_nav_fallback',
							'depth'          => 1,
						)
					);
					?>
				</nav>

				<div class="header-actions">
					<form class="header-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label class="screen-reader-text" for="header-search-field">Search F1 Wiki</label>
						<input id="header-search-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Search the archive&#8230;" autocomplete="off">
						<button type="submit" aria-label="Search"><?php echo f1wiki_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					</form>
				</div>

				<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
					<span class="menu-toggle__open"><?php echo f1wiki_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="menu-toggle__close"><?php echo f1wiki_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="screen-reader-text">Toggle menu</span>
				</button>
			</div>
		</div>
	</header>

	<main id="main" class="site-main">
