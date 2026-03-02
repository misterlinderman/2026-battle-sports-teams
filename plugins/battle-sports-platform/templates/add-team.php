<?php

declare(strict_types=1);

/**
 * Add Team form template.
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$portal_page = get_page_by_path('portal', OBJECT, 'page');
$portal_url = $portal_page ? get_permalink($portal_page) : home_url('/portal/');
$error = isset($error) ? $error : (isset($_GET['bsp_add_team_error']) ? sanitize_text_field(wp_unslash($_GET['bsp_add_team_error'])) : '');

global $wpdb;
$programs = [];
$programs_table = $wpdb->prefix . 'bsp_programs';
if ($wpdb->get_var("SHOW TABLES LIKE '{$programs_table}'") === $programs_table) {
    $programs = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name FROM {$programs_table} WHERE user_id = %d ORDER BY name ASC",
        get_current_user_id()
    ), ARRAY_A);
}
?>
<div class="bsp-register bsp-add-team" id="bsp-add-team">
	<p class="bsp-register__back"><a href="<?php echo esc_url( $portal_url ); ?>">← <?php esc_html_e( 'Back to Portal', 'battle-sports-platform' ); ?></a></p>
	<h1 class="bsp-register__title"><?php esc_html_e( 'Add Team', 'battle-sports-platform' ); ?></h1>
	<p class="bsp-register__intro"><?php esc_html_e( 'Create a new team under an existing program or add a new program.', 'battle-sports-platform' ); ?></p>

	<?php if ( $error ) : ?>
	<div class="bsp-register__error" role="alert"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>

		<form class="bsp-register__form" method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'bsp_add_team', 'bsp_add_team_nonce' ); ?>

		<div class="bsp-register__section">
			<h2 class="bsp-register__section-title"><?php esc_html_e( 'Program', 'battle-sports-platform' ); ?></h2>
			<div class="bsp-register__fields">
				<?php if ( ! empty( $programs ) ) : ?>
				<p class="bsp-register__field">
					<label for="bsp_program_id"><?php esc_html_e( 'Existing Program', 'battle-sports-platform' ); ?></label>
					<select id="bsp_program_id" name="bsp_program_id">
						<option value=""><?php esc_html_e( '— Or add new below —', 'battle-sports-platform' ); ?></option>
						<?php foreach ( $programs as $p ) : ?>
						<option value="<?php echo esc_attr( (string) $p['id'] ); ?>"><?php echo esc_html( $p['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<?php endif; ?>
				<p class="bsp-register__field">
					<label for="bsp_program_name"><?php esc_html_e( 'New Program Name', 'battle-sports-platform' ); ?></label>
					<input type="text" id="bsp_program_name" name="bsp_program_name" placeholder="<?php esc_attr_e( 'Leave blank to use selected program', 'battle-sports-platform' ); ?>">
				</p>
			</div>
		</div>

		<div class="bsp-register__section">
			<h2 class="bsp-register__section-title"><?php esc_html_e( 'Team Details', 'battle-sports-platform' ); ?></h2>
			<div class="bsp-register__fields">
				<p class="bsp-register__field">
					<label for="bsp_team_name"><?php esc_html_e( 'Team Name', 'battle-sports-platform' ); ?> <span class="required">*</span></label>
					<input type="text" id="bsp_team_name" name="bsp_team_name" required>
				</p>
				<p class="bsp-register__field">
					<label for="bsp_primary_color"><?php esc_html_e( 'Primary Color', 'battle-sports-platform' ); ?></label>
					<input type="text" id="bsp_primary_color" name="bsp_primary_color" placeholder="<?php esc_attr_e( 'e.g. Navy', 'battle-sports-platform' ); ?>">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_secondary_color"><?php esc_html_e( 'Secondary Color', 'battle-sports-platform' ); ?></label>
					<input type="text" id="bsp_secondary_color" name="bsp_secondary_color" placeholder="<?php esc_attr_e( 'e.g. Gold', 'battle-sports-platform' ); ?>">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_team_logo"><?php esc_html_e( 'Team Logo', 'battle-sports-platform' ); ?></label>
					<input type="file" id="bsp_team_logo" name="bsp_team_logo" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps,image/jpeg,image/png,application/pdf">
					<span class="bsp-register__hint"><?php esc_html_e( 'JPG, PNG, PDF, AI, EPS — max 50MB', 'battle-sports-platform' ); ?></span>
				</p>
			</div>
		</div>

		<div class="bsp-register__actions">
			<button type="submit" class="bsp-btn-primary bsp-btn-primary--lg"><?php esc_html_e( 'Add Team', 'battle-sports-platform' ); ?></button>
		</div>
	</form>
</div>
