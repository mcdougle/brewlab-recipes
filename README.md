# BrewLab Recipes

A free WordPress plugin for embedding homebrew recipe cards in your blog posts — beer, mead, cider, and wine. Enter a recipe once in the admin, then drop it into any post with a shortcode or block. Readers get a clean, tabbed card with a live batch-size scaler and a US/Metric unit toggle built in.

Part of the [BrewLab](https://brewlab.app) suite.

## Features

- Beer, mead, cider, and wine — each gets the fields that actually apply
- Fermentables, hops, yeast, other additions, mash steps, and fermentation steps, each with their own admin UI
- Live batch-size scaler — type in a different batch size and every ingredient amount recalculates
- US/Metric unit toggle — every quantity on the card converts instantly
- "Preview Recipe Card" button on the edit screen — see your changes before publishing
- Affiliate links on any ingredient
- Print-friendly output, isolated from the surrounding page
- Automatic updates, right in your Plugins screen — no need to re-download a zip by hand

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

This plugin isn't in the WordPress.org directory, so install it the same way you'd install any plugin bought or downloaded directly from a developer:

1. Download the latest zip from the [Releases page](https://github.com/mcdougle/brewlab-recipes/releases/latest).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the zip you downloaded, click **Install Now**, then **Activate**.

That's it — future updates will show up as a normal "update available" notice in your Plugins screen, the same as any other plugin.

## Getting Started

### 1. Create a recipe

In your WordPress admin, go to **BrewLab Recipes → Add New Recipe**. A recipe has its own edit screen with a few sections:

- **Media** — a header image and a recipe color (used for the card's header background).
- **Recipe Details** — brew type (beer/mead/cider/wine/other), a short summary, who to credit as the author, and free-form notes.
- **Batch Details** — style, batch size, boil time, OG/FG/ABV/IBU/SRM.
- **Fermentables, Other Additions, Hops, Yeast, Mash Steps, Fermentation Steps** — one section per ingredient/step type. Click **+ Add** to open a modal, fill in the fields, and save — each item shows up as a row you can click to edit or remove.

Fill in whatever sections apply — a mead recipe can skip Hops and Mash Steps entirely, for example. Click **Preview Recipe Card** in the Publish box at any point to see exactly what readers will see, before you publish.

When you're happy with it, click **Publish**.

### 2. Embed it in a post

Every recipe has a numeric ID — you'll see it in the URL when editing the recipe (`post.php?post=123&action=edit`). If you're using the Gutenberg block, you don't need it at all — the block's recipe picker lists every recipe by title.

**Shortcode**, anywhere in a post's content:
```
[brewlab_recipe id="123"]
```

**Gutenberg block**: search for "Recipe" in the block inserter, add the BrewLab Recipe block, and pick your recipe from the dropdown.

Either way, the recipe renders as a full card wherever you place it — the underlying recipe data stays separate from the post, so editing the recipe later updates it everywhere it's embedded.

## For Developers

Internal naming, for anyone reading or extending the code:

- Prefix: `brewlab_recipes_` / `BREWLAB_RECIPES_` / `_brewlab_recipes_`
- CSS classes: `brewlab-recipes-*`
- Post type slug: `brewlab_recipe`
- Shortcode: `[brewlab_recipe id="123"]`
- Gutenberg block: `brewlab/recipe`
- Text domain: `brewlab-recipes`

File structure:

```
brewlab-recipes.php     Plugin bootstrap — constants, includes loader
includes/                Post type, meta fields, admin UI, update checker
templates/                Front-end recipe card markup
assets/                   CSS, JS, icons
```
