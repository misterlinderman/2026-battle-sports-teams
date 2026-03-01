<?php

declare(strict_types=1);

/**
 * Roster management template.
 *
 * Container for the roster manager UI. JavaScript populates
 * team selector, roster table, and handles Add/Delete/Import.
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$portal_page = get_page_by_path('portal', OBJECT, 'page');
$portal_url  = $portal_page ? get_permalink($portal_page) : home_url('/portal/');
?>
<div class="bsp-roster-manager">
	<p class="bsp-roster-manager__back">
		<a href="<?php echo esc_url($portal_url); ?>"><?php esc_html_e('← Back to Portal', 'battle-sports-platform'); ?></a>
	</p>
	<div class="bsp-roster-manager__header">
		<label for="bsp-roster-team-select" class="bsp-roster-manager__label">
			<?php esc_html_e('Team', 'battle-sports-platform'); ?>
		</label>
		<select class="bsp-roster-manager__team-select" id="bsp-roster-team-select" aria-label="<?php esc_attr_e('Select team', 'battle-sports-platform'); ?>">
			<option value=""><?php esc_html_e('— Select a team —', 'battle-sports-platform'); ?></option>
		</select>
	</div>

	<p class="bsp-roster-manager__message" role="status" aria-live="polite" hidden></p>

	<div class="bsp-roster-manager__roster">
		<div class="bsp-roster-manager__table-wrap">
			<table class="bsp-roster-manager__table">
				<thead>
					<tr>
						<th><?php esc_html_e('Name', 'battle-sports-platform'); ?></th>
						<th><?php esc_html_e('Number', 'battle-sports-platform'); ?></th>
						<th><?php esc_html_e('Jersey Size', 'battle-sports-platform'); ?></th>
						<th><?php esc_html_e('Short Size', 'battle-sports-platform'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>

	<div class="bsp-roster-manager__actions">
		<button type="button" class="bsp-roster-manager__add-btn">
			<?php esc_html_e('Add Player', 'battle-sports-platform'); ?>
		</button>
	</div>

	<div class="bsp-roster-manager__import">
		<h3 class="bsp-roster-manager__import-title"><?php esc_html_e('Import CSV', 'battle-sports-platform'); ?></h3>
		<p class="bsp-roster-manager__import-guide">
			<?php
			esc_html_e(
				'CSV format: First row must be headers. Supported columns: Name (or player_name), Number (or player_number), Jersey Size (or jersey_size), Short Size (or short_size). Example: Name,Number,Jersey Size,Short Size',
				'battle-sports-platform'
			);
			?>
		</p>
		<button type="button" class="bsp-roster-manager__import-btn">
			<?php esc_html_e('Choose CSV file...', 'battle-sports-platform'); ?>
		</button>
		<input type="file" class="bsp-roster-manager__import-input" accept=".csv,text/csv" hidden aria-label="<?php esc_attr_e('Select CSV file to import', 'battle-sports-platform'); ?>">
	</div>
</div>
