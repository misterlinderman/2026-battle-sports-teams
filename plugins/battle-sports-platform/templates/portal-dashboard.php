<?php

declare(strict_types=1);

/**
 * Customer portal dashboard template.
 *
 * @var array<object> $teams
 * @var array<object> $recent_orders
 * @var array<object> $pending_approvals
 */

defined('ABSPATH') || exit;

$first_name = wp_get_current_user()->first_name;
$user_display = !empty($first_name) ? $first_name : wp_get_current_user()->display_name;
$teams_count = count($teams);
$orders_count = count($recent_orders);
$approvals_count = count($pending_approvals);
?>
<div class="bsp-portal bsp-portal--dashboard">
	<h1 class="bsp-portal__title">
		<?php
		printf(
			/* translators: %s: User's first name */
			esc_html__('Welcome, %s', 'battle-sports-platform'),
			esc_html($user_display)
		);
		?>
	</h1>

	<div class="bsp-portal__summary">
		<div class="bsp-portal__card bsp-portal__card--teams">
			<span class="bsp-portal__card-value"><?php echo esc_html((string) $teams_count); ?></span>
			<span class="bsp-portal__card-label"><?php esc_html_e('My Teams', 'battle-sports-platform'); ?></span>
		</div>
		<div class="bsp-portal__card bsp-portal__card--orders">
			<span class="bsp-portal__card-value"><?php echo esc_html((string) $orders_count); ?></span>
			<span class="bsp-portal__card-label"><?php esc_html_e('Active Orders', 'battle-sports-platform'); ?></span>
		</div>
		<div class="bsp-portal__card bsp-portal__card--approvals">
			<span class="bsp-portal__card-value"><?php echo esc_html((string) $approvals_count); ?></span>
			<span class="bsp-portal__card-label"><?php esc_html_e('Pending Approvals', 'battle-sports-platform'); ?></span>
		</div>
	</div>

	<div class="bsp-portal__actions">
		<a href="#" class="bsp-portal__action bsp-portal__action--order"><?php esc_html_e('Start New Order', 'battle-sports-platform'); ?></a>
		<?php if (current_user_can('bsp_manage_roster')) : ?>
		<a href="<?php echo esc_url(\BattleSports\CustomerPortal\Portal::get_rosters_page_url()); ?>" class="bsp-portal__action bsp-portal__action--rosters"><?php esc_html_e('Manage Rosters', 'battle-sports-platform'); ?></a>
		<?php endif; ?>
		<?php if (current_user_can('bsp_view_artwork_queue')) : ?>
		<a href="<?php echo esc_url(\BattleSports\CustomerPortal\Portal::get_artwork_queue_page_url()); ?>" class="bsp-portal__action bsp-portal__action--queue"><?php esc_html_e('View Artwork Queue', 'battle-sports-platform'); ?></a>
		<?php endif; ?>
	</div>

	<?php if (!empty($pending_approvals)) : ?>
	<div class="bsp-portal-artwork bsp-portal__section" data-empty-text="<?php echo esc_attr(__('No artwork pending approval.', 'battle-sports-platform')); ?>">
		<h2 class="bsp-portal__section-title"><?php esc_html_e('My Artwork', 'battle-sports-platform'); ?></h2>
		<p class="bsp-portal__section-desc"><?php esc_html_e('Review proofs and approve or request revisions.', 'battle-sports-platform'); ?></p>
		<div class="bsp-portal-artwork__grid">
			<?php foreach ($pending_approvals as $approval) : ?>
			<?php
			$proof_thumb = '';
			if (!empty($approval->proof_attachment_id)) {
				$proof_thumb = wp_get_attachment_image_url((int) $approval->proof_attachment_id, 'thumbnail');
			}
			$proof_url = $proof_thumb ?: (wp_get_attachment_url((int) ($approval->proof_attachment_id ?? 0)) ?: '');
			?>
			<div class="bsp-portal-artwork__item" data-artwork-id="<?php echo esc_attr((string) $approval->id); ?>">
				<div class="bsp-portal-artwork__preview">
					<?php if ($proof_thumb) : ?>
					<img src="<?php echo esc_url($proof_thumb); ?>" alt="<?php echo esc_attr(sprintf(__('Proof for order %s', 'battle-sports-platform'), $approval->order_ref ?? '')); ?>" class="bsp-portal-artwork__thumbnail">
					<?php elseif ($proof_url) : ?>
					<a href="<?php echo esc_url($proof_url); ?>" target="_blank" rel="noopener" class="bsp-portal-artwork__link"><?php esc_html_e('View proof', 'battle-sports-platform'); ?></a>
					<?php else : ?>
					<span class="bsp-portal-artwork__no-preview"><?php esc_html_e('No preview', 'battle-sports-platform'); ?></span>
					<?php endif; ?>
				</div>
				<div class="bsp-portal-artwork__info">
					<strong><?php echo esc_html($approval->order_ref ?? ''); ?></strong>
					<?php if (!empty($approval->product_type)) : ?>
					<span class="bsp-portal-artwork__product"><?php echo esc_html($approval->product_type); ?></span>
					<?php endif; ?>
				</div>
				<div class="bsp-portal-artwork__actions">
					<button type="button" class="bsp-portal-artwork__btn bsp-portal-artwork__btn--approve"><?php esc_html_e('Approve', 'battle-sports-platform'); ?></button>
					<button type="button" class="bsp-portal-artwork__btn bsp-portal-artwork__btn--revision"><?php esc_html_e('Request Revision', 'battle-sports-platform'); ?></button>
				</div>
				<div class="bsp-portal-artwork__revision-form" hidden>
					<label for="bsp-revision-notes-<?php echo esc_attr((string) $approval->id); ?>" class="bsp-portal-artwork__revision-label"><?php esc_html_e('Revision notes', 'battle-sports-platform'); ?></label>
					<textarea id="bsp-revision-notes-<?php echo esc_attr((string) $approval->id); ?>" class="bsp-portal-artwork__revision-notes" rows="3" placeholder="<?php esc_attr_e('Describe what changes you need...', 'battle-sports-platform'); ?>"></textarea>
					<div class="bsp-portal-artwork__revision-buttons">
						<button type="button" class="bsp-portal-artwork__btn bsp-portal-artwork__btn--revision-submit"><?php esc_html_e('Submit', 'battle-sports-platform'); ?></button>
						<button type="button" class="bsp-portal-artwork__btn bsp-portal-artwork__btn--revision-cancel"><?php esc_html_e('Cancel', 'battle-sports-platform'); ?></button>
					</div>
				</div>
				<p class="bsp-portal-artwork__message" role="status" aria-live="polite" hidden></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if (!empty($recent_orders)) : ?>
	<div class="bsp-portal__orders">
		<h2 class="bsp-portal__section-title"><?php esc_html_e('Recent Orders', 'battle-sports-platform'); ?></h2>
		<table class="bsp-portal__table">
			<thead class="bsp-portal__table-head">
				<tr class="bsp-portal__table-row">
					<th class="bsp-portal__table-header"><?php esc_html_e('Order Ref', 'battle-sports-platform'); ?></th>
					<th class="bsp-portal__table-header"><?php esc_html_e('Product', 'battle-sports-platform'); ?></th>
					<th class="bsp-portal__table-header"><?php esc_html_e('Status', 'battle-sports-platform'); ?></th>
					<th class="bsp-portal__table-header"><?php esc_html_e('Submitted Date', 'battle-sports-platform'); ?></th>
				</tr>
			</thead>
			<tbody class="bsp-portal__table-body">
				<?php foreach ($recent_orders as $order) : ?>
				<tr class="bsp-portal__table-row">
					<td class="bsp-portal__table-cell"><?php echo esc_html($order->order_ref ?? ''); ?></td>
					<td class="bsp-portal__table-cell"><?php echo esc_html($order->product_type ?? ''); ?></td>
					<td class="bsp-portal__table-cell"><?php echo esc_html($order->status ?? ''); ?></td>
					<td class="bsp-portal__table-cell"><?php echo esc_html($order->submitted_at ?? ''); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php else : ?>
	<div class="bsp-portal__orders">
		<h2 class="bsp-portal__section-title"><?php esc_html_e('Recent Orders', 'battle-sports-platform'); ?></h2>
		<p class="bsp-portal__empty"><?php esc_html_e('No orders yet.', 'battle-sports-platform'); ?></p>
	</div>
	<?php endif; ?>
</div>
