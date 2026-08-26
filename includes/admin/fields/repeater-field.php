<?php
//------------------------------------------------------------------------------
//   Repeater Field Renderer
//------------------------------------------------------------------------------
// Renders one repeater section (fermentables, hops, etc.) as a summary list
// of already-added rows, each editable through one shared modal (rendered
// once per page by brewlab_recipes_render_repeater_modal(), not once per
// section) instead of live inline table inputs. Reads field definitions from
// repeater-schemas.php and stored values from repeater-data.php.
//
// Every row's real submittable fields are hidden <input>s inside its <li> —
// admin-repeater.js only ever edits those directly; the modal's visible
// fields are a template it clones into itself and copies values to/from.
// That keeps the $_POST shape (brewlab_recipes_repeater[section][index][field])
// identical to before this rewrite, so save.php didn't need any changes.

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
		'<div class="brewlab-recipes-repeater" data-section="%s" data-label="%s" data-next-index="%d">',
		esc_attr( $section ),
		esc_attr( $schemas[ $section ]['label'] ),
		count( $rows )
	);

	echo '<ul class="brewlab-recipes-repeater__list">';
	foreach ( $rows as $index => $row ) {
		brewlab_recipes_render_repeater_item( $section, $fields, $index, $row );
	}
	echo '</ul>';

	// Hidden template for a brand-new row's hidden inputs — cloned on Add,
	// same role admin-repeater.js's row-template played before this rewrite.
	echo '<template class="brewlab-recipes-repeater__item-template">';
	brewlab_recipes_render_repeater_item( $section, $fields, '__INDEX__', [] );
	echo '</template>';

	// This section's modal form fields — the shared modal (rendered once,
	// see below) swaps its body to a clone of whichever section's template
	// triggered it.
	echo '<template class="brewlab-recipes-repeater__fields-template">';
	brewlab_recipes_render_repeater_modal_fields( $fields );
	echo '</template>';

	printf(
		'<p><button type="button" class="button brewlab-recipes-repeater__add">%s</button></p>',
		esc_html__( 'Add Row', 'brewlab-recipes' )
	);

	echo '</div>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_item()
//------------------------------------------------------------------------------
function brewlab_recipes_render_repeater_item( $section, $fields, $index, $row ) {
	echo '<li class="brewlab-recipes-repeater__item">';
	printf(
		'<span class="brewlab-recipes-repeater__item-summary">%s</span>',
		esc_html( brewlab_recipes_repeater_item_summary( $section, $fields, $row ) )
	);

	foreach ( $fields as $key => $field ) {
		$name  = sprintf( 'brewlab_recipes_repeater[%s][%s][%s]', $section, $index, $key );
		$value = $row[ $key ] ?? '';
		printf(
			'<input type="hidden" class="brewlab-recipes-repeater__item-field" data-field="%s" name="%s" value="%s" />',
			esc_attr( $key ),
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	printf(
		' <button type="button" class="button-link brewlab-recipes-repeater__item-edit">%s</button>' .
		' <button type="button" class="button-link-delete brewlab-recipes-repeater__item-remove">%s</button>',
		esc_html__( 'Edit', 'brewlab-recipes' ),
		esc_html__( 'Remove', 'brewlab-recipes' )
	);
	echo '</li>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_repeater_item_summary()
//------------------------------------------------------------------------------
// Up to the first three non-link fields (schema declaration order), skipping
// anything blank — 'select' values already come back as their option label
// via repeater-data.php's brewlab_recipes_repeater_cell_value(), matching
// what admin-repeater.js does when it rebuilds this same summary after a
// modal save (it reads the modal <select>'s chosen option text directly,
// same end result, no schema duplicated into JS to get there).
function brewlab_recipes_repeater_item_summary( $section, $fields, $row ) {
	$parts = [];
	foreach ( $fields as $key => $field ) {
		if ( 'link' === $key || count( $parts ) >= 3 ) {
			continue;
		}
		$value = brewlab_recipes_repeater_cell_value( $section, $key, $row[ $key ] ?? '' );
		if ( '' !== $value ) {
			$parts[] = $value;
		}
	}
	return $parts ? implode( ' ', $parts ) : __( '(empty)', 'brewlab-recipes' );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_modal_fields()
//------------------------------------------------------------------------------
// Unlike brewlab_recipes_render_repeater_item()'s hidden inputs, these carry
// no name/value — they're a template the modal clones and populates
// per-open, never submitted directly.
function brewlab_recipes_render_repeater_modal_fields( $fields ) {
	echo '<table class="form-table"><tbody>';
	foreach ( $fields as $key => $field ) {
		printf( '<tr><th scope="row"><label>%s</label></th><td>', esc_html( $field['label'] ) );
		brewlab_recipes_render_repeater_modal_input( $key, $field );
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_modal_input()
//------------------------------------------------------------------------------
function brewlab_recipes_render_repeater_modal_input( $key, $field ) {
	switch ( $field['type'] ) {

		case 'select':
			printf( '<select data-field="%s">', esc_attr( $key ) );
			echo '<option value=""></option>';
			foreach ( $field['options'] as $option_value => $option_label ) {
				printf(
					'<option value="%s">%s</option>',
					esc_attr( $option_value ),
					esc_html( $option_label )
				);
			}
			echo '</select>';
			break;

		case 'number':
			printf(
				'<input type="number" step="any" data-field="%s" class="small-text" />',
				esc_attr( $key )
			);
			break;

		case 'url':
			printf(
				'<input type="url" data-field="%s" class="regular-text" />',
				esc_attr( $key )
			);
			break;

		default:
			printf(
				'<input type="text" data-field="%s" class="regular-text" />',
				esc_attr( $key )
			);
	}
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_modal()
//------------------------------------------------------------------------------
// One shared modal for all six sections, rendered once in the footer rather
// than once per metabox — its body gets swapped to whichever section's
// fields-template triggered it. Scoped to the recipe edit screen the same
// way the nonce field in metaboxes.php is.
function brewlab_recipes_render_repeater_modal() {
	$screen = get_current_screen();
	if ( ! $screen || 'brewlab_recipe' !== $screen->post_type ) {
		return;
	}
	?>
	<div class="brewlab-recipes-repeater-modal" style="display:none;">
		<div class="brewlab-recipes-repeater-modal__backdrop"></div>
		<div class="brewlab-recipes-repeater-modal__dialog" role="dialog" aria-modal="true">
			<h2 class="brewlab-recipes-repeater-modal__title"></h2>
			<div class="brewlab-recipes-repeater-modal__body"></div>
			<p class="brewlab-recipes-repeater-modal__actions">
				<button type="button" class="button button-primary brewlab-recipes-repeater-modal__save"><?php esc_html_e( 'Save', 'brewlab-recipes' ); ?></button>
				<button type="button" class="button brewlab-recipes-repeater-modal__cancel"><?php esc_html_e( 'Cancel', 'brewlab-recipes' ); ?></button>
			</p>
		</div>
	</div>
	<?php
}
add_action( 'admin_footer', 'brewlab_recipes_render_repeater_modal' );
