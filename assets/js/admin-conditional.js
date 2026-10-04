/**
 * Conditional Fields
 *
 * Three independent controllers on the same file since all are "recipe edit
 * screen field visibility" — brew-type business logic (hops/mash/boil-time/
 * IBU) below, a fully generic depends_on mechanism (any field with a
 * 'depends_on' schema key, e.g. "Other Type Name" only mattering when Brew
 * Type is "Other") after it, which isn't specific to brew type at all, and
 * the Water box's collapsible water profile panel at the bottom.
 *
 * Brew-Type-Conditional Fields — beer always includes hops and a mash, so
 * Show Hops/Show Mash Profile (the Options sidebar box) only mean anything
 * for a non-beer recipe: for "beer" the whole Options box hides and the
 * Hops/Mash Steps metaboxes are forced visible regardless of the
 * checkboxes. Boil Time and IBU are a simpler case — beer-only fields, no
 * override, just hidden outright for anything else.
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

//------------------------------------------------------------------------------
// Generic depends_on controller — any row simple-fields.php rendered with a
// 'depends_on' schema key (currently "Other Type Name" depending on Brew
// Type = "Other", and "Custom Author Name" depending on Show Author As =
// "Custom Name"). PHP already computes each row's correct display on load
// (see brewlab_recipes_render_conditional_row_attrs()); this only has to
// react when the controlling field changes afterward.
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.brewlab-recipes-conditional' ).forEach( function ( row ) {
			var controller = document.getElementById( row.getAttribute( 'data-depends-on' ) );
			var expected   = row.getAttribute( 'data-depends-value' );
			if ( ! controller ) {
				return;
			}

			controller.addEventListener( 'change', function () {
				row.style.display = ( controller.value === expected ) ? '' : 'none';
			} );
		} );
	} );
} )();

//------------------------------------------------------------------------------
// Water profile panel — the optional source/target profile inside the Water
// box (see brewlab_recipes_render_water_profile_box()). PHP opens it on load
// when anything is saved; this only handles opening and removing afterward.
// Remove blanks every input rather than just hiding the panel, so the next
// save clears the stored values instead of keeping data nobody can see.
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var profile = document.querySelector( '.brewlab-recipes-water-profile' );
		if ( ! profile ) {
			return;
		}

		var openButton   = profile.querySelector( '.brewlab-recipes-water-profile__open' );
		var removeButton = profile.querySelector( '.brewlab-recipes-water-profile__remove' );
		var panel        = profile.querySelector( '.brewlab-recipes-water-profile__panel' );

		openButton.addEventListener( 'click', function () {
			profile.classList.add( 'is-open' );
			panel.querySelector( 'select' ).focus();
		} );

		removeButton.addEventListener( 'click', function () {
			panel.querySelectorAll( 'input:not([type="hidden"]), select' ).forEach( function ( field ) {
				field.value = '';
			} );
			// Back to the default Target/Measured choice through a real click,
			// so admin-repeater.js's toggle handler also moves the active
			// button highlight, not just the hidden value.
			var defaultKind = panel.querySelector( '.brewlab-recipes-toggle__option' );
			if ( defaultKind ) {
				defaultKind.click();
			}

			profile.classList.remove( 'is-open' );
			openButton.focus();
		} );
	} );
} )();
