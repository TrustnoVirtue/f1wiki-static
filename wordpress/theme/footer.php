<?php
/**
 * Footer.
 *
 * @package F1Wiki
 */
?>
	</main>

	<footer class="site-footer">
		<div class="container footer-grid">
			<div class="footer-brand">
				<?php f1wiki_site_logo(); ?>
				<p>Drivers, teams, circuits, races and seasons — connected in one independent Formula 1 archive.</p>
				<p class="footer-brand__note">Archive <?php echo esc_html( f1wiki_archive_range_label() ); ?>. Built with WordPress and open race data.</p>
			</div>

			<div class="footer-column">
				<h2>Browse</h2>
				<ul>
					<li><a href="<?php echo esc_url( f1wiki_archive_link( 'driver' ) ); ?>">Drivers</a></li>
					<li><a href="<?php echo esc_url( f1wiki_archive_link( 'team' ) ); ?>">Teams</a></li>
					<li><a href="<?php echo esc_url( f1wiki_archive_link( 'circuit' ) ); ?>">Circuits</a></li>
					<li><a href="<?php echo esc_url( f1wiki_archive_link( 'season' ) ); ?>">Seasons</a></li>
					<li><a href="<?php echo esc_url( f1wiki_archive_link( 'race' ) ); ?>">Races</a></li>
				</ul>
			</div>

			<div class="footer-column">
				<h2>About</h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-menu',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>

			<div class="footer-column footer-column--note">
				<h2>Data note</h2>
				<p>Statistics describe the <?php echo esc_html( f1wiki_archive_range_label() ); ?> seasons only. Race data comes from the Jolpica F1 API. Fastest-lap records begin in 2004.</p>
				<p>F1 Wiki is a fan project, not affiliated with Formula 1, the FIA or any team. No official logos or photography are used.</p>
			</div>
		</div>

		<div class="container footer-bottom">
			<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> F1 Wiki. An independent encyclopedia.</p>
			<a href="#top">Back to top</a>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
