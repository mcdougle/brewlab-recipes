<?php
//------------------------------------------------------------------------------
//   Enqueue
//------------------------------------------------------------------------------
// Loads recipe-card.css only on requests that actually rendered a recipe.
// Deliberately doesn't pre-scan $post->post_content for the shortcode/block
// (has_shortcode()/has_block()) — the old plugin did exactly that, and it
// silently missed any recipe pulled in via a widget, template part, or
// custom loop outside the_content() (root-caused in the pre-rebuild audit).
// Checking render.php's "did a recipe actually render" flag instead of
// guessing from content works no matter how the recipe ended up on the page.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_maybe_print_recipe_styles()
//------------------------------------------------------------------------------
// Printed directly in wp_footer rather than via wp_enqueue_style() — by the
// time we know whether a recipe rendered this request, wp_head has already
// fired, so wp_enqueue_style()'s normal head-output path isn't available.
function brewlab_recipes_maybe_print_recipe_styles() {
	if ( ! brewlab_recipes_recipe_was_rendered() ) {
		return;
	}

	printf(
		'<link rel="stylesheet" id="brewlab-recipes-card-css" href="%s" media="all" />' . "\n",
		esc_url( BREWLAB_RECIPES_URL . 'assets/css/recipe-card.css?ver=' . BREWLAB_RECIPES_VERSION )
	);
}
add_action( 'wp_footer', 'brewlab_recipes_maybe_print_recipe_styles' );
