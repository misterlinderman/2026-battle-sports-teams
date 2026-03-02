<?php

declare(strict_types=1);

namespace BattleSports;

/**
 * Main plugin bootstrap class.
 *
 * Orchestrates initialization of roles, post types, REST API, and customer portal.
 */
final class Plugin {

    private static ?self $instance = null;

    /**
     * Retrieves the plugin instance.
     *
     * @return self
     */
    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin and hooks into WordPress.
     *
     * @return void
     */
    public function init(): void {
        $this->register_hooks();
    }

    /**
     * Registers WordPress hooks for plugin components.
     *
     * Stub: will be filled per phase.
     *
     * @return void
     */
    public function register_hooks(): void {
        add_action('init', [$this, 'load_roles'], 0);
        add_action('init', [$this, 'load_post_types']);
        add_action('rest_api_init', [$this, 'load_rest_api']);
        add_action('init', [$this, 'load_portal']);
        add_action('init', [$this, 'load_intake']);
        $this->load_admin();

        // Register POST handlers on init at priority 5.
        // These must be registered at plugins_loaded; if added from within init,
        // they would never run in the same request (WordPress fires init once).
        add_action('init', [\BattleSports\CustomerPortal\CoachRegistration::class, 'handle_submission'], 5);
        add_action('init', [\BattleSports\CustomerPortal\AddTeam::class, 'handle_submission'], 5);
        add_action('init', [\BattleSports\Intake\IntakeHandler::class, 'maybe_handle_submission'], 5);
    }

    /**
     * Loads custom user roles (bsp_coach, bsp_designer).
     *
     * @return void
     */
    public function load_roles(): void {
        Roles::register();
    }

    /**
     * Loads custom post types (bsp_roster, bsp_team, bsp_artwork, bsp_order).
     *
     * @return void
     */
    public function load_post_types(): void {
        // Stub: will be implemented in class-post-types.php
    }

    /**
     * Loads REST API endpoints under battle-sports/v1 namespace.
     *
     * @return void
     */
    public function load_rest_api(): void {
        $rest_api = new RestApi();
        $rest_api->register_routes();
    }

    /**
     * Loads customer portal functionality.
     *
     * @return void
     */
    public function load_portal(): void {
        \BattleSports\CustomerPortal\Portal::init();
        \BattleSports\CustomerPortal\CoachRegistration::init();
        \BattleSports\CustomerPortal\AddTeam::init();
    }

    /**
     * Loads intake form (multi-step order forms) and submission handler.
     *
     * @return void
     */
    public function load_intake(): void {
        \BattleSports\Intake\IntakeForm::init();
        \BattleSports\Intake\IntakeHandler::init();
    }

    /**
     * Loads admin settings page.
     *
     * @return void
     */
    public function load_admin(): void {
        \BattleSports\Admin\AdminSettings::init();
    }

    /**
     * Creates the $50 submission fee WooCommerce product on plugin activation.
     *
     * Checks if option 'bsp_submission_fee_product_id' exists and product still exists.
     * If not, creates a WooCommerce simple product and stores its ID.
     *
     * @return void
     */
    public static function create_submission_fee_product(): void {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $product_id = (int) get_option('bsp_submission_fee_product_id', 0);
        if ($product_id > 0) {
            $product = wc_get_product($product_id);
            if ($product && $product->exists()) {
                return;
            }
        }

        $product = new \WC_Product_Simple();
        $product->set_name(__('Uniform Design Submission Fee', 'battle-sports-platform'));
        $product->set_regular_price('50.00');
        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_status('publish');
        $product->set_sku('BSP-SUBMISSION-FEE');
        $product->save();

        update_option('bsp_submission_fee_product_id', $product->get_id());
    }

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct() {
    }
}
