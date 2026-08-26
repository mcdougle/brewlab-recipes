<?php
//------------------------------------------------------------------------------
//   Block
//------------------------------------------------------------------------------
// Registers the brewlab/recipe block — the second of the two ways a recipe
// gets embedded (the shortcode is the other). Dynamic block: only recipeId
// is stored in post_content, PHP renders the actual card at request time via
// render_recipe(), same as the shortcode — so an edited recipe never shows
// stale markup baked into a static block.
//
// The recipe post type is show_in_rest => false (it's not meant to be
// independently browsable — see render.php's header comment), so the
// editor's recipe picker can't use the native /wp/v2/brewlab_recipe route.
// This file adds a narrow one instead: id + title only, capped at 200,
// gated to edit_posts.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_register_block()
//------------------------------------------------------------------------------
function brewlab_recipes_register_block() {
	wp_register_script(
		'brewlab-recipes-block',
		BREWLAB_RECIPES_URL . 'assets/js/block.js',
		[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ],
		BREWLAB_RECIPES_VERSION,
		true
	);

	register_block_type( 'brewlab/recipe', [
		'attributes'      => [
			'recipeId' => [
				'type'    => 'number',
				'default' => 0,
			],
		],
		'editor_script'   => 'brewlab-recipes-block',
		'render_callback' => 'brewlab_recipes_block_render',
	] );
}
add_action( 'init', 'brewlab_recipes_register_block' );

//------------------------------------------------------------------------------
//   brewlab_recipes_block_render()
//------------------------------------------------------------------------------
function brewlab_recipes_block_render( $attributes ) {
	$recipe_id = isset( $attributes['recipeId'] ) ? absint( $attributes['recipeId'] ) : 0;
	if ( ! $recipe_id ) {
		return '';
	}

	return brewlab_recipes_render_recipe( $recipe_id );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_register_rest_routes()
//------------------------------------------------------------------------------
function brewlab_recipes_register_rest_routes() {
	register_rest_route( 'brewlab-recipes/v1', '/recipes', [
		'methods'             => 'GET',
		'callback'            => 'brewlab_recipes_rest_get_recipes',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	] );
}
add_action( 'rest_api_init', 'brewlab_recipes_register_rest_routes' );

//------------------------------------------------------------------------------
//   brewlab_recipes_rest_get_recipes()
//------------------------------------------------------------------------------
// id + title only — the picker doesn't need meta, and exposing it here would
// just be a second, unauthenticated-adjacent way to read recipe data.
function brewlab_recipes_rest_get_recipes() {
	$query = new WP_Query( [
		'post_type'      => 'brewlab_recipe',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	] );

	$recipes = [];
	foreach ( $query->posts as $post_id ) {
		$recipes[] = [
			'id'    => $post_id,
			'title' => get_the_title( $post_id ),
		];
	}

	return rest_ensure_response( $recipes );
}
