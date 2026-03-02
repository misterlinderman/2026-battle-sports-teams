<?php
/**
 * Template Name: Catalog
 * Description: Product catalog / uniform line listing.
 *
 * @package Battle_Sports
 */

defined( 'ABSPATH' ) || exit;

$products = bst_get_catalog_products();
$catalog_page = get_page_by_path( 'products', OBJECT, 'page' );
$base_url     = $catalog_page ? get_permalink( $catalog_page ) : home_url( '/products/' );

get_header();
?>

<main id="content" class="site-main site-main--catalog">
	<section class="bsp-hero">
		<div class="bsp-hero__overlay"></div>
		<div class="bsp-hero__content container">
			<h1 class="bsp-hero__title">Battle Sports Uniforms</h1>
			<p class="bsp-hero__subtitle">Custom team uniforms built for performance. From flag to tackle—engineered to dominate.</p>
			<p class="bsp-hero__tagline">Six lines. One standard of excellence.</p>
		</div>
	</section>

	<section class="bsp-catalog-grid-section">
		<div class="container">
			<div class="bsp-catalog-grid" role="list">
				<?php foreach ( $products as $product ) : ?>
					<?php
					$intake_slug = $product['intake_slug'] ?? $product['slug'];
					$product_url = $base_url . $intake_slug . '/';
					$child_page  = get_page_by_path( $intake_slug, OBJECT, 'page' );
					if ( $child_page ) {
						$product_url = get_permalink( $child_page );
					}
					?>
					<article class="bsp-product-card" role="listitem">
						<a class="bsp-product-card__link" href="<?php echo esc_url( $product_url ); ?>">
							<div class="bsp-product-card__image-wrap">
								<div class="bsp-product-card__image-placeholder" aria-hidden="true">
									<span class="bsp-product-card__image-icon">+</span>
									<span class="bsp-product-card__image-label"><?php echo esc_html( $product['name'] ); ?></span>
								</div>
							</div>
							<div class="bsp-product-card__body">
								<h2 class="bsp-product-card__name"><?php echo esc_html( $product['name'] ); ?></h2>
								<span class="bsp-product-card__sport"><?php echo esc_html( $product['sport'] ); ?></span>
								<span class="bsp-product-card__cta bsp-btn-primary">Configure Uniform</span>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
