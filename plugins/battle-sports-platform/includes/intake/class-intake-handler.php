<?php

declare(strict_types=1);

namespace BattleSports\Intake;

defined('ABSPATH') || exit;

/**
 * Handles intake form submission: creates WooCommerce order with submission fee,
 * creates artwork queue entry, and redirects to checkout.
 */
final class IntakeHandler {

    /**
     * Registers hooks for form submission handling and order status.
     *
     * @return void
     */
    public static function init(): void {
        // maybe_handle_submission is registered in Plugin::register_hooks() at init priority 5
        add_action('woocommerce_order_status_changed', [self::class, 'on_order_status_changed'], 10, 3);
    }

    /**
     * When order status becomes processing or completed, update artwork to in_queue.
     *
     * @param int    $order_id   Order ID.
     * @param string $old_status Old status.
     * @param string $new_status New status.
     * @return void
     */
    public static function on_order_status_changed(int $order_id, string $old_status, string $new_status): void {
        if (!in_array($new_status, ['processing', 'completed'], true)) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order || !$order->get_id()) {
            return;
        }

        $intake_data = $order->get_meta('_bsp_intake_data');
        if ($intake_data === '' || $intake_data === null) {
            return;
        }

        $decoded = json_decode($intake_data, true);
        if (!is_array($decoded) || empty($decoded['artwork_id'])) {
            return;
        }

        $artwork_id = (int) $decoded['artwork_id'];
        if ($artwork_id <= 0) {
            return;
        }

        $queue = new \BattleSports\Artwork\ArtworkQueue();
        $row   = $queue->get_by_id($artwork_id);
        if (!$row || $row->status !== 'submitted') {
            return;
        }

        $queue->update_status($artwork_id, 'in_queue', 0, __('Payment received.', 'battle-sports-platform'));

