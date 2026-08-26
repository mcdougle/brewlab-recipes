/**
 * Media & Color Pickers
 *
 * Wires the Media metabox's two fields to WP's built-in pickers instead of
 * the plain attachment-ID/hex-text inputs simple-fields.php fell back to
 * before this existed: wp.media for the recipe image, wp-color-picker
 * (WP core's bundled Iris picker) for the recipe color. Neither field's
 * name/value contract changes — save.php already expects a hidden input
 * holding an attachment ID and a text input holding a hex string, so this
 * file only has to keep those inputs in sync with the picker UI.
 */
( function ( $ ) {
	'use strict';

	function initColorPickers() {
		$( '.brewlab-recipes-color-picker' ).wpColorPicker();
	}

	function initMediaFields() {
		$( '.brewlab-recipes-media-field' ).each( function () {
			var $field   = $( this );
			var $input   = $field.find( 'input[type="hidden"]' );
			var $preview = $field.find( '.brewlab-recipes-media-field__preview' );
			var $select  = $field.find( '.brewlab-recipes-media-field__select' );
			var $remove  = $field.find( '.brewlab-recipes-media-field__remove' );
			var frame;

			$select.on( 'click', function ( event ) {
				event.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: brewlabRecipesMedia.selectTitle,
					button: { text: brewlabRecipesMedia.selectButton },
					library: { type: 'image' },
					multiple: false,
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var previewUrl = ( attachment.sizes && attachment.sizes.thumbnail )
						? attachment.sizes.thumbnail.url
						: attachment.url;

					$input.val( attachment.id );
					$preview.attr( 'src', previewUrl ).show();
					$select.hide();
					$remove.show();
				} );

				frame.open();
			} );

			$remove.on( 'click', function ( event ) {
				event.preventDefault();
				$input.val( '' );
				$preview.attr( 'src', '' ).hide();
				$remove.hide();
				$select.show();
			} );
		} );
	}

	$( function () {
		initColorPickers();
		initMediaFields();
	} );
} )( jQuery );
