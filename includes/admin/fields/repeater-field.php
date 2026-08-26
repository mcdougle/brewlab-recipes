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
	echo '<span class="brewlab-recipes-repeater__item-summary">';
	brewlab_recipes_render_repeater_item_summary( $section, $fields, $row );
	echo '</span>';

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
//   brewlab_recipes_render_repeater_item_summary()
//------------------------------------------------------------------------------
// A row's summary: a left-hand "primary" cluster (name/variety, plus an
// amount+unit-style chip when a primary field is paired via inline_with)
// and a right-aligned "meta" cluster, each field's chip styled per its
// schema 'summary' config (slot/bold/muted/width/suffix) — see the
// repeater-schemas.php header comment for the shape. This is the single
// place a row ever gets turned into a summary: admin-repeater.js doesn't
// duplicate this logic — after a modal save it POSTs the row's values to
// brewlab_recipes_ajax_render_repeater_summary() (below), which calls this
// same function, so there's exactly one implementation of "what a row
// looks like" for both the initial page load and every live edit.
function brewlab_recipes_render_repeater_item_summary( $section, $fields, $row ) {
	$primary = [];
	$meta    = [];
	$skip    = [];

	foreach ( $fields as $key => $field ) {
		if ( in_array( $key, $skip, true ) || empty( $field['summary'] ) ) {
			continue;
		}

		$config = $field['summary'];
		$value  = brewlab_recipes_repeater_cell_value( $section, $key, $row[ $key ] ?? '' );

		// A primary/meta field that's the first half of an inline_with pair
		// (amount+unit, temp+temp_unit) renders as one combined chip.
		if ( ! empty( $field['inline_with'] ) && isset( $fields[ $field['inline_with'] ] ) ) {
			$partner_key = $field['inline_with'];
			$partner_val = brewlab_recipes_repeater_cell_value( $section, $partner_key, $row[ $partner_key ] ?? '' );
			$value       = trim( $value . ' ' . $partner_val );
			$skip[]      = $partner_key;
		}

		// Hops' time is measured in days for a dry-hop addition, minutes for
		// everything else — the one suffix that depends on another field's
		// value rather than being static.
		if ( 'hops' === $section && 'time' === $key ) {
			$config['suffix'] = ( 'dry_hop' === ( $row['use'] ?? '' ) ) ? ' days' : ' min';
		}

		if ( '' === $value ) {
			continue;
		}
		if ( ! empty( $config['suffix'] ) ) {
			$value .= $config['suffix'];
		}

		$chip = [
			'value' => $value,
			'bold'  => ! empty( $config['bold'] ),
			'muted' => ! empty( $config['muted'] ),
			'width' => $config['width'] ?? null,
			'grow'  => ! empty( $config['grow'] ),
		];

		if ( 'meta' === ( $config['slot'] ?? 'primary' ) ) {
			$meta[] = $chip;
		} else {
			$primary[] = $chip;
		}
	}

	if ( ! $primary && ! $meta ) {
		printf( '<span class="brewlab-recipes-repeater__item-empty">%s</span>', esc_html__( '(empty)', 'brewlab-recipes' ) );
		return;
	}

	echo '<span class="brewlab-recipes-repeater__item-primary">';
	array_map( 'brewlab_recipes_render_repeater_summary_chip', $primary );
	echo '</span>';

	if ( $meta ) {
		echo '<span class="brewlab-recipes-repeater__item-meta">';
		array_map( 'brewlab_recipes_render_repeater_summary_chip', $meta );
		echo '</span>';
	}
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_summary_chip()
//------------------------------------------------------------------------------
function brewlab_recipes_render_repeater_summary_chip( array $chip ) {
	$classes = [ 'brewlab-recipes-repeater__chip' ];
	if ( $chip['bold'] ) {
		$classes[] = 'brewlab-recipes-repeater__chip--bold';
	}
	if ( $chip['muted'] ) {
		$classes[] = 'brewlab-recipes-repeater__chip--muted';
	}

	// Fixed-width chips (amount, temp, time, days...) get a right-aligned
	// column of that width, so varying-length values still line up on a
	// consistent edge. The one 'grow' chip per section (name/variety) fills
	// whatever space is left; anything else with neither (hops' alpha) just
	// sits at its own natural size.
	if ( $chip['width'] ) {
		$style = sprintf( ' style="flex:0 0 %dpx;text-align:right;"', (int) $chip['width'] );
	} elseif ( $chip['grow'] ) {
		$style = ' style="flex:1 1 auto;"';
	} else {
		$style = ' style="flex:0 0 auto;"';
	}

	printf(
		'<span class="%s"%s>%s</span>',
		esc_attr( implode( ' ', $classes ) ),
		$style,
		esc_html( $chip['value'] )
	);
}

//------------------------------------------------------------------------------
//   brewlab_recipes_ajax_render_repeater_summary()
//------------------------------------------------------------------------------
// admin-repeater.js calls this after every modal Save, POSTing the row's
// current field values and getting back the exact HTML
// brewlab_recipes_render_repeater_item_summary() would have produced on a
// full page load — reusing save.php's row sanitizer so an AJAX-rendered
// summary is sanitized the same way a saved one is, not a separate,
// looser path.
function brewlab_recipes_ajax_render_repeater_summary() {
	check_ajax_referer( 'brewlab_recipes_save_meta', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	$section = sanitize_key( wp_unslash( $_POST['section'] ?? '' ) );
	$schemas = brewlab_recipes_repeater_schemas();
	if ( ! isset( $schemas[ $section ] ) ) {
		wp_send_json_error();
	}

	$fields  = $schemas[ $section ]['fields'];
	$raw_row = is_array( $_POST['row'] ?? null ) ? wp_unslash( $_POST['row'] ) : [];
	$row     = brewlab_recipes_sanitize_repeater_row( $raw_row, $fields );

	ob_start();
	brewlab_recipes_render_repeater_item_summary( $section, $fields, $row );
	wp_send_json_success( [ 'html' => ob_get_clean() ] );
}
add_action( 'wp_ajax_brewlab_recipes_render_repeater_summary', 'brewlab_recipes_ajax_render_repeater_summary' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_modal_fields()
//------------------------------------------------------------------------------
// Unlike brewlab_recipes_render_repeater_item()'s hidden inputs, these carry
// no name/value — they're a template the modal clones and populates
// per-open, never submitted directly. Label-above-field (not label-beside,
// the way the admin form-table rows work) — matches the old plugin's modal
// layout, and reads more like a compact form than a settings table.
//
// A field with an 'inline_with' key (e.g. amount → unit, temp → temp_unit)
// renders paired with the field it names in one row instead of two,
// matching how Batch Size+Unit pairs in Batch Details — the paired field is
// skipped when the loop reaches it on its own.
function brewlab_recipes_render_repeater_modal_fields( $fields ) {
	$skip_next = null;

	foreach ( $fields as $key => $field ) {
		if ( $key === $skip_next ) {
			$skip_next = null;
			continue;
		}

		if ( ! empty( $field['inline_with'] ) && isset( $fields[ $field['inline_with'] ] ) ) {
			$partner_key = $field['inline_with'];
			$skip_next   = $partner_key;

			printf( '<div class="brewlab-recipes-repeater-modal-field"><label>%s</label>', esc_html( $field['label'] ) );
			echo '<div class="brewlab-recipes-repeater-modal-field__inline">';
			brewlab_recipes_render_repeater_modal_input( $key, $field );
			brewlab_recipes_render_repeater_modal_input( $partner_key, $fields[ $partner_key ] );
			echo '</div></div>';
			continue;
		}

		printf( '<div class="brewlab-recipes-repeater-modal-field"><label>%s</label>', esc_html( $field['label'] ) );
		brewlab_recipes_render_repeater_modal_input( $key, $field );
		echo '</div>';
	}
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_repeater_modal_input()
//------------------------------------------------------------------------------
function brewlab_recipes_render_repeater_modal_input( $key, $field ) {
	if ( 'select' === $field['type'] && 'toggle' === ( $field['widget'] ?? '' ) ) {
		brewlab_recipes_render_repeater_modal_toggle( $key, $field );
		return;
	}

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
//   brewlab_recipes_render_repeater_modal_toggle()
//------------------------------------------------------------------------------
// A segmented button pair (e.g. °F / °C) instead of a <select> — for any
// 'select' field with 'widget' => 'toggle' set (currently just temp_unit).
// The real value lives in a hidden input carrying data-field like every
// other modal input, so admin-repeater.js's existing generic value-sync
// loop (modalBody.querySelectorAll('[data-field]')) picks it up unchanged;
// the visible buttons are just UI that write into it on click. data-label
// mirrors the active button's short text, since a hidden input has no
// visible "selected option" text the way a <select> does — see
// admin-repeater.js's onModalSave() for where that gets read.
function brewlab_recipes_render_repeater_modal_toggle( $key, $field ) {
	$options = $field['short_labels'] ?? $field['options'];
	$default = array_key_first( $options );

	printf( '<div class="brewlab-recipes-toggle">' );
	printf(
		'<input type="hidden" data-field="%s" data-label="%s" value="%s" />',
		esc_attr( $key ),
		esc_attr( $options[ $default ] ),
		esc_attr( $default )
	);
	foreach ( $options as $value => $label ) {
		printf(
			'<button type="button" class="brewlab-recipes-toggle__option%s" data-value="%s">%s</button>',
			$value === $default ? ' is-active' : '',
			esc_attr( $value ),
			esc_html( $label )
		);
	}
	echo '</div>';
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
