/**
 * Media & Color Pickers
 *
 * Wires the Media metabox's two fields: wp.media for the recipe image, and
 * a hand-rolled canvas HSV picker for the recipe color — the old plugin's
 * exact picker (SV square + hue strip + hex + presets), ported faithfully
 * rather than rebuilt from scratch, since it's real interaction code (drag
 * handling, HSV math) worth getting right the first time. Neither field's
 * name/value contract changes — save.php already expects a hidden input
 * holding an attachment ID and a hidden input holding a hex string, so this
 * file only has to keep those inputs in sync with the picker UI.
 */
( function () {
	'use strict';

	//------------------------------------------------------------------------
	//  Recipe image
	//------------------------------------------------------------------------
	function initImagePicker() {
		var imageId  = document.getElementById( 'brewlab-recipes-image-id' );
		var preview  = document.querySelector( '.brewlab-recipes-media-field__preview' );
		var selectBtn = document.querySelector( '.brewlab-recipes-media-field__select' );
		var removeBtn = document.querySelector( '.brewlab-recipes-media-field__remove' );
		if ( ! imageId || ! selectBtn ) {
			return;
		}

		var frame;

		selectBtn.addEventListener( 'click', function ( event ) {
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
				var previewUrl = ( attachment.sizes && attachment.sizes.medium )
					? attachment.sizes.medium.url
					: attachment.url;

				imageId.value = attachment.id;
				preview.src   = previewUrl;
				preview.style.display = '';
				removeBtn.style.display = '';
			} );

			frame.open();
		} );

		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				imageId.value = '';
				preview.src = '';
				preview.style.display = 'none';
				removeBtn.style.display = 'none';
			} );
		}
	}

	//------------------------------------------------------------------------
	//  Recipe color
	//------------------------------------------------------------------------
	function initColorPicker() {
		var colorInput  = document.getElementById( 'brewlab-recipes-header-color' );
		var colorBtn    = document.getElementById( 'brewlab-recipes-color-btn' );
		var colorSwatch = document.getElementById( 'brewlab-recipes-color-swatch' );
		var modal       = document.querySelector( '.brewlab-recipes-color-modal' );
		if ( ! colorInput || ! colorBtn || ! modal ) {
			return;
		}

		var svCanvas   = modal.querySelector( '.brewlab-recipes-color-sv-canvas' );
		var hueCanvas  = modal.querySelector( '.brewlab-recipes-color-hue-canvas' );
		var svCursor   = modal.querySelector( '.brewlab-recipes-color-sv-cursor' );
		var hueCursor  = modal.querySelector( '.brewlab-recipes-color-hue-cursor' );
		var previewEl  = modal.querySelector( '.brewlab-recipes-color-preview-swatch' );
		var hexInput   = modal.querySelector( '.brewlab-recipes-color-hex-input' );
		var saveBtn    = modal.querySelector( '.brewlab-recipes-color-apply' );
		var resetBtn   = modal.querySelector( '.brewlab-recipes-color-reset' );
		var closeBtn   = modal.querySelector( '.brewlab-recipes-color-close' );
		var backdrop   = modal.querySelector( '.brewlab-recipes-color-backdrop' );
		var presets    = modal.querySelectorAll( '.brewlab-recipes-color-preset' );

		var DEFAULT_COLOR = colorSwatch.getAttribute( 'data-default' ) || '#1e1b17';
		var SV_W = 220, SV_H = 180, HUE_W = 220;
		var svCtx  = svCanvas.getContext( '2d' );
		var hueCtx = hueCanvas.getContext( '2d' );

		// HSV state (h: 0-360, s: 0-1, v: 0-1)
		var h = 0, s = 0.05, v = 0.12;

		function drawHue() {
			var grad = hueCtx.createLinearGradient( 0, 0, HUE_W, 0 );
			for ( var i = 0; i <= 12; i++ ) {
				grad.addColorStop( i / 12, 'hsl(' + Math.round( i * 30 ) + ',100%,50%)' );
			}
			hueCtx.fillStyle = grad;
			hueCtx.fillRect( 0, 0, HUE_W, 16 );
			hueCursor.style.left = Math.round( ( h / 360 ) * HUE_W ) + 'px';
		}

		function drawSV() {
			var gradH = svCtx.createLinearGradient( 0, 0, SV_W, 0 );
			gradH.addColorStop( 0, '#fff' );
			gradH.addColorStop( 1, 'hsl(' + h + ',100%,50%)' );
			svCtx.fillStyle = gradH;
			svCtx.fillRect( 0, 0, SV_W, SV_H );

			var gradV = svCtx.createLinearGradient( 0, 0, 0, SV_H );
			gradV.addColorStop( 0, 'rgba(0,0,0,0)' );
			gradV.addColorStop( 1, '#000' );
			svCtx.fillStyle = gradV;
			svCtx.fillRect( 0, 0, SV_W, SV_H );

			svCursor.style.left = Math.round( s * SV_W ) + 'px';
			svCursor.style.top  = Math.round( ( 1 - v ) * SV_H ) + 'px';
		}

		function hsvToHex( hh, ss, vv ) {
			var r, g, b;
			var i = Math.floor( hh / 60 ) % 6;
			var f = hh / 60 - Math.floor( hh / 60 );
			var p = vv * ( 1 - ss );
			var q = vv * ( 1 - f * ss );
			var t = vv * ( 1 - ( 1 - f ) * ss );
			if ( 0 === i ) { r = vv; g = t; b = p; }
			else if ( 1 === i ) { r = q; g = vv; b = p; }
			else if ( 2 === i ) { r = p; g = vv; b = t; }
			else if ( 3 === i ) { r = p; g = q; b = vv; }
			else if ( 4 === i ) { r = t; g = p; b = vv; }
			else { r = vv; g = p; b = q; }
			var toHex = function ( c ) {
				return ( '0' + Math.round( c * 255 ).toString( 16 ) ).slice( -2 );
			};
			return '#' + toHex( r ) + toHex( g ) + toHex( b );
		}

		function hexToHsv( hex ) {
			hex = hex.replace( '#', '' );
			if ( 3 === hex.length ) {
				hex = hex[ 0 ] + hex[ 0 ] + hex[ 1 ] + hex[ 1 ] + hex[ 2 ] + hex[ 2 ];
			}
			var r = parseInt( hex.slice( 0, 2 ), 16 ) / 255;
			var g = parseInt( hex.slice( 2, 4 ), 16 ) / 255;
			var b = parseInt( hex.slice( 4, 6 ), 16 ) / 255;
			var max = Math.max( r, g, b ), min = Math.min( r, g, b ), d = max - min;
			var hh = 0, ss = 0 === max ? 0 : d / max, vv = max;
			if ( 0 !== d ) {
				if ( max === r ) { hh = ( ( g - b ) / d % 6 ) * 60; }
				else if ( max === g ) { hh = ( ( b - r ) / d + 2 ) * 60; }
				else { hh = ( ( r - g ) / d + 4 ) * 60; }
			}
			return { h: ( ( hh % 360 ) + 360 ) % 360, s: ss, v: vv };
		}

		function render() {
			drawHue();
			drawSV();
			var hex = hsvToHex( h, s, v );
			previewEl.style.background = hex;
			hexInput.value = hex.replace( '#', '' );
		}

		function setFromHex( hex ) {
			if ( ! /^#?[0-9a-fA-F]{6}$/.test( hex ) ) {
				return;
			}
			hex = hex.indexOf( '#' ) === 0 ? hex : '#' + hex;
			var hsv = hexToHsv( hex );
			h = hsv.h; s = hsv.s; v = hsv.v;
			render();
		}

		function clamp( val, min, max ) {
			return Math.max( min, Math.min( max, val ) );
		}

		function pickSV( point ) {
			var rect = svCanvas.getBoundingClientRect();
			var ox = ( point.clientX - rect.left ) * ( SV_W / rect.width );
			var oy = ( point.clientY - rect.top ) * ( SV_H / rect.height );
			s = clamp( ox / SV_W, 0, 1 );
			v = clamp( 1 - oy / SV_H, 0, 1 );
			render();
		}

		function pickHue( point ) {
			var rect = hueCanvas.getBoundingClientRect();
			var ox = ( point.clientX - rect.left ) * ( HUE_W / rect.width );
			h = clamp( ( ox / HUE_W ) * 360, 0, 360 );
			render();
		}

		var draggingSV = false, draggingHue = false;

		svCanvas.addEventListener( 'mousedown', function ( e ) { draggingSV = true; pickSV( e ); e.preventDefault(); } );
		hueCanvas.addEventListener( 'mousedown', function ( e ) { draggingHue = true; pickHue( e ); e.preventDefault(); } );
		window.addEventListener( 'mousemove', function ( e ) {
			if ( draggingSV ) { pickSV( e ); }
			if ( draggingHue ) { pickHue( e ); }
		} );
		window.addEventListener( 'mouseup', function () { draggingSV = false; draggingHue = false; } );

		function touchPoint( e ) {
			return e.touches[ 0 ] || e.changedTouches[ 0 ];
		}
		svCanvas.addEventListener( 'touchstart', function ( e ) { draggingSV = true; pickSV( touchPoint( e ) ); e.preventDefault(); }, { passive: false } );
		hueCanvas.addEventListener( 'touchstart', function ( e ) { draggingHue = true; pickHue( touchPoint( e ) ); e.preventDefault(); }, { passive: false } );
		window.addEventListener( 'touchmove', function ( e ) {
			if ( draggingSV ) { pickSV( touchPoint( e ) ); }
			if ( draggingHue ) { pickHue( touchPoint( e ) ); }
		}, { passive: false } );
		window.addEventListener( 'touchend', function () { draggingSV = false; draggingHue = false; } );

		hexInput.addEventListener( 'input', function () {
			var cleaned = hexInput.value.replace( /[^0-9a-fA-F]/g, '' );
			hexInput.value = cleaned;
			if ( 6 === cleaned.length ) {
				setFromHex( cleaned );
			}
		} );

		presets.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				setFromHex( btn.getAttribute( 'data-color' ) );
			} );
		} );

		function openModal() {
			setFromHex( colorInput.value || DEFAULT_COLOR );
			modal.style.display = 'block';
		}

		function closeModal() {
			modal.style.display = 'none';
		}

		colorBtn.addEventListener( 'click', openModal );
		closeBtn.addEventListener( 'click', closeModal );
		backdrop.addEventListener( 'click', closeModal );

		resetBtn.addEventListener( 'click', function () {
			setFromHex( DEFAULT_COLOR );
		} );

		saveBtn.addEventListener( 'click', function () {
			var hex = '#' + hexInput.value;
			if ( ! /^#[0-9a-fA-F]{6}$/.test( hex ) ) {
				return;
			}
			colorInput.value = hex;
			colorSwatch.style.background = hex;
			closeModal();
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initImagePicker();
		initColorPicker();
	} );
} )();
