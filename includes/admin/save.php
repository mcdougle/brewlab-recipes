<?php
//------------------------------------------------------------------------------
//   Save Handler
//------------------------------------------------------------------------------
// Saves every field from simple-fields.php's schema, plus the six repeater
// sections from repeater-schemas.php (fermentables, hops, etc.), on
// save_post_brewlab_recipe. Repeater rows are sanitized per-field the same
// way the simple fields are, then stored as one JSON-encoded array per
// section — see brewlab_recipes_save_repeater_field() below.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_save_meta()
//------------------------------------------------------------------------------
function brewlab_recipes_save_meta( $post_id ) {
	if ( ! isset( $_POST['brewlab_recipes_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['brewlab_recipes_nonce'] ) ), 'brewlab_recipes_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( brewlab_recipes_simple_fields() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			brewlab_recipes_save_simple_field( $post_id, $key, $field );
		}
	}

	foreach ( brewlab_recipes_repeater_schemas() as $section_key => $section ) {
		brewlab_recipes_save_repeater_field( $post_id, $section_key, $section['fields'] );

		if ( isset( $section['profile_label'] ) ) {
			brewlab_recipes_save_repeater_profile_name( $post_id, $section_key );
		}
	}

	brewlab_recipes_sync_excerpt( $post_id );
}
add_action( 'save_post_brewlab_recipe', 'brewlab_recipes_save_meta' );

//------------------------------------------------------------------------------
//   brewlab_recipes_sanitize_simple_field_from_post()
//------------------------------------------------------------------------------
// Pulls one simple field's value out of $_POST and sanitizes it per its
// schema type — the read half of brewlab_recipes_save_simple_field(), split
// out so the live preview builder (brewlab_recipes_build_preview_data() in
// render.php) reads $_POST the exact same way a real save does, instead of a
// second copy of this switch that could quietly drift from it. Returns null
// when the field is absent from $_POST entirely — a checkbox never returns
// null, since an absent checkbox is meaningfully "0", not "leave it alone".
function brewlab_recipes_sanitize_simple_field_from_post( $key, $field ) {
	$name = 'brewlab_recipes_' . $key;

	if ( 'checkbox' === $field['type'] ) {
		return isset( $_POST[ $name ] ) ? '1' : '0';
	}

	if ( ! isset( $_POST[ $name ] ) ) {
		return null;
	}

	$raw = wp_unslash( $_POST[ $name ] );

	switch ( $field['type'] ) {

		case 'textarea':
			// sanitize_textarea_field(), not sanitize_text_field() — the
			// latter strips line breaks, which would silently break the
			// notes field the way it did in the old plugin (it was never
			// actually wired up to a form field, so the bug never fired).
			return sanitize_textarea_field( $raw );

		case 'number':
			// floatval(), not sanitize_text_field() — the old plugin ran
			// every field through sanitize_text_field() including numeric
			// ones, which stores whatever text was typed rather than a
			// validated number.
			return '' === $raw ? '' : (string) floatval( $raw );

		case 'select':
			return array_key_exists( $raw, $field['options'] ?? [] ) ? $raw : '';

		case 'media':
			$attachment_id = intval( $raw );
			return $attachment_id > 0 ? (string) $attachment_id : '';

		case 'color':
			return sanitize_hex_color( $raw ) ?: '';

		default:
			return sanitize_text_field( $raw );
	}
}

//------------------------------------------------------------------------------
//   brewlab_recipes_save_simple_field()
//------------------------------------------------------------------------------
function brewlab_recipes_save_simple_field( $post_id, $key, $field ) {
	$meta_key = '_brewlab_recipes_' . $key;
	$value    = brewlab_recipes_sanitize_simple_field_from_post( $key, $field );

	if ( null === $value ) {
		return;
	}

	// Media/color are the two types stored as "absent" rather than "empty
	// string" when cleared — everything else (including an emptied text
	// field) is fine stored as ''.
	if ( '' === $value && in_array( $field['type'], [ 'media', 'color' ], true ) ) {
		delete_post_meta( $post_id, $meta_key );
		return;
	}

	update_post_meta( $post_id, $meta_key, $value );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_save_repeater_field()
//------------------------------------------------------------------------------
// $_POST['brewlab_recipes_repeater'][$section] is keyed by the row indexes
// admin-repeater.js assigned client-side (see that file for why they're not
// necessarily contiguous). Rows left completely blank — added via "Add Row"
// and never filled in, or never removed — are dropped rather than stored.
function brewlab_recipes_save_repeater_field( $post_id, $section, array $fields ) {
	$meta_key = '_brewlab_recipes_' . $section;
	$posted   = $_POST['brewlab_recipes_repeater'][ $section ] ?? null;

	if ( ! is_array( $posted ) ) {
		delete_post_meta( $post_id, $meta_key );
		return;
	}

	$rows = [];
	foreach ( $posted as $raw_row ) {
		if ( ! is_array( $raw_row ) ) {
			continue;
		}

		$row = brewlab_recipes_sanitize_repeater_row( wp_unslash( $raw_row ), $fields );
		if ( brewlab_recipes_repeater_row_is_empty( $row ) ) {
			continue;
		}

		$rows[] = $row;
	}

	if ( empty( $rows ) ) {
		delete_post_meta( $post_id, $meta_key );
		return;
	}

	update_post_meta( $post_id, $meta_key, wp_json_encode( $rows ) );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_save_repeater_profile_name()
//------------------------------------------------------------------------------
// Only mash_steps/fermentation_steps have a profile_label in their schema —
// this saves that single companion field (e.g. "Hochkurz Step Mash") as a
// plain sibling postmeta key, not part of the section's JSON rows array.
function brewlab_recipes_save_repeater_profile_name( $post_id, $section ) {
	$name     = 'brewlab_recipes_' . $section . '_profile_name';
	$meta_key = '_' . $name;

	if ( ! isset( $_POST[ $name ] ) ) {
		return;
	}

	update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_sanitize_repeater_row()
//------------------------------------------------------------------------------
// Same per-type sanitizing rules as brewlab_recipes_save_simple_field(), cut
// down to the field types repeater-schemas.php actually uses.
function brewlab_recipes_sanitize_repeater_row( array $raw_row, array $fields ) {
	$row = [];

	foreach ( $fields as $key => $field ) {
		$raw = $raw_row[ $key ] ?? '';

		switch ( $field['type'] ) {

			case 'number':
				$row[ $key ] = '' === $raw ? '' : (string) floatval( $raw );
				break;

			case 'select':
				$row[ $key ] = array_key_exists( $raw, $field['options'] ?? [] ) ? $raw : '';
				break;

			case 'url':
				$row[ $key ] = esc_url_raw( $raw );
				break;

			default:
				$row[ $key ] = sanitize_text_field( $raw );
		}
	}

	return $row;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_repeater_row_is_empty()
//------------------------------------------------------------------------------
function brewlab_recipes_repeater_row_is_empty( array $row ) {
	foreach ( $row as $value ) {
		if ( '' !== $value ) {
			return false;
		}
	}
	return true;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_sync_excerpt()
//------------------------------------------------------------------------------
// Mirrors the summary field into post_excerpt. Unhooks itself around the
// wp_update_post() call, since that call re-triggers save_post_brewlab_recipe
// and would otherwise recurse.
function brewlab_recipes_sync_excerpt( $post_id ) {
	$summary = get_post_meta( $post_id, '_brewlab_recipes_summary', true );

	remove_action( 'save_post_brewlab_recipe', 'brewlab_recipes_save_meta' );
	wp_update_post( [
		'ID'           => $post_id,
		'post_excerpt' => $summary,
	] );
	add_action( 'save_post_brewlab_recipe', 'brewlab_recipes_save_meta' );
}
