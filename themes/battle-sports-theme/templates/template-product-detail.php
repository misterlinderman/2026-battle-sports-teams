<?php
/**
 * Template Name: Product Detail
 * Description: Single product / uniform line detail page.
 *
 * @package Battle_Sports
 */

defined( 'ABSPATH' ) || exit;

$queried   = get_queried_object();
$post_slug = ( $queried instanceof WP_Post ) ? $queried->post_name : '';
$product   = $post_slug ? bst_get_product_by_slug( $post_slug ) : null;

if ( ! $product ) {
	$product = bst_get_product_by_slug( 'battle-7v7' );
}

$order_url  = add_query_arg( 'product', $product['slug'], home_url( '/order/' ) );
$contact_url = home_url( '/contact/' );
$colors   = $product['colors'] ?? array( 'Royal', 'Navy', 'Black', 'White', 'Red', 'Forest' );
$styles   = $product['styles'] ?? array( 'Standard', 'Alternate', 'Compression' );
$materials = $product['materials'] ?? array( 'Performance Mesh', 'Dri-FIT Polyester', 'Heavyweight Tackle Twill' );

get_header();
?>

<main id="content" class="site-main site-main--product-detail">
	<div class="container">
		<header class="bsp-product-header">
			<span class="bsp-product-header__sport"><?php echo esc_html( $product['sport'] ); ?></span>
			<h1 class="bsp-product-header__name"><?php echo esc_html( $product['name'] ); ?></h1>
			<p class="bsp-product-header__description"><?php echo esc_html( $product['description'] ); ?></p>
		</header>

		<div class="bsp-product-layout">
			<div class="bsp-product-gallery">
				<div class="bsp-product-gallery__main">
					<div class="bsp-product-gallery__placeholder">
						<span class="bsp-product-gallery__placeholder-icon">+</span>
						<span class="bsp-product-gallery__placeholder-label">Main image</span>
					</div>
				</div>
				<div class="bsp-product-gallery__thumbs">
					<div class="bsp-product-gallery__thumb-placeholder" aria-hidden="true"><span>1</span></div>
					<div class="bsp-product-gallery__thumb-placeholder" aria-hidden="true"><span>2</span></div>
					<div class="bsp-product-gallery__thumb-placeholder" aria-hidden="true"><span>3</span></div>
				</div>
			</div>

			<div class="bsp-product-info">
				<section class="bsp-product-specs">
					<h2 class="bsp-product-specs__title">Key Specs</h2>

					<div class="bsp-product-specs__group">
						<h3 class="bsp-product-specs__label">Available Colors</h3>
						<div class="bsp-color-swatches">
							<?php foreach ( $colors as $color ) : ?>
								<span class="bsp-color-swatch" title="<?php echo esc_attr( $color ); ?>" style="--swatch-color: <?php echo esc_attr( bst_get_swatch_color( $color ) ); ?>;" aria-label="<?php echo esc_attr( $color ); ?>"></span>
							<?php endforeach; ?>
						</div>
						<p class="bsp-product-specs__values"><?php echo esc_html( implode( ', ', $colors ) ); ?></p>
					</div>

					<div class="bsp-product-specs__group">
						<h3 class="bsp-product-specs__label">Styles</h3>
						<p class="bsp-product-specs__values"><?php echo esc_html( implode( ', ', $styles ) ); ?></p>
					</div>

					<div class="bsp-product-specs__group">
						<h3 class="bsp-product-specs__label">Materials</h3>
						<p class="bsp-product-specs__values"><?php echo esc_html( implode( ', ', $materials ) ); ?></p>
					</div>
				</section>

				<div class="bsp-product-actions">
					<a href="<?php echo esc_url( $order_url ); ?>" class="bsp-btn-primary bsp-btn-primary--lg">Start Your Order</a>
					<a href="<?php echo esc_url( $contact_url ); ?>" class="bsp-btn-secondary">Need Help?</a>
				</div>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
