<?php

declare(strict_types=1);

/**
 * Coach registration form template.
 *
 * Steps: Account → Program → First Team (name, logo, colors).
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$register_page = get_page_by_path('register', OBJECT, 'page');
$action = $register_page ? get_permalink($register_page) : home_url('/register/');
$login_page = get_page_by_path('login', OBJECT, 'page');
$login_url = $login_page ? get_permalink($login_page) : home_url('/login/');
$error = isset($_GET['bsp_register_error']) ? sanitize_text_field(wp_unslash($_GET['bsp_register_error'])) : '';
?>
<div class="bsp-register" id="bsp-coach-register">
	<h1 class="bsp-register__title"><?php esc_html_e('Create Your Coach Account', 'battle-sports-platform'); ?></h1>
	<p class="bsp-register__intro"><?php esc_html_e('Set up your program and first team. No order required—manage rosters and start orders when you\'re ready.', 'battle-sports-platform'); ?></p>

	<?php if ( $error ) : ?>
	<div class="bsp-register__error" role="alert">
		<?php echo esc_html( $error ); ?>
	</div>
	<?php endif; ?>

	<form class="bsp-register__form" method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data" id="bsp-coach-register-form">
		<?php wp_nonce_field( 'bsp_coach_register', 'bsp_coach_register_nonce' ); ?>

		<div class="bsp-register__section">
			<h2 class="bsp-register__section-title"><?php esc_html_e('Your Account', 'battle-sports-platform'); ?></h2>
			<div class="bsp-register__fields">
				<p class="bsp-register__field">
					<label for="bsp_first_name"><?php esc_html_e('First Name', 'battle-sports-platform'); ?> <span class="required">*</span></label>
					<input type="text" id="bsp_first_name" name="bsp_first_name" required autocomplete="given-name">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_last_name"><?php esc_html_e('Last Name', 'battle-sports-platform'); ?> <span class="required">*</span></label>
					<input type="text" id="bsp_last_name" name="bsp_last_name" required autocomplete="family-name">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_email"><?php esc_html_e('Email', 'battle-sports-platform'); ?> <span class="required">*</span></label>
					<input type="email" id="bsp_email" name="bsp_email" required autocomplete="email">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_password"><?php esc_html_e('Password', 'battle-sports-platform'); ?> <span class="required">*</span></label>
					<input type="password" id="bsp_password" name="bsp_password" required minlength="8" autocomplete="new-password">
					<span class="bsp-register__hint"><?php esc_html_e('At least 8 characters', 'battle-sports-platform'); ?></span>
				</p>
			</div>
		</div>

		<div class="bsp-register__section">
			<h2 class="bsp-register__section-title"><?php esc_html_e('Your Program', 'battle-sports-platform'); ?></h2>
			<p class="bsp-register__section-desc"><?php esc_html_e('Your organization or league (e.g. "Westside Youth Football", "Lincoln High Athletics").', 'battle-sports-platform'); ?></p>
			<div class="bsp-register__fields">
				<p class="bsp-register__field">
					<label for="bsp_program_name"><?php esc_html_e('Program Name', 'battle-sports-platform'); ?> <span class="required">*</span></label>
					<input type="text" id="bsp_program_name" name="bsp_program_name" required placeholder="<?php esc_attr_e( 'e.g. Westside Youth Football', 'battle-sports-platform' ); ?>">
				</p>
			</div>
		</div>

		<div class="bsp-register__section">
			<h2 class="bsp-register__section-title"><?php esc_html_e('Your First Team', 'battle-sports-platform'); ?></h2>
			<p class="bsp-register__section-desc"><?php esc_html_e('Add your first team. You can add more teams later from your portal.', 'battle-sports-platform'); ?></p>
			<div class="bsp-register__fields">
				<p class="bsp-register__field">
					<label for="bsp_team_name"><?php esc_html_e('Team Name', 'battle-sports-platform'); ?> <span class="required">*</span></label>
					<input type="text" id="bsp_team_name" name="bsp_team_name" required placeholder="<?php esc_attr_e( 'e.g. Varsity Eagles', 'battle-sports-platform' ); ?>">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_primary_color"><?php esc_html_e('Primary Color', 'battle-sports-platform'); ?></label>
					<input type="text" id="bsp_primary_color" name="bsp_primary_color" placeholder="<?php esc_attr_e( 'e.g. Navy', 'battle-sports-platform' ); ?>">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_secondary_color"><?php esc_html_e('Secondary Color', 'battle-sports-platform'); ?></label>
					<input type="text" id="bsp_secondary_color" name="bsp_secondary_color" placeholder="<?php esc_attr_e( 'e.g. Gold', 'battle-sports-platform' ); ?>">
				</p>
				<p class="bsp-register__field">
					<label for="bsp_team_logo"><?php esc_html_e('Team Logo', 'battle-sports-platform'); ?></label>
					<input type="file" id="bsp_team_logo" name="bsp_team_logo" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps,image/jpeg,image/png,application/pdf">
					<span class="bsp-register__hint"><?php esc_html_e('JPG, PNG, PDF, AI, EPS — max 50MB', 'battle-sports-platform'); ?></span>
				</p>
			</div>
		</div>

		<div class="bsp-register__actions">
			<button type="submit" class="bsp-btn-primary bsp-btn-primary--lg"><?php esc_html_e('Create Account', 'battle-sports-platform'); ?></button>
		</div>

		<p class="bsp-register__login-link">
			<?php
			printf(
				/* translators: %s: URL to login page */
				esc_html__( 'Already have an account? <a href="%s">Log in</a>', 'battle-sports-platform' ),
				esc_url( $login_url )
			);
			?>
		</p>
	</form>
</div>
