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
//
// A field's 'summary' key drives the admin row summary (see
// brewlab_recipes_render_repeater_item() in repeater-field.php): 'slot' is
// 'primary' (left side) or 'meta' (right-aligned), 'bold'/'muted' set text
// weight/color, 'width' pins a fixed-width column (omit for the flexible
// name/variety field), 'suffix' appends fixed text when the value is
// non-empty. A primary/meta field that's also the first half of an
// inline_with pair (e.g. amount+unit) renders as one combined chip using
// both fields' values — reusing that existing relationship instead of a
// second "these two go together" key. This is old-plugin-exact per-section
// styling (bold varies by field, hops bolds two fields where every other
// section bolds one, chip widths differ), expressed as schema data instead
// of six hand-written summary-building functions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_repeater_schemas()
//------------------------------------------------------------------------------
function brewlab_recipes_repeater_schemas() {
	return [

		'fermentables' => [
			'label'      => __( 'Fermentables', 'brewlab-recipes' ),
			'item_label' => __( 'Fermentable', 'brewlab-recipes' ),
			'fields'     => [
				'name'   => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
					'summary'  => [ 'slot' => 'primary', 'grow' => true ],
				],
				'link'   => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
				'amount' => [
					'type'        => 'number',
					'label'       => __( 'Amount', 'brewlab-recipes' ),
					'required'    => true,
					'inline_with' => 'unit',
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 80 ],
				],
				'unit'   => [
					'type'    => 'select',
					'label'   => __( 'Unit', 'brewlab-recipes' ),
					'options' => [
						'lb'  => __( 'lb', 'brewlab-recipes' ),
						'oz'  => __( 'oz', 'brewlab-recipes' ),
						'kg'  => __( 'kg', 'brewlab-recipes' ),
						'g'   => __( 'g', 'brewlab-recipes' ),
						'l'   => __( 'L', 'brewlab-recipes' ),
						'gal' => __( 'gal', 'brewlab-recipes' ),
					],
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
			],
		],

		'additions' => [
			'label'      => __( 'Other Additions', 'brewlab-recipes' ),
			'item_label' => __( 'Addition', 'brewlab-recipes' ),
			'fields'     => [
				'name'   => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
					'summary'  => [ 'slot' => 'primary', 'grow' => true ],
				],
				'link'   => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
				'amount' => [
					'type'        => 'number',
					'label'       => __( 'Amount', 'brewlab-recipes' ),
					'required'    => true,
					'inline_with' => 'unit',
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 80 ],
				],
				'unit'   => [
					'type'    => 'select',
					'label'   => __( 'Unit', 'brewlab-recipes' ),
					'options' => [
						'oz'   => __( 'oz', 'brewlab-recipes' ),
						'g'    => __( 'g', 'brewlab-recipes' ),
						'lb'   => __( 'lb', 'brewlab-recipes' ),
						'kg'   => __( 'kg', 'brewlab-recipes' ),
						'tsp'  => __( 'tsp', 'brewlab-recipes' ),
						'tbsp' => __( 'tbsp', 'brewlab-recipes' ),
						'cup'  => __( 'cup', 'brewlab-recipes' ),
						'ml'   => __( 'ml', 'brewlab-recipes' ),
						'l'    => __( 'L', 'brewlab-recipes' ),
						'each' => __( 'each', 'brewlab-recipes' ),
						'drop' => __( 'drop(s)', 'brewlab-recipes' ),
					],
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
					'summary' => [ 'slot' => 'meta', 'muted' => true ],
				],
			],
		],

		'hops' => [
			'label'      => __( 'Hops', 'brewlab-recipes' ),
			'item_label' => __( 'Hop', 'brewlab-recipes' ),
			'fields'     => [
				'variety' => [
					'type'     => 'text',
					'label'    => __( 'Variety', 'brewlab-recipes' ),
					'required' => true,
					'summary'  => [ 'slot' => 'primary', 'bold' => true, 'grow' => true ],
				],
				'link'    => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
				'amount'  => [
					'type'        => 'number',
					'label'       => __( 'Amount', 'brewlab-recipes' ),
					'required'    => true,
					'inline_with' => 'unit',
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 60 ],
				],
				'unit'    => [
					'type'    => 'select',
					'label'   => __( 'Unit', 'brewlab-recipes' ),
					'options' => [
						'oz' => __( 'oz', 'brewlab-recipes' ),
						'g'  => __( 'g', 'brewlab-recipes' ),
						'kg' => __( 'kg', 'brewlab-recipes' ),
						'lb' => __( 'lb', 'brewlab-recipes' ),
					],
				],
				'alpha'   => [
					'type'    => 'number',
					'label'   => __( 'Alpha Acid %', 'brewlab-recipes' ),
					'summary' => [ 'slot' => 'primary', 'muted' => true, 'suffix' => '%' ],
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
					'summary' => [ 'slot' => 'meta', 'muted' => true ],
				],
				'time'    => [
					'type'    => 'number',
					'label'   => __( 'Time (min)', 'brewlab-recipes' ),
					// Suffix depends on 'use' (dry hop is measured in days,
					// everything else in minutes) — the one case that
					// doesn't fit a static suffix, handled directly in
					// brewlab_recipes_repeater_item_summary().
					'summary' => [ 'slot' => 'meta', 'muted' => true ],
				],
			],
		],

		'yeast' => [
			'label'      => __( 'Yeast', 'brewlab-recipes' ),
			'item_label' => __( 'Yeast', 'brewlab-recipes' ),
			'fields'     => [
				'name'   => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
					'summary'  => [ 'slot' => 'primary', 'grow' => true ],
				],
				'link'   => [
					'type'  => 'url',
					'label' => __( 'Affiliate Link', 'brewlab-recipes' ),
				],
				'amount' => [
					'type'        => 'number',
					'label'       => __( 'Amount', 'brewlab-recipes' ),
					'required'    => true,
					'inline_with' => 'unit',
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 80 ],
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
			],
		],

		'mash_steps' => [
			'label'         => __( 'Mash Steps', 'brewlab-recipes' ),
			'item_label'    => __( 'Mash Step', 'brewlab-recipes' ),
			'profile_label' => __( 'Profile Name', 'brewlab-recipes' ),
			'fields'        => [
				'name'      => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
					'summary'  => [ 'slot' => 'primary', 'grow' => true ],
				],
				'type'      => [
					'type'    => 'select',
					'label'   => __( 'Type', 'brewlab-recipes' ),
					'options' => [
						'temperature' => __( 'Temperature Rest', 'brewlab-recipes' ),
						'infusion'    => __( 'Infusion', 'brewlab-recipes' ),
						'decoction'   => __( 'Decoction', 'brewlab-recipes' ),
					],
				],
				'temp'      => [
					'type'        => 'number',
					'label'       => __( 'Temp', 'brewlab-recipes' ),
					'inline_with' => 'temp_unit',
					'summary'     => [ 'slot' => 'meta', 'width' => 80 ],
				],
				'temp_unit' => [
					'type'         => 'select',
					'label'        => __( 'Temp Unit', 'brewlab-recipes' ),
					'widget'       => 'toggle',
					'options'      => [
						'f' => __( 'Fahrenheit (°F)', 'brewlab-recipes' ),
						'c' => __( 'Celsius (°C)', 'brewlab-recipes' ),
					],
					'short_labels' => [
						'f' => '°F',
						'c' => '°C',
					],
				],
				'time'      => [
					'type'    => 'number',
					'label'   => __( 'Time (min)', 'brewlab-recipes' ),
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'width' => 60, 'suffix' => ' min' ],
				],
			],
		],

		'fermentation_steps' => [
			'label'         => __( 'Fermentation Steps', 'brewlab-recipes' ),
			'item_label'    => __( 'Fermentation Step', 'brewlab-recipes' ),
			'profile_label' => __( 'Profile Name', 'brewlab-recipes' ),
			'fields'        => [
				'name'      => [
					'type'     => 'text',
					'label'    => __( 'Name', 'brewlab-recipes' ),
					'required' => true,
					'summary'  => [ 'slot' => 'primary', 'grow' => true ],
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
					'type'        => 'number',
					'label'       => __( 'Temp', 'brewlab-recipes' ),
					'inline_with' => 'temp_unit',
					'summary'     => [ 'slot' => 'meta', 'width' => 80 ],
				],
				'temp_unit' => [
					'type'         => 'select',
					'label'        => __( 'Temp Unit', 'brewlab-recipes' ),
					'widget'       => 'toggle',
					'options'      => [
						'f' => __( 'Fahrenheit (°F)', 'brewlab-recipes' ),
						'c' => __( 'Celsius (°C)', 'brewlab-recipes' ),
					],
					'short_labels' => [
						'f' => '°F',
						'c' => '°C',
					],
				],
				'days'      => [
					'type'    => 'number',
					'label'   => __( 'Days', 'brewlab-recipes' ),
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'width' => 70, 'suffix' => ' days' ],
				],
				'ramp'      => [
					'type'  => 'text',
					'label' => __( 'Ramp', 'brewlab-recipes' ),
				],
				'pressure'  => [
					'type'    => 'text',
					'label'   => __( 'Pressure', 'brewlab-recipes' ),
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'width' => 70, 'suffix' => ' PSI' ],
				],
			],
		],

	];
}
