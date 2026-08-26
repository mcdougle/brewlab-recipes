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
// Config entries carry a 'type' of 'simple' or 'repeater', which the render
// dispatcher below uses to pick simple-fields.php or repeater-field.php.
// The six repeater boxes are built straight from repeater-schemas.php so
// their titles/order can't drift out of sync with the schema itself.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_metabox_config()
//------------------------------------------------------------------------------
function brewlab_recipes_metabox_config() {
	$boxes = [
		[
			'id'       => 'brewlab_recipes_media',
			'title'    => __( 'Media', 'brewlab-recipes' ),
			'section'  => 'media',
			'context'  => 'normal',
			'priority' => 'high',
			'type'     => 'simple',
		],
		[
			'id'       => 'brewlab_recipes_recipe_details',
			'title'    => __( 'Recipe Details', 'brewlab-recipes' ),
			'section'  => 'recipe_details',
			'context'  => 'normal',
			'priority' => 'high',
			'type'     => 'simple',
		],
		[
			'id'       => 'brewlab_recipes_batch_details',
			'title'    => __( 'Batch Details', 'brewlab-recipes' ),
			'section'  => 'batch_details',
			'context'  => 'normal',
			'priority' => 'high',
			'type'     => 'simple',
		],
	];

	foreach ( brewlab_recipes_repeater_schemas() as $key => $schema ) {
		$boxes[] = [
			'id'       => 'brewlab_recipes_' . $key,
			'title'    => $schema['label'],
			'section'  => $key,
			'context'  => 'normal',
			'priority' => 'default',
			'type'     => 'repeater',
		];
	}

	return $boxes;
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
			[
				'section' => $box['section'],
				'type'    => $box['type'],
			]
		);
	}
}
add_action( 'add_meta_boxes_brewlab_recipe', 'brewlab_recipes_register_metaboxes' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_metabox()
//------------------------------------------------------------------------------
// Single dispatcher for every box registered above — reads which schema
// section and renderer to use from the $args passed to add_meta_box().
function brewlab_recipes_render_metabox( $post, $metabox ) {
	$section = $metabox['args']['section'] ?? '';
	$type    = $metabox['args']['type'] ?? 'simple';

	if ( 'repeater' === $type ) {
		brewlab_recipes_render_repeater_field( $post->ID, $section );
	} else {
		brewlab_recipes_render_simple_fields( $post->ID, $section );
	}
}

//------------------------------------------------------------------------------
//   brewlab_recipes_enqueue_admin_assets()
//------------------------------------------------------------------------------
// Scoped to the recipe screens so none of this loads on other post types'
// add/edit/list screens. admin.css is the one stylesheet the list table
// (edit.php) also needs, for its two custom columns — everything else here
// (repeater UI, media/color pickers) is single-edit-screen only.
function brewlab_recipes_enqueue_admin_assets( $hook ) {
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ) {
		return;
	}
	if ( 'brewlab_recipe' !== get_current_screen()->post_type ) {
		return;
	}

	$admin_css_deps = [];

	if ( 'edit.php' !== $hook ) {
		wp_enqueue_style(
			'brewlab-recipes-admin-repeater',
			BREWLAB_RECIPES_URL . 'assets/css/admin-repeater.css',
			[],
			BREWLAB_RECIPES_VERSION
		);
		wp_enqueue_script(
			'brewlab-recipes-admin-repeater',
			BREWLAB_RECIPES_URL . 'assets/js/admin-repeater.js',
			[],
			BREWLAB_RECIPES_VERSION,
			true
		);

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'brewlab-recipes-admin-media',
			BREWLAB_RECIPES_URL . 'assets/css/admin-media.css',
			[],
			BREWLAB_RECIPES_VERSION
		);
		wp_enqueue_script(
			'brewlab-recipes-admin-media-color',
			BREWLAB_RECIPES_URL . 'assets/js/admin-media-color.js',
			[ 'jquery', 'wp-color-picker', 'media-editor' ],
			BREWLAB_RECIPES_VERSION,
			true
		);
		wp_localize_script( 'brewlab-recipes-admin-media-color', 'brewlabRecipesMedia', [
			'selectTitle'  => __( 'Select Recipe Image', 'brewlab-recipes' ),
			'selectButton' => __( 'Use This Image', 'brewlab-recipes' ),
		] );

		$admin_css_deps = [ 'brewlab-recipes-admin-repeater', 'brewlab-recipes-admin-media' ];
	}

	// Declared as depending on the two feature stylesheets above (when
	// they're enqueued) so it reliably loads — and therefore wins on any
	// equal-specificity selector both define — after them, rather than
	// trusting enqueue call order alone.
	wp_enqueue_style(
		'brewlab-recipes-admin',
		BREWLAB_RECIPES_URL . 'assets/css/admin.css',
		$admin_css_deps,
		BREWLAB_RECIPES_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'brewlab_recipes_enqueue_admin_assets' );

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
