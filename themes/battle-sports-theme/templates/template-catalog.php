<?php
/**
 * Template Name: Catalog
 * Description: Product catalog / uniform line listing.
 *
 * @package Battle_Sports
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main site-main--catalog">
	<div class="container">
		<?php
		while ( have_posts() ) :
			the_post();
			the_title( '<h1 class="page-title">', '</h1>' );
			the_content();
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
