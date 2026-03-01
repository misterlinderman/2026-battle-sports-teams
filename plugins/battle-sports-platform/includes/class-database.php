<?php

declare(strict_types=1);

namespace BattleSports;

defined('ABSPATH') || exit;

/**
 * Database installation and schema management.
 *
 * Creates and upgrades custom tables (bsp_teams, bsp_rosters, bsp_artwork_queue)
 * via dbDelta on plugin activation.
 */
final class Database {

	private const DB_VERSION = '1.2';

	/**
	 * Installs or upgrades database tables.
	 *
	 * Called on plugin activation. Runs dbDelta only if bsp_db_version is not set
	 * or is less than the current version.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		$installed_version = get_option('bsp_db_version', '0');
		if (version_compare($installed_version, self::DB_VERSION, '>=')) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		$tables = [
			'bsp_teams'        => "CREATE TABLE {$prefix}bsp_teams (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				org_name varchar(255) NOT NULL,
				team_name varchar(255) NOT NULL,
				age_group varchar(50) DEFAULT NULL,
				primary_color varchar(100) DEFAULT NULL,
				secondary_color varchar(100) DEFAULT NULL,
				logo_attachment_id bigint(20) unsigned DEFAULT NULL,
				sport varchar(100) DEFAULT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY user_id (user_id)
			) $charset_collate;",
			'bsp_rosters'      => "CREATE TABLE {$prefix}bsp_rosters (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				team_id bigint(20) unsigned NOT NULL,
				player_name varchar(255) NOT NULL,
				player_number varchar(10) DEFAULT NULL,
				jersey_size varchar(20) DEFAULT NULL,
				short_size varchar(20) DEFAULT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY team_id (team_id)
			) $charset_collate;",
			'bsp_artwork_queue' => "CREATE TABLE {$prefix}bsp_artwork_queue (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				order_ref varchar(100) NOT NULL,
				team_id bigint(20) unsigned DEFAULT NULL,
				user_id bigint(20) unsigned NOT NULL,
				status varchar(50) NOT NULL DEFAULT 'submitted',
				assigned_designer_id bigint(20) unsigned DEFAULT NULL,
				proof_attachment_id bigint(20) unsigned DEFAULT NULL,
				monday_item_id varchar(100) DEFAULT NULL,
				product_type varchar(100) DEFAULT NULL,
				submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY status (status),
				KEY assigned_designer_id (assigned_designer_id)
			) $charset_collate;",
			'bsp_webhook_log'  => "CREATE TABLE {$prefix}bsp_webhook_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event varchar(100) NOT NULL,
				url text NOT NULL,
				payload longtext NOT NULL,
				http_code int(11) DEFAULT 0,
				error_msg text DEFAULT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY event (event)
			) $charset_collate;",
			'bsp_artwork_log' => "CREATE TABLE {$prefix}bsp_artwork_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				artwork_id bigint(20) unsigned NOT NULL,
				from_status varchar(50) NOT NULL,
				to_status varchar(50) NOT NULL,
				changed_by bigint(20) unsigned NOT NULL,
				changed_at datetime DEFAULT CURRENT_TIMESTAMP,
				notes text DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY artwork_id (artwork_id)
			) $charset_collate;",
		];

		foreach ($tables as $table_name => $sql) {
			dbDelta($sql);
		}

		update_option('bsp_db_version', self::DB_VERSION);
	}
}
