<?php

declare(strict_types=1);

namespace BattleSports\CustomerPortal;

defined('ABSPATH') || exit;

/**
 * Customer portal dashboard data provider.
 *
 * Queries teams, orders, and pending approvals for the portal dashboard.
 */
final class Dashboard {

    /**
     * Gets teams belonging to the user.
     *
     * @param int $user_id WordPress user ID.
     * @return list<object>
     */
    public function get_user_teams(int $user_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'bsp_teams';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY team_name ASC",
                $user_id
            ),
            OBJECT_K
        );

        return $results ? array_values((array) $results) : [];
    }

    /**
     * Gets recent artwork queue orders for the user.
     *
     * @param int $user_id WordPress user ID.
     * @param int $limit   Maximum number of orders to return. Default 5.
     * @return list<object>
     */
    public function get_recent_orders(int $user_id, int $limit = 5): array {
        global $wpdb;
        $table = $wpdb->prefix . 'bsp_artwork_queue';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY submitted_at DESC LIMIT %d",
                $user_id,
                $limit
            ),
            OBJECT_K
        );

        return $results ? array_values((array) $results) : [];
    }

    /**
     * Gets artwork items with status 'proof_sent' belonging to the user's teams or orders.
     *
     * @param int $user_id WordPress user ID.
     * @return list<object>
     */
    public function get_pending_approvals(int $user_id): array {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'bsp_artwork_queue';
        $teams_table = $wpdb->prefix . 'bsp_teams';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT q.* FROM {$queue_table} q
                LEFT JOIN {$teams_table} t ON q.team_id = t.id
                WHERE q.status = %s AND (t.user_id = %d OR q.user_id = %d)
                ORDER BY q.updated_at DESC",
                'proof_sent',
                $user_id,
                $user_id
            ),
            OBJECT_K
        );

        return $results ? array_values((array) $results) : [];
    }
}
