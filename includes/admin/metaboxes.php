<?php
//------------------------------------------------------------------------------
//   Metaboxes
//------------------------------------------------------------------------------
// Registers the brewlab_recipe edit-screen metaboxes from one config array
// (brewlab_recipes_metabox_config()) instead of a separate add_meta_box()
// call per box, and enforces box order every load — WordPress persists a
// user's drag-and-drop order to user_meta, which drifts from the intended
// order over time; the old plugin hit this directly.
//
// Only the three simple-field boxes (media, recipe details, batch details)
// are registered for now. The six repeater boxes (fermentables, additions,
// hops, yeast, mash profile, fermentation profile) get appended to the
// config in Phase 3, once repeater-field.php's renderer exists to back them.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_metabox_config()
//------------------------------------------------------------------------------
function brewlab_recipes_metabox_config() {
	return [
		[
			'id'       => 'brewlab_recipes_media',
			'title'    => __( 'Media', 'brewlab-recipes' ),
			'section'  => 'media',
			'context'  => 'normal',
			'priority' => 'high',
		],
		[
			'id'       => 'brewlab_recipes_recipe_details',
			'title'    => __( 'Recipe Details', 'brewlab-recipes' ),
			'section'  => 'recipe_details',
			'context'  => 'normal',
			'priority' => 'high',
		],
		[
			'id'       => 'brewlab_recipes_batch_details',
			'title'    => __( 'Batch Details', 'brewlab-recipes' ),
			'section'  => 'batch_details',
			'context'  => 'normal',
			'priority' => 'high',
		],
	];
}

//------------------------------------------------------------------------------
//   brewlab_recipes_register_metaboxes()
//------------------------------------------------------------------------------
function brewlab_recipes_register_metaboxes() {
	foreach ( brewlab_recipes_metabox_config() as $box ) {
		add_meta_box(
			$box['id'],
			$box['title'],
			'brewlab_recipes_render_metabox',
			'brewlab_recipe',
			$box['context'],
			$box['priority'],
			[ 'section' => $box['section'] ]
		);
	}
}
add_action( 'add_meta_boxes_brewlab_recipe', 'brewlab_recipes_register_metaboxes' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_metabox()
//------------------------------------------------------------------------------
// Single dispatcher for every box registered above — reads which schema
// section to render from the $args passed to add_meta_box().
function brewlab_recipes_render_metabox( $post, $metabox ) {
	$section = $metabox['args']['section'] ?? '';
	brewlab_recipes_render_simple_fields( $post->ID, $section );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_enforce_metabox_order()
//------------------------------------------------------------------------------
// Rebuilds the order from the config array on every load instead of trusting
// WordPress's persisted per-user order, grouped by context so a future
// 'side' box (Phase 3's Options box) is handled without changes here.
function brewlab_recipes_enforce_metabox_order() {
	$by_context = [];
	foreach ( brewlab_recipes_metabox_config() as $box ) {
		$by_context[ $box['context'] ][] = $box['id'];
	}

	$order = [];
	foreach ( $by_context as $context => $ids ) {
		$order[ $context ] = implode( ',', $ids );
	}

	return $order;
}
add_filter( 'get_user_option_meta-box-order_brewlab_recipe', 'brewlab_recipes_enforce_metabox_order' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_nonce_field()
//------------------------------------------------------------------------------
// Emitted once, independent of which/how many metaboxes exist — the save
// handler (includes/admin/save.php) checks action 'brewlab_recipes_save_meta'
// against field name 'brewlab_recipes_nonce'.
function brewlab_recipes_render_nonce_field( $post ) {
	if ( 'brewlab_recipe' !== get_post_type( $post ) ) {
		return;
	}
	wp_nonce_field( 'brewlab_recipes_save_meta', 'brewlab_recipes_nonce' );
}
add_action( 'edit_form_after_title', 'brewlab_recipes_render_nonce_field' );
