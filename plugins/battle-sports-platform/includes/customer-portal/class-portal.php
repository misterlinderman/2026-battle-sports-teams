<?php

declare(strict_types=1);

namespace BattleSports\CustomerPortal;

defined('ABSPATH') || exit;

/**
 * Customer portal shortcode and access control.
 *
 * Registers [bsp_portal] shortcode. Shows login form for guests,
 * "pending approval" for users without bsp_view_portal, or dashboard for authorized users.
 */
final class Portal {

    private const SHORTCODE = 'bsp_portal';
    private const SHORTCODE_ROSTER = 'bsp_roster_manager';

    /**
     * Initializes the portal and registers the shortcode.
     *
     * @return void
     */
    public static function init(): void {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
        add_shortcode(self::SHORTCODE_ROSTER, [self::class, 'render_roster_manager']);
    }

    /**
     * Renders the portal shortcode output.
     *
     * @param array<string, string> $atts Shortcode attributes (unused).
     * @return string
     */
    public static function render(array $atts = []): string {
        if (!is_user_logged_in()) {
            return self::render_login_form();
        }

        if (!current_user_can('bsp_view_portal')) {
            return self::render_pending_approval();
        }

        return self::render_dashboard();
    }

    /**
     * Renders the WordPress login form.
     *
     * @return string
     */
    private static function render_login_form(): string {
        $form = wp_login_form(
            [
                'echo'             => false,
                'redirect'         => get_permalink(),
                'form_id'          => 'bsp-portal-login-form',
                'label_username'   => __('Username or Email', 'battle-sports-platform'),
                'label_password'   => __('Password', 'battle-sports-platform'),
                'label_remember'   => __('Remember Me', 'battle-sports-platform'),
                'label_log_in'     => __('Log In', 'battle-sports-platform'),
                'id_username'     => 'bsp-portal-user_login',
                'id_password'     => 'bsp-portal-user_pass',
                'id_remember'     => 'bsp-portal-rememberme',
                'id_submit'       => 'bsp-portal-wp-submit',
                'remember'         => true,
                'value_username'   => '',
                'value_remember'   => false,
            ]
        );

        return '<div class="bsp-portal bsp-portal--login">' .
            '<p class="bsp-portal__login-message">' . esc_html__('Please log in to access the customer portal.', 'battle-sports-platform') . '</p>' .
            $form .
            '</div>';
    }

    /**
     * Renders the pending approval message.
     *
     * @return string
     */
    private static function render_pending_approval(): string {
        return '<div class="bsp-portal bsp-portal--pending">' .
            '<p class="bsp-portal__pending-message">' .
            esc_html__('Your account is pending approval. You will receive access to the portal once your account has been approved.', 'battle-sports-platform') .
            '</p>' .
            '</div>';
    }

    /**
     * Renders the portal dashboard.
     *
     * @return string
     */
    private static function render_dashboard(): string {
        $user_id = get_current_user_id();
        $dashboard = new Dashboard();
        $teams = $dashboard->get_user_teams($user_id);
        $recent_orders = $dashboard->get_recent_orders($user_id, 5);
        $pending_approvals = $dashboard->get_pending_approvals($user_id);

        ob_start();
        include BSP_PLUGIN_DIR . 'templates/portal-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Renders the roster manager shortcode.
     *
     * Shows roster-view.php if user has bsp_manage_roster capability.
     *
     * @param array<string, string> $atts Shortcode attributes (unused).
     * @return string
     */
    public static function render_roster_manager(array $atts = []): string {
        if (!is_user_logged_in()) {
            return self::render_login_form();
        }

        if (!current_user_can('bsp_view_portal')) {
            return self::render_pending_approval();
        }

        if (!current_user_can('bsp_manage_roster')) {
            return '<div class="bsp-portal bsp-portal--pending">' .
                '<p class="bsp-portal__pending-message">' .
                esc_html__('You do not have permission to manage rosters.', 'battle-sports-platform') .
                '</p></div>';
        }

        self::enqueue_roster_assets();
        ob_start();
        include BSP_PLUGIN_DIR . 'templates/roster-view.php';
        return ob_get_clean();
    }

    /**
     * Enqueues roster manager script and localizes bspData.
     *
     * @return void
     */
    private static function enqueue_roster_assets(): void {
        $handle = 'bsp-roster-manager';
        $src    = BSP_PLUGIN_URL . 'assets/src/js/roster.js';
        wp_enqueue_script(
            $handle,
            $src,
            [],
            BSP_VERSION,
            true
        );
        wp_localize_script(
            $handle,
            'bspData',
            [
                'nonce'  => wp_create_nonce('wp_rest'),
                'apiUrl' => esc_url_raw(rest_url('battle-sports/v1')),
            ]
        );
    }

    /**
     * Gets the rosters page URL.
     *
     * @return string
     */
    public static function get_rosters_page_url(): string {
        $portal = get_page_by_path('portal', OBJECT, 'page');
        if (!$portal) {
            return home_url('/portal/rosters/');
        }
        $children = get_pages(
            [
                'parent'      => $portal->ID,
                'post_status' => 'publish',
                'number'      => 50,
            ]
        );
        foreach ($children ?: [] as $child) {
            if ($child->post_name === 'rosters') {
                return get_permalink($child);
            }
        }
        return home_url('/portal/rosters/');
    }

    /**
     * Creates the portal page on plugin activation if it does not exist.
     *
     * @return void
     */
    public static function create_portal_page_on_activation(): void {
        $slug = 'portal';
        $existing = get_page_by_path($slug, OBJECT, 'page');
        $portal_id = 0;

        if ($existing) {
            $portal_id = (int) $existing->ID;
        } else {
            $template = 'templates/template-portal.php';
            $theme = wp_get_theme();
            $template_file = $theme->get_stylesheet_directory() . '/' . $template;

            if (!is_file($template_file)) {
                $template = 'template-portal.php';
                $template_file = $theme->get_stylesheet_directory() . '/' . $template;
            }

            $page_id = wp_insert_post(
                [
                    'post_title'   => __('Customer Portal', 'battle-sports-platform'),
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '[' . self::SHORTCODE . ']',
                    'post_author'  => 1,
                ],
                true
            );

            if (!is_wp_error($page_id) && is_file($template_file)) {
                update_post_meta($page_id, '_wp_page_template', $template);
            }
            $portal_id = !is_wp_error($page_id) ? (int) $page_id : 0;
        }

        self::create_rosters_page_on_activation($portal_id);
    }

    /**
     * Creates the rosters sub-page under portal on activation.
     *
     * @param int $portal_page_id Portal page ID.
     * @return void
     */
    public static function create_rosters_page_on_activation(int $portal_page_id): void {
        if ($portal_page_id <= 0) {
            return;
        }

        $children = get_pages(
            [
                'parent'      => $portal_page_id,
                'post_status' => 'any',
                'number'      => 100,
            ]
        );
        foreach ($children ?: [] as $child) {
            if ($child->post_name === 'rosters') {
                return;
            }
        }

        wp_insert_post(
            [
                'post_title'   => __('Manage Rosters', 'battle-sports-platform'),
                'post_name'    => 'rosters',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '[' . self::SHORTCODE_ROSTER . ']',
                'post_author'  => 1,
                'post_parent'  => $portal_page_id,
            ],
            true
        );
    }
}
