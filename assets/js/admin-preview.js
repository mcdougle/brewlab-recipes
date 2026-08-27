/**
 * Recipe Preview
 *
 * "Preview Recipe Card" button in the Publish box — POSTs the entire edit
 * form to brewlab_recipes_ajax_preview_recipe() (includes/admin/preview.php)
 * and drops the rendered card into a modal, so a change can be checked
 * without saving first and then hunting down wherever the recipe is
 * embedded. Serializing the whole #post form (rather than hand-picking
 * fields) means every simple field and every repeater row's hidden inputs
 * are already exactly what a real save would see — nothing here needs to
 * know the field list and stay in sync with it.
 */
( function () {
	'use strict';

	function openModal( html ) {
		var modal = document.querySelector( '.brewlab-recipes-preview-modal' );
		if ( ! modal ) {
			return;
		}
		var body = modal.querySelector( '.brewlab-recipes-preview-modal__body' );
		body.innerHTML = html;

		// recipe-card.js only wires up cards present at DOMContentLoaded —
		// one just got injected well after that, so its tabs, unit toggle,
		// and batch scaler need to be initialized here explicitly or none
		// of them would do anything.
		var card = body.querySelector( '.brewlab-recipes-card' );
		if ( card && window.brewlabRecipesInitCard ) {
			window.brewlabRecipesInitCard( card );
		}

		modal.style.display = 'block';
	}

	function closeModal() {
		var modal = document.querySelector( '.brewlab-recipes-preview-modal' );
		if ( modal ) {
			modal.style.display = 'none';
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var btn = document.getElementById( 'brewlab-recipes-preview-btn' );
		if ( ! btn ) {
			return;
		}

		var modal = document.querySelector( '.brewlab-recipes-preview-modal' );
		if ( modal ) {
			modal.querySelector( '.brewlab-recipes-preview-modal__close' ).addEventListener( 'click', closeModal );
			modal.querySelector( '.brewlab-recipes-preview-modal__backdrop' ).addEventListener( 'click', closeModal );
		}

		btn.addEventListener( 'click', function () {
			var form = document.getElementById( 'post' );
			if ( ! form ) {
				return;
			}

			var originalText = btn.textContent;
			btn.disabled     = true;
			btn.textContent  = brewlabRecipesPreview.loadingText;

			var body = new URLSearchParams( new FormData( form ) );
			body.set( 'action', 'brewlab_recipes_preview_recipe' );

			fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) { return response.json(); } )
				.then( function ( result ) {
					if ( result && result.success ) {
						openModal( result.data.html );
					} else {
						openModal( '<p>' + brewlabRecipesPreview.errorText + '</p>' );
					}
				} )
				.catch( function () {
					openModal( '<p>' + brewlabRecipesPreview.errorText + '</p>' );
				} )
				.finally( function () {
					btn.disabled    = false;
					btn.textContent = originalText;
				} );
		} );
	} );
} )();
