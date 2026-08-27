<?php
//------------------------------------------------------------------------------
//   Repeater Schemas
//------------------------------------------------------------------------------
// Declares the field shape for every JSON-array meta field on a recipe:
// fermentables, additions, hops, yeast, mash steps, and fermentation steps.
// This is the single source of truth for those six data shapes — the admin
// repeater UI, the front-end template, and the save handler should all read
// field definitions and select-option labels from here instead of each
// redefining their own copy, which would let the six sections drift out of
// sync with each other over time.
//
// A field's 'summary' key drives the admin row summary (see
// brewlab_recipes_render_repeater_item() in repeater-field.php): 'slot' is
// 'primary' (left side) or 'meta' (right-aligned), 'bold'/'muted' set text
// weight/color, 'width' pins a fixed-width column (omit it for a chip that
// should sit at its own natural size, like name/variety), 'suffix' appends
// fixed text when the value is non-empty, and 'order' sets display order
// within its slot (lower first) — needed because the summary row's visual
// order isn't always the schema's field-declaration order (every
// ingredient section leads its primary chips with amount+unit, not name,
// even though name is declared first for the modal's sake). No individual
// chip grows to fill space — only the primary/meta containers do (see the
// CSS): each chip's own span has no flex property, only the row's
// left-hand/right-hand wrapper does, which keeps a multi-chip group (e.g.
// hops: amount, variety, alpha) packed tightly together instead of one
// chip stretching and shoving the ones after it away. A primary/meta field
// that's also the first half of an inline_with pair (e.g. amount+unit)
// renders as one combined chip using both fields' values — reusing that
// existing relationship instead of a second "these two go together" key.
// Per-section styling varies by design (bold varies by field, hops bolds
// two fields where every other section bolds one, chip widths and order
// differ) — expressed as schema data here instead of six hand-written
// summary-building functions.

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
					'summary'  => [ 'slot' => 'primary', 'order' => 2 ],
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
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 80, 'order' => 1 ],
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
					'summary'  => [ 'slot' => 'primary', 'order' => 2 ],
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
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 80, 'order' => 1 ],
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
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'order' => 1 ],
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
					'summary'  => [ 'slot' => 'primary', 'bold' => true, 'order' => 2 ],
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
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 60, 'order' => 1 ],
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
					'summary' => [ 'slot' => 'primary', 'muted' => true, 'suffix' => '%', 'order' => 3 ],
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
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'order' => 2 ],
				],
				'time'    => [
					'type'    => 'number',
					'label'   => __( 'Time (min)', 'brewlab-recipes' ),
					// Suffix depends on 'use' (dry hop is measured in days,
					// everything else in minutes) — the one case that
					// doesn't fit a static suffix, handled directly in
					// brewlab_recipes_repeater_item_summary().
					//
					// order:1 (before 'use') so the composite summary string
					// reads "60 min · Boil" — time first, then use.
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'order' => 1 ],
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
					'summary'  => [ 'slot' => 'primary', 'order' => 2 ],
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
					'summary'     => [ 'slot' => 'primary', 'bold' => true, 'width' => 80, 'order' => 1 ],
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
					'summary'  => [ 'slot' => 'primary' ],
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
					'summary'     => [ 'slot' => 'meta', 'width' => 80, 'order' => 1 ],
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
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'width' => 60, 'suffix' => ' min', 'order' => 2 ],
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
					'summary'  => [ 'slot' => 'primary' ],
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
					'summary'     => [ 'slot' => 'meta', 'width' => 80, 'order' => 1 ],
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
				// order 2/3 (pressure before days) sets the summary's display
				// order deliberately — days is declared first in this array
				// only because that's the modal's field order.
				'days'      => [
					'type'    => 'number',
					'label'   => __( 'Days', 'brewlab-recipes' ),
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'width' => 70, 'suffix' => ' days', 'order' => 3 ],
				],
				'ramp'      => [
					'type'  => 'text',
					'label' => __( 'Ramp', 'brewlab-recipes' ),
				],
				'pressure'  => [
					'type'    => 'text',
					'label'   => __( 'Pressure', 'brewlab-recipes' ),
					'hint'    => 'PSI',
					'summary' => [ 'slot' => 'meta', 'muted' => true, 'width' => 70, 'suffix' => ' PSI', 'order' => 2 ],
				],
			],
		],

	];
}
