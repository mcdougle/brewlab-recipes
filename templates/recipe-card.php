<?php
//------------------------------------------------------------------------------
//   Recipe Card
//------------------------------------------------------------------------------
// Markup for one recipe. Always include()'d from
// brewlab_recipes_render_recipe() (includes/render.php), never loaded
// directly — $recipe is the data array that function builds, not something
// this file fetches itself. A post can embed more than one recipe, so this
// file must stay function-definition-free (see the note atop render.php for
// why) — anything reusable belongs there instead.
//
// Structure, grouping rules, and unit-conversion data attributes are ported
// from wp-brewtools-recipes/templates/recipe-card.php field-for-field (see
// assets/js/recipe-card.js for the client-side conversion engine that reads
// the data-base/data-unit/data-type attributes this file writes). Grouping
// labels are read from repeater-schemas.php's own field options instead of
// a second hardcoded copy, so a label only ever needs to change in one place.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schemas = brewlab_recipes_repeater_schemas();

$brew_type       = $recipe['brew_type'] ?: '';
$is_beer         = 'beer' === $brew_type;
$show_hops       = $is_beer || '0' !== (string) ( $recipe['show_hops'] ?? '' );
$show_mash       = $is_beer || '0' !== (string) ( $recipe['show_mash'] ?? '' );

$brew_type_label = 'other' === $brew_type && ! empty( $recipe['brew_type_other'] )
	? $recipe['brew_type_other']
	: brewlab_recipes_field_option_label( 'recipe_details', 'brew_type', $brew_type );

// Style badge is redundant noise when it's empty or just repeats the type
// badge (e.g. style "Mead" on a type-"Mead" recipe) — collapse to one badge
// rather than showing "MEAD" / "Mead" side by side.
$show_style_badge = ! empty( $recipe['style'] ) && 0 !== strcasecmp( $recipe['style'], $brew_type_label );

$image_id  = (int) ( $recipe['image_id'] ?? 0 );
$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

$batch_size = floatval( $recipe['batch_size'] ?? 0 );
$batch_unit = $recipe['batch_size_unit'] ?: 'gallons';

$srm     = $recipe['srm'];
$srm_hex = $srm ? brewlab_recipes_srm_color( $srm ) : '';

$author_name = '';
if ( 'post_author' === $recipe['author_display'] ) {
	$author_name = get_the_author_meta( 'display_name', get_post_field( 'post_author', $recipe['id'] ) );
} elseif ( 'custom' === $recipe['author_display'] && ! empty( $recipe['author_custom'] ) ) {
	$author_name = $recipe['author_custom'];
}

// Author's own measurement system, inferred from the batch unit they chose
// — 'gallons' reads as US, 'litres' as metric. Drives the "Default" option
// in the unit toggle.
$author_system = 'gallons' === $batch_unit ? 'us' : 'metric';

$header_color = $recipe['header_color'] ?: '#1e1b17';
$uid          = 'brewlab-recipes-' . $recipe['id'];

$fermentables = $recipe['fermentables'];
$additions    = $recipe['additions'];
$hops         = $recipe['hops'];
$yeasts       = $recipe['yeast'];
$mash_steps   = $recipe['mash_steps'];
$mash_name    = $recipe['mash_steps_profile_name'] ?? '';
$ferm_steps   = $recipe['fermentation_steps'];
$ferm_name    = $recipe['fermentation_steps_profile_name'] ?? '';

$ferm_type_labels = $schemas['fermentation_steps']['fields']['type']['options'];

$hop_use_labels = $schemas['hops']['fields']['use']['options'];
$hops_by_use    = [];
foreach ( $hops as $h ) {
	$hops_by_use[ $h['use'] ?? 'boil' ][] = $h;
}
$boil_hops = array_values( array_filter( $hops, function ( $h ) {
	return 'boil' === ( $h['use'] ?? '' );
} ) );

