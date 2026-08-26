/**
 * Admin Repeater Rows
 *
 * Add/edit/remove behavior for the six repeater metaboxes (fermentables,
 * hops, etc.) on the recipe edit screen, backed by one shared modal instead
 * of live inline table inputs. Each row's real submittable values live in
 * hidden inputs inside its <li> — this file only ever edits those directly;
 * the modal's visible fields are a template cloned into it and copied
 * to/from on open/save. That keeps every field's POSTed name
 * (brewlab_recipes_repeater[section][index][field]) exactly what it was
 * before this rewrite, so includes/admin/save.php needed no changes.
 *
 * The index counter only ever increments, even across removals — reusing a
 * removed row's index would make a later row's inputs share its array index
 * and silently overwrite each other in $_POST on save.
 */
( function () {
	'use strict';

	var modal, modalTitle, modalBody, modalSaveButton;
	var activeContainer = null;
	var activeItem       = null; // null while adding; the <li> being edited otherwise.

	function nextIndex( container ) {
		var next = parseInt( container.getAttribute( 'data-next-index' ), 10 );
		if ( isNaN( next ) ) {
			next = 0;
		}
		container.setAttribute( 'data-next-index', next + 1 );
		return next;
	}

	function initModal() {
		modal = document.querySelector( '.brewlab-recipes-repeater-modal' );
		if ( ! modal ) {
			return;
		}

		modalTitle      = modal.querySelector( '.brewlab-recipes-repeater-modal__title' );
		modalBody       = modal.querySelector( '.brewlab-recipes-repeater-modal__body' );
		modalSaveButton = modal.querySelector( '.brewlab-recipes-repeater-modal__save' );

		modalSaveButton.addEventListener( 'click', onModalSave );
		modal.querySelector( '.brewlab-recipes-repeater-modal__cancel' ).addEventListener( 'click', closeModal );
		modal.querySelector( '.brewlab-recipes-repeater-modal__close' ).addEventListener( 'click', closeModal );
		modal.querySelector( '.brewlab-recipes-repeater-modal__backdrop' ).addEventListener( 'click', closeModal );
	}

	function openModal( container, item ) {
		activeContainer = container;
		activeItem      = item;

		var template = container.querySelector( '.brewlab-recipes-repeater__fields-template' );
		modalBody.innerHTML = '';
		modalBody.appendChild( template.content.cloneNode( true ) );

		var label = container.getAttribute( 'data-label' );
		modalTitle.textContent = ( item ? 'Edit ' : 'Add ' ) + label;

		if ( item ) {
			item.querySelectorAll( '.brewlab-recipes-repeater__item-field' ).forEach( function ( hidden ) {
				var field = modalBody.querySelector( '[data-field="' + hidden.getAttribute( 'data-field' ) + '"]' );
				if ( field ) {
					field.value = hidden.value;
				}
			} );
		}

		modal.style.display = 'block';
	}

	function closeModal() {
		modal.style.display = 'none';
		activeContainer = null;
		activeItem      = null;
	}

	function onModalSave() {
		var container = activeContainer;
		var item       = activeItem;
		var isNew      = ! item;

		if ( isNew ) {
			var itemTemplate = container.querySelector( '.brewlab-recipes-repeater__item-template' );
			var fragment      = itemTemplate.content.cloneNode( true );
			item = fragment.querySelector( '.brewlab-recipes-repeater__item' );

			var index = nextIndex( container );
			item.querySelectorAll( '[name]' ).forEach( function ( input ) {
				input.name = input.name.split( '__INDEX__' ).join( index );
			} );
		}

		// Copy modal field values into the item's hidden inputs, and build
		// its summary from the same fields in the same pass — a 'select'
		// field's summary text is its chosen option's label (read straight
		// off the modal <select>, same value repeater-data.php's
		// brewlab_recipes_repeater_cell_value() would produce server-side),
		// not the raw stored value.
		var summaryParts = [];
		modalBody.querySelectorAll( '[data-field]' ).forEach( function ( field ) {
			var key    = field.getAttribute( 'data-field' );
			var hidden = item.querySelector( '.brewlab-recipes-repeater__item-field[data-field="' + key + '"]' );
			if ( ! hidden ) {
				return;
			}
			hidden.value = field.value;

			if ( 'link' === key || summaryParts.length >= 3 ) {
				return;
			}
			var displayValue = field.value;
			if ( 'SELECT' === field.tagName && field.selectedIndex >= 0 ) {
				displayValue = field.options[ field.selectedIndex ].text;
			}
			if ( displayValue ) {
				summaryParts.push( displayValue );
			}
		} );

		item.querySelector( '.brewlab-recipes-repeater__item-summary' ).textContent =
			summaryParts.length ? summaryParts.join( ' ' ) : '(empty)';

		if ( isNew ) {
			container.querySelector( '.brewlab-recipes-repeater__list' ).appendChild( item );
		}

		closeModal();
	}

	document.addEventListener( 'click', function ( event ) {
		var addButton = event.target.closest( '.brewlab-recipes-repeater__add' );
		if ( addButton ) {
			event.preventDefault();
			openModal( addButton.closest( '.brewlab-recipes-repeater' ), null );
			return;
		}

		var editButton = event.target.closest( '.brewlab-recipes-repeater__item-edit' );
		if ( editButton ) {
			event.preventDefault();
			var item = editButton.closest( '.brewlab-recipes-repeater__item' );
			openModal( item.closest( '.brewlab-recipes-repeater' ), item );
			return;
		}

		var removeButton = event.target.closest( '.brewlab-recipes-repeater__item-remove' );
		if ( removeButton ) {
			event.preventDefault();
			removeButton.closest( '.brewlab-recipes-repeater__item' ).remove();
		}
	} );

	document.addEventListener( 'DOMContentLoaded', initModal );
} )();
