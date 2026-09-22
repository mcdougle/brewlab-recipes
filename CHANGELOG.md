# Changelog

## 1.0.5
- Plugin author now shows as "BrewLab" instead of "mcdougle" on the Plugins
  page and in "View details".
- The hop "Add Hop" modal now labels the time field "Time (days)" when Use
  is set to Dry Hop, instead of always showing "Time (min)".
- Added a per-addition IBU field for hops. Enter the IBU an addition
  contributes (like a 60 minute Perle addition at ~30 IBU) and it shows
  next to the alpha acid on the card and in the admin row summary. Not
  calculated, entered directly, consistent with the rest of the plugin.
- The admin hop row summary now labels alpha acid "5.5% AA" instead of a
  bare "5.5%", so it isn't confused with the new IBU figure next to it.
- Fixed fermentables stored in litres showing a lowercase "l" instead of
  "L" once the front-end script ran, from the 1.0.3 fermentable-totals fix.

## 1.0.4
- Fixed OG and FG losing their trailing zeros: "1.040" was shown as "1.04"
  and "1.000" as "1". Gravity now always displays to at least three
  decimals, on the recipe card and in the recipes list, including for
  recipes saved before this fix.

## 1.0.3
- Fixed the Fermentables total and percentages when ingredients use
  different units. A recipe listing 2 gal of juice plus 12 oz of concentrate
  showed "Total: 14 gal" and shares of 14% / 86%, because the raw numbers
  were added together. Totals and percentages are now computed only when
  every fermentable is a weight or every one is a volume, converted to a
  common unit first (10 lb + 8 oz is 10.5 lb). Mixed weight-and-volume
  lists show no total or percentages instead of a wrong one.
- Fermentables listed in litres or gallons now convert correctly with the
  US/Metric toggle and the batch scaler. They were previously relabelled as
  ounces.

## 1.0.2
- Added a small "Powered by BrewLab" credit line below the recipe card,
  linking back to brewlab.app.

## 1.0.1
- Added this changelog.

## 1.0.0
- First release. Recipe cards for beer, mead, cider, and wine, embedded via
  shortcode or Gutenberg block. Fermentables, hops, yeast, other additions,
  mash steps, and fermentation steps, each with their own admin UI. Live
  batch-size scaler and US/Metric unit toggle on the front-end card.
  "Preview Recipe Card" button on the edit screen. Affiliate links on any
  ingredient. Isolated print output. Automatic update checks via GitHub
  Releases.
