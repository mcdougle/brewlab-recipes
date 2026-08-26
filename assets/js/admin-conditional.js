/**
 * Brew-Type-Conditional Fields
 *
 * Beer always includes hops and a mash — Show Hops/Show Mash Profile (the
 * Options sidebar box) only mean anything for a non-beer recipe, so for
 * "beer" the whole Options box hides and the Hops/Mash Steps metaboxes are
 * forced visible regardless of the checkboxes. Boil Time and IBU are a
 * simpler case — beer-only fields, no override, just hidden outright for
 * anything else.
 *
 * One centralized controller instead of a listener per affected box: same
 * end result as scattering the logic across each box's own render callback,
 * without tying the generic repeater renderer to two specific sections.
 * Trade-off: runs once on DOMContentLoaded rather than synchronously as
 * each box streams in, so there's a brief on-load flash of the wrong state
 * before this corrects it — acceptable on an admin-only screen.
 */
( function () {
	'use strict';

	function isBeer() {
		var select = document.getElementById( 'brewlab-recipes-brew-type' );
		return !! select && 'beer' === select.value;
	}

	function apply() {
		var beer = isBeer();

		document.querySelectorAll( '.brewlab-recipes-beer-only' ).forEach( function ( el ) {
			el.style.display = beer ? '' : 'none';
		} );

		var optionsBox = document.getElementById( 'brewlab_recipes_options' );
		if ( optionsBox ) {
			optionsBox.style.display = beer ? 'none' : '';
		}

		var showHops = document.querySelector( 'input[name="brewlab_recipes_show_hops"]' );
		var showMash = document.querySelector( 'input[name="brewlab_recipes_show_mash"]' );
		var hopsBox  = document.getElementById( 'brewlab_recipes_hops' );
		var mashBox  = document.getElementById( 'brewlab_recipes_mash_steps' );

		if ( hopsBox ) {
			hopsBox.style.display = ( beer || ( showHops && showHops.checked ) ) ? '' : 'none';
		}
		if ( mashBox ) {
			mashBox.style.display = ( beer || ( showMash && showMash.checked ) ) ? '' : 'none';
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var brewType = document.getElementById( 'brewlab-recipes-brew-type' );
		var showHops = document.querySelector( 'input[name="brewlab_recipes_show_hops"]' );
		var showMash = document.querySelector( 'input[name="brewlab_recipes_show_mash"]' );

		if ( brewType ) {
			brewType.addEventListener( 'change', apply );
		}
		if ( showHops ) {
			showHops.addEventListener( 'change', apply );
		}
		if ( showMash ) {
			showMash.addEventListener( 'change', apply );
		}

		apply();
	} );
} )();
