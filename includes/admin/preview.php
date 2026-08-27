<?php
//------------------------------------------------------------------------------
//   Recipe Preview
//------------------------------------------------------------------------------
// A "Preview Recipe Card" button in the Publish box (post_submitbox_misc_actions
// is the same hook core uses for its own "Preview Changes" link) that opens
// the card, styled and interactive, in a modal right on the edit screen —
// against whatever is currently in the form, not what's last saved. Avoids
// the save → find the post → find the embedded card loop for checking a
// change, especially on a recipe with a lot of ingredients.
//
// admin-preview.js POSTs the *entire* edit form (FormData over the #post
// element) to brewlab_recipes_ajax_preview_recipe() below, with the action
// field overridden to route it through admin-ajax.php instead of a real
// save — so every simple field and repeater row hidden input is already
// exactly what a real save_post would see, with no separate "collect the
// form" logic to keep in sync with the form itself. See
// brewlab_recipes_build_preview_data() in render.php for how that POST data
// becomes a $recipe array.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//------------------------------------------------------------------------------
//   brewlab_recipes_render_preview_button()
//------------------------------------------------------------------------------
function brewlab_recipes_render_preview_button( $post ) {
	if ( 'brewlab_recipe' !== $post->post_type ) {
		return;
	}
	?>
	<div class="misc-pub-section brewlab-recipes-preview-section">
		<button type="button" class="button" id="brewlab-recipes-preview-btn"><?php esc_html_e( 'Preview Recipe Card', 'brewlab-recipes' ); ?></button>
	</div>
	<?php
}
add_action( 'post_submitbox_misc_actions', 'brewlab_recipes_render_preview_button' );

//------------------------------------------------------------------------------
//   brewlab_recipes_render_preview_modal()
//------------------------------------------------------------------------------
function brewlab_recipes_render_preview_modal() {
	$screen = get_current_screen();
	if ( ! $screen || 'brewlab_recipe' !== $screen->post_type ) {
		return;
	}
	?>
	<div class="brewlab-recipes-preview-modal" style="display:none;">
		<div class="brewlab-recipes-preview-modal__backdrop"></div>
		<div class="brewlab-recipes-preview-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Recipe card preview', 'brewlab-recipes' ); ?>">
			<button type="button" class="brewlab-recipes-preview-modal__close" aria-label="<?php esc_attr_e( 'Close', 'brewlab-recipes' ); ?>">&times;</button>
			<div class="brewlab-recipes-preview-modal__body"></div>
		</div>
	</div>
	<?php
}
add_action( 'admin_footer', 'brewlab_recipes_render_preview_modal' );

//------------------------------------------------------------------------------
//   brewlab_recipes_ajax_preview_recipe()
//------------------------------------------------------------------------------
// Nonce lives under the form's own 'brewlab_recipes_nonce' field name rather
// than a generic 'nonce' key — admin-preview.js POSTs the form as-is instead
// of renaming fields, so this reads it under the name the form already gives
// it.
function brewlab_recipes_ajax_preview_recipe() {
	check_ajax_referer( 'brewlab_recipes_save_meta', 'brewlab_recipes_nonce' );

	$post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;
	if ( ! $post_id || 'brewlab_recipe' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error();
	}

	$recipe = brewlab_recipes_build_preview_data( $post_id );

	ob_start();
	include BREWLAB_RECIPES_PATH . 'templates/recipe-card.php';
	$html = ob_get_clean();

	wp_send_json_success( [ 'html' => $html ] );
}
add_action( 'wp_ajax_brewlab_recipes_preview_recipe', 'brewlab_recipes_ajax_preview_recipe' );
