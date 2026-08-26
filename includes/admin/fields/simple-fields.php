<?php
//------------------------------------------------------------------------------
//   Simple Fields
//------------------------------------------------------------------------------
// Schema and render functions for every recipe meta field that isn't one of
// the six repeaters (see repeater-schemas.php for those): media, recipe
// details, batch details, and the brew-type-conditional options. Mirrors
// that file's plain-data-schema shape so save.php can walk both the same
// way instead of hardcoding a separate field list.
//
// Media and Batch Details render with dedicated functions instead of the
// generic schema loop, since their layout (an inline image/color row; a few
// inline field pairs like Batch Size+Unit and O.G./F.G.) doesn't fit a
// one-row-per-field pattern. Recipe Details and Options still loop
// generically — every field they have is a plain single-row field.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_simple_fields()
//------------------------------------------------------------------------------
function brewlab_recipes_simple_fields() {
	return [

		'media' => [
			'label'  => __( 'Media', 'brewlab-recipes' ),
			'fields' => [
				'image_id'     => [
					'type'  => 'media',
					'label' => __( 'Recipe Image', 'brewlab-recipes' ),
				],
				'header_color' => [
					'type'    => 'color',
					'label'   => __( 'Recipe Color', 'brewlab-recipes' ),
					'default' => '#1e1b17',
				],
			],
		],

		'recipe_details' => [
			'label'  => __( 'Recipe Details', 'brewlab-recipes' ),
			'fields' => [
				'brew_type'       => [
					'type'     => 'select',
					'label'    => __( 'Brew Type', 'brewlab-recipes' ),
					'required' => true,
					'options'  => [
						'beer'  => __( 'Beer', 'brewlab-recipes' ),
						'mead'  => __( 'Mead', 'brewlab-recipes' ),
						'wine'  => __( 'Wine', 'brewlab-recipes' ),
						'cider' => __( 'Cider', 'brewlab-recipes' ),
						'other' => __( 'Other', 'brewlab-recipes' ),
					],
				],
				'brew_type_other' => [
					'type'       => 'text',
					'label'      => __( 'Other Type Name', 'brewlab-recipes' ),
					'depends_on' => [ 'field' => 'brew_type', 'value' => 'other' ],
				],
				'summary'         => [
					'type'  => 'textarea',
					'label' => __( 'Summary', 'brewlab-recipes' ),
					'hint'  => __( 'Short description', 'brewlab-recipes' ),
				],
				'author_display'  => [
					'type'    => 'select',
					'label'   => __( 'Author', 'brewlab-recipes' ),
					'options' => [
						'none'        => __( 'Hidden', 'brewlab-recipes' ),
						'post_author' => __( 'Post Author', 'brewlab-recipes' ),
						'custom'      => __( 'Custom Name', 'brewlab-recipes' ),
					],
				],
				'author_custom'   => [
					'type'       => 'text',
					'label'      => __( 'Custom Author Name', 'brewlab-recipes' ),
					'depends_on' => [ 'field' => 'author_display', 'value' => 'custom' ],
				],
				'notes'           => [
					'type'  => 'textarea',
					'label' => __( 'Notes', 'brewlab-recipes' ),
				],
			],
		],

		'batch_details' => [
			'label'  => __( 'Batch Details', 'brewlab-recipes' ),
			'fields' => [
				'style'            => [
					'type'  => 'text',
					'label' => __( 'Style', 'brewlab-recipes' ),
				],
				'batch_size'       => [
					'type'  => 'number',
					'label' => __( 'Batch Size', 'brewlab-recipes' ),
				],
				'batch_size_unit'  => [
					'type'    => 'select',
					'label'   => __( 'Unit', 'brewlab-recipes' ),
					'options' => [
						'gallons' => __( 'Gallons', 'brewlab-recipes' ),
						'litres'  => __( 'Litres', 'brewlab-recipes' ),
					],
				],
				'boil_time'        => [
					'type'      => 'number',
					'label'     => __( 'Boil Time', 'brewlab-recipes' ),
					'hint'      => __( 'minutes', 'brewlab-recipes' ),
					'beer_only' => true,
				],
				'original_gravity' => [
					'type'  => 'number',
					'label' => __( 'Original Gravity', 'brewlab-recipes' ),
				],
				'final_gravity'    => [
					'type'  => 'number',
					'label' => __( 'Final Gravity', 'brewlab-recipes' ),
				],
				'abv'              => [
					'type'  => 'number',
					'label' => __( 'ABV', 'brewlab-recipes' ),
					'hint'  => '%',
				],
				'ibu'              => [
					'type'      => 'number',
					'label'     => __( 'IBU', 'brewlab-recipes' ),
					'hint'      => __( 'Bitterness', 'brewlab-recipes' ),
					'beer_only' => true,
				],
				'srm'              => [
					'type'  => 'number',
					'label' => __( 'SRM', 'brewlab-recipes' ),
				],
			],
		],

		'options' => [
			'label'  => __( 'Options', 'brewlab-recipes' ),
			'fields' => [
				'show_hops' => [
					'type'  => 'checkbox',
					'label' => __( 'Show Hops', 'brewlab-recipes' ),
					'hint'  => __( 'Add hop details for something other than beer.', 'brewlab-recipes' ),
				],
				'show_mash' => [
					'type'  => 'checkbox',
					'label' => __( 'Show Mash Profile', 'brewlab-recipes' ),
					'hint'  => __( 'Add mash profile details for something other than beer.', 'brewlab-recipes' ),
				],
			],
		],

	];
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_simple_fields()
//------------------------------------------------------------------------------
// Dispatches to a dedicated layout for media/batch_details, or a generic
// one-row-per-field loop for everything else (recipe_details, options).
// Called from a metabox's render callback with $section matching a
// top-level key in brewlab_recipes_simple_fields().
function brewlab_recipes_render_simple_fields( $post_id, $section ) {
	if ( 'media' === $section ) {
		brewlab_recipes_render_media_box( $post_id );
		return;
	}
	if ( 'batch_details' === $section ) {
		brewlab_recipes_render_batch_details_box( $post_id );
		return;
	}

	$sections = brewlab_recipes_simple_fields();
	if ( ! isset( $sections[ $section ] ) ) {
		return;
	}

	echo '<div class="brewlab-recipes-fields">';

	if ( 'recipe_details' === $section ) {
		brewlab_recipes_render_name_proxy_row( $post_id );
	}

	foreach ( $sections[ $section ]['fields'] as $key => $field ) {
		brewlab_recipes_render_simple_field( $post_id, $key, $field );
	}

	echo '</div>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_name_proxy_row()
//------------------------------------------------------------------------------
// Not a real field — brewlab_recipe only supports 'title' (see
// post-types.php), so the recipe's name already is post_title. This is a
// proxy input with no name attribute of its own: it seeds its value from
// post_title and forwards every keystroke into WP's native #title input
// (hidden via admin.css) so the native field still gets submitted and saved
// through WP core's own normal post-save path. No new meta key, no save.php
// changes — the whole point is to move where the title reads/writes from
// without duplicating where it's stored.
function brewlab_recipes_render_name_proxy_row( $post_id ) {
	$post  = get_post( $post_id );
	$title = ( $post && __( 'Auto Draft' ) !== $post->post_title ) ? $post->post_title : '';
	?>
	<div class="brewlab-recipes-row">
		<label for="brewlab-recipes-name-proxy"><?php esc_html_e( 'Name', 'brewlab-recipes' ); ?></label>
		<div class="brewlab-recipes-input">
			<input type="text" id="brewlab-recipes-name-proxy"
				placeholder="<?php esc_attr_e( 'Recipe name', 'brewlab-recipes' ); ?>"
				value="<?php echo esc_attr( $title ); ?>"
				oninput="document.getElementById('title').value=this.value;" />
		</div>
	</div>
	<?php
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_media_box()
//------------------------------------------------------------------------------
function brewlab_recipes_render_media_box( $post_id ) {
	$image_id  = (int) get_post_meta( $post_id, '_brewlab_recipes_image_id', true );
	$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
	$color     = get_post_meta( $post_id, '_brewlab_recipes_header_color', true ) ?: '#1e1b17';
	?>
	<div class="brewlab-recipes-media-box">
		<div class="brewlab-recipes-media-image">
			<img src="<?php echo esc_url( $image_url ); ?>" class="brewlab-recipes-media-field__preview"<?php echo $image_url ? '' : ' style="display:none;"'; ?> alt="" />
		</div>
		<div class="brewlab-recipes-media-controls">
			<div class="brewlab-recipes-media-actions">
				<input type="hidden" id="brewlab-recipes-image-id" name="brewlab_recipes_image_id" value="<?php echo esc_attr( $image_id ?: '' ); ?>" />
				<button type="button" class="button brewlab-recipes-media-field__select"><?php esc_html_e( 'Change Image', 'brewlab-recipes' ); ?></button>
				<button type="button" class="button brewlab-recipes-media-field__remove"<?php echo $image_url ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove Image', 'brewlab-recipes' ); ?></button>
			</div>
			<div class="brewlab-recipes-media-divider"></div>
			<div class="brewlab-recipes-media-color">
				<input type="hidden" id="brewlab-recipes-header-color" name="brewlab_recipes_header_color" value="<?php echo esc_attr( $color ); ?>" />
				<button type="button" id="brewlab-recipes-color-btn" class="brewlab-recipes-color-btn" title="<?php esc_attr_e( 'Edit recipe color', 'brewlab-recipes' ); ?>">
					<span class="brewlab-recipes-color-swatch" id="brewlab-recipes-color-swatch" data-default="#1e1b17" style="background:<?php echo esc_attr( $color ); ?>"></span>
					<span class="brewlab-recipes-color-label"><?php esc_html_e( 'Recipe Color', 'brewlab-recipes' ); ?></span>
				</button>
			</div>
		</div>
	</div>
	<?php
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_color_picker_modal()
//------------------------------------------------------------------------------
// Rendered once in the footer (same pattern as the repeater modal in
// repeater-field.php) — a hand-rolled canvas HSV picker, not WP core's
// bundled Iris picker, matching the old plugin's exact UI. The SV
// square/hue strip/hex/preset interaction is all in admin-media-color.js;
// this only needs to emit the DOM it operates on.
function brewlab_recipes_render_color_picker_modal() {
	$screen = get_current_screen();
	if ( ! $screen || 'brewlab_recipe' !== $screen->post_type ) {
		return;
	}

	$presets = [ '#1e1b17', '#1a2634', '#1a2a1a', '#2d1a0e', '#1f1a2e', '#2a1a1a', '#0d1f2d', '#263326' ];
	?>
	<div class="brewlab-recipes-color-modal" style="display:none;">
		<div class="brewlab-recipes-color-backdrop"></div>
		<div class="brewlab-recipes-color-dialog" role="dialog" aria-modal="true">
			<button type="button" class="brewlab-recipes-color-close" aria-label="<?php esc_attr_e( 'Close', 'brewlab-recipes' ); ?>">&times;</button>
			<h2 class="brewlab-recipes-color-title"><?php esc_html_e( 'Recipe Color', 'brewlab-recipes' ); ?></h2>
			<div class="brewlab-recipes-color-body">
				<div class="brewlab-recipes-color-sv-wrap">
					<canvas class="brewlab-recipes-color-sv-canvas" width="220" height="180"></canvas>
					<div class="brewlab-recipes-color-sv-cursor"></div>
				</div>
				<canvas class="brewlab-recipes-color-hue-canvas" width="220" height="16"></canvas>
				<div class="brewlab-recipes-color-hue-cursor"></div>
				<div class="brewlab-recipes-color-preview-row">
					<div class="brewlab-recipes-color-preview-swatch"></div>
					<div class="brewlab-recipes-color-hex-wrap">
						<span class="brewlab-recipes-color-hex-hash">#</span>
						<input type="text" class="brewlab-recipes-color-hex-input" maxlength="6" spellcheck="false" />
					</div>
				</div>
				<div class="brewlab-recipes-color-presets">
					<?php foreach ( $presets as $preset ) : ?>
						<button type="button" class="brewlab-recipes-color-preset" data-color="<?php echo esc_attr( $preset ); ?>" style="background:<?php echo esc_attr( $preset ); ?>" title="<?php echo esc_attr( $preset ); ?>"></button>
					<?php endforeach; ?>
				</div>
			</div>
			<p class="brewlab-recipes-color-actions">
				<button type="button" class="button brewlab-recipes-color-reset"><?php esc_html_e( 'Reset to Default', 'brewlab-recipes' ); ?></button>
				<button type="button" class="button button-primary brewlab-recipes-color-apply"><?php esc_html_e( 'Apply', 'brewlab-recipes' ); ?></button>
			</p>
		</div>
	</div>
	<?php
}
add_action( 'admin_footer', 'brewlab_recipes_render_color_picker_modal' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_batch_details_box()
//------------------------------------------------------------------------------
// Style, Boil Time, ABV, IBU, and SRM are plain single-input rows (same
// brewlab_recipes_render_field_input() the generic loop uses elsewhere);
// Batch Size+Unit and O.G./F.G. render as inline pairs instead, which the
// generic one-row-per-field loop has no way to express.
function brewlab_recipes_render_batch_details_box( $post_id ) {
	$fields = brewlab_recipes_simple_fields()['batch_details']['fields'];
	$get    = function ( $key ) use ( $post_id ) {
		return get_post_meta( $post_id, '_brewlab_recipes_' . $key, true );
	};
	// Computed server-side so beer-only rows don't flash visible-then-hidden
	// on load — admin-conditional.js takes over from here for live changes.
	$is_beer = 'beer' === $get( 'brew_type' );
	?>
	<div class="brewlab-recipes-fields">

		<div class="brewlab-recipes-row">
			<label for="brewlab-recipes-style"><?php esc_html_e( 'Style', 'brewlab-recipes' ); ?></label>
			<div class="brewlab-recipes-input">
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-style', 'brewlab_recipes_style', $get( 'style' ), $fields['style'] ); ?>
			</div>
		</div>

		<div class="brewlab-recipes-row">
			<label for="brewlab-recipes-batch-size"><?php esc_html_e( 'Batch Size', 'brewlab-recipes' ); ?></label>
			<div class="brewlab-recipes-input brewlab-recipes-input--inline">
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-batch-size', 'brewlab_recipes_batch_size', $get( 'batch_size' ), $fields['batch_size'] ); ?>
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-batch-size-unit', 'brewlab_recipes_batch_size_unit', $get( 'batch_size_unit' ), $fields['batch_size_unit'] ); ?>
			</div>
		</div>

		<div class="brewlab-recipes-row brewlab-recipes-beer-only"<?php echo $is_beer ? '' : ' style="display:none;"'; ?>>
			<label for="brewlab-recipes-boil-time"><?php esc_html_e( 'Boil Time', 'brewlab-recipes' ); ?><span class="brewlab-recipes-hint"><?php esc_html_e( 'minutes', 'brewlab-recipes' ); ?></span></label>
			<div class="brewlab-recipes-input">
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-boil-time', 'brewlab_recipes_boil_time', $get( 'boil_time' ), $fields['boil_time'] ); ?>
			</div>
		</div>

		<div class="brewlab-recipes-row">
			<label><?php esc_html_e( 'Gravity', 'brewlab-recipes' ); ?></label>
			<div class="brewlab-recipes-input brewlab-recipes-input--inline">
				<div class="brewlab-recipes-labeled-input">
					<span class="brewlab-recipes-field-label"><?php esc_html_e( 'O.G.', 'brewlab-recipes' ); ?></span>
					<?php brewlab_recipes_render_field_input( 'brewlab-recipes-original-gravity', 'brewlab_recipes_original_gravity', $get( 'original_gravity' ), $fields['original_gravity'] ); ?>
				</div>
				<div class="brewlab-recipes-labeled-input">
					<span class="brewlab-recipes-field-label"><?php esc_html_e( 'F.G.', 'brewlab-recipes' ); ?></span>
					<?php brewlab_recipes_render_field_input( 'brewlab-recipes-final-gravity', 'brewlab_recipes_final_gravity', $get( 'final_gravity' ), $fields['final_gravity'] ); ?>
				</div>
			</div>
		</div>

		<div class="brewlab-recipes-row">
			<label for="brewlab-recipes-abv"><?php esc_html_e( 'ABV', 'brewlab-recipes' ); ?><span class="brewlab-recipes-hint">%</span></label>
			<div class="brewlab-recipes-input">
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-abv', 'brewlab_recipes_abv', $get( 'abv' ), $fields['abv'] ); ?>
			</div>
		</div>

		<div class="brewlab-recipes-row brewlab-recipes-beer-only"<?php echo $is_beer ? '' : ' style="display:none;"'; ?>>
			<label for="brewlab-recipes-ibu"><?php esc_html_e( 'IBU', 'brewlab-recipes' ); ?><span class="brewlab-recipes-hint"><?php esc_html_e( 'Bitterness', 'brewlab-recipes' ); ?></span></label>
			<div class="brewlab-recipes-input">
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-ibu', 'brewlab_recipes_ibu', $get( 'ibu' ), $fields['ibu'] ); ?>
			</div>
		</div>

		<div class="brewlab-recipes-row">
			<label for="brewlab-recipes-srm"><?php esc_html_e( 'SRM', 'brewlab-recipes' ); ?></label>
			<div class="brewlab-recipes-input">
				<?php brewlab_recipes_render_field_input( 'brewlab-recipes-srm', 'brewlab_recipes_srm', $get( 'srm' ), $fields['srm'] ); ?>
			</div>
		</div>

	</div>
	<?php
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_simple_field()
//------------------------------------------------------------------------------
// One full row (label + hint + input) for the generic per-section loop.
// A field with a 'depends_on' key (e.g. brew_type_other only mattering when
// brew_type is 'other') renders with its initial visibility computed
// server-side from the controlling field's current value, and a
// data-depends-on/data-depends-value pair admin-conditional.js reads to
// keep it in sync live — see brewlab_recipes_render_conditional_row_attrs().
function brewlab_recipes_render_simple_field( $post_id, $key, $field ) {
	$name  = 'brewlab_recipes_' . $key;
	$id    = 'brewlab-recipes-' . str_replace( '_', '-', $key );
	$value = get_post_meta( $post_id, '_brewlab_recipes_' . $key, true );

	printf( '<div class="brewlab-recipes-row%s"%s>', empty( $field['depends_on'] ) ? '' : ' brewlab-recipes-conditional', brewlab_recipes_render_conditional_row_attrs( $post_id, $field ) );
	printf( '<label for="%s">%s', esc_attr( $id ), esc_html( $field['label'] ) );
	if ( ! empty( $field['hint'] ) ) {
		printf( '<span class="brewlab-recipes-hint">%s</span>', esc_html( $field['hint'] ) );
	}
	echo '</label>';
	echo '<div class="brewlab-recipes-input">';
	brewlab_recipes_render_field_input( $id, $name, $value, $field );
	echo '</div></div>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_conditional_row_attrs()
//------------------------------------------------------------------------------
// '' for a field with no 'depends_on'. Otherwise the data attributes
// admin-conditional.js needs to watch the controlling field, plus an
// inline display:none computed from its current value so a conditional
// row that shouldn't show yet doesn't flash visible on load.
function brewlab_recipes_render_conditional_row_attrs( $post_id, $field ) {
	if ( empty( $field['depends_on'] ) ) {
		return '';
	}

	$controls      = $field['depends_on']['field'];
	$expected      = $field['depends_on']['value'];
	$controller_id = 'brewlab-recipes-' . str_replace( '_', '-', $controls );
	$current       = get_post_meta( $post_id, '_brewlab_recipes_' . $controls, true );
	$visible       = ( $current === $expected );

	return sprintf(
		' data-depends-on="%s" data-depends-value="%s"%s',
		esc_attr( $controller_id ),
		esc_attr( $expected ),
		$visible ? '' : ' style="display:none;"'
	);
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_field_input()
//------------------------------------------------------------------------------
// Just the <input>/<select>/etc — no row wrapper, no label. Split out from
// brewlab_recipes_render_simple_field() so the Media and Batch Details
// bespoke layouts (which need the same input types in a different row
// shape) can call it directly instead of duplicating the type switch.
function brewlab_recipes_render_field_input( $id, $name, $value, $field ) {
	switch ( $field['type'] ) {

		case 'select':
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
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

		case 'textarea':
			printf(
				'<textarea id="%s" name="%s" class="large-text" rows="4">%s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_textarea( $value )
			);
			break;

		case 'checkbox':
			printf(
				'<input type="checkbox" id="%s" name="%s" value="1"%s />',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( $value, '1', false )
			);
			break;

		case 'number':
			printf(
				'<input type="number" step="any" id="%s" name="%s" value="%s" class="small-text" />',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
			break;

		default:
			printf(
				'<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
	}
}
