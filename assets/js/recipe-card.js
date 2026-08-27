/**
 * Recipe Card
 *
 * Tab switching, the unit-conversion engine (weight/volume/temp), and the
 * live batch-size scaler for the front-end recipe card. Ported from
 * wp-brewtools-recipes/templates/recipe-card.php's inline <script>, split
 * into an external file (wp_enqueue_script, see includes/enqueue.php)
 * instead of an inline block per card — this project enqueues assets
 * rather than printing them inline. Since one page can embed more than one
 * recipe, initRecipeCard() runs once per .brewlab-recipes-card found,
 * instead of the old plugin's single per-card inline closure.
 *
 * Reads the data-base/data-unit/data-type attributes templates/recipe-card.php
 * writes onto .brewlab-recipes-qty and .brewlab-recipes-unit-label elements —
 * see that file for what each attribute means.
 */
( function () {
	'use strict';

	// All weights route through grams as the common base.
	var TO_G   = { g: 1, kg: 1000, oz: 28.3495, lb: 453.592 };
	var FROM_G = { g: 1, kg: 0.001, oz: 0.035274, lb: 0.0022046 };
	// All volumes route through litres.
	var TO_L   = { litres: 1, gallons: 3.78541 };
	var FROM_L = { litres: 1, gallons: 0.264172 };

	// Pick the best target unit for a weight, based on original unit +
	// target system. Fermentables stored as lb/kg stay large-unit; hops/
	// yeast stored as oz/g stay small-unit.
	function targetWeightUnit( origUnit, system ) {
		var large = ( system === 'us' ) ? 'lb' : 'kg';
		var small = ( system === 'us' ) ? 'oz' : 'g';
		return ( origUnit === 'lb' || origUnit === 'kg' ) ? large : small;
	}

	function convertWeight( val, fromUnit, toUnit ) {
		if ( ! fromUnit || ! toUnit || fromUnit === toUnit ) return val;
		return val * ( TO_G[ fromUnit ] || 1 ) * ( FROM_G[ toUnit ] || 1 );
	}
	function convertVolume( val, fromUnit, toUnit ) {
		if ( ! fromUnit || ! toUnit || fromUnit === toUnit ) return val;
		return val * ( TO_L[ fromUnit ] || 1 ) * ( FROM_L[ toUnit ] || 1 );
	}
	function convertTemp( valF, system ) {
		return system === 'metric' ? Math.round( ( valF - 32 ) * 5 / 9 * 10 ) / 10 : valF;
	}

	function fmt( val ) {
		if ( val >= 1000 ) return Math.round( val ).toString();
		if ( val >= 100 )  return ( Math.round( val * 10 ) / 10 ).toString();
		if ( val >= 10 )   return ( Math.round( val * 10 ) / 10 ).toString();
		return ( Math.round( val * 100 ) / 100 ).toString();
	}

	// Yeast pitch amount isn't a linear function of batch size, so scaling
	// it to arbitrary decimal precision like a weight would imply a false
	// exactness. For packets specifically, round to the nearest half — good
	// enough to stop a big batch-size change from leaving the reader with a
	// laughably undersized pitch, without pretending "1.37 packets" means
	// anything. Never round down to zero packets. Other yeast units (grams,
	// mL, billion cells) scale like any other weight/volume quantity.
	function fmtYeast( val, unit ) {
		if ( unit === 'pkg' ) {
			return Math.max( Math.round( val * 2 ) / 2, 0.5 ).toString();
		}
		return fmt( val );
	}

	function initRecipeCard( card ) {
		var authorSystem  = card.dataset.authorSystem || 'us';
		var currentSystem = 'author';

		// ── Tab switching ───────────────────────────────────────────────
		card.querySelectorAll( '.brewlab-recipes-tab-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var tid = btn.dataset.tab;
				card.querySelectorAll( '.brewlab-recipes-tab-btn' ).forEach( function ( b ) {
					b.classList.remove( 'is-active' );
					b.setAttribute( 'aria-selected', 'false' );
				} );
				card.querySelectorAll( '.brewlab-recipes-tab-panel' ).forEach( function ( p ) {
					p.classList.remove( 'is-active' );
				} );
				btn.classList.add( 'is-active' );
				btn.setAttribute( 'aria-selected', 'true' );
				var panel = document.getElementById( tid );
				if ( panel ) panel.classList.add( 'is-active' );
			} );
		} );

		function resolvedSystem( sys ) {
			return sys === 'author' ? authorSystem : sys;
		}

		function applySystem( sys ) {
			var res = resolvedSystem( sys );

			card.querySelectorAll( '.brewlab-recipes-qty' ).forEach( function ( el ) {
				var base = parseFloat( el.dataset.base );
				var unit = el.dataset.unit;
				var type = el.dataset.type;
				if ( isNaN( base ) ) return;

				if ( type === 'temp' ) {
					el.textContent = ( sys === 'author' ) ? base : convertTemp( base, res );
					return;
				}
				if ( type === 'weight' ) {
					if ( sys === 'author' ) { el.textContent = fmt( base ); return; }
					el.textContent = fmt( convertWeight( base, unit, targetWeightUnit( unit, res ) ) );
					return;
				}
				if ( type === 'yeast' ) {
					// Not part of the US/Metric toggle — the unit set mixes
					// weight/volume/count/cell-count with no shared conversion
					// basis — so this always just redisplays the base value here.
					// The batch scaler (scaleAll(), below) is what actually
					// changes it.
					el.textContent = fmt( base );
					return;
				}
			} );

			card.querySelectorAll( '.brewlab-recipes-unit-label' ).forEach( function ( el ) {
				var key   = ( sys === 'author' ) ? 'author' : sys;
				var label = el.dataset[ key ] || el.dataset.author || '';
				if ( el.dataset.author === '°F' || el.dataset.author === '°C' ) {
					label = res === 'metric' ? '°C' : '°F';
				}
				el.textContent = label;
			} );

			var batchInput = card.querySelector( '.brewlab-recipes-batch-input' );
			if ( batchInput ) {
				var baseVal  = parseFloat( batchInput.dataset.base );
				var baseUnit = batchInput.dataset.baseUnit || 'gallons';
				var newUnit  = ( sys === 'author' ) ? baseUnit : ( res === 'us' ? 'gallons' : 'litres' );

				// Re-express whatever batch size is CURRENTLY showing — which
				// may be a size the reader typed in, not the recipe's
				// original one — in the newly selected unit, rather than
				// resetting to the recipe's original size. A reader who
				// dialed the scaler up to "20 gallons" and then clicks
				// Metric should see ~75.7 L, not the original batch size
				// converted to litres.
				var currentVal  = parseFloat( batchInput.value );
				var currentUnit = batchInput.dataset.currentUnit || baseUnit;
				var dispVal     = ( ! currentVal || currentVal <= 0 )
					? ( sys === 'author' ? baseVal : convertVolume( baseVal, baseUnit, newUnit ) )
					: convertVolume( currentVal, currentUnit, newUnit );

				batchInput.value               = fmt( dispVal );
				batchInput.dataset.currentUnit = newUnit;
				batchInput.step                = ( sys === 'author' ) ? 0.5 : ( res === 'metric' ? 1 : 0.5 );
			}

			card.querySelectorAll( '.brewlab-recipes-unit-btn' ).forEach( function ( btn ) {
				btn.classList.toggle( 'is-active', btn.dataset.system === sys );
			} );
		}

		// ── Batch scaler ────────────────────────────────────────────────
		var batchInput = card.querySelector( '.brewlab-recipes-batch-input' );
		if ( batchInput ) {
			var baseBatch     = parseFloat( batchInput.dataset.base ) || 1;
			var baseBatchUnit = batchInput.dataset.baseUnit || 'gallons';

			var getDisplayBase = function () {
				if ( currentSystem === 'author' ) return baseBatch;
				var res   = resolvedSystem( currentSystem );
				var toVol = res === 'us' ? 'gallons' : 'litres';
				return convertVolume( baseBatch, baseBatchUnit, toVol );
			};

			var scaleAll = function ( inputVal ) {
				if ( ! inputVal || inputVal <= 0 ) return;
				var ratio = inputVal / getDisplayBase();
				var res   = resolvedSystem( currentSystem );

				card.querySelectorAll( '.brewlab-recipes-qty' ).forEach( function ( el ) {
					var type = el.dataset.type;
					if ( type === 'temp' ) return;
					var base = parseFloat( el.dataset.base );
					var unit = el.dataset.unit;
					if ( isNaN( base ) ) return;

					if ( type === 'yeast' ) {
						el.textContent = fmtYeast( base * ratio, unit );
						return;
					}

					var converted = ( currentSystem === 'author' )
						? base
						: convertWeight( base, unit, targetWeightUnit( unit, res ) );
					el.textContent = fmt( converted * ratio );
				} );
			};

			batchInput.addEventListener( 'input', function () {
				var v = parseFloat( batchInput.value );
				if ( v > 0 ) scaleAll( v );
			} );
			batchInput.addEventListener( 'blur', function () {
				var v = parseFloat( batchInput.value );
				if ( ! v || v <= 0 ) { batchInput.value = fmt( getDisplayBase() ); scaleAll( getDisplayBase() ); }
			} );

			card.querySelectorAll( '.brewlab-recipes-unit-btn' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					currentSystem = btn.dataset.system;
					applySystem( currentSystem );
					var v = parseFloat( batchInput.value );
					if ( v > 0 ) scaleAll( v );
				} );
			} );
		} else {
			card.querySelectorAll( '.brewlab-recipes-unit-btn' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					currentSystem = btn.dataset.system;
					applySystem( currentSystem );
				} );
			} );
		}

		applySystem( 'author' );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.brewlab-recipes-card' ).forEach( initRecipeCard );
	} );

	// Exposed so anything that injects a card into the DOM after page load
	// — the admin "Preview Recipe Card" modal (admin-preview.js), so far
	// the only such case — can wire it up the same way, instead of a
	// second copy of this file's init logic or the card silently sitting
	// there with no tabs/toggle/scaler working.
	window.brewlabRecipesInitCard = initRecipeCard;
} )();
