/**
 * Admin Repeater Rows
 *
 * Add/edit/delete behavior for the six repeater metaboxes (fermentables,
 * hops, etc.) on the recipe edit screen, backed by one shared modal instead
 * of live inline table inputs. Clicking anywhere on a row opens it in the
 * modal — there's no separate Edit button — and Delete lives in the modal
 * footer (shown only while editing) instead of on the row itself.
 *
 * Each row's real submittable values live in hidden inputs inside its <li>
 * — this file only ever edits those directly; the modal's visible fields
 * are a template cloned into it and copied to/from on open/save. That keeps
 * every field's POSTed name (brewlab_recipes_repeater[section][index][field])
 * exactly what it was before this rewrite, so includes/admin/save.php needed
 * no changes for the row data itself.
 *
 * The index counter only ever increments, even across deletions — reusing a
 * removed row's index would make a later row's inputs share its array index
 * and silently overwrite each other in $_POST on save.
 *
 * A toggle-widget field (e.g. temp_unit's °F/°C pair) is a hidden input
 * carrying data-field like any other modal field — the visible buttons
 * are UI only, writing into that hidden input on click — so the generic
 * value-sync code below needs no special case for it. It does need its own
 * visual (which button looks active) synced separately after the hidden
 * input's value changes underneath it, both right after the fields-template
 * clones in and right after populating from an existing row.
 */
( function () {
	'use strict';

	var modal, modalTitle, modalBody, modalSaveButton, modalDeleteButton;
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

		modalTitle         = modal.querySelector( '.brewlab-recipes-repeater-modal__title' );
		modalBody          = modal.querySelector( '.brewlab-recipes-repeater-modal__body' );
		modalSaveButton    = modal.querySelector( '.brewlab-recipes-repeater-modal__save' );
		modalDeleteButton  = modal.querySelector( '.brewlab-recipes-repeater-modal__delete' );

		modalSaveButton.addEventListener( 'click', onModalSave );
		modalDeleteButton.addEventListener( 'click', onModalDelete );
		modal.querySelector( '.brewlab-recipes-repeater-modal__close' ).addEventListener( 'click', closeModal );
		modal.querySelector( '.brewlab-recipes-repeater-modal__backdrop' ).addEventListener( 'click', closeModal );
	}

	function openModal( container, item ) {
		activeContainer = container;
		activeItem      = item;

		var template = container.querySelector( '.brewlab-recipes-repeater__fields-template' );
		modalBody.innerHTML = '';
		modalBody.appendChild( template.content.cloneNode( true ) );
		syncToggleButtons( modalBody );

		var label = container.getAttribute( 'data-item-label' );
		modalTitle.textContent = ( item ? 'Edit ' : 'Add ' ) + label;
		modalDeleteButton.style.display = item ? '' : 'none';

		if ( item ) {
			item.querySelectorAll( '.brewlab-recipes-repeater__item-field' ).forEach( function ( hidden ) {
				var field = modalBody.querySelector( '[data-field="' + hidden.getAttribute( 'data-field' ) + '"]' );
				if ( field ) {
					field.value = hidden.value;
				}
			} );
			syncToggleButtons( modalBody );
		}

		modal.style.display = 'block';
	}

	// Makes every .brewlab-recipes-toggle's active button (and its hidden
	// input's data-label) match its hidden input's current value — needed
	// after that value gets set some other way (template clone, populating
	// from an existing row) rather than by clicking a toggle button directly.
	function syncToggleButtons( scope ) {
		scope.querySelectorAll( '.brewlab-recipes-toggle' ).forEach( function ( toggle ) {
			var hidden  = toggle.querySelector( 'input[type="hidden"]' );
			var buttons = toggle.querySelectorAll( '.brewlab-recipes-toggle__option' );
			buttons.forEach( function ( btn ) {
				var active = btn.getAttribute( 'data-value' ) === hidden.value;
				btn.classList.toggle( 'is-active', active );
				if ( active ) {
					hidden.setAttribute( 'data-label', btn.textContent );
				}
			} );
		} );
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
			} else if ( field.hasAttribute( 'data-label' ) ) {
				displayValue = field.getAttribute( 'data-label' );
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

	function onModalDelete() {
		if ( activeItem ) {
			activeItem.remove();
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

		var toggleOption = event.target.closest( '.brewlab-recipes-toggle__option' );
		if ( toggleOption ) {
			event.preventDefault();
			var toggle = toggleOption.closest( '.brewlab-recipes-toggle' );
			toggle.querySelector( 'input[type="hidden"]' ).value = toggleOption.getAttribute( 'data-value' );
			syncToggleButtons( toggle.parentNode );
			return;
		}

		var item = event.target.closest( '.brewlab-recipes-repeater__item' );
		if ( item ) {
			openModal( item.closest( '.brewlab-recipes-repeater' ), item );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', initModal );
} )();
