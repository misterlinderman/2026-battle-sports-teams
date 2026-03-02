<?php

declare(strict_types=1);

namespace BattleSports\CustomerPortal;

defined('ABSPATH') || exit;

/**
 * Coach registration and onboarding.
 *
 * Multi-step form: Account → Program → First Team (name, logo, colors).
 * Creates user, program, team without requiring an intake order.
 */
final class CoachRegistration {

    private const SHORTCODE = 'bsp_coach_register';
    private const NONCE_ACTION = 'bsp_coach_register';
    private const MAX_LOGO_BYTES = 52428800; // 50MB

    /**
     * Initializes registration and registers shortcode.
     *
     * @return void
     */
    public static function init(): void {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
        // handle_submission is registered in Plugin::register_hooks() at init priority 5
    }

    /**
     * Renders the registration shortcode output.
     *
     * @param array<string, string> $atts Shortcode attributes.
     * @return string
     */
    public static function render(array $atts = []): string {
        if (is_user_logged_in() && current_user_can('bsp_view_portal')) {
            $portal = get_page_by_path('portal', OBJECT, 'page');
            $url = $portal ? get_permalink($portal) : home_url('/portal/');
            return '<div class="bsp-register bsp-register--logged-in">' .
                '<p>' . esc_html__('You are already logged in.', 'battle-sports-platform') . '</p>' .
                '<p><a href="' . esc_url($url) . '" class="bsp-btn-primary">' . esc_html__('Go to My Portal', 'battle-sports-platform') . '</a></p>' .
                '</div>';
        }

        $error = isset($_GET['bsp_register_error']) ? sanitize_text_field(wp_unslash($_GET['bsp_register_error'])) : '';
        $success = isset($_GET['bsp_register_success']) ? true : false;

        if ($success) {
            $portal = get_page_by_path('portal', OBJECT, 'page');
            $url = $portal ? get_permalink($portal) : home_url('/portal/');
            return '<div class="bsp-register bsp-register--success">' .
                '<p>' . esc_html__('Your account has been created. Redirecting to your portal...', 'battle-sports-platform') . '</p>' .
                '<p><a href="' . esc_url($url) . '" class="bsp-btn-primary">' . esc_html__('Go to My Portal', 'battle-sports-platform') . '</a></p>' .
                '<script>setTimeout(function(){ window.location.href = ' . wp_json_encode($url) . '; }, 2000);</script>' .
                '</div>';
        }

        self::enqueue_assets();
        ob_start();
        include BSP_PLUGIN_DIR . 'templates/coach-registration.php';
        return ob_get_clean();
    }

    /**
     * Enqueues registration form assets.
     *
     * @return void
     */
    private static function enqueue_assets(): void {
        wp_enqueue_style(
            'bsp-coach-register',
            BSP_PLUGIN_URL . 'assets/src/css/coach-registration.css',
            [],
            BSP_VERSION
        );
        wp_enqueue_script(
            'bsp-coach-register',
            BSP_PLUGIN_URL . 'assets/src/js/coach-registration.js',
            [],
            BSP_VERSION,
            true
        );
    }

    /**
     * Handles form submission.
     *
     * @return void
     */
    public static function handle_submission(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bsp_coach_register_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bsp_coach_register_nonce'])), self::NONCE_ACTION)) {
            wp_safe_redirect(add_query_arg('bsp_register_error', urlencode(__('Security check failed. Please try again.', 'battle-sports-platform')), wp_get_referer() ?: home_url('/register/')));
            exit;
        }

