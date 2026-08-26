<?php
//------------------------------------------------------------------------------
//   Icons
//------------------------------------------------------------------------------
// Inline-SVG icon helper. Icons are loaded from assets/svg/{name}.svg at
// runtime (cached per request) instead of being hardcoded as PHP string
// literals — the .svg file is always the single source of truth for its own
// markup, so there's nothing to drift or accumulate export-tool cruft.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_icon()
//------------------------------------------------------------------------------
// Returns an inline <svg>...</svg> string for the named icon, or '' if no
// matching file exists. $classes is a shorthand for a class attribute;
// $attrs can set/override any other attribute (including 'class').
function brewlab_recipes_icon( $name, $classes = '', array $attrs = [] ) {
	static $cache = [];

	if ( ! isset( $cache[ $name ] ) ) {
		$cache[ $name ] = brewlab_recipes_load_icon_svg( $name );
	}

	$svg = $cache[ $name ];
	if ( '' === $svg ) {
		return '';
	}

	if ( $classes ) {
		$attrs['class'] = trim( ( $attrs['class'] ?? '' ) . ' ' . $classes );
	}

	$attr_string = '';
	foreach ( $attrs as $key => $value ) {
		$attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	return preg_replace( '/<svg /', '<svg' . $attr_string . ' ', $svg, 1 );
}

//------------------------------------------------------------------------------
//   brewlab_recipes_load_icon_svg()
//------------------------------------------------------------------------------
function brewlab_recipes_load_icon_svg( $name ) {
	$path = BREWLAB_RECIPES_PATH . 'assets/svg/' . sanitize_file_name( $name ) . '.svg';
	if ( ! file_exists( $path ) ) {
		return '';
	}

	$svg = file_get_contents( $path );

	// Strip the XML prolog and any comments — keeps hand-authored icons
	// clean by construction and guards against cruft in any icon dropped in
	// later from an external export tool (Inkscape, Illustrator, etc.).
	$svg = preg_replace( '/<\?xml.*?\?>/s', '', $svg );
	$svg = preg_replace( '/<!--.*?-->/s', '', $svg );

	return trim( $svg );
}
