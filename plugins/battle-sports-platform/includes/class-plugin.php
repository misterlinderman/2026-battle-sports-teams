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
    }

    /**
     * Loads intake form (multi-step order forms).
     *
     * @return void
     */
    public function load_intake(): void {
        \BattleSports\Intake\IntakeForm::init();
    }

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct() {
    }
}
