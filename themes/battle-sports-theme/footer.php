<?php
/**
 * Footer template.
 *
 * @package Battle_Sports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="site-footer" role="contentinfo">
	<div class="site-footer__inner">
		<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'battle-sports' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'menu_class'     => 'footer-nav__menu',
					'container'      => false,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>

		<p class="site-footer__copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'battle-sports' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
