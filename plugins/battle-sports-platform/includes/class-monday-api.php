<?php

declare(strict_types=1);

namespace BattleSports;

defined('ABSPATH') || exit;

/**
 * Monday.com API helper (GraphQL v2).
 *
 * Uses wp_remote_post() to https://api.monday.com/v2.
 * No SDK; simple GraphQL queries.
 */
final class MondayApi {

	private const API_URL = 'https://api.monday.com/v2';
	private const OPTION_API_KEY = 'bsp_monday_api_key';
	private const OPTION_CONFIG  = 'bsp_monday_config';

	/**
	 * Sends a GraphQL request to Monday.com API.
	 *
	 * @param string               $query     GraphQL query string.
	 * @param array<string, mixed>  $variables Optional variables.
	 * @return array{data?: array, errors?: array}|\WP_Error
	 */
	public function request(string $query, array $variables = []): array|\WP_Error {
		$api_key = $this->get_api_key();
		if ($api_key === '') {
			return new \WP_Error(
				'missing_api_key',
				__('Monday.com API key is not configured.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		$body = [
			'query'     => $query,
			'variables' => $variables,
		];

		$response = wp_remote_post(self::API_URL, [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => $api_key,
				'API-Version'  => '2024-01',
			],
			'body'    => wp_json_encode($body),
			'timeout' => 15,
		]);

		if (is_wp_error($response)) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code($response);
		$raw  = wp_remote_retrieve_body($response);
		$json = json_decode($raw, true);

		if ($code < 200 || $code >= 300) {
			return new \WP_Error(
				'monday_api_error',
				$json['error_message'] ?? $raw ?: __('Monday API request failed.', 'battle-sports-platform'),
				['status' => $code]
			);
		}

		if (!is_array($json)) {
			return new \WP_Error(
				'invalid_response',
				__('Invalid response from Monday.com API.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		if (!empty($json['errors'])) {
			$msg = is_array($json['errors'][0] ?? null)
				? ($json['errors'][0]['message'] ?? wp_json_encode($json['errors']))
				: wp_json_encode($json['errors']);
			return new \WP_Error(
				'monday_graphql_error',
				$msg,
				['status' => 400]
			);
		}

		return $json;
	}

	/**
	 * Creates a new item on a Monday board.
	 *
	 * @param int                  $board_id      Monday board ID.
	 * @param string               $group_id     Monday group ID (e.g. "topics" or numeric).
	 * @param string                $name         Item name.
	 * @param array<string, mixed>  $column_values Column ID => value map.
	 * @return array{id: string}|\WP_Error
	 */
	public function create_item(int $board_id, string $group_id, string $name, array $column_values = []): array|\WP_Error {
		$col_json = wp_json_encode($column_values);
		$query    = '
			mutation ($boardId: ID!, $groupId: String!, $itemName: String!, $columnValues: JSON!) {
				create_item (board_id: $boardId, group_id: $groupId, item_name: $itemName, column_values: $columnValues) {
					id
				}
			}
		';

		$result = $this->request($query, [
			'boardId'      => (string) $board_id,
			'groupId'      => $group_id,
			'itemName'     => $name,
			'columnValues' => $col_json,
		]);

		if (is_wp_error($result)) {
			return $result;
		}

		$id = $result['data']['create_item']['id'] ?? null;
		if ($id === null) {
			return new \WP_Error(
				'monday_no_id',
				__('Monday API did not return item ID.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		return ['id' => $id];
	}

	/**
	 * Updates a status column for an item.
	 *
	 * @param int    $item_id     Monday item ID.
	 * @param string $column_id   Status column ID.
	 * @param string $status_label Status label (e.g. "In Progress").
	 * @return true|\WP_Error
	 */
	public function update_status(int $item_id, string $column_id, string $status_label): true|\WP_Error {
		$col_value = wp_json_encode([
			'label' => $status_label,
		]);

		$query = '
			mutation ($itemId: ID!, $boardId: ID!, $columnId: String!, $value: JSON!) {
				change_column_value (item_id: $itemId, board_id: $boardId, column_id: $columnId, value: $value) {
					id
				}
			}
		';

		$config = $this->get_config();
		$board_id = $config['board_id'] ?? 0;
		if ($board_id <= 0) {
			return new \WP_Error(
				'missing_board_id',
				__('Monday board ID is not configured.', 'battle-sports-platform'),
				['status' => 500]
			);
		}

		$result = $this->request($query, [
			'itemId'   => (string) $item_id,
			'boardId'  => (string) $board_id,
			'columnId' => $column_id,
			'value'    => $col_value,
		]);

		if (is_wp_error($result)) {
			return $result;
		}

		return true;
	}

	/**
	 * Fetches board name for "Test Connection" — returns board name if valid.
	 *
	 * @return array{success: bool, board_name?: string, error?: string}
	 */
	public function test_connection(): array {
		$config = $this->get_config();
		$board_id = $config['board_id'] ?? get_option('bsp_monday_board_id', '');
		if ($board_id === '' || $board_id === null) {
			return ['success' => false, 'error' => __('Board ID is not configured.', 'battle-sports-platform')];
		}

		$board_id = (string) $board_id;
		$query    = 'query ($boardId: ID!) { boards(ids: [$boardId]) { id name } }';

		$result = $this->request($query, ['boardId' => $board_id]);

		if (is_wp_error($result)) {
			return ['success' => false, 'error' => $result->get_error_message()];
		}

		$boards = $result['data']['boards'] ?? [];
		if (empty($boards)) {
			return ['success' => false, 'error' => __('Board not found or no access.', 'battle-sports-platform')];
		}

		$name = $boards[0]['name'] ?? __('Unknown', 'battle-sports-platform');
		return ['success' => true, 'board_name' => $name];
	}

	/**
	 * Gets the Monday API key from options.
	 *
	 * @return string
	 */
	private function get_api_key(): string {
		$key = get_option(self::OPTION_API_KEY, '');
		// Option stores raw value; wp_hash would make it irreversible, so we store plain (masked in UI).
		return is_string($key) ? $key : '';
	}

	/**
	 * Gets Monday config (board_id, column IDs) from options.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config(): array {
		$config = get_option(self::OPTION_CONFIG, '');
		if ($config === '') {
			// Fallback: board ID might be stored separately for admin UI.
			$board_id = get_option('bsp_monday_board_id', '');
			return $board_id !== '' ? ['board_id' => $board_id] : [];
		}

		if (is_string($config)) {
			$decoded = json_decode($config, true);
			return is_array($decoded) ? $decoded : [];
		}

		return is_array($config) ? $config : [];
	}
}
