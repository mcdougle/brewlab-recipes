<?php
//------------------------------------------------------------------------------
//   Repeater Field Renderer
//------------------------------------------------------------------------------
// Renders one repeater section (fermentables, hops, etc.) as an editable
// table of rows, reading field definitions from repeater-schemas.php and
// stored values from repeater-data.php. Row add/remove is handled
// client-side by assets/js/admin-repeater.js; this file only needs to emit
// the existing rows plus a hidden template row for that script to clone.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_field()
//------------------------------------------------------------------------------
// Called from a metabox's render callback with $section matching a top-level
// key in brewlab_recipes_repeater_schemas() (e.g. 'fermentables', 'hops').
function brewlab_recipes_render_repeater_field( $post_id, $section ) {
	$schemas = brewlab_recipes_repeater_schemas();
	if ( ! isset( $schemas[ $section ] ) ) {
		return;
	}

	$fields = $schemas[ $section ]['fields'];
	$rows   = brewlab_recipes_get_repeater_rows( $post_id, $section );

	printf(
		'<div class="brewlab-recipes-repeater" data-section="%s" data-next-index="%d">',
		esc_attr( $section ),
		count( $rows )
	);

	echo '<table class="widefat brewlab-recipes-repeater__table"><thead><tr>';
	foreach ( $fields as $field ) {
		printf( '<th>%s</th>', esc_html( $field['label'] ) );
	}
	echo '<th></th>';
	echo '</tr></thead><tbody class="brewlab-recipes-repeater__rows">';

	foreach ( $rows as $index => $row ) {
		brewlab_recipes_render_repeater_row( $section, $fields, $index, $row );
	}

	echo '</tbody></table>';

	// Hidden template row admin-repeater.js clones for "Add Row" — kept out
	// of the visible tbody so it's never submitted as-is.
	echo '<table style="display:none;"><tbody>';
	brewlab_recipes_render_repeater_row( $section, $fields, '__INDEX__', [], 'brewlab-recipes-repeater__row-template' );
	echo '</tbody></table>';

	printf(
		'<p><button type="button" class="button brewlab-recipes-repeater__add">%s</button></p>',
		esc_html__( 'Add Row', 'brewlab-recipes' )
	);

	echo '</div>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_row()
//------------------------------------------------------------------------------
function brewlab_recipes_render_repeater_row( $section, $fields, $index, $row, $extra_class = '' ) {
	printf(
		'<tr class="brewlab-recipes-repeater__row %s">',
		esc_attr( $extra_class )
	);

	foreach ( $fields as $key => $field ) {
		$name  = sprintf( 'brewlab_recipes_repeater[%s][%s][%s]', $section, $index, $key );
		$value = $row[ $key ] ?? '';
		echo '<td>';
		brewlab_recipes_render_repeater_input( $name, $field, $value );
		echo '</td>';
	}

	printf(
		'<td><button type="button" class="button-link-delete brewlab-recipes-repeater__remove">%s</button></td>',
		esc_html__( 'Remove', 'brewlab-recipes' )
	);
	echo '</tr>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_input()
//------------------------------------------------------------------------------
// Only the field types repeater-schemas.php actually uses — text, number,
// select, url. Not the full switch simple-fields.php has, since no repeater
// row needs a textarea, checkbox, media, or color picker.
function brewlab_recipes_render_repeater_input( $name, $field, $value ) {
	switch ( $field['type'] ) {

		case 'select':
			printf( '<select name="%s">', esc_attr( $name ) );
			echo '<option value=""></option>';
			foreach ( $field['options'] as $option_value => $option_label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $option_value ),
					selected( $value, $option_value, false ),
					esc_html( $option_label )
				);
			}
			echo '</select>';
			break;

		case 'number':
			printf(
				'<input type="number" step="any" name="%s" value="%s" class="small-text" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
			break;

		case 'url':
			printf(
				'<input type="url" name="%s" value="%s" class="regular-text" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
			break;

		default:
			printf(
				'<input type="text" name="%s" value="%s" class="regular-text" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
	}
}
