<?php
//------------------------------------------------------------------------------
//   Recipe Render
//------------------------------------------------------------------------------
// Turns a recipe post ID into markup. brewlab_recipes_render_recipe() is the
// only entry point anything outside this file should call — the shortcode
// and block render callbacks (Phase 5) both go through it instead of talking
// to templates/recipe-card.php directly. The helper functions below exist
// because templates/recipe-card.php is include()'d fresh on every call (a
// post can embed more than one recipe), so any function it needed would be
// redeclared on the second include and fatal — they have to live here,
// loaded once at bootstrap, instead.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_get_recipe_data()
//------------------------------------------------------------------------------
// Decodes every simple field and repeater section for one recipe into a flat
// array keyed by field/section name — the shape templates/recipe-card.php
// expects in $recipe. Returns null for anything that isn't a brewlab_recipe.
function brewlab_recipes_get_recipe_data( $recipe_id ) {
	$recipe_id = absint( $recipe_id );

	if ( 'brewlab_recipe' !== get_post_type( $recipe_id ) ) {
		return null;
	}

	$data = [
		'id'    => $recipe_id,
		'title' => get_the_title( $recipe_id ),
	];

	foreach ( brewlab_recipes_simple_fields() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$data[ $key ] = get_post_meta( $recipe_id, '_brewlab_recipes_' . $key, true );
		}
	}

	foreach ( brewlab_recipes_repeater_schemas() as $section_key => $schema ) {
		$data[ $section_key ] = brewlab_recipes_get_repeater_rows( $recipe_id, $section_key );

		if ( isset( $schema['profile_label'] ) ) {
			$data[ $section_key . '_profile_name' ] = get_post_meta( $recipe_id, '_brewlab_recipes_' . $section_key . '_profile_name', true );
		}
	}

	return $data;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_build_preview_data()
//------------------------------------------------------------------------------
// Same shape as brewlab_recipes_get_recipe_data(), but built from the
// current $_POST (the edit screen's live, possibly-unsaved field values)
// instead of the database — powers the admin "Preview Recipe Card" button
// (includes/admin/preview.php) so a change can be checked without saving
// first and then hunting down wherever the recipe happens to be embedded.
// Falls back to the saved value for anything absent from $_POST (e.g. a
// field POSTed unmodified isn't always present, and this only ever runs
// against a real, previously-saved recipe post). Reuses save.php's own
// field-level sanitizers so a previewed value is sanitized exactly the way
// a saved one would be, not a second, possibly-looser copy of the same
// rules.
function brewlab_recipes_build_preview_data( $post_id ) {
	$post_id = absint( $post_id );

	$data = [
		'id'    => $post_id,
		'title' => isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : get_the_title( $post_id ),
	];

	foreach ( brewlab_recipes_simple_fields() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$value          = brewlab_recipes_sanitize_simple_field_from_post( $key, $field );
			$data[ $key ]   = null !== $value ? $value : get_post_meta( $post_id, '_brewlab_recipes_' . $key, true );
		}
	}

	foreach ( brewlab_recipes_repeater_schemas() as $section_key => $schema ) {
		$posted = $_POST['brewlab_recipes_repeater'][ $section_key ] ?? null;

		if ( is_array( $posted ) ) {
			$rows = [];
			foreach ( $posted as $raw_row ) {
				if ( ! is_array( $raw_row ) ) {
					continue;
				}
				$row = brewlab_recipes_sanitize_repeater_row( wp_unslash( $raw_row ), $schema['fields'] );
				if ( ! brewlab_recipes_repeater_row_is_empty( $row ) ) {
					$rows[] = $row;
				}
			}
			$data[ $section_key ] = $rows;
		} else {
			$data[ $section_key ] = brewlab_recipes_get_repeater_rows( $post_id, $section_key );
		}

		if ( isset( $schema['profile_label'] ) ) {
			$profile_name = 'brewlab_recipes_' . $section_key . '_profile_name';
			$data[ $section_key . '_profile_name' ] = isset( $_POST[ $profile_name ] )
				? sanitize_text_field( wp_unslash( $_POST[ $profile_name ] ) )
				: get_post_meta( $post_id, '_' . $profile_name, true );
		}
	}

	return $data;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_recipe()
//------------------------------------------------------------------------------
function brewlab_recipes_render_recipe( $recipe_id ) {
	$recipe = brewlab_recipes_get_recipe_data( $recipe_id );
	if ( null === $recipe ) {
		return '';
	}

	brewlab_recipes_recipe_was_rendered( true );

	ob_start();
	include BREWLAB_RECIPES_PATH . 'templates/recipe-card.php';
	return ob_get_clean();
}

//------------------------------------------------------------------------------
//   brewlab_recipes_recipe_was_rendered()
//------------------------------------------------------------------------------
// Tracks whether render_recipe() actually produced a card this request.
// includes/enqueue.php checks this instead of pre-scanning post_content for
// the shortcode/block — that approach misses any recipe pulled in via a
// widget, template part, or custom loop outside the_content(). Call with no
// args to read the flag, call with true to set it.
function brewlab_recipes_recipe_was_rendered( $set = null ) {
	static $rendered = false;
	if ( null !== $set ) {
		$rendered = $rendered || $set;
	}
	return $rendered;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_field_option_label()
//------------------------------------------------------------------------------
// Reads a select field's option label straight from simple-fields.php's
// schema instead of keeping a second copy of the option list here.
function brewlab_recipes_field_option_label( $section_key, $field_key, $value ) {
	$fields  = brewlab_recipes_simple_fields()[ $section_key ]['fields'] ?? [];
	$options = $fields[ $field_key ]['options'] ?? [];
	return $options[ $value ] ?? '';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_srm_color()
//------------------------------------------------------------------------------
// Standard SRM-to-hex approximation (the same beer-color scale used across
// most homebrewing software) for the stat strip's color swatch. Values are
// old-plugin-exact (wpbtr_srm_to_hex()) rather than a fresh approximation,
// so a recipe's swatch doesn't shift color on migration. Returns '' for
// anything blank/non-numeric rather than guessing at a color.
function brewlab_recipes_srm_color( $srm ) {
	static $scale = [
		1  => '#FFE699', 2  => '#FFD878', 3  => '#FFCA5A', 4  => '#FFBF42',
		5  => '#FBB123', 6  => '#F8A600', 7  => '#F39C00', 8  => '#EA8F00',
		9  => '#E58500', 10 => '#DE7C00', 11 => '#D77200', 12 => '#CF6900',
		13 => '#CB6100', 14 => '#C35900', 15 => '#BB5100', 16 => '#B54C00',
		17 => '#B04500', 18 => '#A63E00', 19 => '#A13700', 20 => '#9B3200',
		21 => '#952D00', 22 => '#8E2900', 23 => '#882300', 24 => '#821E00',
		25 => '#7B1A00', 26 => '#771900', 27 => '#701400', 28 => '#6A0F00',
		29 => '#660D00', 30 => '#600900', 31 => '#5B0000', 32 => '#560000',
		33 => '#520000', 34 => '#4D0000', 35 => '#470000', 36 => '#440000',
		37 => '#3F0000', 38 => '#3B0000', 39 => '#380000', 40 => '#350000',
	];

	if ( ! is_numeric( $srm ) ) {
		return '';
	}

	$step = (int) round( (float) $srm );
	if ( $step < 1 ) {
		return '';
	}

	return $scale[ min( $step, 40 ) ];
}
