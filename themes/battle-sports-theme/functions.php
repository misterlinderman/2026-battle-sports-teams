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
