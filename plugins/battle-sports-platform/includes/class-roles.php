<?php

declare(strict_types=1);

namespace BattleSports;

/**
 * Registers custom user roles for the Battle Sports platform.
 *
 * Roles: bsp_coach (portal/orders/roster), bsp_designer (artwork queue/proofs).
 */
final class Roles {

    private const ROLE_COACH = 'bsp_coach';
    private const ROLE_DESIGNER = 'bsp_designer';
    private const OPTION_ROLES_REGISTERED = 'bsp_roles_registered';

    /**
     * Registers custom roles on init (priority 0).
     *
     * Uses option flag to avoid re-adding roles on every page load.
     *
     * @return void
     */
    public static function register(): void {
        if (!get_option(self::OPTION_ROLES_REGISTERED)) {
            add_role(
                self::ROLE_COACH,
                __('Coach / Customer', 'battle-sports-platform'),
                [
                    'read' => true,
                    'upload_files' => true,
                    'bsp_submit_order' => true,
                    'bsp_view_portal' => true,
                    'bsp_manage_roster' => true,
                ]
            );

            add_role(
                self::ROLE_DESIGNER,
                __('Designer', 'battle-sports-platform'),
                [
                    'read' => true,
                    'bsp_view_portal' => true,
                    'bsp_view_artwork_queue' => true,
                    'bsp_upload_proof' => true,
                    'bsp_manage_artwork_queue' => true,
                ]
            );

            update_option(self::OPTION_ROLES_REGISTERED, true);
        }

        // Grant portal, artwork, and settings capabilities to administrators (runs every init, idempotent).
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('bsp_view_portal');
            $admin->add_cap('bsp_manage_roster');
            $admin->add_cap('bsp_submit_order');
            $admin->add_cap('bsp_view_artwork_queue');
            $admin->add_cap('bsp_upload_proof');
            $admin->add_cap('bsp_manage_artwork_queue');
            $admin->add_cap('bsp_manage_settings');
        }

        // Grant bsp_manage_settings to users with admin-level caps (map_meta_cap + user_has_cap for reliability).
        add_filter('map_meta_cap', [self::class, 'map_bsp_manage_settings'], 10, 4);
        add_filter('user_has_cap', [self::class, 'grant_bsp_manage_settings'], 1, 4);

        // Ensure designer has required caps (fixes roles created before these existed).
        $designer = get_role(self::ROLE_DESIGNER);
        if ($designer) {
            if (empty($designer->capabilities['bsp_manage_artwork_queue'])) {
                $designer->add_cap('bsp_manage_artwork_queue');
            }
            if (empty($designer->capabilities['bsp_view_portal'])) {
                $designer->add_cap('bsp_view_portal');
            }
        }
    }

    /**
     * Maps bsp_manage_settings so users with admin-level caps can access Battle Sports admin.
     *
     * @param array  $caps    Required capabilities.
     * @param string $cap     Capability being checked.
     * @param int    $user_id User ID.
     * @param array  $args    Additional args.
     * @return array
     */
    public static function map_bsp_manage_settings(array $caps, string $cap, int $user_id, array $args): array {
        if ($cap !== 'bsp_manage_settings') {
            return $caps;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return ['do_not_allow'];
        }
        $admin_caps = ['manage_options', 'activate_plugins', 'edit_theme_options'];
        foreach ($admin_caps as $admin_cap) {
            if (user_can($user, $admin_cap)) {
                return ['read'];
            }
        }
        return ['do_not_allow'];
    }

    /**
     * Directly grants bsp_manage_settings to users with admin-level caps.
     *
     * @param array   $allcaps All capabilities for the user.
     * @param array   $caps    Capabilities being checked.
     * @param array   $args    Additional args.
     * @param \WP_User $user   User object.
     * @return array
     */
    public static function grant_bsp_manage_settings(array $allcaps, array $caps, array $args, $user): array {
        $admin_caps = ['manage_options', 'activate_plugins', 'edit_theme_options'];
        foreach ($admin_caps as $admin_cap) {
            if (!empty($allcaps[$admin_cap])) {
                $allcaps['bsp_manage_settings'] = true;
                break;
            }
        }
        return $allcaps;
    }
}
