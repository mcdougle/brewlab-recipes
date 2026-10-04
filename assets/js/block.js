/**
 * BrewLab Recipe Block
 *
 * Hand-written against wp.blocks/wp.element/wp.components — no build step,
 * matching the rest of this plugin's admin JS. Dynamic block: `save()`
 * returns null since recipe-card.php renders the actual markup server-side
 * from the stored recipeId, not from anything baked into post_content.
 */
( function ( blocks, element, components, apiFetch, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	// BrewLab logo, path data from block.php (read out of
	// assets/svg/brewlab-logo.svg). Unfilled outline/bubble paths take
	// currentColor; the liquid and white highlights keep the logo's colors.
	var iconData = window.brewlabRecipesBlockIcon;
	var icon = el(
		'svg',
		{ xmlns: 'http://www.w3.org/2000/svg', viewBox: iconData.viewBox, width: 24, height: 24 },
		iconData.paths.map( function ( path, index ) {
			return el( 'path', { key: index, fill: path.fill || 'currentColor', d: path.d } );
		} )
	);

	function Edit( props ) {
		var recipeId     = props.attributes.recipeId;
		var setAttributes = props.setAttributes;

		var recipesState = element.useState( [] );
		var recipes       = recipesState[ 0 ];
		var setRecipes    = recipesState[ 1 ];

		var loadingState = element.useState( true );
		var loading       = loadingState[ 0 ];
		var setLoading    = loadingState[ 1 ];

		element.useEffect( function () {
			apiFetch( { path: '/brewlab-recipes/v1/recipes' } )
				.then( function ( result ) {
					setRecipes( result );
					setLoading( false );
				} )
				.catch( function () {
					setLoading( false );
				} );
		}, [] );

		var options = [ { label: __( 'Select a recipe…', 'brewlab-recipes' ), value: 0 } ];
		recipes.forEach( function ( recipe ) {
			options.push( {
				label: recipe.title || __( '(no title)', 'brewlab-recipes' ),
				value: recipe.id,
			} );
		} );

		var selected = recipes.filter( function ( recipe ) {
			return recipe.id === recipeId;
		} )[ 0 ];

		return el(
			'div',
			{ className: 'brewlab-recipes-block-edit' },
			el( components.SelectControl, {
				label: __( 'Recipe', 'brewlab-recipes' ),
				value: recipeId,
				options: options,
				disabled: loading,
				onChange: function ( value ) {
					setAttributes( { recipeId: parseInt( value, 10 ) } );
				},
			} ),
			el(
				components.Placeholder,
				{
					icon: icon,
					label: __( 'BrewLab Recipe', 'brewlab-recipes' ),
				},
				selected
					? el( 'p', {}, selected.title )
					: el( 'p', {}, __( 'No recipe selected yet.', 'brewlab-recipes' ) )
			)
		);
	}

	blocks.registerBlockType( 'brewlab/recipe', {
		title: __( 'BrewLab Recipe', 'brewlab-recipes' ),
		icon: icon,
		category: 'widgets',
		attributes: {
			recipeId: {
				type: 'number',
				default: 0,
			},
		},
		edit: Edit,
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.apiFetch, window.wp.i18n );
