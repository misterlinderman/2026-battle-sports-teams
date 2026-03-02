<?php

declare(strict_types=1);

namespace BattleSports\CustomerPortal;

defined('ABSPATH') || exit;

/**
 * Add Team form — allows coaches to create teams (with program) without intake.
 */
final class AddTeam {

    public const SHORTCODE = 'bsp_add_team';
    private const NONCE_ACTION = 'bsp_add_team';
    private const MAX_LOGO_BYTES = 52428800;

    /**
     * Initializes and registers shortcode.
     *
     * @return void
     */
    public static function init(): void {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
        // handle_submission is registered in Plugin::register_hooks() at init priority 5
    }

    /**
     * Renders the Add Team shortcode.
     *
     * @param array<string, string> $atts Shortcode attributes.
     * @return string
     */
    public static function render(array $atts = []): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to add a team.', 'battle-sports-platform') . '</p>';
        }
        if (!current_user_can('bsp_view_portal')) {
            return '<p>' . esc_html__('You do not have permission to add teams.', 'battle-sports-platform') . '</p>';
        }

        $portal = get_page_by_path('portal', OBJECT, 'page');
        $portal_url = $portal ? get_permalink($portal) : home_url('/portal/');
        $error = isset($_GET['bsp_add_team_error']) ? sanitize_text_field(wp_unslash($_GET['bsp_add_team_error'])) : '';
        $success = isset($_GET['bsp_add_team_success']) ? true : false;

        if ($success) {
            return '<div class="bsp-add-team bsp-add-team--success">' .
                '<p>' . esc_html__('Team added successfully.', 'battle-sports-platform') . '</p>' .
                '<p><a href="' . esc_url($portal_url) . '" class="bsp-btn-primary">' . esc_html__('Back to Portal', 'battle-sports-platform') . '</a></p>' .
                '<p><a href="' . esc_url(\BattleSports\CustomerPortal\Portal::get_rosters_page_url()) . '" class="bsp-btn-secondary">' . esc_html__('Manage Rosters', 'battle-sports-platform') . '</a></p>' .
                '</div>';
        }

        wp_enqueue_style('bsp-coach-register', BSP_PLUGIN_URL . 'assets/src/css/coach-registration.css', [], BSP_VERSION);

        $add_team_page = get_page_by_path('add-team', OBJECT, 'page');
        $parent_portal = get_page_by_path('portal', OBJECT, 'page');

        ob_start();
        $action = $add_team_page ? get_permalink($add_team_page) : home_url('/portal/add-team/');
        if ($parent_portal) {
            $children = get_pages(['parent' => $parent_portal->ID, 'post_name' => 'add-team', 'number' => 1]);
            if (!empty($children)) {
                $action = get_permalink($children[0]);
            }
        }
        include BSP_PLUGIN_DIR . 'templates/add-team.php';
        return ob_get_clean();
    }

    /**
     * Handles form submission.
     *
     * @return void
     */
    public static function handle_submission(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bsp_add_team_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bsp_add_team_nonce'])), self::NONCE_ACTION)) {
            wp_safe_redirect(add_query_arg('bsp_add_team_error', urlencode(__('Security check failed.', 'battle-sports-platform')), wp_get_referer() ?: home_url('/portal/add-team/')));
            exit;
        }

        if (!is_user_logged_in() || !current_user_can('bsp_submit_order')) {
            wp_safe_redirect(add_query_arg('bsp_add_team_error', urlencode(__('Permission denied.', 'battle-sports-platform')), wp_get_referer() ?: home_url('/portal/add-team/')));
            exit;
        }

        $program_id = isset($_POST['bsp_program_id']) ? absint($_POST['bsp_program_id']) : 0;
        $program_name = isset($_POST['bsp_program_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_program_name'])) : '';
        $team_name = isset($_POST['bsp_team_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_team_name'])) : '';
        $primary_color = isset($_POST['bsp_primary_color']) ? sanitize_text_field(wp_unslash($_POST['bsp_primary_color'])) : '';
        $secondary_color = isset($_POST['bsp_secondary_color']) ? sanitize_text_field(wp_unslash($_POST['bsp_secondary_color'])) : '';

        global $wpdb;
        $org_name = $program_name;
        if ($program_id > 0) {
            $prog = $wpdb->get_row($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}bsp_programs WHERE id = %d AND user_id = %d",
                $program_id,
                get_current_user_id()
            ));
            if ($prog) {
                $org_name = $prog->name;
            }
        } elseif (trim($program_name) !== '') {
            $wpdb->insert(
                $wpdb->prefix . 'bsp_programs',
                ['user_id' => get_current_user_id(), 'name' => trim($program_name)],
                ['%d', '%s']
            );
            if ($wpdb->insert_id) {
                $program_id = (int) $wpdb->insert_id;
                $org_name = trim($program_name);
            }
        }
        if (trim($org_name) === '' && $program_id <= 0) {
            wp_safe_redirect(add_query_arg('bsp_add_team_error', urlencode(__('Program or organization name is required.', 'battle-sports-platform')), wp_get_referer() ?: home_url('/portal/add-team/')));
            exit;
        }
        if (trim($team_name) === '') {
            wp_safe_redirect(add_query_arg('bsp_add_team_error', urlencode(__('Team name is required.', 'battle-sports-platform')), wp_get_referer() ?: home_url('/portal/add-team/')));
            exit;
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

        global $wpdb;
        $table = $wpdb->prefix . 'bsp_teams';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            \BattleSports\Database::install();
        }
        $user_id = get_current_user_id();
        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'            => $user_id,
                'program_id'         => $program_id > 0 ? $program_id : null,
                'org_name'           => $org_name,
                'team_name'          => $team_name,
                'primary_color'      => $primary_color ?: null,
                'secondary_color'    => $secondary_color ?: null,
                'logo_attachment_id' => $logo_attachment_id,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
        );
        if ($inserted === false) {
            wp_safe_redirect(add_query_arg('bsp_add_team_error', urlencode(__('Could not save team. Please try again.', 'battle-sports-platform')), wp_get_referer() ?: home_url('/portal/add-team/')));
            exit;
        }

        $redirect = wp_get_referer();
        if (!$redirect) {
            $add_team_page = get_pages(['post_name' => 'add-team', 'number' => 1]);
            $redirect = !empty($add_team_page) ? add_query_arg('bsp_add_team_success', '1', get_permalink($add_team_page[0])) : home_url('/portal/?bsp_add_team_success=1');
        } else {
            $redirect = add_query_arg('bsp_add_team_success', '1', $redirect);
        }
        wp_safe_redirect($redirect);
        exit;
    }
}