        \BattleSports\Webhooks::trigger_make_webhook('order_payment_received', [
            'artwork_id' => $artwork_id,
            'order_id'  => $order_id,
            'order_ref' => $row->order_ref ?? '',
        ]);
    }

    /**
     * Processes intake form POST if valid submission.
     *
     * @return void
     */
    public static function maybe_handle_submission(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $nonce = isset($_POST['bsp_intake_nonce']) ? sanitize_text_field(wp_unslash($_POST['bsp_intake_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'bsp_intake_submit')) {
            return;
        }

        $data = self::parse_submission_data();
        if ($data === null) {
            self::redirect_with_error(__('Invalid form data.', 'battle-sports-platform'));
            exit;
        }

        $errors = IntakeForm::validate_submission($data);
        if (!empty($errors)) {
            self::redirect_with_error(implode(' ', $errors));
            exit;
        }

        if (!function_exists('wc_create_order')) {
            self::redirect_with_error(__('WooCommerce is required for submission.', 'battle-sports-platform'));
            exit;
        }

        $product_id = (int) get_option('bsp_submission_fee_product_id', 0);
        if ($product_id <= 0) {
            \BattleSports\Plugin::create_submission_fee_product();
            $product_id = (int) get_option('bsp_submission_fee_product_id', 0);
        }
        if ($product_id <= 0) {
            self::redirect_with_error(__('Submission fee product is not configured.', 'battle-sports-platform'));
            exit;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            self::redirect_with_error(__('Please log in to submit your order.', 'battle-sports-platform'));
            exit;
        }

        $result = self::create_order_and_redirect($data, $product_id, $user_id);
        if (is_wp_error($result)) {
            self::redirect_with_error($result->get_error_message());
            exit;
        }

        wp_safe_redirect($result['payment_url']);
        exit;
    }

    /**
     * Parses and sanitizes submission data from POST.
     *
     * @return array<string, mixed>|null Structured data or null if invalid.
     */
    private static function parse_submission_data(): ?array {
        $state_raw = isset($_POST['bsp_intake_state']) ? sanitize_text_field(wp_unslash($_POST['bsp_intake_state'])) : '';
        $product   = isset($_POST['bsp_intake_product']) ? sanitize_key(wp_unslash($_POST['bsp_intake_product'])) : '7v7';

        $data = [
            'product'  => $product,
            'customer' => [],
            'team'      => [],
            'design'    => [],
            'roster'    => [],
        ];

        if ($state_raw !== '') {
            $decoded = json_decode($state_raw, true);
            if (is_array($decoded)) {
                $data['customer'] = isset($decoded['customer']) && is_array($decoded['customer'])
                    ? array_map('sanitize_text_field', $decoded['customer'])
                    : [];
                $data['team'] = isset($decoded['team']) && is_array($decoded['team'])
                    ? array_map('sanitize_text_field', $decoded['team'])
                    : [];
                $data['design'] = isset($decoded['design']) && is_array($decoded['design'])
                    ? array_map(fn($v) => is_string($v) ? sanitize_text_field($v) : $v, $decoded['design'])
                    : [];
                $data['roster'] = isset($decoded['roster']) && is_array($decoded['roster'])
                    ? $decoded['roster']
                    : [];
            }
        }

        if (empty($data['customer'])) {
            $data['customer'] = [
                'first_name' => isset($_POST['bsp_customer_first_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_first_name'])) : '',
                'last_name'  => isset($_POST['bsp_customer_last_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_last_name'])) : '',
                'role'       => isset($_POST['bsp_customer_role']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_role'])) : '',
                'street'     => isset($_POST['bsp_customer_street']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_street'])) : '',
                'city'       => isset($_POST['bsp_customer_city']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_city'])) : '',
                'state'      => isset($_POST['bsp_customer_state']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_state'])) : '',
                'zip'        => isset($_POST['bsp_customer_zip']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_zip'])) : '',
                'email'      => isset($_POST['bsp_customer_email']) ? sanitize_email(wp_unslash($_POST['bsp_customer_email'])) : '',
                'phone'      => isset($_POST['bsp_customer_phone']) ? sanitize_text_field(wp_unslash($_POST['bsp_customer_phone'])) : '',
            ];
        }

        if (empty($data['team']['org_name'])) {
            $data['team']['org_name']   = isset($_POST['bsp_team_org_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_team_org_name'])) : '';
            $data['team']['team_name']   = isset($_POST['bsp_team_name']) ? sanitize_text_field(wp_unslash($_POST['bsp_team_name'])) : '';
            $data['team']['age_group']   = isset($_POST['bsp_team_age_group']) ? sanitize_text_field(wp_unslash($_POST['bsp_team_age_group'])) : '';
        }

        return $data;
    }

    /**
     * Creates WooCommerce order and returns payment URL.
     *
     * @param array<string, mixed> $data        Sanitized intake data.
     * @param int                  $product_id Submission fee product ID.
     * @param int                  $user_id    Current user ID.
     * @return array{order_id: int, payment_url: string}|\WP_Error
     */
    private static function create_order_and_redirect(array $data, int $product_id, int $user_id): array|\WP_Error {
        $product = wc_get_product($product_id);
        if (!$product || !$product->exists()) {
            return new \WP_Error('bsp_invalid_product', __('Submission fee product not found.', 'battle-sports-platform'));
        }

        $order = wc_create_order(['customer_id' => $user_id]);
        if (!$order || !$order->get_id()) {
            return new \WP_Error('bsp_order_failed', __('Failed to create order.', 'battle-sports-platform'));
        }

        $order->add_product($product, 1);

        $customer = $data['customer'];
        $address  = [
            'first_name' => $customer['first_name'] ?? '',
            'last_name'  => $customer['last_name'] ?? '',
            'address_1'  => $customer['street'] ?? '',
            'city'       => $customer['city'] ?? '',
            'state'      => $customer['state'] ?? '',
            'postcode'   => $customer['zip'] ?? '',
            'email'      => $customer['email'] ?? '',
            'phone'      => $customer['phone'] ?? '',
        ];
        $order->set_address($address, 'billing');
        $order->set_address($address, 'shipping');

        $order->calculate_totals();
        $order->set_status('pending', __('Awaiting payment.', 'battle-sports-platform'));
        $order->save();

        $order_ref = (string) $order->get_order_number();
        $queue     = new \BattleSports\Artwork\ArtworkQueue();
        $artwork_id = $queue->create([
            'order_ref'    => $order_ref,
            'user_id'     => $user_id,
            'team_id'     => null,
            'product_type' => $data['product'] ?? '7v7',
        ]);

        $intake_meta = [
            'artwork_id' => $artwork_id ?: null,
            'customer'   => $data['customer'],
            'team'       => $data['team'],
            'design'     => $data['design'],
            'roster'     => $data['roster'],
            'product'    => $data['product'],
        ];
        $order->update_meta_data('_bsp_intake_data', wp_json_encode($intake_meta));
        $order->save();

        if ($artwork_id) {
            $queue = new \BattleSports\Artwork\ArtworkQueue();
            $art_row = $queue->get_by_id($artwork_id);
            \BattleSports\Webhooks::trigger_make_webhook('artwork_submitted', [
                'artwork_id'   => $artwork_id,
                'order_ref'    => $order_ref,
                'team_name'    => $art_row ? ($art_row->team_name ?? '') : '',
                'product_type' => $data['product'] ?? '7v7',
            ]);
        }

        $payment_url = $order->get_checkout_payment_url();

        return [
            'order_id'    => $order->get_id(),
            'payment_url' => $payment_url,
        ];
    }

    /**
     * Redirects back with an error message.
     *
     * @param string $message Error message.
     * @return void
     */
    private static function redirect_with_error(string $message): void {
        $referer = wp_get_referer();
        $url     = $referer ?: home_url('/');
        $url     = add_query_arg('bsp_error', rawurlencode($message), $url);
        wp_safe_redirect($url);
    }
}
