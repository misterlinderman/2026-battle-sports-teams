<?php

declare(strict_types=1);

/**
 * Plugin Name: Battle Sports Platform
 * Plugin URI: https://battleuniforms.com
 * Description: Custom platform for Battle Sports uniform orders, customer portal, artwork queue, and roster management.
 * Version: 0.1.0
 * Author: Ask and Deliver
 * Text Domain: battle-sports-platform
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.2
 */

namespace BattleSports;

defined('ABSPATH') || exit;

define('BSP_VERSION', '0.1.0');
define('BSP_PLUGIN_FILE', __FILE__);
define('BSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BSP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * PSR-4 autoloader for BattleSports\ namespace mapping to includes/
 * Uses WordPress class-{slug}.php naming convention.
 *
 * @param string $class Fully qualified class name
 * @return void
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'BattleSports\\';
    $base_dir = BSP_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);
    $slug = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class_name));
    $file_name = 'class-' . $slug . '.php';

    $path = $base_dir;
    foreach ($parts as $part) {
        $path .= strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $part)) . '/';
    }
    $file = $path . $file_name;

    if (is_file($file)) {
        require $file;
    }
});

register_activation_hook(BSP_PLUGIN_FILE, static function (): void {
    Database::install();
    \BattleSports\CustomerPortal\Portal::create_portal_page_on_activation();
    \BattleSports\CustomerPortal\CoachRegistration::create_pages_on_activation();
    \BattleSports\Plugin::create_submission_fee_product();
});

add_action('plugins_loaded', static function (): void {
    Plugin::get_instance()->init();
}, 0);