$has_ingredients  = ! empty( $fermentables ) || ! empty( $hops ) || ! empty( $yeasts ) || ! empty( $additions );
// Unlike the old plugin (which always showed a Method tab, empty or not —
// a mead/cider/wine with no mash and no boil just showed "No method steps
// added yet." forever), this only shows Method when there's actually a
// mash section or a boil section to show.
$has_method       = ( $show_mash && ! empty( $mash_steps ) ) || ( $show_hops && ( $recipe['boil_time'] || ! empty( $boil_hops ) ) );
$has_fermentation = ! empty( $ferm_steps );
$has_notes        = ! empty( $recipe['notes'] );

// Whichever of the four tabs is first available starts active, rather
// than assuming Ingredients-else-Method — a recipe could plausibly have
// none of those two but still have Fermentation or Notes.
$default_tab = 'notes';
foreach ( [ 'ingredients' => $has_ingredients, 'method' => $has_method, 'fermentation' => $has_fermentation, 'notes' => $has_notes ] as $tab => $present ) {
	if ( $present ) {
		$default_tab = $tab;
		break;
	}
}

// Weight-unit target for a given original unit + display system — large
// units (fermentables' lb/kg) stay large, small units (hops/yeast's oz/g)
// stay small, mirroring assets/js/recipe-card.js's targetWeightUnit().
$us_weight_unit  = function ( $unit, $small_only = false ) {
	if ( ! $small_only && in_array( $unit, [ 'kg', 'g' ], true ) ) {
		return 'lb';
	}
	return in_array( $unit, [ 'kg', 'g', 'lb' ], true ) ? 'oz' : $unit;
};
$metric_weight_unit = function ( $unit ) {
	return in_array( $unit, [ 'lb', 'oz' ], true ) ? ( 'lb' === $unit ? 'kg' : 'g' ) : $unit;
};
?>
<div class="brewlab-recipes-card" id="<?php echo esc_attr( $uid ); ?>" data-author-system="<?php echo esc_attr( $author_system ); ?>" style="--brewlab-recipes-header-bg: <?php echo esc_attr( $header_color ); ?>">

	<?php // ── Header ?>
	<div class="brewlab-recipes-card__header<?php echo $image_url ? ' has-image' : ''; ?>">
		<?php if ( $image_url ) : ?>
			<div class="brewlab-recipes-card__image">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $recipe['title'] ); ?>" />
			</div>
		<?php endif; ?>

		<div class="brewlab-recipes-card__meta">
			<div class="brewlab-recipes-card__eyebrow">
				<?php if ( $brew_type_label ) : ?>
					<span class="brewlab-recipes-tag"><?php echo esc_html( $brew_type_label ); ?></span>
				<?php endif; ?>
				<?php if ( $show_style_badge ) : ?>
					<span class="brewlab-recipes-tag brewlab-recipes-tag--muted"><?php echo esc_html( $recipe['style'] ); ?></span>
				<?php endif; ?>
			</div>

			<h2 class="brewlab-recipes-card__title"><?php echo esc_html( $recipe['title'] ); ?></h2>

			<?php if ( ! empty( $recipe['summary'] ) ) : ?>
				<p class="brewlab-recipes-card__summary"><?php echo esc_html( $recipe['summary'] ); ?></p>
			<?php endif; ?>

			<?php if ( $author_name ) : ?>
				<p class="brewlab-recipes-card__author"><?php echo esc_html( sprintf( __( 'by %s', 'brewlab-recipes' ), $author_name ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php // ── Toolbar — functional controls live on a neutral strip, not the
	// per-recipe-colored header, so they stay readable regardless of what
	// color a recipe's author picked. ?>
	<div class="brewlab-recipes-toolbar">
		<div class="brewlab-recipes-toolbar__left">
			<?php if ( $batch_size > 0 ) : ?>
				<div class="brewlab-recipes-batch-scaler">
					<label class="brewlab-recipes-batch-scaler__label" for="<?php echo esc_attr( $uid ); ?>-batch"><?php esc_html_e( 'Batch', 'brewlab-recipes' ); ?></label>
					<div class="brewlab-recipes-batch-scaler__control">
						<input type="number" id="<?php echo esc_attr( $uid ); ?>-batch"
							class="brewlab-recipes-batch-input"
							value="<?php echo esc_attr( $batch_size ); ?>"
							min="0.1" step="0.5"
							data-base="<?php echo esc_attr( $batch_size ); ?>"
							data-base-unit="<?php echo esc_attr( $batch_unit ); ?>" />
						<span class="brewlab-recipes-batch-scaler__unit brewlab-recipes-unit-label"
							data-author="<?php echo esc_attr( $batch_unit ); ?>"
							data-us="<?php echo esc_attr( 'litres' === $batch_unit ? 'gallons' : $batch_unit ); ?>"
							data-metric="<?php echo esc_attr( 'gallons' === $batch_unit ? 'litres' : $batch_unit ); ?>"
						><?php echo esc_html( $batch_unit ); ?></span>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<div class="brewlab-recipes-toolbar__right">
			<div class="brewlab-recipes-unit-toggle">
				<span class="brewlab-recipes-unit-toggle__label"><?php esc_html_e( 'Units', 'brewlab-recipes' ); ?></span>
				<button class="brewlab-recipes-unit-btn" data-system="author"><?php esc_html_e( 'Original', 'brewlab-recipes' ); ?></button>
				<button class="brewlab-recipes-unit-btn" data-system="us"><?php esc_html_e( 'US', 'brewlab-recipes' ); ?></button>
				<button class="brewlab-recipes-unit-btn" data-system="metric"><?php esc_html_e( 'Metric', 'brewlab-recipes' ); ?></button>
			</div>
			<button type="button" class="brewlab-recipes-print-btn"
				data-print-css="<?php echo esc_url( BREWLAB_RECIPES_URL . 'assets/css/recipe-card.css?ver=' . BREWLAB_RECIPES_VERSION ); ?>"
				data-print-fonts="<?php echo esc_url( brewlab_recipes_fonts_url() ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
				<?php esc_html_e( 'Print', 'brewlab-recipes' ); ?>
			</button>
		</div>
	</div>

	<?php // ── Stat strip ?>
	<?php
	$stats = [];
	if ( '' !== $recipe['abv'] )                     $stats[] = [ __( 'ABV', 'brewlab-recipes' ), $recipe['abv'] . '%', '' ];
	if ( '' !== $recipe['original_gravity'] )        $stats[] = [ __( 'OG', 'brewlab-recipes' ), $recipe['original_gravity'], '' ];
	if ( '' !== $recipe['final_gravity'] )           $stats[] = [ __( 'FG', 'brewlab-recipes' ), $recipe['final_gravity'], '' ];
	if ( $show_hops && '' !== $recipe['ibu'] )       $stats[] = [ __( 'IBU', 'brewlab-recipes' ), $recipe['ibu'], '' ];
	if ( $srm )                                      $stats[] = [ __( 'SRM', 'brewlab-recipes' ), $srm, $srm_hex ];
	?>
	<?php if ( ! empty( $stats ) ) : ?>
		<div class="brewlab-recipes-stats">
			<?php foreach ( $stats as [ $label, $value, $color ] ) : ?>
				<div class="brewlab-recipes-stat">
					<div class="brewlab-recipes-stat__value">
						<?php echo esc_html( $value ); ?>
						<?php if ( $color ) : ?>
							<span class="brewlab-recipes-srm-dot" style="background: <?php echo esc_attr( $color ); ?>"></span>
						<?php endif; ?>
					</div>
					<div class="brewlab-recipes-stat__label"><?php echo esc_html( $label ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php // ── Tabs ?>
	<div class="brewlab-recipes-tabs">

		<div class="brewlab-recipes-tabs__nav" role="tablist">
			<?php if ( $has_ingredients ) : ?>
				<button class="brewlab-recipes-tab-btn<?php echo 'ingredients' === $default_tab ? ' is-active' : ''; ?>" role="tab" data-tab="<?php echo esc_attr( $uid ); ?>-ingredients" aria-selected="<?php echo 'ingredients' === $default_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Ingredients', 'brewlab-recipes' ); ?></button>
			<?php endif; ?>
			<?php if ( $has_method ) : ?>
				<button class="brewlab-recipes-tab-btn<?php echo 'method' === $default_tab ? ' is-active' : ''; ?>" role="tab" data-tab="<?php echo esc_attr( $uid ); ?>-method" aria-selected="<?php echo 'method' === $default_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Method', 'brewlab-recipes' ); ?></button>
			<?php endif; ?>
			<?php if ( $has_fermentation ) : ?>
				<button class="brewlab-recipes-tab-btn<?php echo 'fermentation' === $default_tab ? ' is-active' : ''; ?>" role="tab" data-tab="<?php echo esc_attr( $uid ); ?>-fermentation" aria-selected="<?php echo 'fermentation' === $default_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Fermentation', 'brewlab-recipes' ); ?></button>
			<?php endif; ?>
			<?php if ( $has_notes ) : ?>
				<button class="brewlab-recipes-tab-btn<?php echo 'notes' === $default_tab ? ' is-active' : ''; ?>" role="tab" data-tab="<?php echo esc_attr( $uid ); ?>-notes" aria-selected="<?php echo 'notes' === $default_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Notes', 'brewlab-recipes' ); ?></button>
			<?php endif; ?>
		</div>

		<?php // ── Ingredients tab: Fermentables + Other Additions + Hops + Yeast ?>
		<?php if ( $has_ingredients ) : ?>
			<div class="brewlab-recipes-tab-panel<?php echo 'ingredients' === $default_tab ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid ); ?>-ingredients">

				<?php if ( ! empty( $fermentables ) ) :
					$ferm_icon = 'barley';
					if ( 'mead' === $brew_type )       $ferm_icon = 'honey';
					elseif ( 'wine' === $brew_type )   $ferm_icon = 'grape';
					elseif ( 'cider' === $brew_type )  $ferm_icon = 'apple';

					$ferm_type_labels_map = $schemas['fermentables']['fields']['type']['options'];
					$ferm_type_order      = array_merge( array_keys( $ferm_type_labels_map ), [ '' ] );

					$ferm_grouped = [];
					foreach ( $fermentables as $f ) {
						$ferm_grouped[ $f['type'] ?? '' ][] = $f;
					}
					$ferm_has_multiple_types = count( $ferm_grouped ) > 1;
					$ferm_total              = array_sum( array_map( function ( $f ) {
						return floatval( $f['amount'] ?? 0 );
					}, $fermentables ) );
					?>
					<div class="brewlab-recipes-group">
						<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading"><?php echo brewlab_recipes_icon( $ferm_icon, 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Fermentables', 'brewlab-recipes' ); ?></h3>
						<div class="brewlab-recipes-list">
							<?php foreach ( $ferm_type_order as $type_key ) :
								if ( empty( $ferm_grouped[ $type_key ] ) ) continue; ?>
								<?php if ( $ferm_has_multiple_types ) : ?>
									<div class="brewlab-recipes-subheading"><?php echo esc_html( $ferm_type_labels_map[ $type_key ] ?? __( 'Other', 'brewlab-recipes' ) ); ?></div>
								<?php endif; ?>
								<?php foreach ( $ferm_grouped[ $type_key ] as $f ) :
									$base_amt  = floatval( $f['amount'] ?? 0 );
									$orig_unit = $f['unit'] ?? '';
									$pct       = $ferm_total > 0 ? round( $base_amt / $ferm_total * 100 ) : 0;
									?>
									<div class="brewlab-recipes-item">
										<span class="brewlab-recipes-item__amt">
											<span class="brewlab-recipes-qty" data-base="<?php echo esc_attr( $base_amt ); ?>" data-unit="<?php echo esc_attr( $orig_unit ); ?>" data-type="weight"><?php echo esc_html( $base_amt ); ?></span>
											<span class="brewlab-recipes-unit-label" data-author="<?php echo esc_attr( $orig_unit ); ?>" data-us="<?php echo esc_attr( $us_weight_unit( $orig_unit ) ); ?>" data-metric="<?php echo esc_attr( $metric_weight_unit( $orig_unit ) ); ?>"><?php echo esc_html( $orig_unit ); ?></span>
										</span>
										<span class="brewlab-recipes-item__name"><?php
											$link = $f['link'] ?? '';
											$name = $f['name'] ?? '';
											if ( $link ) {
												printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $link ), esc_html( $name ) );
											} else {
												echo esc_html( $name );
											}
										?></span>
										<span class="brewlab-recipes-item__pct"><?php echo esc_html( $pct ); ?>%</span>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
							<?php if ( $ferm_total ) :
								$tot_u = $fermentables[0]['unit'] ?? ''; ?>
								<div class="brewlab-recipes-item brewlab-recipes-item--total">
									<span class="brewlab-recipes-item__name"></span>
									<span class="brewlab-recipes-item__amt brewlab-recipes-item__total-label">
										<?php esc_html_e( 'Total:', 'brewlab-recipes' ); ?>
										<span class="brewlab-recipes-qty" data-base="<?php echo esc_attr( $ferm_total ); ?>" data-unit="<?php echo esc_attr( $tot_u ); ?>" data-type="weight"><?php echo esc_html( $ferm_total ); ?></span>
										<span class="brewlab-recipes-unit-label" data-author="<?php echo esc_attr( $tot_u ); ?>" data-us="<?php echo esc_attr( $us_weight_unit( $tot_u ) ); ?>" data-metric="<?php echo esc_attr( $metric_weight_unit( $tot_u ) ); ?>"><?php echo esc_html( $tot_u ); ?></span>
									</span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $additions ) ) :
					$stage_labels_map     = $schemas['additions']['fields']['stage']['options'];
					$stage_order          = array_merge( array_keys( $stage_labels_map ), [ '' ] );
					$additions_by_stage   = [];
					foreach ( $additions as $a ) {
						$additions_by_stage[ $a['stage'] ?? '' ][] = $a;
					}
					$has_multiple_stages  = count( $additions_by_stage ) > 1;
					?>
					<div class="brewlab-recipes-group">
						<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading"><?php echo brewlab_recipes_icon( 'notes', 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Other Additions', 'brewlab-recipes' ); ?></h3>
						<div class="brewlab-recipes-list">
							<?php foreach ( $stage_order as $stage_key ) :
								if ( empty( $additions_by_stage[ $stage_key ] ) ) continue; ?>
								<?php if ( $has_multiple_stages ) : ?>
									<div class="brewlab-recipes-subheading"><?php echo esc_html( $stage_labels_map[ $stage_key ] ?? ucfirst( $stage_key ) ); ?></div>
								<?php endif; ?>
								<?php foreach ( $additions_by_stage[ $stage_key ] as $a ) : ?>
									<div class="brewlab-recipes-item">
										<span class="brewlab-recipes-item__amt"><?php echo $a['amount'] ? esc_html( $a['amount'] ) . ' ' . esc_html( $a['unit'] ) : '&mdash;'; ?></span>
										<span class="brewlab-recipes-item__name"><?php
											$link = $a['link'] ?? '';
											$name = $a['name'] ?? '';
											if ( $link ) {
												printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $link ), esc_html( $name ) );
											} else {
												echo esc_html( $name );
											}
										?></span>
										<span class="brewlab-recipes-item__pct"><?php echo $a['stage'] ? esc_html__( 'Added at: ', 'brewlab-recipes' ) . esc_html( $stage_labels_map[ $a['stage'] ] ?? ucfirst( $a['stage'] ) ) : ''; ?></span>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $show_hops && ! empty( $hops ) ) : ?>
					<div class="brewlab-recipes-group">
						<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading"><?php echo brewlab_recipes_icon( 'hops', 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Hops', 'brewlab-recipes' ); ?></h3>
						<div class="brewlab-recipes-list">
							<?php foreach ( $hops_by_use as $use => $use_hops ) : ?>
								<?php if ( count( $hops_by_use ) > 1 ) : ?>
									<div class="brewlab-recipes-subheading"><?php echo esc_html( $hop_use_labels[ $use ] ?? ucfirst( $use ) ); ?></div>
								<?php endif; ?>
								<?php foreach ( $use_hops as $h ) :
									$base_amt  = floatval( $h['amount'] ?? 0 );
									$orig_unit = $h['unit'] ?? '';
									?>
									<div class="brewlab-recipes-item">
										<span class="brewlab-recipes-item__amt">
											<span class="brewlab-recipes-qty" data-base="<?php echo esc_attr( $base_amt ); ?>" data-unit="<?php echo esc_attr( $orig_unit ); ?>" data-type="weight"><?php echo esc_html( $base_amt ); ?></span>
											<span class="brewlab-recipes-unit-label" data-author="<?php echo esc_attr( $orig_unit ); ?>" data-us="<?php echo esc_attr( $us_weight_unit( $orig_unit, true ) ); ?>" data-metric="<?php echo esc_attr( $metric_weight_unit( $orig_unit ) ); ?>"><?php echo esc_html( $orig_unit ); ?></span>
										</span>
										<span class="brewlab-recipes-item__name">
											<?php
											$link = $h['link'] ?? '';
											$name = $h['variety'] ?? '';
											if ( $link ) {
												printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $link ), esc_html( $name ) );
											} else {
												echo esc_html( $name );
											}
											?>
											<?php if ( ! empty( $h['alpha'] ) ) : ?>
												<span class="brewlab-recipes-item__detail"><?php echo esc_html( $h['alpha'] ); ?>% AA</span>
											<?php endif; ?>
										</span>
										<span class="brewlab-recipes-item__pct brewlab-recipes-item__use">
											<?php
											$time_val = $h['time'] ?? '';
											if ( in_array( $use, [ 'mash', 'first_wort' ], true ) ) {
												echo esc_html( $hop_use_labels[ $use ] ?? ucfirst( $use ) );
											} elseif ( '' !== $time_val ) {
												$time_unit = 'dry_hop' === $use ? __( 'days', 'brewlab-recipes' ) : __( 'min', 'brewlab-recipes' );
												echo esc_html( $time_val ) . ' ' . esc_html( $time_unit ) . ' &middot; ' . esc_html( $hop_use_labels[ $use ] ?? ucfirst( $use ) );
											} else {
												echo esc_html( $hop_use_labels[ $use ] ?? ucfirst( $use ) );
											}
											?>
										</span>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $yeasts ) ) : ?>
					<div class="brewlab-recipes-group">
						<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading"><?php echo brewlab_recipes_icon( 'yeast', 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Yeast', 'brewlab-recipes' ); ?></h3>
						<div class="brewlab-recipes-list">
							<?php foreach ( $yeasts as $y ) :
								$base_amt  = floatval( $y['amount'] ?? 0 );
								$orig_unit = $y['unit'] ?? '';
								?>
								<div class="brewlab-recipes-item">
									<span class="brewlab-recipes-item__amt">
										<?php
										// data-type="yeast" (not "weight") — the unit set mixes
										// weight/volume/count/cell-count with no shared conversion
										// basis, so this never joins the US/Metric unit toggle (see
										// applySystem() in recipe-card.js, which just redisplays the
										// base value for this type regardless of system). It does
										// join the batch scaler, but rounded to the nearest half
										// packet rather than scaled to arbitrary precision — yeast
										// pitch isn't a linear function of batch size (one packet
										// covers a range of batch sizes; doubling for a high-ABV or
										// lager recipe is a deliberate brewer choice, not a ratio to
										// preserve) — see fmtYeast() in recipe-card.js.
										?>
										<span class="brewlab-recipes-qty" data-base="<?php echo esc_attr( $base_amt ); ?>" data-unit="<?php echo esc_attr( $orig_unit ); ?>" data-type="yeast"><?php echo esc_html( $base_amt ); ?></span>
										<?php echo esc_html( brewlab_recipes_repeater_cell_value( 'yeast', 'unit', $orig_unit ) ); ?>
									</span>
									<span class="brewlab-recipes-item__name"><?php
										$link = $y['link'] ?? '';
										$name = $y['name'] ?? '';
										if ( $link ) {
											printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $link ), esc_html( $name ) );
										} else {
											echo esc_html( $name );
										}
									?></span>
									<span class="brewlab-recipes-item__pct"></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

			</div>
		<?php endif; ?>

		<?php // ── Method tab: Mash + Boil ?>
		<?php if ( $has_method ) : ?>
		<div class="brewlab-recipes-tab-panel<?php echo 'method' === $default_tab ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid ); ?>-method">

			<?php if ( $show_mash && ! empty( $mash_steps ) ) : ?>
				<div class="brewlab-recipes-group">
					<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading">
						<?php echo brewlab_recipes_icon( 'thermometer', 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Mash', 'brewlab-recipes' ); ?><?php if ( $mash_name ) : ?><span class="brewlab-recipes-group__subtitle"><?php echo esc_html( $mash_name ); ?></span><?php endif; ?>
					</h3>
					<div class="brewlab-recipes-steps">
						<?php foreach ( $mash_steps as $step ) : ?>
							<div class="brewlab-recipes-step">
								<div class="brewlab-recipes-step__dot"></div>
								<div class="brewlab-recipes-step__body">
									<span class="brewlab-recipes-step__name"><?php echo esc_html( $step['name'] ?? '' ); ?></span>
									<span class="brewlab-recipes-step__detail">
										<?php
										$parts = [];
										if ( ! empty( $step['temp'] ) ) {
											$tf         = floatval( $step['temp'] );
											$temp_unit  = strtoupper( $step['temp_unit'] ?? 'f' );
											$parts[]    = sprintf(
												'<span class="brewlab-recipes-qty" data-base="%1$s" data-unit="%2$s" data-type="temp">%1$s</span><span class="brewlab-recipes-unit-label" data-author="°%2$s" data-us="°F" data-metric="°C">°%2$s</span>',
												esc_attr( $tf ),
												esc_attr( $temp_unit )
											);
										}
										if ( ! empty( $step['time'] ) ) {
											$parts[] = esc_html( $step['time'] ) . ' ' . esc_html__( 'min', 'brewlab-recipes' );
										}
										echo implode( ' &middot; ', $parts );
										?>
									</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_hops && ( $recipe['boil_time'] || ! empty( $boil_hops ) ) ) : ?>
				<div class="brewlab-recipes-group">
					<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading">
						<?php echo brewlab_recipes_icon( 'thermometer', 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Boil', 'brewlab-recipes' ); ?><?php if ( $recipe['boil_time'] ) : ?><span class="brewlab-recipes-group__subtitle"><?php echo esc_html( $recipe['boil_time'] ); ?> <?php esc_html_e( 'min', 'brewlab-recipes' ); ?></span><?php endif; ?>
					</h3>
					<?php if ( ! empty( $boil_hops ) ) : ?>
						<div class="brewlab-recipes-list">
							<?php foreach ( $boil_hops as $h ) :
								$base_amt  = floatval( $h['amount'] ?? 0 );
								$orig_unit = $h['unit'] ?? '';
								$t         = $h['time'] ?? '';
								?>
								<div class="brewlab-recipes-item">
									<span class="brewlab-recipes-item__amt">
										<?php echo '' !== $t ? esc_html( $t ) . ' ' . esc_html__( 'min', 'brewlab-recipes' ) : '&mdash;'; ?>
									</span>
									<span class="brewlab-recipes-item__name">
										<?php
										$link = $h['link'] ?? '';
										$name = $h['variety'] ?? '';
										if ( $link ) {
											printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $link ), esc_html( $name ) );
										} else {
											echo esc_html( $name );
										}
										?>
										<?php if ( ! empty( $h['alpha'] ) ) : ?>
											<span class="brewlab-recipes-item__detail"><?php echo esc_html( $h['alpha'] ); ?>% AA</span>
										<?php endif; ?>
									</span>
									<span class="brewlab-recipes-item__pct">
										<span class="brewlab-recipes-qty" data-base="<?php echo esc_attr( $base_amt ); ?>" data-unit="<?php echo esc_attr( $orig_unit ); ?>" data-type="weight"><?php echo esc_html( $base_amt ); ?></span>
										<span class="brewlab-recipes-unit-label" data-author="<?php echo esc_attr( $orig_unit ); ?>" data-us="<?php echo esc_attr( $us_weight_unit( $orig_unit, true ) ); ?>" data-metric="<?php echo esc_attr( $metric_weight_unit( $orig_unit ) ); ?>"><?php echo esc_html( $orig_unit ); ?></span>
									</span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
		<?php endif; ?>

		<?php // ── Fermentation tab ?>
		<?php if ( $has_fermentation ) : ?>
			<div class="brewlab-recipes-tab-panel<?php echo 'fermentation' === $default_tab ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid ); ?>-fermentation">

				<div class="brewlab-recipes-group">
					<h3 class="brewlab-recipes-group__title brewlab-recipes-section-heading">
						<?php echo brewlab_recipes_icon( 'fermenter', 'brewlab-recipes-icon' ); ?><?php esc_html_e( 'Fermentation', 'brewlab-recipes' ); ?>
						<?php if ( $ferm_name ) : ?><span class="brewlab-recipes-group__subtitle"><?php echo esc_html( $ferm_name ); ?></span><?php endif; ?>
					</h3>
				</div>

				<div class="brewlab-recipes-steps">
					<?php foreach ( $ferm_steps as $step ) :
						$step_type     = $step['type'] ?? 'primary';
						$step_name     = $step['name'] ?: ( $ferm_type_labels[ $step_type ] ?? ucfirst( $step_type ) );
						$step_temp     = $step['temp'] ?? '';
						$step_temp_u   = strtoupper( $step['temp_unit'] ?? 'f' );
						$step_days     = $step['days'] ?? '';
						$step_ramp     = $step['ramp'] ?? '';
						$step_pressure = $step['pressure'] ?? '';
						$show_temp     = $step_temp && 0.0 !== floatval( $step_temp );
						$show_days     = $step_days && 0.0 !== floatval( $step_days );
						$show_ramp     = $step_ramp && 0.0 !== floatval( $step_ramp );
						$show_pressure = $step_pressure && 0.0 !== floatval( $step_pressure );
						?>
						<div class="brewlab-recipes-step">
							<div class="brewlab-recipes-step__dot"></div>
							<div class="brewlab-recipes-step__body">
								<span class="brewlab-recipes-step__name"><?php echo esc_html( $step_name ); ?></span>
								<span class="brewlab-recipes-step__detail"><?php
									$parts = [];
									if ( $show_temp ) {
										$parts[] = sprintf(
											'<span class="brewlab-recipes-qty" data-base="%1$s" data-unit="%2$s" data-type="temp">%1$s</span><span class="brewlab-recipes-unit-label" data-author="°%2$s" data-us="°F" data-metric="°C">°%2$s</span>',
											esc_attr( $step_temp ),
											esc_attr( $step_temp_u )
										);
									}
									if ( $show_pressure ) $parts[] = esc_html__( 'Pressure Ferment @', 'brewlab-recipes' ) . ' ' . esc_html( $step_pressure ) . ' PSI';
									if ( $show_days )     $parts[] = esc_html( $step_days ) . ' ' . esc_html__( 'days', 'brewlab-recipes' );
									if ( $show_ramp )     $parts[] = esc_html( $step_ramp ) . ' ' . esc_html__( 'day ramp', 'brewlab-recipes' );
									echo implode( ' &middot; ', $parts );
								?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			</div>
		<?php endif; ?>

		<?php // ── Notes tab ?>
		<?php if ( $has_notes ) : ?>
			<div class="brewlab-recipes-tab-panel<?php echo 'notes' === $default_tab ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid ); ?>-notes">
				<div class="brewlab-recipes-notes"><?php echo nl2br( esc_html( $recipe['notes'] ) ); ?></div>
			</div>
		<?php endif; ?>

	</div>

</div>
