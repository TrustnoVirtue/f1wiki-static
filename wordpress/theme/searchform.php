<?php
/**
 * Search form.
 *
 * @package F1Wiki
 */

$form_id = 'search-form-' . wp_unique_id();
?>
<form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $form_id ); ?>">Search F1 Wiki</label>
	<input id="<?php echo esc_attr( $form_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Search the archive…">
	<button class="button" type="submit"><?php echo f1wiki_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Search</button>
</form>
