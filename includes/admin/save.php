<?php
//------------------------------------------------------------------------------
//   Save Handler
//------------------------------------------------------------------------------
// Saves every simple (non-repeater) field from simple-fields.php's schema on
// save_post_brewlab_recipe. Repeater fields (fermentables, hops, etc.) get
// their own save/sanitize path wired in here in Phase 3, once
// repeater-field.php exists — this file only handles the plain fields for
// now, matching where the build order currently stands.

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

	brewlab_recipes_sync_excerpt( $post_id );
}
add_action( 'save_post_brewlab_recipe', 'brewlab_recipes_save_meta' );

//------------------------------------------------------------------------------
//   brewlab_recipes_save_simple_field()
//------------------------------------------------------------------------------
function brewlab_recipes_save_simple_field( $post_id, $key, $field ) {
	$name     = 'brewlab_recipes_' . $key;
	$meta_key = '_brewlab_recipes_' . $key;

	// Unchecked checkboxes never appear in $_POST at all, so this has to be
	// handled before the generic isset() guard below.
	if ( 'checkbox' === $field['type'] ) {
		update_post_meta( $post_id, $meta_key, isset( $_POST[ $name ] ) ? '1' : '0' );
		return;
	}

	if ( ! isset( $_POST[ $name ] ) ) {
		return;
	}

	$raw = wp_unslash( $_POST[ $name ] );

	switch ( $field['type'] ) {

		case 'textarea':
			// sanitize_textarea_field(), not sanitize_text_field() — the
			// latter strips line breaks, which would silently break the
			// notes field the way it did in the old plugin (it was never
			// actually wired up to a form field, so the bug never fired).
			$value = sanitize_textarea_field( $raw );
			break;

		case 'number':
			// floatval(), not sanitize_text_field() — the old plugin ran
			// every field through sanitize_text_field() including numeric
			// ones, which stores whatever text was typed rather than a
			// validated number.
			$value = '' === $raw ? '' : (string) floatval( $raw );
			break;

		case 'select':
			$value = array_key_exists( $raw, $field['options'] ?? [] ) ? $raw : '';
			break;

		case 'media':
			$attachment_id = intval( $raw );
			if ( $attachment_id > 0 ) {
				update_post_meta( $post_id, $meta_key, $attachment_id );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
			return;

		case 'color':
			$color = sanitize_hex_color( $raw );
			if ( $color ) {
				update_post_meta( $post_id, $meta_key, $color );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
			return;

		default:
			$value = sanitize_text_field( $raw );
	}

	update_post_meta( $post_id, $meta_key, $value );
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
