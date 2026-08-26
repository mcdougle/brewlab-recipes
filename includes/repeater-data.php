<?php
//------------------------------------------------------------------------------
//   Repeater Data
//------------------------------------------------------------------------------
// Reads a repeater section's stored meta (JSON-encoded arrays, written by
// includes/admin/save.php) back into a plain PHP array, and the shared
// value-formatting rule (a 'select' field's raw stored value becomes its
// option label) any repeater row display needs. Lives outside includes/admin/
// because neither is admin-only — the front-end render path
// (includes/render.php) and the admin repeater UI both need the same reads
// and the same formatting rule, so both go through here instead of each
// keeping their own copy.

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

//------------------------------------------------------------------------------
//   brewlab_recipes_repeater_cell_value()
//------------------------------------------------------------------------------
// 'select' fields display their option label, everything else (text/number/
// url) displays as stored. A field's short_labels (e.g. temp_unit's "°F"
// instead of "Fahrenheit (°F)") takes precedence over options where both
// exist — the compact form reads better anywhere this value gets displayed,
// not just in the toggle widget it was added for.
function brewlab_recipes_repeater_cell_value( $section_key, $field_key, $value ) {
	$field = brewlab_recipes_repeater_schemas()[ $section_key ]['fields'][ $field_key ] ?? [];
	if ( 'select' === ( $field['type'] ?? '' ) ) {
		$labels = $field['short_labels'] ?? $field['options'] ?? [];
		return $labels[ $value ] ?? $value;
	}
	return $value;
}
