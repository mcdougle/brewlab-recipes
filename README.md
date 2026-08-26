# BrewLab Recipes

A WordPress plugin that embeds homebrew recipe cards in blog posts, supporting beer, mead, cider, and wine. Recipes are managed as a non-public custom post type and dropped into posts via shortcode or Gutenberg block — the post content stays clean, and the recipe (ingredients, batch details, mash/fermentation profile) renders as a structured card wherever it's placed.

Part of the [BrewLab](https://brewlab.app) suite.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Naming

- Prefix: `brewlab_recipes_` / `BREWLAB_RECIPES_` / `_brewlab_recipes_`
- CSS classes: `brewlab-recipes-*`
- Post type slug: `brewlab_recipe`
- Shortcode: `[brewlab_recipe id="123"]`
- Gutenberg block: `brewlab/recipe`
- Text domain: `brewlab-recipes`

## Structure

```
brewlab-recipes.php     Plugin bootstrap — constants, includes loader
includes/                Post type, meta fields, taxonomies, admin UI
templates/                Front-end recipe card markup
assets/                   CSS, JS, icons
```
