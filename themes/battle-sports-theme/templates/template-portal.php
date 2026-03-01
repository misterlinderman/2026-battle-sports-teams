<?php
/**
 * Template Name: Customer Portal
 * Description: Full-width customer portal page (no sidebar).
 *
 * @package Battle_Sports
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main site-main--portal site-main--full-width">
	<?php
	while ( have_posts() ) :
		the_post();
		echo do_shortcode( '[bsp_portal]' );
	endwhile;
	?>
</main>

<?php
get_footer();
