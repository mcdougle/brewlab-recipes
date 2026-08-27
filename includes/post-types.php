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
		// BrewLab's actual flask logo (recolored grayscale — see
		// brewlab_recipes_menu_icon_svg() below) instead of an arbitrary
		// dashicon (previously dashicons-carrot, which had no connection to
		// the plugin at all). brewlab_recipes_print_menu_icon_hover_css()
		// swaps in a solid-white variant on hover/current, matching how
		// every native dashicon menu item behaves.
		//
		// Must be a data: URI, not a plain file URL — WP only applies its
		// menu-icon size constraint (scaled to 20x20 via CSS background-size)
		// to 'none', a dashicons-* class, or a data:image/svg+xml URI. A
		// regular URL renders the image at its native size instead, which
		// on this 370x425 source blew out over most of the admin sidebar.
		'menu_icon'    => 'data:image/svg+xml;base64,' . base64_encode( brewlab_recipes_menu_icon_svg( '#a7aaad', '#c3c4c7', '#ffffff' ) ),
		'supports'     => [ 'title' ],
	] );
}
add_action( 'init', 'brewlab_recipes_register_post_type' );

//------------------------------------------------------------------------------
//   brewlab_recipes_menu_icon_svg()
//------------------------------------------------------------------------------
// Recolors the BrewLab logo (assets/svg/brewlab-logo.svg, full color: a
// black outline/bubbles, a pale yellow liquid fill, and white interior
// highlights) into a flat two-tone variant for the admin menu — data: URIs
// can't use fill="currentColor" the way an inline <svg> in the DOM can
// (there's no element for it to inherit color FROM), so getting a "grayscale
// at rest, white on hover" effect like a native dashicon means generating
// two static recolored variants and swapping the whole image via CSS
// instead of one icon that recolors itself. Reads the one source SVG file
// rather than keeping separate pre-recolored copies on disk, so the artwork
// only ever needs updating in one place.
function brewlab_recipes_menu_icon_svg( $outline, $liquid, $interior ) {
	$svg = file_get_contents( BREWLAB_RECIPES_PATH . 'assets/svg/brewlab-logo.svg' );

	$svg = str_replace( 'fill="#F6F28E"', 'fill="' . $liquid . '"', $svg );
	$svg = str_replace( 'fill="white"', 'fill="' . $interior . '"', $svg );
	// The outline/bubble paths have no fill attribute at all in the source
	// (SVG defaults to black) — inject one so they can be recolored too.
	// Only matches those: paths that already have a fill are "<path fill=…
	// d=…", not "<path d=…".
	$svg = preg_replace( '/<path d="/', '<path fill="' . $outline . '" d="', $svg );

	return $svg;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_print_menu_icon_hover_css()
//------------------------------------------------------------------------------
// The sidebar renders on every admin screen, not just this plugin's own —
// so unlike the rest of this plugin's admin assets (scoped to the recipe
// edit/list screens in metaboxes.php), this has to load globally. It's one
// small rule, not worth a whole enqueued stylesheet for every admin page
// load, so it's inlined here instead. Targets the menu link by its actual
// href rather than guessing WP's generated `toplevel_page_*` class name,
// which isn't worth depending on for one rule.
function brewlab_recipes_print_menu_icon_hover_css() {
	// Not user input — generated from our own SVG plus hardcoded hex colors
	// — so this is plain output, not an attribute value needing esc_attr().
	$hover_icon = 'data:image/svg+xml;base64,' . base64_encode( brewlab_recipes_menu_icon_svg( '#ffffff', '#ffffff', '#ffffff' ) );
	printf(
		'<style>#adminmenu a[href="edit.php?post_type=brewlab_recipe"]:hover .wp-menu-image,#adminmenu li.current a[href="edit.php?post_type=brewlab_recipe"] .wp-menu-image,#adminmenu li.wp-has-current-submenu a[href="edit.php?post_type=brewlab_recipe"] .wp-menu-image{background-image:url(%s);}</style>' . "\n",
		$hover_icon
	);
}
add_action( 'admin_head', 'brewlab_recipes_print_menu_icon_hover_css' );
