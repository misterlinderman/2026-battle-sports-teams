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
		<a href="#" class="bsp-portal__action bsp-portal__action--queue"><?php esc_html_e('View Artwork Queue', 'battle-sports-platform'); ?></a>
	</div>

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
