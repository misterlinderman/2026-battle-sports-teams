<?php
/**
 * Template Name: Full Width
 * Description: Full-width page layout without sidebar.
 *
 * @package Battle_Sports
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main site-main--full-width">
	<?php
	while ( have_posts() ) :
		the_post();
		the_title( '<h1 class="page-title">', '</h1>' );
		the_content();
	endwhile;
	?>
</main>

<?php
get_footer();
