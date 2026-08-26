<?php
//------------------------------------------------------------------------------
//   Admin Columns
//------------------------------------------------------------------------------
// "Used In" and an inline stats summary on the Recipes list table. Neither
// column reads anything that gets set elsewhere — this file also owns
// writing _brewlab_recipes_parent_post_id, the meta "Used In" displays,
// since nothing else in the plugin tracks which post first embedded a
// recipe. Runs on every save_post, not just posts using the shortcode/block,
// since a recipe can be embedded from any post type.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_track_recipe_usage()
//------------------------------------------------------------------------------
// First post to embed a recipe wins — deliberately never overwritten once
// set, matching the old plugin's semantics (a recipe usually has one "home"
// post even if reused elsewhere later).
function brewlab_recipes_track_recipe_usage( $post_id, $post ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( 'brewlab_recipe' === $post->post_type ) {
		return;
	}

	foreach ( brewlab_recipes_find_embedded_recipe_ids( $post->post_content ) as $recipe_id ) {
		if ( ! get_post_meta( $recipe_id, '_brewlab_recipes_parent_post_id', true ) ) {
			update_post_meta( $recipe_id, '_brewlab_recipes_parent_post_id', $post_id );
		}
	}
}
add_action( 'save_post', 'brewlab_recipes_track_recipe_usage', 10, 2 );

//------------------------------------------------------------------------------
//   brewlab_recipes_find_embedded_recipe_ids()
//------------------------------------------------------------------------------
// Scans post content for both embed methods — the [brewlab_recipe id="x"]
// shortcode and the brewlab/recipe block (recursing into innerBlocks, since
// the block could be nested inside a Columns/Group block).
function brewlab_recipes_find_embedded_recipe_ids( $content ) {
	$ids = [];

	if ( has_shortcode( $content, 'brewlab_recipe' ) &&
		preg_match_all( '/\[brewlab_recipe\b[^\]]*\bid=["\']?(\d+)["\']?[^\]]*\]/', $content, $matches ) ) {
		foreach ( $matches[1] as $id ) {
			$ids[] = (int) $id;
		}
	}

	brewlab_recipes_collect_block_recipe_ids( parse_blocks( $content ), $ids );

	return array_values( array_unique( array_filter( $ids ) ) );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_collect_block_recipe_ids()
//------------------------------------------------------------------------------
function brewlab_recipes_collect_block_recipe_ids( array $blocks, array &$ids ) {
	foreach ( $blocks as $block ) {
		if ( 'brewlab/recipe' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['recipeId'] ) ) {
			$ids[] = (int) $block['attrs']['recipeId'];
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			brewlab_recipes_collect_block_recipe_ids( $block['innerBlocks'], $ids );
		}
	}
}

//------------------------------------------------------------------------------
//   brewlab_recipes_add_admin_columns()
//------------------------------------------------------------------------------
function brewlab_recipes_add_admin_columns( $columns ) {
	$columns['used_in'] = __( 'Used In', 'brewlab-recipes' );
	$columns['stats']   = __( 'Stats', 'brewlab-recipes' );
	return $columns;
}
add_filter( 'manage_brewlab_recipe_posts_columns', 'brewlab_recipes_add_admin_columns' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_admin_column()
//------------------------------------------------------------------------------
function brewlab_recipes_render_admin_column( $column, $post_id ) {
	switch ( $column ) {

		case 'used_in':
			$parent_id = get_post_meta( $post_id, '_brewlab_recipes_parent_post_id', true );
			if ( $parent_id && get_post( $parent_id ) ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url( get_edit_post_link( $parent_id ) ),
					esc_html( get_the_title( $parent_id ) )
				);
			} else {
				echo '&#8212;';
			}
			break;

		case 'stats':
			$abv = get_post_meta( $post_id, '_brewlab_recipes_abv', true );
			$og  = get_post_meta( $post_id, '_brewlab_recipes_original_gravity', true );
			$ibu = get_post_meta( $post_id, '_brewlab_recipes_ibu', true );

			$parts = [];
			if ( '' !== $abv ) {
				$parts[] = $abv . '% ABV';
			}
			if ( '' !== $og ) {
				$parts[] = 'OG ' . $og;
			}
			if ( '' !== $ibu ) {
				$parts[] = $ibu . ' IBU';
			}

			echo $parts ? esc_html( implode( ' · ', $parts ) ) : '&#8212;';
			break;
	}
}
add_action( 'manage_brewlab_recipe_posts_custom_column', 'brewlab_recipes_render_admin_column', 10, 2 );
