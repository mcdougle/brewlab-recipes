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
			'add_new_item'  => __( 'Add New Recipe', 'brewlab-recipes' ),
			'edit_item'     => __( 'Edit Recipe', 'brewlab-recipes' ),
		],
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-carrot',
		'supports'     => [ 'title' ],
	] );
}
add_action( 'init', 'brewlab_recipes_register_post_type' );
