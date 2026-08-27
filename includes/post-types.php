<?php
//------------------------------------------------------------------------------
//   Post Types
//------------------------------------------------------------------------------
// Registers the brewlab_recipe custom post type. Non-public — recipes are
// embedded into posts via shortcode/block, not browsed directly on the site.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   init: register brewlab_recipe
//------------------------------------------------------------------------------
function brewlab_recipes_register_post_type() {
	register_post_type( 'brewlab_recipe', [
		'labels'       => [
			'name'          => __( 'Recipes', 'brewlab-recipes' ),
			'singular_name' => __( 'Recipe', 'brewlab-recipes' ),
			'menu_name'     => __( 'BrewLab Recipes', 'brewlab-recipes' ),
			// Without this, WP defaults the first submenu item (the recipe
			// list) to the same label as the top-level menu — "BrewLab
			// Recipes" appearing twice, once as the menu and once directly
			// under it.
			'all_items'     => __( 'Manage Recipes', 'brewlab-recipes' ),
			'add_new_item'  => __( 'Add New Recipe', 'brewlab-recipes' ),
			'edit_item'     => __( 'Edit Recipe', 'brewlab-recipes' ),
		],
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		// BrewLab's actual flask logo, not a dashicon — full color, so it
		// won't get WP's usual monochrome hover/active recoloring (dimming
		// still applies), but it's the real brand mark instead of an
		// arbitrary dashicon (previously dashicons-carrot, which had no
		// connection to the plugin at all).
		//
		// Must be a data: URI, not a plain file URL — WP only applies its
		// menu-icon size constraint (scaled to 20x20 via CSS background-size)
		// to 'none', a dashicons-* class, or a data:image/svg+xml URI. A
		// regular URL renders the image at its native size instead, which
		// on this 370x425 source blew out over most of the admin sidebar.
		'menu_icon'    => 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( BREWLAB_RECIPES_PATH . 'assets/svg/brewlab-logo.svg' ) ),
		'supports'     => [ 'title' ],
	] );
}
add_action( 'init', 'brewlab_recipes_register_post_type' );
