<?php

declare(strict_types=1);

namespace BattleSports\Artwork;

defined('ABSPATH') || exit;

/**
 * Artwork proof upload and customer approval workflow.
 *
 * Handles proof upload, customer approval, and revision requests.
 */
final class ArtworkApproval {

	private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
	private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps'];
	private const ALLOWED_MIMES = [
		'image/jpeg',
		'image/png',
		'application/pdf',
		'application/postscript',
		'application/illustrator',
	];

	public function __construct(
		private ArtworkQueue $queue = new ArtworkQueue(),
	) {}

	/**
	 * Validates and uploads a proof file, updates artwork status to proof_sent,
	 * saves attachment ID, and sends email to customer.
	 *
	 * @param int                   $artwork_id Artwork queue ID.
	 * @param array<string, mixed>   $file       $_FILES array element (e.g. $_FILES['file']).
	 * @return true|\WP_Error
	 */
	public function upload_proof(int $artwork_id, array $file): true|\WP_Error {
		$row = $this->queue->get_by_id($artwork_id);
		if (!$row) {
			return new \WP_Error(
				'not_found',
				__('Artwork not found.', 'battle-sports-platform'),
				['status' => 404]
			);
		}

		$validation = $this->validate_proof_file($file);
		if (is_wp_error($validation)) {
			return $validation;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$overrides = [
			'test_form' => false,
			'mimes'     => $this->get_allowed_mime_map(),
		];

		$upload = wp_handle_upload($file, $overrides);

		if (isset($upload['error'])) {
			return new \WP_Error(
				'upload_failed',
				$upload['error'],
				['status' => 400]
			);
		}

		$attachment_id = $this->create_attachment($upload, $artwork_id);
		if (!$attachment_id) {
			return new \WP_Error(
				'attachment_failed',
				__('Failed to create attachment.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'bsp_artwork_queue';
		$wpdb->update(
			$table,
			['proof_attachment_id' => $attachment_id],
			['id' => $artwork_id],
			['%d'],
			['%d']
		);

		$user_id = get_current_user_id();
		$result = $this->queue->update_status($artwork_id, 'proof_sent', $user_id);

		if (is_wp_error($result)) {
			return $result;
		}

		$this->send_proof_notification_email($row, $upload['url']);

		return true;
	}

	/**
	 * Customer approves artwork. Verifies user owns the artwork's team,
	 * transitions status to approved, triggers Make.com webhook.
	 *
	 * @param int $artwork_id Artwork queue ID.
	 * @param int $user_id    User ID (should own the team).
	 * @return true|\WP_Error
	 */
	public function customer_approve(int $artwork_id, int $user_id): true|\WP_Error {
		$row = $this->queue->get_by_id($artwork_id);
		if (!$row) {
			return new \WP_Error(
				'not_found',
				__('Artwork not found.', 'battle-sports-platform'),
				['status' => 404]
			);
		}

		if (!$this->user_owns_artwork_team($row, $user_id)) {
			return new \WP_Error(
				'forbidden',
				__('You do not have permission to approve this artwork.', 'battle-sports-platform'),
				['status' => 403]
			);
		}

		if ($row->status !== 'proof_sent') {
			return new \WP_Error(
				'invalid_state',
				__('Artwork must be in proof_sent status to approve.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$result = $this->queue->update_status($artwork_id, 'approved', $user_id);

		if (is_wp_error($result)) {
			return $result;
		}

		return true;
	}

	/**
	 * Customer requests revision. Verifies user owns the team, transitions to revision_requested,
	 * notifies designer.
	 *
	 * @param int    $artwork_id Artwork queue ID.
	 * @param int    $user_id    User ID (should own the team).
	 * @param string $notes      Revision notes for the designer.
	 * @return true|\WP_Error
	 */
	public function customer_request_revision(int $artwork_id, int $user_id, string $notes): true|\WP_Error {
		$row = $this->queue->get_by_id($artwork_id);
		if (!$row) {
			return new \WP_Error(
				'not_found',
				__('Artwork not found.', 'battle-sports-platform'),
				['status' => 404]
			);
		}

		if (!$this->user_owns_artwork_team($row, $user_id)) {
			return new \WP_Error(
				'forbidden',
				__('You do not have permission to request revision for this artwork.', 'battle-sports-platform'),
				['status' => 403]
			);
		}

		if ($row->status !== 'proof_sent') {
			return new \WP_Error(
				'invalid_state',
				__('Artwork must be in proof_sent status to request revision.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$result = $this->queue->update_status($artwork_id, 'revision_requested', $user_id, $notes);

		if (is_wp_error($result)) {
			return $result;
		}

		$this->notify_designer_revision($row, $notes);

		return true;
	}

	/**
	 * Validates proof file (extension, MIME, size).
	 *
	 * @param array<string, mixed> $file $_FILES element.
	 * @return true|\WP_Error
	 */
	private function validate_proof_file(array $file): true|\WP_Error {
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return new \WP_Error(
				'invalid_file',
				__('No valid file uploaded.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$size = (int) ($file['size'] ?? 0);
		if ($size > self::MAX_FILE_SIZE) {
			return new \WP_Error(
				'file_too_large',
				__('File exceeds maximum size of 50MB.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		if ($size === 0) {
			return new \WP_Error(
				'empty_file',
				__('File is empty.', 'battle-sports-platform'),
				['status' => 400]
			);
		}

		$name = $file['name'] ?? '';
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
			return new \WP_Error(
				'invalid_extension',
				sprintf(
					/* translators: %s: allowed extensions */
					__('Invalid file type. Allowed: %s', 'battle-sports-platform'),
					implode(', ', self::ALLOWED_EXTENSIONS)
				),
				['status' => 400]
			);
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
		if ($finfo) {
			finfo_close($finfo);
		}
		if ($mime && !in_array($mime, self::ALLOWED_MIMES, true)) {
			$allowed_mimes = array_merge(
				self::ALLOWED_MIMES,
				$this->get_ai_eps_mimes()
			);
			if (!in_array($mime, $allowed_mimes, true)) {
				return new \WP_Error(
					'invalid_mime',
					__('Invalid file type. Allowed: jpg, png, pdf, ai, eps', 'battle-sports-platform'),
					['status' => 400]
				);
			}
		}

		return true;
	}

	/**
	 * Returns MIME map for wp_handle_upload override.
	 *
	 * @return array<string, string>
	 */
	private function get_allowed_mime_map(): array {
		return [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'pdf'          => 'application/pdf',
			'ai'           => 'application/postscript',
			'eps'          => 'application/postscript',
		];
	}

	/**
	 * Additional MIME types for AI/EPS (variants).
	 *
	 * @return list<string>
	 */
	private function get_ai_eps_mimes(): array {
		return [
			'application/illustrator',
			'application/octet-stream', // Some .ai files
		];
	}

	/**
	 * Creates WordPress attachment from upload result.
	 *
	 * @param array{file: string, url: string, type: string} $upload    Result of wp_handle_upload.
	 * @param int                                            $artwork_id For attachment title.
	 * @return int|false Attachment ID or false.
	 */
	private function create_attachment(array $upload, int $artwork_id): int|false {
		$file_path = $upload['file'];
		$url       = $upload['url'];
		$mime      = $upload['type'] ?? '';

		$attachment = [
			'post_mime_type' => $mime,
			'post_title'     => sprintf(
				/* translators: %d: artwork ID */
				__('Proof for artwork #%d', 'battle-sports-platform'),
				$artwork_id
			),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment($attachment, $file_path);

		if ($attachment_id && !is_wp_error($attachment_id)) {
			wp_generate_attachment_metadata($attachment_id, $file_path);
		}

		return is_int($attachment_id) ? $attachment_id : false;
	}

	/**
	 * Checks if user owns the artwork (via team ownership or order ownership).
	 *
	 * @param object $row     Artwork queue row.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	private function user_owns_artwork_team(object $row, int $user_id): bool {
		if ($row->team_id) {
			global $wpdb;
			$teams_table = $wpdb->prefix . 'bsp_teams';
			$owner_id    = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT user_id FROM {$teams_table} WHERE id = %d",
					(int) $row->team_id
				)
			);
			if ($owner_id === $user_id) {
				return true;
			}
		}
		return (int) $row->user_id === $user_id;
	}

	/**
	 * Sends proof notification email to customer.
	 *
	 * @param object $row   Artwork queue row.
	 * @param string $url  Proof file URL.
	 * @return void
	 */
	private function send_proof_notification_email(object $row, string $url): void {
		$customer = get_userdata((int) $row->user_id);
		if (!$customer || !$customer->user_email) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: order reference */
			__('[Battle Sports] Proof ready for order %s', 'battle-sports-platform'),
			$row->order_ref
		);

		$team_name = $row->team_name ?? $row->order_ref;
		$message   = sprintf(
			/* translators: 1: team/order name, 2: proof URL, 3: site name */
			__(
				"Hello,\n\nYour proof for %1\$s is ready for review.\n\nView proof: %2\$s\n\nPlease log in to your portal to approve or request revisions.\n\n— %3\$s",
				'battle-sports-platform'
			),
			esc_html($team_name),
			esc_url($url),
			get_bloginfo('name')
		);

		wp_mail($customer->user_email, $subject, $message);
	}

	/**
	 * Notifies assigned designer of revision request.
	 *
	 * @param object $row   Artwork queue row.
	 * @param string $notes Revision notes.
	 * @return void
	 */
	private function notify_designer_revision(object $row, string $notes): void {
		$designer_id = (int) ($row->assigned_designer_id ?? 0);
		if ($designer_id <= 0) {
			return;
		}

		$designer = get_userdata($designer_id);
		if (!$designer || !$designer->user_email) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: order reference */
			__('[Battle Sports] Revision requested for order %s', 'battle-sports-platform'),
			$row->order_ref
		);

		$message = sprintf(
			/* translators: 1: order reference, 2: customer notes */
			__(
				"Hello,\n\nA revision has been requested for order %1\$s.\n\nCustomer notes:\n%2\$s\n\nPlease log in to the artwork queue to address the revision.\n\n— %3\$s",
				'battle-sports-platform'
			),
			esc_html($row->order_ref),
			esc_html($notes),
			get_bloginfo('name')
		);

		wp_mail($designer->user_email, $subject, $message);
	}
}
