<?php
//------------------------------------------------------------------------------
//   Enqueue
//------------------------------------------------------------------------------
// Loads recipe-card.css only on requests that actually rendered a recipe.
// Deliberately doesn't pre-scan $post->post_content for the shortcode/block
// (has_shortcode()/has_block()) — that misses any recipe pulled in via a
// widget, template part, or custom loop outside the_content(). Checking
// render.php's "did a recipe actually render" flag instead of guessing from
// content works no matter how the recipe ended up on the page.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_maybe_print_recipe_styles()
//------------------------------------------------------------------------------
// Printed directly in wp_footer rather than via wp_enqueue_style()/
// wp_enqueue_script() — by the time we know whether a recipe rendered this
// request, wp_head has already fired, so their normal head-output path
// isn't available. Playfair Display + Source Sans 3 (the card's display and
// body fonts) load from Google Fonts as their own <link> here rather than
// an @import inside recipe-card.css, so the font request starts loading in
// parallel instead of only being discovered once the stylesheet downloads.
function brewlab_recipes_maybe_print_recipe_styles() {
	if ( ! brewlab_recipes_recipe_was_rendered() ) {
		return;
	}

	printf(
		'<link rel="stylesheet" id="brewlab-recipes-card-fonts-css" href="%s" media="all" />' . "\n",
		esc_url( brewlab_recipes_fonts_url() )
	);
	printf(
		'<link rel="stylesheet" id="brewlab-recipes-card-css" href="%s" media="all" />' . "\n",
		esc_url( BREWLAB_RECIPES_URL . 'assets/css/recipe-card.css?ver=' . BREWLAB_RECIPES_VERSION )
	);
	printf(
		'<script id="brewlab-recipes-card-js" src="%s"></script>' . "\n",
		esc_url( BREWLAB_RECIPES_URL . 'assets/js/recipe-card.js?ver=' . BREWLAB_RECIPES_VERSION )
	);
}
add_action( 'wp_footer', 'brewlab_recipes_maybe_print_recipe_styles' );
