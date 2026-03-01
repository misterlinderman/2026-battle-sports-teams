<?php

declare(strict_types=1);

namespace BattleSports;

defined('ABSPATH') || exit;

/**
 * Make.com and Monday.com webhook integration layer.
 *
 * Outbound: Battle Sports → Make.com (HMAC-signed, logged).
 * Inbound: Make.com → Battle Sports (signature verification, status/item updates).
 */
final class Webhooks {

	public const VALID_OUTBOUND_EVENTS = [
		'artwork_submitted',
		'artwork_status_changed',
		'proof_approved',
		'revision_requested',
		'order_payment_received',
	];

	public const VALID_INBOUND_EVENTS = [
		'monday_status_updated',
		'monday_item_created',
	];

	private const LOG_TABLE = 'bsp_webhook_log';
	private const TIMEOUT = 10;
	private const SIGNATURE_HEADER = 'X-BSP-Signature';
	private const SIGNATURE_PREFIX = 'sha256=';

	/**
	 * Triggers an outbound webhook to Make.com.
	 *
	 * POSTs to URL in option 'bsp_make_webhook_url'.
	 * Signs payload with HMAC-SHA256 using 'bsp_make_webhook_secret'.
	 * Adds header X-BSP-Signature: sha256={signature}.
	 * Payload wrapper: { event, timestamp, data }
	 * Logs to bsp_webhook_log.
	 *
	 * @param string               $event One of: artwork_submitted, artwork_status_changed, proof_approved, revision_requested, order_payment_received.
	 * @param array<string, mixed>  $data  Event-specific payload data.
	 * @return bool True if request was sent (regardless of HTTP response).
	 */
	public static function trigger_make_webhook(string $event, array $data): bool {
		if (!in_array($event, self::VALID_OUTBOUND_EVENTS, true)) {
			return false;
		}

		$url = get_option('bsp_make_webhook_url', '');
		if ($url === '') {
			return false;
		}

		$payload = [
			'event'     => $event,
			'timestamp' => gmdate('c'),
			'data'     => $data,
		];

		$body    = wp_json_encode($payload);
		$secret  = get_option('bsp_make_webhook_secret', '');
		$sig_raw = $secret !== '' ? hash_hmac('sha256', $body, $secret) : '';
		$sig_hdr = self::SIGNATURE_PREFIX . $sig_raw;

		$headers = [
			'Content-Type'    => 'application/json',
			self::SIGNATURE_HEADER => $sig_hdr,
		];

		$response = wp_remote_post($url, [
			'body'    => $body,
			'headers' => $headers,
			'timeout' => self::TIMEOUT,
		]);

		$http_code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
		self::log_webhook($event, $url, $payload, $http_code, is_wp_error($response) ? $response->get_error_message() : null);

		return true;
	}

	/**
	 * Logs an outbound webhook call to bsp_webhook_log.
	 *
	 * @param string               $event     Event name.
	 * @param string               $url       Webhook URL.
	 * @param array<string, mixed> $payload   Full payload.
	 * @param int                  $http_code Response HTTP code (0 if error).
	 * @param string|null          $error     Error message if wp_remote_post failed.
	 * @return void
	 */
	private static function log_webhook(string $event, string $url, array $payload, int $http_code, ?string $error = null): void {
		global $wpdb;

		$table = $wpdb->prefix . self::LOG_TABLE;
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
			return;
		}

