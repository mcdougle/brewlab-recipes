<?php
//------------------------------------------------------------------------------
//   Shortcode
//------------------------------------------------------------------------------
// Registers [brewlab_recipe id="123"], one of two ways a recipe gets
// embedded in a post (the Gutenberg block in block.php is the other).
// Both funnel through render_recipe() — this file's callback is a thin
// wrapper around it, not a second render path.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_shortcode()
//------------------------------------------------------------------------------
function brewlab_recipes_shortcode( $atts ) {
	$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'brewlab_recipe' );
	return brewlab_recipes_render_recipe( (int) $atts['id'] );
}
add_shortcode( 'brewlab_recipe', 'brewlab_recipes_shortcode' );
