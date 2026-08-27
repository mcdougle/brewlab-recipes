<?php
//------------------------------------------------------------------------------
//   Updates
//------------------------------------------------------------------------------
// This plugin isn't in the WordPress.org directory (see the license header
// in brewlab-recipes.php — direct distribution keeps the proprietary
// license, WordPress.org requires GPL), so WordPress's native update check
// has nothing to compare against on its own. Plugin Update Checker
// (vendored at includes/vendor/plugin-update-checker/, MIT licensed)
// hooks the same update-check machinery WordPress.org plugins use, reading
// version info from this repo's GitHub Releases instead — end users still
// see the normal "update available" row and Update Now button.
//
// The GitHub repo must be public for this to work: an unauthenticated
// request is what every installed copy of the plugin makes to check for
// updates, and there's no reasonable way to ship a private-repo access
// token inside a plugin distributed to other people's sites.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BREWLAB_RECIPES_PATH . 'includes/vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$brewlab_recipes_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/mcdougle/brewlab-recipes/',
	BREWLAB_RECIPES_PATH . 'brewlab-recipes.php',
	'brewlab-recipes'
);
$brewlab_recipes_update_checker->setBranch( 'main' );

// Reads the version/changelog from each GitHub Release rather than raw tag
// source zips (which unpack to a github-generated folder name PUC doesn't
// always handle cleanly) — see recipes-plugin-distribution.md's Phase B
// step 8 for the release process this expects.
$brewlab_recipes_update_checker->getVcsApi()->enableReleaseAssets( '/\.zip($|[?&#])/i' );
