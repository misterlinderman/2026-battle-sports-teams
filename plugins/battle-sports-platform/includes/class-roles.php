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
                    'bsp_view_artwork_queue' => true,
                    'bsp_upload_proof' => true,
                    'bsp_manage_artwork_queue' => true,
                ]
            );

            update_option(self::OPTION_ROLES_REGISTERED, true);
        }

        // Grant artwork capabilities to administrators (runs every init, idempotent).
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('bsp_view_artwork_queue');
            $admin->add_cap('bsp_upload_proof');
            $admin->add_cap('bsp_manage_artwork_queue');
        }

        // Ensure designer has bsp_manage_artwork_queue (fixes roles created before this cap existed).
        $designer = get_role(self::ROLE_DESIGNER);
        if ($designer && empty($designer->capabilities['bsp_manage_artwork_queue'])) {
            $designer->add_cap('bsp_manage_artwork_queue');
        }
    }
}
