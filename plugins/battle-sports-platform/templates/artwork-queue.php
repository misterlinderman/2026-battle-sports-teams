<?php

declare(strict_types=1);

/**
 * Designer artwork queue template.
 *
 * Tabbed interface: All | In Queue | In Progress | Proof Sent | Awaiting Revision
 * Rows: Order Ref | Team | Product Type | Submitted | Status | Assigned | Actions
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$portal_page = get_page_by_path('portal', OBJECT, 'page');
$portal_url  = $portal_page ? get_permalink($portal_page) : home_url('/portal/');
?>
<div class="bsp-artwork-queue" id="bsp-artwork-queue">
	<p class="bsp-artwork-queue__back">
		<?php if (current_user_can('bsp_manage_roster')) : ?>
		<a href="<?php echo esc_url($portal_url); ?>"><?php esc_html_e('← Back to Portal', 'battle-sports-platform'); ?></a>
		<span aria-hidden="true"> · </span>
		<?php endif; ?>
		<a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><?php esc_html_e('Log Out', 'battle-sports-platform'); ?></a>
	</p>

	<h1 class="bsp-artwork-queue__title"><?php esc_html_e('Artwork Queue', 'battle-sports-platform'); ?></h1>

	<div class="bsp-artwork-queue__tabs" role="tablist" aria-label="<?php esc_attr_e('Filter by status', 'battle-sports-platform'); ?>">
		<button type="button" class="bsp-artwork-queue__tab bsp-artwork-queue__tab--active" role="tab" aria-selected="true" data-tab="">
			<?php esc_html_e('All', 'battle-sports-platform'); ?>
		</button>
		<button type="button" class="bsp-artwork-queue__tab" role="tab" aria-selected="false" data-tab="in_queue">
			<?php esc_html_e('In Queue', 'battle-sports-platform'); ?>
		</button>
		<button type="button" class="bsp-artwork-queue__tab" role="tab" aria-selected="false" data-tab="in_progress">
			<?php esc_html_e('In Progress', 'battle-sports-platform'); ?>
		</button>
		<button type="button" class="bsp-artwork-queue__tab" role="tab" aria-selected="false" data-tab="proof_sent">
			<?php esc_html_e('Proof Sent', 'battle-sports-platform'); ?>
		</button>
		<button type="button" class="bsp-artwork-queue__tab" role="tab" aria-selected="false" data-tab="revision_requested">
			<?php esc_html_e('Awaiting Revision', 'battle-sports-platform'); ?>
		</button>
	</div>

	<div class="bsp-artwork-queue__filters">
		<label for="bsp-artwork-assignment" class="bsp-artwork-queue__filter-label">
			<?php esc_html_e('Assignment', 'battle-sports-platform'); ?>
		</label>
		<select id="bsp-artwork-assignment" class="bsp-artwork-queue__filter-select" aria-label="<?php esc_attr_e('Filter by assignment', 'battle-sports-platform'); ?>">
			<option value=""><?php esc_html_e('All', 'battle-sports-platform'); ?></option>
			<option value="me"><?php esc_html_e('Assigned to me', 'battle-sports-platform'); ?></option>
			<option value="unassigned"><?php esc_html_e('Unassigned', 'battle-sports-platform'); ?></option>
		</select>

		<label for="bsp-artwork-date-from" class="bsp-artwork-queue__filter-label">
			<?php esc_html_e('From date', 'battle-sports-platform'); ?>
		</label>
		<input type="date" id="bsp-artwork-date-from" class="bsp-artwork-queue__filter-date" aria-label="<?php esc_attr_e('Filter from date', 'battle-sports-platform'); ?>">

		<label for="bsp-artwork-date-to" class="bsp-artwork-queue__filter-label">
			<?php esc_html_e('To date', 'battle-sports-platform'); ?>
		</label>
		<input type="date" id="bsp-artwork-date-to" class="bsp-artwork-queue__filter-date" aria-label="<?php esc_attr_e('Filter to date', 'battle-sports-platform'); ?>">
	</div>

	<p class="bsp-artwork-queue__message" role="status" aria-live="polite" hidden></p>

	<div class="bsp-artwork-queue__table-wrap">
		<table class="bsp-artwork-queue__table">
			<thead>
				<tr>
					<th><?php esc_html_e('Order Ref', 'battle-sports-platform'); ?></th>
					<th><?php esc_html_e('Team', 'battle-sports-platform'); ?></th>
					<th><?php esc_html_e('Product Type', 'battle-sports-platform'); ?></th>
					<th><?php esc_html_e('Submitted', 'battle-sports-platform'); ?></th>
					<th><?php esc_html_e('Status', 'battle-sports-platform'); ?></th>
					<th><?php esc_html_e('Assigned', 'battle-sports-platform'); ?></th>
					<th><?php esc_html_e('Actions', 'battle-sports-platform'); ?></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>

	<div class="bsp-artwork-queue__empty" hidden>
		<?php esc_html_e('No artwork items match the current filters.', 'battle-sports-platform'); ?>
	</div>

	<div class="bsp-artwork-queue__modal" id="bsp-artwork-upload-modal" role="dialog" aria-modal="true" aria-labelledby="bsp-artwork-upload-modal-title" hidden>
		<div class="bsp-artwork-queue__modal-backdrop"></div>
		<div class="bsp-artwork-queue__modal-content">
			<h2 id="bsp-artwork-upload-modal-title" class="bsp-artwork-queue__modal-title">
				<?php esc_html_e('Upload Proof', 'battle-sports-platform'); ?>
			</h2>
			<p class="bsp-artwork-queue__modal-order-ref"></p>
			<form class="bsp-artwork-queue__upload-form" id="bsp-artwork-upload-form">
				<input type="hidden" name="artwork_id" id="bsp-artwork-upload-id" value="">
				<label for="bsp-artwork-proof-file" class="bsp-artwork-queue__upload-label">
					<?php esc_html_e('Choose file (JPG, PNG, PDF, AI, EPS — max 50MB)', 'battle-sports-platform'); ?>
				</label>
				<input type="file" id="bsp-artwork-proof-file" name="file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps,image/jpeg,image/png,application/pdf,application/postscript,application/illustrator" required>
				<div class="bsp-artwork-queue__modal-actions">
					<button type="submit" class="bsp-artwork-queue__btn bsp-artwork-queue__btn--primary">
						<?php esc_html_e('Upload', 'battle-sports-platform'); ?>
					</button>
					<button type="button" class="bsp-artwork-queue__btn bsp-artwork-queue__btn--cancel">
						<?php esc_html_e('Cancel', 'battle-sports-platform'); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
