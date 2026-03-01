<?php
/**
 * Header template.
 *
 * @package Battle_Sports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'battle-sports' ); ?></a>

<header class="site-header" role="banner">
	<div class="site-header__inner">
		<div class="site-branding">
			<a class="site-branding__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="site-branding__name"><?php bloginfo( 'name' ); ?></span>
				<?php if ( get_bloginfo( 'description', 'display' ) ) : ?>
					<span class="site-branding__description"><?php bloginfo( 'description' ); ?></span>
				<?php endif; ?>
			</a>
		</div>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'battle-sports' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_class'     => 'primary-nav__menu',
					'menu_id'        => 'primary-nav-menu',
					'container'      => false,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>

		<button class="primary-nav__hamburger" type="button" aria-label="<?php esc_attr_e( 'Toggle menu', 'battle-sports' ); ?>" aria-expanded="false" aria-controls="primary-nav-menu">
			<span class="primary-nav__hamburger-line" aria-hidden="true"></span>
			<span class="primary-nav__hamburger-line" aria-hidden="true"></span>
			<span class="primary-nav__hamburger-line" aria-hidden="true"></span>
		</button>
	</div>
</header>
