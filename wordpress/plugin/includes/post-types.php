<?php
/**
 * Content type registration.
 *
 * @package F1WikiCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a standard label set.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array
 */
function f1wiki_post_type_labels( $singular, $plural ) {
	return array(
		'name'                  => $plural,
		'singular_name'         => $singular,
		'menu_name'             => $plural,
		'name_admin_bar'        => $singular,
		'archives'              => $plural . ' Archive',
		'attributes'            => $singular . ' Attributes',
		'parent_item_colon'     => 'Parent ' . $singular . ':',
		'all_items'             => 'All ' . $plural,
		'add_new_item'          => 'Add New ' . $singular,
		'add_new'               => 'Add New',
		'new_item'              => 'New ' . $singular,
		'edit_item'             => 'Edit ' . $singular,
		'update_item'           => 'Update ' . $singular,
		'view_item'             => 'View ' . $singular,
		'view_items'            => 'View ' . $plural,
		'search_items'          => 'Search ' . $plural,
		'not_found'             => 'No ' . strtolower( $plural ) . ' found',
		'not_found_in_trash'    => 'No ' . strtolower( $plural ) . ' found in Trash',
		'featured_image'        => 'Featured image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into ' . strtolower( $singular ),
		'uploaded_to_this_item' => 'Uploaded to this ' . strtolower( $singular ),
		'items_list'            => $plural . ' list',
		'items_list_navigation' => $plural . ' list navigation',
		'filter_items_list'     => 'Filter ' . strtolower( $plural ) . ' list',
	);
}

/**
 * Register all F1 wiki post types.
 */
function f1wiki_register_content_types() {
	$types = array(
		'driver'  => array(
			'slug'     => 'drivers',
			'singular' => 'Driver',
			'plural'   => 'Drivers',
			'icon'     => 'dashicons-groups',
			'position' => 20,
		),
		'team'    => array(
			'slug'     => 'teams',
			'singular' => 'Team',
			'plural'   => 'Teams',
			'icon'     => 'dashicons-shield',
			'position' => 21,
		),
		'circuit' => array(
			'slug'     => 'circuits',
			'singular' => 'Circuit',
			'plural'   => 'Circuits',
			'icon'     => 'dashicons-location-alt',
			'position' => 22,
		),
		'race'    => array(
			'slug'     => 'races',
			'singular' => 'Race',
			'plural'   => 'Races',
			'icon'     => 'dashicons-flag',
			'position' => 23,
		),
		'season'  => array(
			'slug'     => 'seasons',
			'singular' => 'Season',
			'plural'   => 'Seasons',
			'icon'     => 'dashicons-calendar-alt',
			'position' => 24,
		),
	);

	foreach ( $types as $type => $config ) {
		register_post_type(
			$type,
			array(
				'labels'             => f1wiki_post_type_labels( $config['singular'], $config['plural'] ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_admin_bar'  => true,
				'show_in_rest'       => true,
				'rest_base'          => $config['slug'],
				'menu_position'      => $config['position'],
				'menu_icon'          => $config['icon'],
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'has_archive'        => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => $config['slug'],
					'with_front' => false,
				),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			)
		);
	}
}
add_action( 'init', 'f1wiki_register_content_types', 5 );
