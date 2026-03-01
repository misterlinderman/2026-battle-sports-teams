<?php

declare(strict_types=1);

namespace BattleSports\Artwork;

defined('ABSPATH') || exit;

/**
 * Artwork queue management.
 *
 * Manages wp_bsp_artwork_queue records and status transitions per the
 * state machine: submitted → in_queue → in_progress → proof_sent →
 * revision_requested (loop) | approved → complete
 */
final class ArtworkQueue {

	private const TABLE = 'bsp_artwork_queue';
	private const LOG_TABLE = 'bsp_artwork_log';

	/** Valid statuses in order of flow. */
	private const STATUSES = [
		'submitted',
		'in_queue',
		'in_progress',
		'proof_sent',
		'revision_requested',
		'approved',
		'complete',
	];

	/**
	 * Valid transitions: from_status => [to_status, ...]
	 */
	private const TRANSITIONS = [
		'submitted'         => ['in_queue'],
		'in_queue'          => ['in_progress'],
		'in_progress'       => ['proof_sent', 'revision_requested'],
		'proof_sent'        => ['approved', 'revision_requested'],
		'revision_requested' => ['in_progress'],
		'approved'          => ['complete'],
		'complete'          => [],
	];

	/**
	 * Creates a new artwork queue record.
	 *
	 * @param array{order_ref: string, team_id?: int, user_id: int, product_type?: string} $args Required: order_ref, user_id.
	 * @return int|false Artwork ID on success, false on failure.
	 */
	public function create(array $args): int|false {
		global $wpdb;

		$order_ref   = sanitize_text_field($args['order_ref'] ?? '');
		$user_id     = absint($args['user_id'] ?? 0);
		$team_id     = isset($args['team_id']) ? absint($args['team_id']) : null;
		$product_type = isset($args['product_type']) ? sanitize_text_field($args['product_type']) : null;

		if ($order_ref === '' || $user_id === 0) {
			return false;
		}

		$table = $wpdb->prefix . self::TABLE;

		$result = $wpdb->insert(
			$table,
			[
				'order_ref'   => $order_ref,
				'team_id'     => $team_id,
				'user_id'     => $user_id,
				'status'      => 'submitted',
				'product_type' => $product_type,
			],
			['%s', '%d', '%d', '%s', '%s']
		);

		if ($result === false) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Gets a paginated list of artwork queue items with optional filters.
	 *
	 * @param array{status?: string, designer_id?: int, unassigned?: bool, date_from?: string, date_to?: string, per_page?: int, page?: int} $filters Optional filters.
	 * @return array{items: list<object>, total: int}
	 */
	public function get_queue(array $filters = []): array {
		global $wpdb;

		$table       = $wpdb->prefix . self::TABLE;
		$teams_table = $wpdb->prefix . 'bsp_teams';
		$per_page    = min(50, max(1, (int) ($filters['per_page'] ?? 20)));
		$page        = max(1, (int) ($filters['page'] ?? 1));
		$offset      = ($page - 1) * $per_page;

		$where = ['1=1'];
		$prepare_args = [];

		if (!empty($filters['status'])) {
			$status = sanitize_text_field($filters['status']);
			if (in_array($status, self::STATUSES, true)) {
				$where[] = 'q.status = %s';
				$prepare_args[] = $status;
			}
		}

		if (!empty($filters['unassigned'])) {
			$where[] = '(q.assigned_designer_id IS NULL OR q.assigned_designer_id = 0)';
		} elseif (isset($filters['designer_id']) && $filters['designer_id'] > 0) {
			$where[] = 'q.assigned_designer_id = %d';
			$prepare_args[] = (int) $filters['designer_id'];
		}

		if (!empty($filters['date_from'])) {
			$where[] = 'q.submitted_at >= %s';
			$prepare_args[] = sanitize_text_field($filters['date_from']) . ' 00:00:00';
		}
		if (!empty($filters['date_to'])) {
			$where[] = 'q.submitted_at <= %s';
			$prepare_args[] = sanitize_text_field($filters['date_to']) . ' 23:59:59';
		}

		$where_sql = implode(' AND ', $where);

		$count_sql = "SELECT COUNT(*) FROM {$table} q WHERE {$where_sql}";
		$total = (int) $wpdb->get_var(
			$prepare_args ? $wpdb->prepare($count_sql, ...$prepare_args) : $count_sql
		);

		$prepare_args[] = $per_page;
		$prepare_args[] = $offset;

		$sql = "SELECT q.id, q.order_ref, q.team_id, q.user_id, q.status, q.assigned_designer_id,
		        q.product_type, q.submitted_at, q.updated_at,
		        t.team_name, t.org_name
		        FROM {$table} q
		        LEFT JOIN {$teams_table} t ON q.team_id = t.id
		        WHERE {$where_sql}
		        ORDER BY q.updated_at DESC
		        LIMIT %d OFFSET %d";

		$items = $wpdb->get_results(
			$wpdb->prepare($sql, ...$prepare_args),
			OBJECT
		);

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	/**
	 * Updates artwork status with validation against the state machine.
	 * Logs the change to wp_bsp_artwork_log.
	 *
	 * @param int    $id         Artwork queue ID.
	 * @param string $new_status Target status.
	 * @param int    $user_id    User performing the change.
	 * @param string $notes      Optional notes for the log.
	 * @return true|\WP_Error
	 */
	public function update_status(int $id, string $new_status, int $user_id, string $notes = ''): true|\WP_Error {
		global $wpdb;

		$new_status = sanitize_text_field($new_status);
		if (!in_array($new_status, self::STATUSES, true)) {
			return new \WP_Error(
				'invalid_status',
				__('Invalid status.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$row = $this->get_by_id($id);
		if (!$row) {
			return new \WP_Error(
				'not_found',
				__('Artwork not found.', 'battle-sports-platform'),
				['status' => 404]
			);
		}

		$from_status = $row->status;
		$allowed = self::TRANSITIONS[$from_status] ?? [];
		if (!in_array($new_status, $allowed, true)) {
			return new \WP_Error(
				'invalid_transition',
				sprintf(
					/* translators: 1: current status, 2: requested status */
					__('Cannot transition from %1$s to %2$s.', 'battle-sports-platform'),
					$from_status,
					$new_status
				),
				['status' => 400]
			);
		}

		$table = $wpdb->prefix . self::TABLE;
		$updated = $wpdb->update(
			$table,
			['status' => $new_status],
			['id' => $id],
			['%s'],
			['%d']
		);

		if ($updated === false) {
			return new \WP_Error(
				'update_failed',
				$wpdb->last_error ?: __('Failed to update status.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		$this->log_status_change($id, $from_status, $new_status, $user_id, $notes);
		$this->trigger_make_webhook($id, $new_status);

		return true;
	}

	/**
	 * Triggers Make.com webhook after status change.
	 *
	 * @param int    $artwork_id Artwork queue ID.
	 * @param string $new_status New status.
	 * @return void
	 */
	private function trigger_make_webhook(int $artwork_id, string $new_status): void {
		$row = $this->get_by_id($artwork_id);
		if (!$row) {
			return;
		}

		$webhook_url = get_option('bsp_make_webhook_url', '');
		if ($webhook_url === '') {
			return;
		}

		$designer_name = '';
		if (!empty($row->assigned_designer_id)) {
			$designer = get_userdata((int) $row->assigned_designer_id);
			$designer_name = $designer ? $designer->display_name : '';
		}

		$payload = [
			'event'             => 'artwork_status_changed',
			'artwork_id'        => $artwork_id,
			'order_ref'         => $row->order_ref,
			'new_status'        => $new_status,
			'team_name'         => $row->team_name ?? '',
			'assigned_designer' => $designer_name,
			'timestamp'         => gmdate('c'),
		];

		$body      = wp_json_encode($payload);
		$secret    = get_option('bsp_make_webhook_secret', '');
		$signature = $secret !== '' ? hash_hmac('sha256', $body, $secret) : '';

		$headers = [
			'Content-Type'    => 'application/json',
			'X-BSP-Signature' => $signature,
		];

		wp_remote_post($webhook_url, [
			'body'    => $body,
			'headers' => $headers,
			'timeout' => 15,
		]);
	}

	/**
	 * Logs a status change to wp_bsp_artwork_log.
	 *
	 * @param int    $artwork_id Artwork queue ID.
	 * @param string $from       Previous status.
	 * @param string $to         New status.
	 * @param int    $user_id    User who made the change.
	 * @param string $notes      Optional notes.
	 * @return void
	 */
	private function log_status_change(int $artwork_id, string $from, string $to, int $user_id, string $notes = ''): void {
		global $wpdb;

		$log_table = $wpdb->prefix . self::LOG_TABLE;
		$wpdb->insert(
			$log_table,
			[
				'artwork_id'  => $artwork_id,
				'from_status' => $from,
				'to_status'   => $to,
				'changed_by'  => $user_id,
				'notes'       => $notes ? sanitize_textarea_field($notes) : null,
			],
			['%d', '%s', '%s', '%d', '%s']
		);
	}

	/**
	 * Assigns a designer to an artwork item.
	 *
	 * @param int $artwork_id  Artwork queue ID.
	 * @param int $designer_id WordPress user ID of the designer.
	 * @return true|\WP_Error
	 */
	public function assign_designer(int $artwork_id, int $designer_id): true|\WP_Error {
		global $wpdb;

		if ($designer_id <= 0) {
			return new \WP_Error(
				'invalid_designer',
				__('Invalid designer ID.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$row = $this->get_by_id($artwork_id);
		if (!$row) {
			return new \WP_Error(
				'not_found',
				__('Artwork not found.', 'battle-sports-platform'),
				['status' => 404]
			);
		}

		$table = $wpdb->prefix . self::TABLE;
		$result = $wpdb->update(
			$table,
			['assigned_designer_id' => $designer_id],
			['id' => $artwork_id],
			['%d'],
			['%d']
		);

		if ($result === false) {
			return new \WP_Error(
				'update_failed',
				$wpdb->last_error ?: __('Failed to assign designer.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		return true;
	}

	/**
	 * Fetches a single artwork queue record by ID.
	 *
	 * @param int $id Artwork queue ID.
	 * @return object|null Row object or null if not found.
	 */
	public function get_by_id(int $id): ?object {
		global $wpdb;

		$table    = $wpdb->prefix . self::TABLE;
		$teams    = $wpdb->prefix . 'bsp_teams';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT q.*, t.team_name, t.org_name, t.user_id AS team_owner_id
				FROM {$table} q
				LEFT JOIN {$teams} t ON q.team_id = t.id
				WHERE q.id = %d",
				$id
			),
			OBJECT
		);

		return $row ?: null;
	}

	/**
	 * Returns valid statuses for the state machine.
	 *
	 * @return list<string>
	 */
	public static function get_valid_statuses(): array {
		return self::STATUSES;
	}

	/**
	 * Returns valid transitions for a given status.
	 *
	 * @param string $status Current status.
	 * @return list<string>
	 */
	public static function get_transitions(string $status): array {
		return self::TRANSITIONS[$status] ?? [];
	}
}
