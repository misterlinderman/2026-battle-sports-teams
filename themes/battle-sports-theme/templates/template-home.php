<?php
/**
 * Template Name: Home
 * Description: Homepage with hero CTA for Create Account and Log In.
 *
 * @package Battle_Sports
 */

defined( 'ABSPATH' ) || exit;

$register_page = get_page_by_path( 'register', OBJECT, 'page' );
$login_page    = get_page_by_path( 'login', OBJECT, 'page' );
$products_page = get_page_by_path( 'products', OBJECT, 'page' );

$register_url = $register_page ? get_permalink( $register_page ) : home_url( '/register/' );
$login_url    = $login_page ? get_permalink( $login_page ) : home_url( '/login/' );
$products_url = $products_page ? get_permalink( $products_page ) : home_url( '/products/' );

get_header();
?>

<main id="content" class="site-main site-main--home">
	<section class="bsp-hero bsp-hero--home">
		<div class="bsp-hero__overlay"></div>
		<div class="bsp-hero__content container">
			<h1 class="bsp-hero__title"><?php esc_html_e( 'Battle Sports Uniforms', 'battle-sports' ); ?></h1>
			<p class="bsp-hero__subtitle"><?php esc_html_e( 'Custom team uniforms built for performance. Manage your programs, teams, and rosters—all in one place.', 'battle-sports' ); ?></p>
			<div class="bsp-hero__ctas">
				<a href="<?php echo esc_url( $register_url ); ?>" class="bsp-btn-primary bsp-btn-primary--lg">
					<?php esc_html_e( 'Create Account', 'battle-sports' ); ?>
				</a>
				<a href="<?php echo esc_url( $login_url ); ?>" class="bsp-btn-secondary">
					<?php esc_html_e( 'Log In', 'battle-sports' ); ?>
				</a>
			</div>
		</div>
	</section>

	<section class="bsp-home-features container">
		<h2 class="bsp-home-features__title"><?php esc_html_e( 'How It Works', 'battle-sports' ); ?></h2>
		<div class="bsp-home-features__grid">
			<div class="bsp-home-features__item">
				<h3><?php esc_html_e( 'Create Your Account', 'battle-sports' ); ?></h3>
				<p><?php esc_html_e( 'Set up your program and first team in minutes. No order required—add your logo, colors, and roster when you\'re ready.', 'battle-sports' ); ?></p>
				<a href="<?php echo esc_url( $register_url ); ?>" class="bsp-home-features__link"><?php esc_html_e( 'Get started', 'battle-sports' ); ?> →</a>
			</div>
			<div class="bsp-home-features__item">
				<h3><?php esc_html_e( 'Manage Programs & Teams', 'battle-sports' ); ?></h3>
				<p><?php esc_html_e( 'Add programs, create teams within them, and manage rosters independently. Start an order whenever you\'re ready.', 'battle-sports' ); ?></p>
			</div>
			<div class="bsp-home-features__item">
				<h3><?php esc_html_e( 'Browse Uniforms', 'battle-sports' ); ?></h3>
				<p><?php esc_html_e( 'From flag football to tackle—six product lines designed for performance. Configure your uniform and submit for design.', 'battle-sports' ); ?></p>
				<a href="<?php echo esc_url( $products_url ); ?>" class="bsp-home-features__link"><?php esc_html_e( 'View products', 'battle-sports' ); ?> →</a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
