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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brew_type_label = brewlab_recipes_field_option_label( 'recipe_details', 'brew_type', $recipe['brew_type'] );
$type_label      = 'other' === $recipe['brew_type'] && ! empty( $recipe['brew_type_other'] )
	? $recipe['brew_type_other']
	: $brew_type_label;

// Style badge is redundant noise when it's empty or just repeats the type
// badge (e.g. style "Mead" on a type-"Mead" recipe) — collapse to one badge
// rather than showing "MEAD" / "MEAD" side by side.
$show_style_badge = ! empty( $recipe['style'] ) && 0 !== strcasecmp( $recipe['style'], $type_label );

$author_name = '';
if ( 'post_author' === $recipe['author_display'] ) {
	$author_name = get_the_author_meta( 'display_name', get_post_field( 'post_author', $recipe['id'] ) );
} elseif ( 'custom' === $recipe['author_display'] && ! empty( $recipe['author_custom'] ) ) {
	$author_name = $recipe['author_custom'];
}

$batch_unit_label = brewlab_recipes_field_option_label( 'batch_details', 'batch_size_unit', $recipe['batch_size_unit'] );
$srm_color        = brewlab_recipes_srm_color( $recipe['srm'] );

$show_hops = '0' !== $recipe['show_hops'] && ! empty( $recipe['hops'] );
$show_mash = '0' !== $recipe['show_mash'] && ! empty( $recipe['mash_steps'] );
?>
<div class="brewlab-recipes-card" style="--brewlab-recipes-header-color: <?php echo esc_attr( $recipe['header_color'] ?: '#1e1b17' ); ?>">

	<div class="brewlab-recipes-card__header">
		<?php if ( ! empty( $recipe['image_id'] ) ) : ?>
			<div class="brewlab-recipes-card__image">
				<?php echo wp_get_attachment_image( $recipe['image_id'], 'medium' ); ?>
			</div>
		<?php endif; ?>

		<div class="brewlab-recipes-card__header-content">
			<div class="brewlab-recipes-card__badges">
				<?php if ( $type_label ) : ?>
					<span class="brewlab-recipes-card__badge"><?php echo esc_html( $type_label ); ?></span>
				<?php endif; ?>
				<?php if ( $show_style_badge ) : ?>
					<span class="brewlab-recipes-card__badge"><?php echo esc_html( $recipe['style'] ); ?></span>
				<?php endif; ?>
			</div>

			<h3 class="brewlab-recipes-card__title"><?php echo esc_html( $recipe['title'] ); ?></h3>

			<?php if ( ! empty( $recipe['summary'] ) ) : ?>
				<p class="brewlab-recipes-card__summary"><?php echo esc_html( $recipe['summary'] ); ?></p>
			<?php endif; ?>

			<?php if ( $author_name ) : ?>
				<p class="brewlab-recipes-card__author"><?php echo esc_html( $author_name ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="brewlab-recipes-card__stats">
		<?php if ( ! empty( $recipe['batch_size'] ) ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'Batch Size', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value"><?php echo esc_html( $recipe['batch_size'] . ' ' . $batch_unit_label ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $recipe['original_gravity'] ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'OG', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value"><?php echo esc_html( $recipe['original_gravity'] ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $recipe['final_gravity'] ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'FG', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value"><?php echo esc_html( $recipe['final_gravity'] ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $recipe['abv'] ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'ABV', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value"><?php echo esc_html( $recipe['abv'] ); ?>%</span>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $recipe['ibu'] ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'IBU', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value"><?php echo esc_html( $recipe['ibu'] ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $recipe['srm'] ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'SRM', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value">
					<?php if ( $srm_color ) : ?>
						<span class="brewlab-recipes-card__srm-swatch" style="background: <?php echo esc_attr( $srm_color ); ?>"></span>
					<?php endif; ?>
					<?php echo esc_html( $recipe['srm'] ); ?>
				</span>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $recipe['boil_time'] ) ) : ?>
			<div class="brewlab-recipes-card__stat">
				<span class="brewlab-recipes-card__stat-label"><?php esc_html_e( 'Boil Time', 'brewlab-recipes' ); ?></span>
				<span class="brewlab-recipes-card__stat-value"><?php echo esc_html( $recipe['boil_time'] ); ?> <?php esc_html_e( 'min', 'brewlab-recipes' ); ?></span>
			</div>
		<?php endif; ?>
	</div>

	<div class="brewlab-recipes-card__section">
		<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'barley', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Fermentables', 'brewlab-recipes' ); ?></h4>
		<?php brewlab_recipes_render_repeater_table( $recipe, 'fermentables' ); ?>
	</div>

	<?php if ( ! empty( $recipe['additions'] ) ) : ?>
		<div class="brewlab-recipes-card__section">
			<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'honey', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Other Additions', 'brewlab-recipes' ); ?></h4>
			<?php brewlab_recipes_render_repeater_table( $recipe, 'additions' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_hops ) : ?>
		<div class="brewlab-recipes-card__section">
			<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'hops', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Hops', 'brewlab-recipes' ); ?></h4>
			<?php brewlab_recipes_render_repeater_table( $recipe, 'hops' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $recipe['yeast'] ) ) : ?>
		<div class="brewlab-recipes-card__section">
			<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'yeast', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Yeast', 'brewlab-recipes' ); ?></h4>
			<?php brewlab_recipes_render_repeater_table( $recipe, 'yeast' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_mash ) : ?>
		<div class="brewlab-recipes-card__section">
			<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'thermometer', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Mash Steps', 'brewlab-recipes' ); ?></h4>
			<?php brewlab_recipes_render_repeater_table( $recipe, 'mash_steps' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $recipe['fermentation_steps'] ) ) : ?>
		<div class="brewlab-recipes-card__section">
			<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'fermenter', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Fermentation', 'brewlab-recipes' ); ?></h4>
			<?php brewlab_recipes_render_repeater_table( $recipe, 'fermentation_steps' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $recipe['notes'] ) ) : ?>
		<div class="brewlab-recipes-card__section">
			<h4 class="brewlab-recipes-card__section-title"><?php echo brewlab_recipes_icon( 'notes', 'brewlab-recipes-card__section-icon' ); ?><?php esc_html_e( 'Notes', 'brewlab-recipes' ); ?></h4>
			<p class="brewlab-recipes-card__notes"><?php echo nl2br( esc_html( $recipe['notes'] ) ); ?></p>
		</div>
	<?php endif; ?>

</div>