        $email = isset($_POST['bsp_email']) ? sanitize_email(wp_unslash($_POST['bsp_email'])) : '';
        $password = isset($_POST['bsp_password']) ? $_POST['bsp_password'] : '';
        $first_name = isset($_POST['bsp_first_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_first_name'])) : '';
        $last_name = isset($_POST['bsp_last_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_last_name'])) : '';
        $program_name = isset($_POST['bsp_program_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_program_name'])) : '';
        $team_name = isset($_POST['bsp_team_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_team_name'])) : '';
        $primary_color = isset($_POST['bsp_primary_color']) ? sanitize_text_field(wp_unslash($_POST['bsp_primary_color'])) : '';
        $secondary_color = isset($_POST['bsp_secondary_color']) ? sanitize_text_field(wp_unslash($_POST['bsp_secondary_color'])) : '';

        $errors = [];
        if (!is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'battle-sports-platform');
        }
        if (strlen($password) < 8) {
            $errors[] = __('Password must be at least 8 characters.', 'battle-sports-platform');
        }
        if (trim($first_name) === '') {
            $errors[] = __('First name is required.', 'battle-sports-platform');
        }
        if (trim($last_name) === '') {
            $errors[] = __('Last name is required.', 'battle-sports-platform');
        }
        if (trim($program_name) === '') {
            $errors[] = __('Program name is required.', 'battle-sports-platform');
        }
        if (trim($team_name) === '') {
            $errors[] = __('Team name is required.', 'battle-sports-platform');
        }

        if (email_exists($email)) {
            $errors[] = __('An account with this email already exists.', 'battle-sports-platform');
        }

        $username = sanitize_user(str_replace(['@', '+', '.'], ['-', '-', '-'], $email), true);
        if (username_exists($username)) {
            $username = $username . '_' . wp_rand(100, 999);
        }

        if (!empty($errors)) {
            wp_safe_redirect(add_query_arg('bsp_register_error', urlencode(implode(' ', $errors)), wp_get_referer() ?: home_url('/register/')));
            exit;
        }

        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'   => $password,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name) ?: $username,
            'role'         => 'bsp_coach',
        ]);

        if (is_wp_error($user_id)) {
            wp_safe_redirect(add_query_arg('bsp_register_error', urlencode($user_id->get_error_message()), wp_get_referer() ?: home_url('/register/')));
            exit;
        }

        wp_set_current_user($user_id);

        global $wpdb;
        $prefix = $wpdb->prefix;

        $program_id = null;
        $wpdb->insert(
            $prefix . 'bsp_programs',
            [
                'user_id' => $user_id,
                'name'    => $program_name,
            ],
            ['%d', '%s']
        );
        if ($wpdb->insert_id) {
            $program_id = (int) $wpdb->insert_id;
        }

        $logo_attachment_id = null;
        if (!empty($_FILES['bsp_team_logo']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $file = $_FILES['bsp_team_logo'];
            if ($file['size'] <= self::MAX_LOGO_BYTES && $file['error'] === UPLOAD_ERR_OK) {
                $upload = wp_handle_upload($file, ['test_form' => false]);
                if (!isset($upload['error'])) {
                    $attachment = [
                        'post_mime_type' => $upload['type'],
                        'post_title'     => sanitize_file_name($file['name']),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment($attachment, $upload['file']);
                    if ($attach_id && !is_wp_error($attach_id)) {
                        wp_generate_attachment_metadata($attach_id, $upload['file']);
                        $logo_attachment_id = $attach_id;
                    }
                }
            }
        }

        $wpdb->insert(
            $prefix . 'bsp_teams',
            [
                'user_id'            => $user_id,
                'program_id'         => $program_id,
                'org_name'           => $program_name,
                'team_name'          => $team_name,
                'primary_color'      => $primary_color ?: null,
                'secondary_color'   => $secondary_color ?: null,
                'logo_attachment_id' => $logo_attachment_id,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
        );

        wp_set_auth_cookie($user_id, true);

        $register_page = get_page_by_path('register', OBJECT, 'page');
        $redirect = $register_page ? add_query_arg('bsp_register_success', '1', get_permalink($register_page)) : home_url('/register/?bsp_register_success=1');
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Creates the register, login, and add-team pages on activation.
     *
     * @return void
     */
    public static function create_pages_on_activation(): void {
        $login_page = get_page_by_path('login', OBJECT, 'page');
        if (!$login_page) {
            wp_insert_post([
                'post_title'   => __('Log In', 'battle-sports-platform'),
                'post_name'    => 'login',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '[bsp_login]',
                'post_author'  => 1,
            ], true);
        }

        $register_page = get_page_by_path('register', OBJECT, 'page');
        if (!$register_page) {
            wp_insert_post([
                'post_title'   => __('Create Account', 'battle-sports-platform'),
                'post_name'    => 'register',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '[' . self::SHORTCODE . ']',
                'post_author'  => 1,
            ], true);
        }

        $portal = get_page_by_path('portal', OBJECT, 'page');
        $portal_id = $portal ? (int) $portal->ID : 0;
        if ($portal_id > 0) {
            $children = get_pages(['parent' => $portal_id, 'post_status' => 'publish', 'number' => 50]);
            $has_add_team = false;
            foreach ($children ?: [] as $child) {
                if ($child->post_name === 'add-team') {
                    $has_add_team = true;
                    break;
                }
            }
            if (!$has_add_team) {
                wp_insert_post([
                    'post_title'   => __('Add Team', 'battle-sports-platform'),
                    'post_name'    => 'add-team',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '[bsp_add_team]',
                    'post_author'  => 1,
                    'post_parent'  => $portal_id,
                ], true);
            }
        }
    }
}
