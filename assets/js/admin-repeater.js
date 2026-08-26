/**
 * Admin Repeater Rows
 *
 * Add/remove-row behavior for the six repeater metaboxes (fermentables,
 * hops, etc.) on the recipe edit screen. The index counter only ever
 * increments, even across removals — reusing a removed row's index would
 * make a later row's inputs share its array index and silently overwrite
 * each other in $_POST on save.
 */
( function () {
	'use strict';

	function nextIndex( container ) {
		var next = parseInt( container.getAttribute( 'data-next-index' ), 10 );
		if ( isNaN( next ) ) {
			next = 0;
		}
		container.setAttribute( 'data-next-index', next + 1 );
		return next;
	}

	document.addEventListener( 'click', function ( event ) {
		var addButton = event.target.closest( '.brewlab-recipes-repeater__add' );
		if ( addButton ) {
			event.preventDefault();

			var container = addButton.closest( '.brewlab-recipes-repeater' );
			var tbody     = container.querySelector( '.brewlab-recipes-repeater__rows' );
			var template  = container.querySelector( '.brewlab-recipes-repeater__row-template' );
			var index     = nextIndex( container );
			var row       = template.cloneNode( true );

			row.classList.remove( 'brewlab-recipes-repeater__row-template' );
			row.innerHTML = row.innerHTML.split( '__INDEX__' ).join( index );

			tbody.appendChild( row );
			return;
		}

		var removeButton = event.target.closest( '.brewlab-recipes-repeater__remove' );
		if ( removeButton ) {
			event.preventDefault();
			removeButton.closest( '.brewlab-recipes-repeater__row' ).remove();
		}
	} );
} )();
