<?php
//------------------------------------------------------------------------------
//   Repeater Schemas
//------------------------------------------------------------------------------
// Declares the field shape for every JSON-array meta field on a recipe:
// fermentables, additions, hops, yeast, mash steps, and fermentation steps.
// This is the single source of truth for those six data shapes — the admin
// repeater UI, the front-end template, and the save handler should all read
// field definitions and select-option labels from here instead of each
// redefining their own copy (that duplication is what made the old plugin's
// meta-fields.php unmaintainable).

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_repeater_schemas()
//------------------------------------------------------------------------------
function brewlab_recipes_repeater_schemas() {
	return [

		'fermentables' => [
			'label'  => __( 'Fermentables', 'brewlab-recipes' ),
			'fields' => [
				'name'   => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
				],
				'amount' => [
					'type'     => 'number',
					'label'    => __( 'Amount', 'brewlab-recipes' ),
					'required' => true,
				],
				'unit'   => [
					'type'  => 'text',
					'label' => __( 'Unit', 'brewlab-recipes' ),
				],
				'type'   => [
					'type'    => 'select',
					'label'   => __( 'Type', 'brewlab-recipes' ),
					'options' => [
						'grain'        => __( 'Grain', 'brewlab-recipes' ),
						'adjunct'      => __( 'Adjunct', 'brewlab-recipes' ),
						'malt_extract' => __( 'Malt Extract', 'brewlab-recipes' ),
						'sugar'        => __( 'Sugar', 'brewlab-recipes' ),
						'honey'        => __( 'Honey', 'brewlab-recipes' ),
						'fruit'        => __( 'Fruit', 'brewlab-recipes' ),
						'other'        => __( 'Other', 'brewlab-recipes' ),
					],
				],
				'link'   => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
			],
		],

		'additions' => [
			'label'  => __( 'Other Additions', 'brewlab-recipes' ),
			'fields' => [
				'name'   => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
				],
				'amount' => [
					'type'     => 'number',
					'label'    => __( 'Amount', 'brewlab-recipes' ),
					'required' => true,
				],
				'unit'   => [
					'type'  => 'text',
					'label' => __( 'Unit', 'brewlab-recipes' ),
				],
				'stage'  => [
					'type'    => 'select',
					'label'   => __( 'Added At', 'brewlab-recipes' ),
					'options' => [
						'primary'     => __( 'Primary', 'brewlab-recipes' ),
						'secondary'   => __( 'Secondary', 'brewlab-recipes' ),
						'bulk_aging'  => __( 'Bulk Aging', 'brewlab-recipes' ),
						'packaging'   => __( 'Packaging', 'brewlab-recipes' ),
					],
				],
				'link'   => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
			],
		],

		'hops' => [
			'label'  => __( 'Hops', 'brewlab-recipes' ),
			'fields' => [
				'variety' => [
					'type'     => 'text',
					'label'    => __( 'Variety', 'brewlab-recipes' ),
					'required' => true,
				],
				'amount'  => [
					'type'     => 'number',
					'label'    => __( 'Amount', 'brewlab-recipes' ),
					'required' => true,
				],
				'unit'    => [
					'type'  => 'text',
					'label' => __( 'Unit', 'brewlab-recipes' ),
				],
				'alpha'   => [
					'type'  => 'number',
					'label' => __( 'Alpha Acid %', 'brewlab-recipes' ),
				],
				'type'    => [
					'type'    => 'select',
					'label'   => __( 'Form', 'brewlab-recipes' ),
					'options' => [
						'pellet'  => __( 'Pellet', 'brewlab-recipes' ),
						'whole'   => __( 'Whole', 'brewlab-recipes' ),
						'extract' => __( 'Extract', 'brewlab-recipes' ),
					],
				],
				'use'     => [
					'type'    => 'select',
					'label'   => __( 'Use', 'brewlab-recipes' ),
					'options' => [
						'boil'       => __( 'Boil', 'brewlab-recipes' ),
						'dry_hop'    => __( 'Dry Hop', 'brewlab-recipes' ),
						'aroma'      => __( 'Aroma', 'brewlab-recipes' ),
						'mash'       => __( 'Mash', 'brewlab-recipes' ),
						'first_wort' => __( 'First Wort', 'brewlab-recipes' ),
					],
				],
				'time'    => [
					'type'  => 'number',
					'label' => __( 'Time (min)', 'brewlab-recipes' ),
				],
				'link'    => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
			],
		],

		'yeast' => [
			'label'  => __( 'Yeast', 'brewlab-recipes' ),
			'fields' => [
				'name'   => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
				],
				'amount' => [
					'type'     => 'number',
					'label'    => __( 'Amount', 'brewlab-recipes' ),
					'required' => true,
				],
				'unit'   => [
					'type'    => 'select',
					'label'   => __( 'Unit', 'brewlab-recipes' ),
					'options' => [
						'pkg'           => __( 'Package', 'brewlab-recipes' ),
						'g'             => __( 'Grams', 'brewlab-recipes' ),
						'ml'            => __( 'mL', 'brewlab-recipes' ),
						'billion_cells' => __( 'Billion Cells', 'brewlab-recipes' ),
						'oz'            => __( 'Ounces', 'brewlab-recipes' ),
					],
				],
				'link'   => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
			],
		],

		'mash_steps' => [
			'label'  => __( 'Mash Steps', 'brewlab-recipes' ),
			'fields' => [
				'name' => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
				],
				'type' => [
					'type'    => 'select',
					'label'   => __( 'Type', 'brewlab-recipes' ),
					'options' => [
						'temperature' => __( 'Temperature Rest', 'brewlab-recipes' ),
						'infusion'    => __( 'Infusion', 'brewlab-recipes' ),
						'decoction'   => __( 'Decoction', 'brewlab-recipes' ),
					],
				],
				'temp' => [
					'type'  => 'number',
					'label' => __( 'Temp (°F)', 'brewlab-recipes' ),
				],
				'time' => [
					'type'  => 'number',
					'label' => __( 'Time (min)', 'brewlab-recipes' ),
				],
			],
		],

		'fermentation_steps' => [
			'label'  => __( 'Fermentation Steps', 'brewlab-recipes' ),
			'fields' => [
				'name'      => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
				],
				'type'      => [
					'type'    => 'select',
					'label'   => __( 'Stage', 'brewlab-recipes' ),
					'options' => [
						'primary'      => __( 'Primary', 'brewlab-recipes' ),
						'secondary'    => __( 'Secondary', 'brewlab-recipes' ),
						'tertiary'     => __( 'Tertiary', 'brewlab-recipes' ),
						'cold_crash'   => __( 'Cold Crash', 'brewlab-recipes' ),
						'carbonation'  => __( 'Carbonation', 'brewlab-recipes' ),
						'conditioning' => __( 'Conditioning', 'brewlab-recipes' ),
					],
				],
				'temp'      => [
					'type'  => 'number',
					'label' => __( 'Temp', 'brewlab-recipes' ),
				],
				'temp_unit' => [
					'type'    => 'select',
					'label'   => __( 'Temp Unit', 'brewlab-recipes' ),
					'options' => [
						'f' => __( 'Fahrenheit (°F)', 'brewlab-recipes' ),
						'c' => __( 'Celsius (°C)', 'brewlab-recipes' ),
					],
				],
				'days'      => [
					'type'  => 'number',
					'label' => __( 'Days', 'brewlab-recipes' ),
				],
				'ramp'      => [
					'type'  => 'text',
					'label' => __( 'Ramp', 'brewlab-recipes' ),
				],
				'pressure'  => [
					'type'  => 'text',
					'label' => __( 'Pressure', 'brewlab-recipes' ),
				],
			],
		],

	];
}
