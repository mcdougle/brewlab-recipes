<?php
/**
 * Plugin Name:       BrewLab Recipes
 * Plugin URI:        https://brewlab.app
 * Description:       Homebrew recipe cards for beer, mead, cider, and wine — embedded in WordPress posts via shortcode or Gutenberg block.
 * Version:           0.1.0
 * Author:            mcdougle
 * Author URI:        https://brewlab.app
 * License:           Proprietary
 * Text Domain:       brewlab-recipes
 *
 * @package BrewLab_Recipes
 */

//------------------------------------------------------------------------------
//   BrewLab Recipes — Plugin Bootstrap
//------------------------------------------------------------------------------
// Defines the plugin's path/URL/version constants and loads each concern
// (post type, meta fields, taxonomies, admin UI, front-end rendering) from
// includes/. Keep this file limited to constants and require_once calls —
// actual registration logic belongs in the included files.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

//------------------------------------------------------------------------------
//   Constants
//------------------------------------------------------------------------------
define( 'BREWLAB_RECIPES_VERSION', '0.1.0' );
define( 'BREWLAB_RECIPES_PATH', plugin_dir_path( __FILE__ ) );
define( 'BREWLAB_RECIPES_URL', plugin_dir_url( __FILE__ ) );

//------------------------------------------------------------------------------
//   Includes
//------------------------------------------------------------------------------
require_once BREWLAB_RECIPES_PATH . 'includes/post-types.php';
require_once BREWLAB_RECIPES_PATH . 'includes/icons.php';
require_once BREWLAB_RECIPES_PATH . 'includes/admin/fields/repeater-schemas.php';
