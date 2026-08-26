<?php
//------------------------------------------------------------------------------
//   Repeater Data
//------------------------------------------------------------------------------
// Reads a repeater section's stored meta (JSON-encoded arrays, written by
// includes/admin/save.php) back into a plain PHP array. Lives outside
// includes/admin/ because it's not an admin-only concern — the front-end
// render path (includes/render.php, Phase 4) decodes the same six meta keys
// to build a recipe card, so both admin and front-end read through here
// instead of each re-implementing the same json_decode() call.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_get_repeater_rows()
//------------------------------------------------------------------------------
function brewlab_recipes_get_repeater_rows( $post_id, $section ) {
	$raw = get_post_meta( $post_id, '_brewlab_recipes_' . $section, true );
	if ( empty( $raw ) ) {
		return [];
	}

	$rows = json_decode( $raw, true );
	return is_array( $rows ) ? $rows : [];
}
