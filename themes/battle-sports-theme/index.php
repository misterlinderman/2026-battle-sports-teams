<?php
/**
 * Main fallback template.
 *
 * @package Battle_Sports
 */

get_header();
?>

<main id="content" class="site-main">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
	else :
		echo '<p>' . esc_html__( 'No content found.', 'battle-sports' ) . '</p>';
	endif;
	?>
</main>

<?php
get_footer();
