<?php
//------------------------------------------------------------------------------
//   Simple Fields
//------------------------------------------------------------------------------
// Schema and render functions for every recipe meta field that isn't one of
// the six repeaters (see repeater-schemas.php for those): media, recipe
// details, and batch details. Mirrors that file's plain-data-schema shape so
// a future save handler can walk both the same way instead of hardcoding a
// separate field list.

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
					'type'  => 'text',
					'label' => __( 'Other Type Name', 'brewlab-recipes' ),
				],
				'style'           => [
					'type'  => 'text',
					'label' => __( 'Style', 'brewlab-recipes' ),
				],
				'summary'         => [
					'type'  => 'textarea',
					'label' => __( 'Summary', 'brewlab-recipes' ),
				],
				'author_display'  => [
					'type'    => 'select',
					'label'   => __( 'Show Author As', 'brewlab-recipes' ),
					'options' => [
						'none'        => __( 'Hidden', 'brewlab-recipes' ),
						'post_author' => __( 'Post Author', 'brewlab-recipes' ),
						'custom'      => __( 'Custom Name', 'brewlab-recipes' ),
					],
				],
				'author_custom'   => [
					'type'  => 'text',
					'label' => __( 'Custom Author Name', 'brewlab-recipes' ),
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
					'type'  => 'number',
					'label' => __( 'Boil Time (min)', 'brewlab-recipes' ),
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
					'label' => __( 'ABV (%)', 'brewlab-recipes' ),
				],
				'ibu'              => [
					'type'  => 'number',
					'label' => __( 'IBU', 'brewlab-recipes' ),
				],
				'srm'              => [
					'type'  => 'number',
					'label' => __( 'SRM', 'brewlab-recipes' ),
				],
				'show_hops'        => [
					'type'  => 'checkbox',
					'label' => __( 'Show Hops Section', 'brewlab-recipes' ),
				],
				'show_mash'        => [
					'type'  => 'checkbox',
					'label' => __( 'Show Mash Section', 'brewlab-recipes' ),
				],
			],
		],

	];
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_simple_fields()
//------------------------------------------------------------------------------
// Renders every field in one schema section as a form-table. Called from a
// metabox's render callback with $section matching a top-level key above
// (e.g. 'media', 'recipe_details', 'batch_details').
function brewlab_recipes_render_simple_fields( $post_id, $section ) {
	$sections = brewlab_recipes_simple_fields();
	if ( ! isset( $sections[ $section ] ) ) {
		return;
	}

	echo '<table class="form-table brewlab-recipes-fields"><tbody>';
	foreach ( $sections[ $section ]['fields'] as $key => $field ) {
		brewlab_recipes_render_simple_field( $post_id, $key, $field );
	}
	echo '</tbody></table>';
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_simple_field()
//------------------------------------------------------------------------------
function brewlab_recipes_render_simple_field( $post_id, $key, $field ) {
	$name  = 'brewlab_recipes_' . $key;
	$id    = 'brewlab-recipes-' . str_replace( '_', '-', $key );
	$value = get_post_meta( $post_id, '_brewlab_recipes_' . $key, true );

	echo '<tr>';
	printf( '<th scope="row"><label for="%s">%s</label></th>', esc_attr( $id ), esc_html( $field['label'] ) );
	echo '<td>';

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

		case 'media':
			// Plain attachment-ID input for now — a proper media-library
			// picker button is JS-driven admin polish, built later.
			printf(
				'<input type="number" id="%s" name="%s" value="%s" class="small-text" /> <p class="description">%s</p>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_html__( 'Attachment ID — a media picker button replaces this once the admin JS lands.', 'brewlab-recipes' )
			);
			break;

		case 'color':
			// Plain hex input for now — the hand-rolled swatch/HSV picker
			// is JS-driven admin polish, built later.
			printf(
				'<input type="text" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s" />',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_attr( $field['default'] ?? '' )
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

	echo '</td></tr>';
}
