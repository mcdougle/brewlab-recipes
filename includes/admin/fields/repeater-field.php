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
// identical to before this rewrite, so save.php didn't need any changes for
// the row data itself. Clicking anywhere on a row opens it in the modal —
// there's no separate Edit button; Delete lives in the modal footer instead
// of on the row, matching the old plugin's interaction model.

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

	$item_label = $schemas[ $section ]['item_label'];

	printf(
		'<div class="brewlab-recipes-repeater" data-section="%s" data-item-label="%s" data-next-index="%d">',
		esc_attr( $section ),
		esc_attr( $item_label ),
		count( $rows )
	);

	// Only mash_steps/fermentation_steps have this — a single name for the
	// whole profile (e.g. "Hochkurz Step Mash"), separate from each step's
	// own name. Plain sibling postmeta key, not part of the JSON rows array.
	if ( isset( $schemas[ $section ]['profile_label'] ) ) {
		brewlab_recipes_render_repeater_profile_name( $post_id, $section, $schemas[ $section ]['profile_label'] );
	}

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
		/* translators: %s: singular item name, e.g. "Fermentable" */
		'<p><button type="button" class="button brewlab-recipes-repeater__add">+ %s</button></p>',
		esc_html( sprintf( __( 'Add %s', 'brewlab-recipes' ), $item_label ) )
	);

	echo '</div>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_profile_name()
//------------------------------------------------------------------------------
function brewlab_recipes_render_repeater_profile_name( $post_id, $section, $label ) {
	$name  = 'brewlab_recipes_' . $section . '_profile_name';
	$value = get_post_meta( $post_id, '_' . $name, true );
	?>
	<div class="brewlab-recipes-row">
		<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
		<div class="brewlab-recipes-input">
			<input type="text" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		</div>
	</div>
	<?php
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_item()
//------------------------------------------------------------------------------
// No Edit/Remove buttons — admin-repeater.js opens the modal on a click
// anywhere on the row (event delegation on the list), and Delete lives in
// the modal footer instead.
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
// per-open, never submitted directly. Label-above-field (not label-beside,
// the way the admin form-table rows work) — matches the old plugin's modal
// layout, and reads more like a compact form than a settings table.
function brewlab_recipes_render_repeater_modal_fields( $fields ) {
	foreach ( $fields as $key => $field ) {
		printf( '<div class="brewlab-recipes-repeater-modal-field"><label>%s</label>', esc_html( $field['label'] ) );
		brewlab_recipes_render_repeater_modal_input( $key, $field );
		echo '</div>';
	}
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
			<button type="button" class="brewlab-recipes-repeater-modal__close" aria-label="<?php esc_attr_e( 'Close', 'brewlab-recipes' ); ?>">&times;</button>
			<h2 class="brewlab-recipes-repeater-modal__title"></h2>
			<div class="brewlab-recipes-repeater-modal__body"></div>
			<p class="brewlab-recipes-repeater-modal__actions">
				<button type="button" class="button brewlab-recipes-repeater-modal__delete" style="display:none;"><?php esc_html_e( 'Delete', 'brewlab-recipes' ); ?></button>
				<button type="button" class="button button-primary brewlab-recipes-repeater-modal__save"><?php esc_html_e( 'Save', 'brewlab-recipes' ); ?></button>
			</p>
		</div>
	</div>
	<?php
}
add_action( 'admin_footer', 'brewlab_recipes_render_repeater_modal' );
