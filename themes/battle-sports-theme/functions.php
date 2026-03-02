<?php
/**
 * Battle Sports theme functions and definitions.
 *
 * @package Battle_Sports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BST_THEME_VERSION', wp_get_theme()->get( 'Version' ) ?: '0.1.0' );

/**
 * Theme setup.
 */
function bst_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	add_theme_support( 'woocommerce' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'battle-sports' ),
			'footer'  => __( 'Footer Menu', 'battle-sports' ),
		)
	);
}
add_action( 'after_setup_theme', 'bst_theme_setup' );

/**
 * Declare WooCommerce theme support.
 */
function bst_woocommerce_setup(): void {
	add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'bst_woocommerce_setup' );

/**
 * Enqueue theme assets.
 */
function bst_enqueue_assets(): void {
	$theme_uri = get_template_directory_uri();
	$version   = BST_THEME_VERSION;

	// Satoshi (Fontshare) — global font for Battle Sports brand
	wp_enqueue_style(
		'battle-sports-fonts',
		'https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'battle-sports-style',
		$theme_uri . '/assets/dist/css/style.css',
		array(),
		$version
	);

	wp_enqueue_script(
		'battle-sports-main',
		$theme_uri . '/assets/dist/js/main.js',
		array(),
		$version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bst_enqueue_assets' );

/**
 * Returns catalog product lines for the product catalog template.
 *
 * @return array<int, array{slug: string, name: string, sport: string, description: string, colors: array<string>, styles: array<string>, materials: array<string>}>
 */
function bst_get_catalog_products(): array {
	return array(
		array(
			'slug'        => 'battle-7v7',
			'name'        => 'Battle 7v7',
			'sport'       => 'Flag Football',
			'description' => 'Jerseys and shorts built for 7-on-7 flag football. Lightweight, breathable, built to move.',
			'colors'      => array( 'Royal', 'Navy', 'Black', 'White', 'Red', 'Forest' ),
			'styles'      => array( 'Standard', 'Alternate', 'Compression' ),
			'materials'   => array( 'Performance Mesh', 'Dri-FIT Polyester' ),
		),
		array(
			'slug'        => 'battle-flag',
			'name'        => 'Battle Flag',
			'sport'       => 'Flag Football',
			'description' => 'Premium flag football jerseys and shorts with a women\'s variant. Designed for speed and agility.',
			'colors'      => array( 'Royal', 'Navy', 'Black', 'White', 'Red', 'Kelly Green' ),
			'styles'      => array( 'Standard', 'Alternate', 'Compression' ),
			'materials'   => array( 'Performance Mesh', 'Dri-FIT Polyester' ),
		),
		array(
			'slug'        => 'battle-womens-flag',
			'name'        => 'Battle Women\'s Flag',
			'sport'       => 'Flag Football',
			'description' => 'Dedicated women\'s flag football uniforms. Athletic cut, performance fabrics, built for the game.',
			'colors'      => array( 'Royal', 'Navy', 'Black', 'White', 'Pink', 'Lavender' ),
			'styles'      => array( 'Standard', 'Alternate', 'Compression' ),
			'materials'   => array( 'Performance Mesh', 'Dri-FIT Polyester' ),
		),
		array(
			'slug'        => 'battle-charlie-tackle',
			'name'        => 'Battle Charlie Tackle',
			'sport'       => 'Tackle Football',
			'description' => 'Jerseys and pants with custom logo and stripe options. Durability meets style.',
			'colors'      => array( 'Royal', 'Navy', 'Black', 'White', 'Red', 'Orange' ),
			'styles'      => array( 'Standard', 'Alternate', 'Custom Stripe' ),
			'materials'   => array( 'Heavyweight Mesh', 'Dri-FIT Polyester', 'Tackle Twill' ),
		),
		array(
			'slug'        => 'battle-alpha-tackle',
			'name'        => 'Battle Alpha Tackle',
			'sport'       => 'Tackle Football',
			'description' => 'Premium tackle jerseys and pants with tackle twill upgrade option. Pro-level performance.',
			'colors'      => array( 'Royal', 'Navy', 'Black', 'White', 'Scarlet', 'Gold' ),
			'styles'      => array( 'Standard', 'Alternate', 'Tackle Twill Upgrade' ),
			'materials'   => array( 'Pro Mesh', 'Dri-FIT Polyester', 'Heavyweight Tackle Twill' ),
		),
		array(
			'slug'        => 'battle-bravo-tackle',
			'name'        => 'Battle Bravo Tackle',
			'sport'       => 'Tackle Football',
			'description' => 'High-performance tackle football jerseys and pants. Built for the trenches.',
			'colors'      => array( 'Royal', 'Navy', 'Black', 'White', 'Maroon', 'Silver' ),
			'styles'      => array( 'Standard', 'Alternate', 'Compression' ),
			'materials'   => array( 'Heavyweight Mesh', 'Dri-FIT Polyester', 'Tackle Twill' ),
		),
	);
}

/**
 * Returns hex color for a named swatch.
 *
 * @param string $name Color name (e.g. Royal, Navy).
 * @return string Hex color.
 */
function bst_get_swatch_color( string $name ): string {
	$map = array(
		'Royal'        => '#4169e1',
		'Navy'         => '#001f3f',
		'Black'        => '#111111',
		'White'        => '#f8f8f8',
		'Red'          => '#dc3545',
		'Forest'       => '#228b22',
		'Kelly Green'  => '#2e8b57',
		'Pink'         => '#ff69b4',
		'Lavender'     => '#e6e6fa',
		'Orange'       => '#ff8c00',
		'Scarlet'      => '#ff2400',
		'Gold'         => '#ffd700',
		'Maroon'       => '#800000',
		'Silver'       => '#c0c0c0',
	);
	return $map[ $name ] ?? '#666666';
}

/**
 * Returns a single product by slug, or null if not found.
 *
 * @param string $slug Product slug (e.g. battle-7v7).
 * @return array{slug: string, name: string, sport: string, description: string, colors: array<string>, styles: array<string>, materials: array<string>}|null
 */
function bst_get_product_by_slug( string $slug ): ?array {
	foreach ( bst_get_catalog_products() as $product ) {
		if ( $product['slug'] === $slug ) {
			$product['colors']   = $product['colors'] ?? array( 'Royal', 'Navy', 'Black', 'White', 'Red', 'Forest' );
			$product['styles']   = $product['styles'] ?? array( 'Standard', 'Alternate', 'Compression' );
			$product['materials'] = $product['materials'] ?? array( 'Performance Mesh', 'Dri-FIT Polyester', 'Heavyweight Tackle Twill' );
			return $product;
		}
	}
	return null;
}
