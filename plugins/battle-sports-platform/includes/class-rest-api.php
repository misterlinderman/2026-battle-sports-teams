<?php

declare(strict_types=1);

namespace BattleSports;

defined('ABSPATH') || exit;

/**
 * REST API endpoints for Battle Sports platform.
 *
 * Namespace: battle-sports/v1
 * Endpoints: teams, roster (CRUD), roster import
 */
final class RestApi {

	private const NAMESPACE = 'battle-sports/v1';

	/**
	 * Registers all REST routes under battle-sports/v1 namespace.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(self::NAMESPACE, '/teams', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_teams'],
				'permission_callback' => [$this, 'check_teams_read_permission'],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'create_team'],
				'permission_callback' => [$this, 'check_teams_create_permission'],
				'args'                => $this->get_team_schema(),
			],
		]);

		register_rest_route(self::NAMESPACE, '/teams/(?P<id>\d+)/roster/import', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'import_roster'],
				'permission_callback' => [$this, 'check_roster_manage_permission'],
				'args'                => [
					'id'      => [
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					],
					'players' => [
						'required' => true,
						'type'     => 'array',
						'items'    => $this->get_player_schema(),
					],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/teams/(?P<id>\d+)/roster', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_roster'],
				'permission_callback' => [$this, 'check_roster_read_permission'],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'create_player'],
				'permission_callback' => [$this, 'check_roster_manage_permission'],
				'args'                => array_merge(
					['id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint']],
					$this->get_player_schema()
				),
			],
		]);

		register_rest_route(self::NAMESPACE, '/teams/(?P<id>\d+)/roster/(?P<player_id>\d+)', [
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [$this, 'delete_player'],
				'permission_callback' => [$this, 'check_roster_delete_permission'],
			],
		]);

		// Artwork endpoints
		register_rest_route(self::NAMESPACE, '/artwork', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_artwork_list'],
				'permission_callback' => [$this, 'check_artwork_list_permission'],
				'args'                => [
					'status'      => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
					'designer_id' => ['type' => 'integer', 'minimum' => 0, 'sanitize_callback' => 'absint'],
					'unassigned'  => ['type' => 'string', 'enum' => ['1', 'true'], 'sanitize_callback' => 'sanitize_text_field'],
					'date_from'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
					'date_to'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
					'page'        => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
					'per_page'   => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/artwork/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_artwork'],
				'permission_callback' => [$this, 'check_artwork_read_permission'],
				'args'                => [
					'id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/artwork/(?P<id>\d+)/status', [
			[
				'methods'             => 'PATCH',
				'callback'            => [$this, 'patch_artwork_status'],
				'permission_callback' => [$this, 'check_artwork_manage_permission'],
				'args'                => [
					'id'     => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
					'status' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
					'notes'  => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field'],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/artwork/(?P<id>\d+)/proof', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'upload_artwork_proof'],
				'permission_callback' => [$this, 'check_artwork_proof_permission'],
				'args'                => [
					'id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/artwork/(?P<id>\d+)/approve', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'approve_artwork'],
				'permission_callback' => [$this, 'check_artwork_coach_permission'],
				'args'                => [
					'id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/artwork/(?P<id>\d+)/revision', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'request_artwork_revision'],
				'permission_callback' => [$this, 'check_artwork_coach_permission'],
				'args'                => [
					'id'    => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
					'notes' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
				],
			],
		]);

		// Inbound webhook from Make.com (no auth; verified via X-BSP-Signature).
		register_rest_route(self::NAMESPACE, '/webhook/make', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [\BattleSports\Webhooks::class, 'handle_inbound_webhook'],
				'permission_callback' => '__return_true',
				'args'                => [],
			],
		]);
	}

	/**
	 * Permission callback: GET /teams (nonce + bsp_view_portal).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_teams_read_permission(\WP_REST_Request $request): true|\WP_Error {
		return $this->check_nonce_and_cap($request, 'bsp_view_portal');
	}

	/**
	 * Permission callback: POST /teams (nonce + bsp_submit_order).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_teams_create_permission(\WP_REST_Request $request): true|\WP_Error {
		return $this->check_nonce_and_cap($request, 'bsp_submit_order');
	}

	/**
	 * Permission callback: GET /teams/{id}/roster (nonce + bsp_view_portal + team ownership).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_roster_read_permission(\WP_REST_Request $request): true|\WP_Error {
		$result = $this->check_nonce_and_cap($request, 'bsp_view_portal');
		if (is_wp_error($result)) {
			return $result;
		}
		$team_id = (int) $request['id'];
		if (!$this->user_owns_team($team_id)) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to access this team.', 'battle-sports-platform'), ['status' => 403]);
		}
		return true;
	}

	/**
	 * Permission callback: POST roster, POST import (nonce + bsp_manage_roster).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_roster_manage_permission(\WP_REST_Request $request): true|\WP_Error {
		return $this->check_nonce_and_cap($request, 'bsp_manage_roster');
	}

	/**
	 * Permission callback: verifies nonce and capability.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param string          $cap     Required capability.
	 * @return true|\WP_Error
	 */
	private function check_nonce_and_cap(\WP_REST_Request $request, string $cap): true|\WP_Error {
		$nonce_error = $this->verify_nonce($request);
		if (is_wp_error($nonce_error)) {
			return $nonce_error;
		}
		if (!current_user_can($cap)) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to perform this action.', 'battle-sports-platform'), ['status' => 403]);
		}
		return true;
	}

	/**
	 * Permission callback: DELETE /teams/{id}/roster/{player_id} (nonce + bsp_manage_roster + team ownership).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_roster_delete_permission(\WP_REST_Request $request): true|\WP_Error {
		$nonce_error = $this->verify_nonce($request);
		if (is_wp_error($nonce_error)) {
			return $nonce_error;
		}

		if (!current_user_can('bsp_manage_roster')) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to perform this action.', 'battle-sports-platform'), ['status' => 403]);
		}

		$team_id = (int) $request['id'];
		if (!$this->user_owns_team($team_id)) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to access this team.', 'battle-sports-platform'), ['status' => 403]);
		}

		return true;
	}

	/**
	 * Permission callback: GET /artwork — Designer (bsp_view_artwork_queue).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_artwork_list_permission(\WP_REST_Request $request): true|\WP_Error {
		return $this->check_nonce_and_cap($request, 'bsp_view_artwork_queue');
	}

	/**
	 * Permission callback: GET /artwork/{id} — Designer or team owner.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_artwork_read_permission(\WP_REST_Request $request): true|\WP_Error {
		$result = $this->verify_nonce($request);
		if (is_wp_error($result)) {
			return $result;
		}
		if (!is_user_logged_in()) {
			return new \WP_Error('rest_not_logged_in', __('You must be logged in.', 'battle-sports-platform'), ['status' => 401]);
		}

		$queue = new \BattleSports\Artwork\ArtworkQueue();
		$row   = $queue->get_by_id((int) $request['id']);
		if (!$row) {
			return new \WP_Error('rest_not_found', __('Artwork not found.', 'battle-sports-platform'), ['status' => 404]);
		}

		$user_id = get_current_user_id();
		if (current_user_can('bsp_view_artwork_queue')) {
			return true;
		}
		if ($row->team_id && $this->user_owns_team((int) $row->team_id)) {
			return true;
		}

		return new \WP_Error('rest_forbidden', __('You do not have permission to access this artwork.', 'battle-sports-platform'), ['status' => 403]);
	}

	/**
	 * Permission callback: PATCH /artwork/{id}/status — Designer (bsp_manage_artwork_queue).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_artwork_manage_permission(\WP_REST_Request $request): true|\WP_Error {
		return $this->check_nonce_and_cap($request, 'bsp_manage_artwork_queue');
	}

	/**
	 * Permission callback: POST /artwork/{id}/proof — Designer (bsp_upload_proof).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_artwork_proof_permission(\WP_REST_Request $request): true|\WP_Error {
		return $this->check_nonce_and_cap($request, 'bsp_upload_proof');
	}

	/**
	 * Permission callback: POST /artwork/{id}/approve, /revision — Coach, team owner.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function check_artwork_coach_permission(\WP_REST_Request $request): true|\WP_Error {
		$result = $this->verify_nonce($request);
		if (is_wp_error($result)) {
			return $result;
		}
		if (!is_user_logged_in()) {
			return new \WP_Error('rest_not_logged_in', __('You must be logged in.', 'battle-sports-platform'), ['status' => 401]);
		}

		$queue = new \BattleSports\Artwork\ArtworkQueue();
		$row   = $queue->get_by_id((int) $request['id']);
		if (!$row) {
			return new \WP_Error('rest_not_found', __('Artwork not found.', 'battle-sports-platform'), ['status' => 404]);
		}

		$user_id = get_current_user_id();
		$owns_team = $row->team_id && $this->user_owns_team((int) $row->team_id);
		$owns_order = (int) $row->user_id === $user_id;
		if (!$owns_team && !$owns_order) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to perform this action.', 'battle-sports-platform'), ['status' => 403]);
		}

		return true;
	}

	/**
	 * Verifies X-WP-Nonce header via wp_verify_nonce.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	private function verify_nonce(\WP_REST_Request $request): true|\WP_Error {
		if (!is_user_logged_in()) {
			return new \WP_Error('rest_not_logged_in', __('You must be logged in.', 'battle-sports-platform'), ['status' => 401]);
		}

		$nonce = $request->get_header('X-WP-Nonce');
		if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
			return new \WP_Error('rest_cookie_invalid_nonce', __('Invalid nonce.', 'battle-sports-platform'), ['status' => 403]);
		}

		return true;
	}

	/**
	 * Checks if the current user owns the given team.
	 *
	 * @param int $team_id Team ID.
	 * @return bool
	 */
	private function user_owns_team(int $team_id): bool {
		global $wpdb;
		$table  = $wpdb->prefix . 'bsp_teams';
		$user_id = get_current_user_id();

		$owner_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$table} WHERE id = %d",
				$team_id
			)
		);

		return $owner_id === $user_id;
	}

	/**
	 * GET /teams
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_teams(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		global $wpdb;
		$teams_table = $wpdb->prefix . 'bsp_teams';
		$rosters_table = $wpdb->prefix . 'bsp_rosters';
		$user_id = get_current_user_id();

		$teams = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.id, t.org_name, t.team_name, t.age_group, t.primary_color, t.secondary_color, t.logo_attachment_id
				FROM {$teams_table} t
				WHERE t.user_id = %d
				ORDER BY t.team_name ASC",
				$user_id
			),
			ARRAY_A
		);

		$response = [];
		foreach ($teams ?: [] as $team) {
			$logo_url = '';
			if (!empty($team['logo_attachment_id'])) {
				$logo_url = wp_get_attachment_image_url((int) $team['logo_attachment_id'], 'medium') ?: '';
			}

			$player_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$rosters_table} WHERE team_id = %d",
					(int) $team['id']
				)
			);

			$response[] = [
				'id'             => (int) $team['id'],
				'org_name'       => $team['org_name'],
				'team_name'      => $team['team_name'],
				'age_group'      => $team['age_group'] ?? '',
				'primary_color'  => $team['primary_color'] ?? '',
				'secondary_color' => $team['secondary_color'] ?? '',
				'logo_url'       => $logo_url,
				'player_count'   => $player_count,
			];
		}

		return rest_ensure_response($response);
	}

	/**
	 * POST /teams
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_team(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$params = $this->get_team_params($request);
		$errors = $this->validate_team_params($params);

		if (!empty($errors)) {
			return new \WP_Error('rest_invalid_param', implode(' ', $errors), ['status' => 400]);
		}

		global $wpdb;
		$table   = $wpdb->prefix . 'bsp_teams';
		$user_id = get_current_user_id();

		$wpdb->insert(
			$table,
			[
				'user_id'         => $user_id,
				'org_name'        => $params['org_name'],
				'team_name'       => $params['team_name'],
				'age_group'       => $params['age_group'],
				'primary_color'   => $params['primary_color'],
				'secondary_color' => $params['secondary_color'],
			],
			['%d', '%s', '%s', '%s', '%s', '%s']
		);

		if ($wpdb->last_error) {
			return new \WP_Error('rest_insert_failed', $wpdb->last_error, ['status' => 500]);
		}

		$team_id = (int) $wpdb->insert_id;

		$created = [
			'id'              => $team_id,
			'org_name'        => $params['org_name'],
			'team_name'       => $params['team_name'],
			'age_group'       => $params['age_group'],
			'primary_color'   => $params['primary_color'],
			'secondary_color' => $params['secondary_color'],
			'logo_url'        => '',
			'player_count'    => 0,
		];

		return rest_ensure_response($created);
	}

	/**
	 * GET /teams/{id}/roster
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_roster(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		global $wpdb;
		$table   = $wpdb->prefix . 'bsp_rosters';
		$team_id = (int) $request['id'];

		$players = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, player_name, player_number, jersey_size, short_size
				FROM {$table}
				WHERE team_id = %d
				ORDER BY player_name ASC",
				$team_id
			),
			ARRAY_A
		);

		$response = [];
		foreach ($players ?: [] as $p) {
			$response[] = [
				'id'           => (int) $p['id'],
				'player_name'  => $p['player_name'],
				'player_number' => $p['player_number'] ?? '',
				'jersey_size'  => $p['jersey_size'] ?? '',
				'short_size'   => $p['short_size'] ?? '',
			];
		}

		return rest_ensure_response($response);
	}

	/**
	 * POST /teams/{id}/roster (single player)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_player(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$team_id = (int) $request['id'];
		if (!$this->user_owns_team($team_id)) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to access this team.', 'battle-sports-platform'), ['status' => 403]);
		}

		$params = $this->get_player_params($request);
		$errors = $this->validate_player_params($params);

		if (!empty($errors)) {
			return new \WP_Error('rest_invalid_param', implode(' ', $errors), ['status' => 400]);
		}

		global $wpdb;
		$table = $wpdb->prefix . 'bsp_rosters';

		$wpdb->insert(
			$table,
			[
				'team_id'       => $team_id,
				'player_name'   => $params['player_name'],
				'player_number' => $params['player_number'],
				'jersey_size'   => $params['jersey_size'],
				'short_size'    => $params['short_size'],
			],
			['%d', '%s', '%s', '%s', '%s']
		);

		if ($wpdb->last_error) {
			return new \WP_Error('rest_insert_failed', $wpdb->last_error, ['status' => 500]);
		}

		$player_id = (int) $wpdb->insert_id;

		$created = [
			'id'            => $player_id,
			'player_name'   => $params['player_name'],
			'player_number' => $params['player_number'],
			'jersey_size'   => $params['jersey_size'],
			'short_size'    => $params['short_size'],
		];

		return rest_ensure_response($created);
	}

	/**
	 * DELETE /teams/{id}/roster/{player_id}
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_player(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		global $wpdb;
		$table     = $wpdb->prefix . 'bsp_rosters';
		$team_id   = (int) $request['id'];
		$player_id = (int) $request['player_id'];

		$deleted = $wpdb->delete(
			$table,
			[
				'id'      => $player_id,
				'team_id' => $team_id,
			],
			['%d', '%d']
		);

		if ($deleted === false) {
			return new \WP_Error('rest_delete_failed', __('Failed to delete player.', 'battle-sports-platform'), ['status' => 500]);
		}

		return rest_ensure_response(['deleted' => true]);
	}

	/**
	 * POST /teams/{id}/roster/import
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import_roster(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$team_id = (int) $request['id'];
		if (!$this->user_owns_team($team_id)) {
			return new \WP_Error('rest_forbidden', __('You do not have permission to access this team.', 'battle-sports-platform'), ['status' => 403]);
		}

		$players = $request->get_param('players');
		if (!is_array($players) || count($players) > 100) {
			return new \WP_Error('rest_invalid_param', __('players must be an array with at most 100 items.', 'battle-sports-platform'), ['status' => 400]);
		}

		$imported = 0;
		$errors   = [];

		foreach ($players as $i => $player_data) {
			$params = $this->normalize_player_data($player_data);
			$validation = $this->validate_player_params($params);

			if (!empty($validation)) {
				$errors[] = sprintf('Row %d: %s', $i + 1, implode(', ', $validation));
				continue;
			}

			global $wpdb;
			$table = $wpdb->prefix . 'bsp_rosters';

			$result = $wpdb->insert(
				$table,
				[
					'team_id'       => $team_id,
					'player_name'   => $params['player_name'],
					'player_number' => $params['player_number'],
					'jersey_size'   => $params['jersey_size'],
					'short_size'    => $params['short_size'],
				],
				['%d', '%s', '%s', '%s', '%s']
			);

			if ($result !== false) {
				$imported++;
			} else {
				$errors[] = sprintf('Row %d: %s', $i + 1, $wpdb->last_error ?: __('Insert failed.', 'battle-sports-platform'));
			}
		}

		return rest_ensure_response(['imported' => $imported, 'errors' => $errors]);
	}

	/**
	 * GET /artwork — Designer: list queue (filterable by status).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_artwork_list(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$queue   = new \BattleSports\Artwork\ArtworkQueue();
		$filters = [
			'page'     => (int) $request->get_param('page'),
			'per_page' => (int) $request->get_param('per_page'),
		];
		if ($request->get_param('status') !== null && $request->get_param('status') !== '') {
			$filters['status'] = (string) $request->get_param('status');
		}
		$unassigned = $request->get_param('unassigned');
		if (in_array($unassigned, ['1', 'true'], true)) {
			$filters['unassigned'] = true;
		} elseif ($request->get_param('designer_id') !== null && $request->get_param('designer_id') > 0) {
			$filters['designer_id'] = (int) $request->get_param('designer_id');
		}
		if ($request->get_param('date_from') !== null && $request->get_param('date_from') !== '') {
			$filters['date_from'] = (string) $request->get_param('date_from');
		}
		if ($request->get_param('date_to') !== null && $request->get_param('date_to') !== '') {
			$filters['date_to'] = (string) $request->get_param('date_to');
		}

		$result = $queue->get_queue($filters);
		$items  = $result['items'];

		foreach ($items as $item) {
			$item->assigned_display_name = '';
			if (!empty($item->assigned_designer_id)) {
				$designer = get_userdata((int) $item->assigned_designer_id);
				$item->assigned_display_name = $designer ? $designer->display_name : '';
			}
		}

		return rest_ensure_response([
			'items' => $items,
			'total' => $result['total'],
		]);
	}

	/**
	 * GET /artwork/{id} — Designer or team owner.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_artwork(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$queue = new \BattleSports\Artwork\ArtworkQueue();
		$row   = $queue->get_by_id((int) $request['id']);
		if (!$row) {
			return new \WP_Error('rest_not_found', __('Artwork not found.', 'battle-sports-platform'), ['status' => 404]);
		}

		$proof_url = '';
		if (!empty($row->proof_attachment_id)) {
			$proof_url = wp_get_attachment_url((int) $row->proof_attachment_id) ?: '';
		}

		$data = (array) $row;
		$data['proof_url'] = $proof_url;

		return rest_ensure_response($data);
	}

	/**
	 * PATCH /artwork/{id}/status — Designer: manual status update with notes.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function patch_artwork_status(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$queue    = new \BattleSports\Artwork\ArtworkQueue();
		$id       = (int) $request['id'];
		$status   = (string) $request['status'];
		$user_id  = get_current_user_id();

		$row = $queue->get_by_id($id);
		if ($row && $status === 'in_progress' && $row->status === 'in_queue' && $user_id > 0) {
			$queue->assign_designer($id, $user_id);
		}

		$result = $queue->update_status(
			$id,
			$status,
			$user_id,
			(string) $request->get_param('notes')
		);

		if (is_wp_error($result)) {
			return $result;
		}

		return rest_ensure_response(['updated' => true]);
	}

	/**
	 * POST /artwork/{id}/proof — Designer: multipart file upload.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function upload_artwork_proof(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$files = $request->get_file_params();
		$file  = $files['file'] ?? ($files['proof'] ?? null);

		if (!$file || empty($file['tmp_name'])) {
			return new \WP_Error('rest_missing_file', __('No file uploaded. Use multipart/form-data with "file" field.', 'battle-sports-platform'), ['status' => 400]);
		}

		$approval = new \BattleSports\Artwork\ArtworkApproval();
		$result   = $approval->upload_proof((int) $request['id'], $file);

		if (is_wp_error($result)) {
			return $result;
		}

		return rest_ensure_response(['uploaded' => true]);
	}

	/**
	 * POST /artwork/{id}/approve — Coach: customer approval.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function approve_artwork(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$approval = new \BattleSports\Artwork\ArtworkApproval();
		$result   = $approval->customer_approve((int) $request['id'], get_current_user_id());

		if (is_wp_error($result)) {
			return $result;
		}

		return rest_ensure_response(['approved' => true]);
	}

	/**
	 * POST /artwork/{id}/revision — Coach: request revision with notes.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function request_artwork_revision(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
		$approval = new \BattleSports\Artwork\ArtworkApproval();
		$result   = $approval->customer_request_revision(
			(int) $request['id'],
			get_current_user_id(),
			(string) $request['notes']
		);

		if (is_wp_error($result)) {
			return $result;
		}

		return rest_ensure_response(['revision_requested' => true]);
	}

	/**
	 * Schema for team create args.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_team_schema(): array {
		return [
			'org_name'       => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'team_name'      => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'age_group'      => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'primary_color'  => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'secondary_color' => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Schema for player create/import args.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_player_schema(): array {
		return [
			'player_name'   => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'player_number' => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'jersey_size'   => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'short_size'    => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Extracts and sanitizes team params from request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, string>
	 */
	private function get_team_params(\WP_REST_Request $request): array {
		return [
			'org_name'       => (string) $request->get_param('org_name'),
			'team_name'      => (string) $request->get_param('team_name'),
			'age_group'      => (string) $request->get_param('age_group'),
			'primary_color'  => (string) $request->get_param('primary_color'),
			'secondary_color' => (string) $request->get_param('secondary_color'),
		];
	}

	/**
	 * Extracts and sanitizes player params from request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, string>
	 */
	private function get_player_params(\WP_REST_Request $request): array {
		return [
			'player_name'   => (string) $request->get_param('player_name'),
			'player_number' => (string) $request->get_param('player_number'),
			'jersey_size'   => (string) $request->get_param('jersey_size'),
			'short_size'    => (string) $request->get_param('short_size'),
		];
	}

	/**
	 * Normalizes player data from array (for import).
	 *
	 * @param mixed $data Raw player data.
	 * @return array<string, string>
	 */
	private function normalize_player_data(mixed $data): array {
		if (!is_array($data)) {
			$data = [];
		}
		return [
			'player_name'   => isset($data['player_name']) ? sanitize_text_field((string) $data['player_name']) : '',
			'player_number' => isset($data['player_number']) ? sanitize_text_field((string) $data['player_number']) : '',
			'jersey_size'   => isset($data['jersey_size']) ? sanitize_text_field((string) $data['jersey_size']) : '',
			'short_size'    => isset($data['short_size']) ? sanitize_text_field((string) $data['short_size']) : '',
		];
	}

	/**
	 * Validates required team params.
	 *
	 * @param array<string, string> $params Team params.
	 * @return list<string>
	 */
	private function validate_team_params(array $params): array {
		$errors = [];
		if (trim($params['org_name'] ?? '') === '') {
			$errors[] = __('org_name is required.', 'battle-sports-platform');
		}
		if (trim($params['team_name'] ?? '') === '') {
			$errors[] = __('team_name is required.', 'battle-sports-platform');
		}
		return $errors;
	}

	/**
	 * Validates required player params.
	 *
	 * @param array<string, string> $params Player params.
	 * @return list<string>
	 */
	private function validate_player_params(array $params): array {
		$errors = [];
		if (trim($params['player_name'] ?? '') === '') {
			$errors[] = __('player_name is required.', 'battle-sports-platform');
		}
		return $errors;
	}
}
