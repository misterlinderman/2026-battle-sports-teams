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
			<button type="button" class="primary-nav__backdrop" aria-hidden="true" aria-label="<?php esc_attr_e( 'Close menu', 'battle-sports' ); ?>"></button>
			<div class="primary-nav__drawer">
				<?php
				if ( has_nav_menu( 'primary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'menu_class'     => 'primary-nav__menu',
							'menu_id'        => 'primary-nav-menu',
							'container'      => false,
						)
					);
				} else {
					$products_page  = get_page_by_path( 'products', OBJECT, 'page' );
					$products_url   = $products_page ? get_permalink( $products_page ) : home_url( '/products/' );
					$portal_page    = get_page_by_path( 'portal', OBJECT, 'page' );
					$portal_url     = $portal_page ? get_permalink( $portal_page ) : home_url( '/portal/' );
					$register_page  = get_page_by_path( 'register', OBJECT, 'page' );
					$register_url   = $register_page ? get_permalink( $register_page ) : home_url( '/register/' );
					$login_page     = get_page_by_path( 'login', OBJECT, 'page' );
					$login_url      = $login_page ? get_permalink( $login_page ) : home_url( '/login/' );
					$contact_page   = get_page_by_path( 'contact', OBJECT, 'page' );
					$contact_url    = $contact_page ? get_permalink( $contact_page ) : home_url( '/contact/' );
					$is_logged_in   = is_user_logged_in();
					?>
					<ul class="primary-nav__menu" id="primary-nav-menu">
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'battle-sports' ); ?></a></li>
						<li><a href="<?php echo esc_url( $products_url ); ?>"><?php esc_html_e( 'Products', 'battle-sports' ); ?></a></li>
						<?php if ( $is_logged_in ) : ?>
						<li><a href="<?php echo esc_url( $portal_url ); ?>"><?php esc_html_e( 'My Portal', 'battle-sports' ); ?></a></li>
						<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log Out', 'battle-sports' ); ?></a></li>
						<?php else : ?>
						<li><a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Create Account', 'battle-sports' ); ?></a></li>
						<li><a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log In', 'battle-sports' ); ?></a></li>
						<?php endif; ?>
						<li><a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'battle-sports' ); ?></a></li>
					</ul>
				<?php } ?>
			</div>
		</nav>

		<button class="primary-nav__hamburger" type="button" aria-label="<?php esc_attr_e( 'Toggle menu', 'battle-sports' ); ?>" aria-expanded="false" aria-controls="primary-nav-menu">
			<span class="primary-nav__hamburger-line" aria-hidden="true"></span>
			<span class="primary-nav__hamburger-line" aria-hidden="true"></span>
			<span class="primary-nav__hamburger-line" aria-hidden="true"></span>
		</button>
	</div>
</header>