		$wpdb->insert(
			$table,
			[
				'event'      => $event,
				'url'        => $url,
				'payload'    => wp_json_encode($payload),
				'http_code'  => $http_code,
				'error_msg'  => $error,
				'created_at' => current_time('mysql'),
			],
			['%s', '%s', '%s', '%d', '%s', '%s']
		);
	}

	/**
	 * Fetches the last N outbound webhook log entries.
	 *
	 * @param int $limit Number of rows (default 20).
	 * @return list<object>
	 */
	public static function get_webhook_log(int $limit = 20): array {
		global $wpdb;

		$table = $wpdb->prefix . self::LOG_TABLE;
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
			return [];
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, event, url, payload, http_code, error_msg, created_at FROM {$table} ORDER BY id DESC LIMIT %d",
				$limit
			),
			OBJECT
		);

		return $rows ?: [];
	}

	/**
	 * Verifies X-BSP-Signature header against request body.
	 *
	 * @param string $body   Raw request body.
	 * @param string $header X-BSP-Signature value (may include sha256= prefix).
	 * @return bool
	 */
	public static function verify_signature(string $body, string $header): bool {
		$secret = get_option('bsp_make_webhook_secret', '');
		if ($secret === '' || $header === '') {
			return false;
		}

		$expected = self::SIGNATURE_PREFIX . hash_hmac('sha256', $body, $secret);
		return hash_equals($expected, $header);
	}

	/**
	 * Handles inbound webhook from Make.com.
	 *
	 * Expects POST with JSON body: { event, data }.
	 * Events: monday_status_updated, monday_item_created.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_inbound_webhook(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$body   = $request->get_body();
		$header = $request->get_header(self::SIGNATURE_HEADER) ?: '';

		if (!self::verify_signature($body, $header)) {
			return new \WP_Error(
				'invalid_signature',
				__('Invalid webhook signature.', 'battle-sports-platform'),
				['status' => 401]
			);
		}

		$payload = json_decode($body, true);
		if (!is_array($payload) || empty($payload['event'])) {
			return new \WP_Error(
				'invalid_payload',
				__('Invalid webhook payload.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$event = sanitize_text_field($payload['event']);
		$data  = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

		if (!in_array($event, self::VALID_INBOUND_EVENTS, true)) {
			return new \WP_Error(
				'unknown_event',
				__('Unknown webhook event.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		if ($event === 'monday_status_updated') {
			$result = self::handle_monday_status_updated($data);
		} else {
			$result = self::handle_monday_item_created($data);
		}

		if (is_wp_error($result)) {
			return $result;
		}

		return rest_ensure_response(['received' => true]);
	}

	/**
	 * Handles monday_status_updated: updates artwork status from Monday.
	 *
	 * Expects data: artwork_id (or order_ref), status (Monday label, mapped to slug).
	 *
	 * @param array<string, mixed> $data Webhook data.
	 * @return true|\WP_Error
	 */
	private static function handle_monday_status_updated(array $data): true|\WP_Error {
		$artwork_id = isset($data['artwork_id']) ? absint($data['artwork_id']) : 0;
		if ($artwork_id <= 0 && !empty($data['order_ref'])) {
			global $wpdb;
			$table = $wpdb->prefix . 'bsp_artwork_queue';
			$artwork_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE order_ref = %s LIMIT 1",
					sanitize_text_field((string) $data['order_ref'])
				)
			);
		}

		if ($artwork_id <= 0) {
			return new \WP_Error(
				'missing_artwork',
				__('artwork_id or order_ref required.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$monday_status = isset($data['status']) ? sanitize_text_field((string) $data['status']) : '';
		if ($monday_status === '') {
			return new \WP_Error(
				'missing_status',
				__('status required.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$status_slug = self::map_monday_status_to_slug($monday_status);
		if ($status_slug === '') {
			return new \WP_Error(
				'invalid_status',
				sprintf(
					/* translators: %s: Monday status */
					__('Unknown Monday status: %s', 'battle-sports-platform'),
					$monday_status
				),
				['status' => 400]
			);
		}

		$queue  = new \BattleSports\Artwork\ArtworkQueue();
		$result = $queue->update_status($artwork_id, $status_slug, 0, __('Updated from Monday.com via Make.com', 'battle-sports-platform'));

		if (is_wp_error($result)) {
			return $result;
		}

		return true;
	}

	/**
	 * Maps Monday.com status labels to artwork queue slugs.
	 *
	 * @param string $monday_status Monday column value (label).
	 * @return string Slug or empty if unknown.
	 */
	private static function map_monday_status_to_slug(string $monday_status): string {
		$map = [
			'submitted'         => 'submitted',
			'in queue'           => 'in_queue',
			'in_queue'          => 'in_queue',
			'in progress'        => 'in_progress',
			'in_progress'       => 'in_progress',
			'proof sent'         => 'proof_sent',
			'proof_sent'        => 'proof_sent',
			'revision requested' => 'revision_requested',
			'revision_requested' => 'revision_requested',
			'approved'          => 'approved',
			'complete'          => 'complete',
		];

		$key = strtolower(trim($monday_status));
		return $map[$key] ?? '';
	}

	/**
	 * Handles monday_item_created: stores monday_item_id on artwork record.
	 *
	 * Expects data: artwork_id (or order_ref), monday_item_id.
	 *
	 * @param array<string, mixed> $data Webhook data.
	 * @return true|\WP_Error
	 */
	private static function handle_monday_item_created(array $data): true|\WP_Error {
		$artwork_id = isset($data['artwork_id']) ? absint($data['artwork_id']) : 0;
		if ($artwork_id <= 0 && !empty($data['order_ref'])) {
			global $wpdb;
			$table = $wpdb->prefix . 'bsp_artwork_queue';
			$artwork_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE order_ref = %s LIMIT 1",
					sanitize_text_field((string) $data['order_ref'])
				)
			);
		}

		if ($artwork_id <= 0) {
			return new \WP_Error(
				'missing_artwork',
				__('artwork_id or order_ref required.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$monday_item_id = isset($data['monday_item_id']) ? sanitize_text_field((string) $data['monday_item_id']) : '';
		if ($monday_item_id === '') {
			return new \WP_Error(
				'missing_item_id',
				__('monday_item_id required.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'bsp_artwork_queue';
		$updated = $wpdb->update(
			$table,
			['monday_item_id' => $monday_item_id],
			['id' => $artwork_id],
			['%s'],
			['%d']
		);

		if ($updated === false) {
			return new \WP_Error(
				'update_failed',
				$wpdb->last_error ?: __('Failed to store Monday item ID.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		return true;
	}
}
