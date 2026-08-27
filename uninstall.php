<?php
//------------------------------------------------------------------------------
//   Uninstall
//------------------------------------------------------------------------------
// WordPress runs this file (not any hook) when the plugin is deleted from
// the Plugins screen — never on ordinary deactivation. Removes every
// brewlab_recipe post and, via wp_delete_post()'s own force-delete, all of
// its postmeta with it. There are no plugin options to clean up (nothing
// in includes/ ever calls add_option()/update_option()).

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$recipe_ids = get_posts( [
	'post_type'      => 'brewlab_recipe',
	'post_status'    => 'any',
	'numberposts'    => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );

foreach ( $recipe_ids as $recipe_id ) {
	wp_delete_post( $recipe_id, true );
}
