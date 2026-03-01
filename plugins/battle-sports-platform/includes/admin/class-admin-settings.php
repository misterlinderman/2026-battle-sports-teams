<?php

declare(strict_types=1);

namespace BattleSports\Admin;

defined('ABSPATH') || exit;

/**
 * Battle Sports admin settings page.
 *
 * Adds "Battle Sports" menu with settings including Submission Fee Product.
 */
final class AdminSettings {

    private const MENU_SLUG = 'battle-sports-settings';
    private const OPTION_GROUP = 'battle_sports_settings';

    /**
     * Registers admin menu and settings.
     *
     * @return void
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu_page']);
        add_action('admin_init', [self::class, 'handle_reset_product']);
    }

    /**
     * Adds Battle Sports admin menu and settings page.
     *
     * @return void
     */
    public static function add_menu_page(): void {
        add_menu_page(
            __('Battle Sports', 'battle-sports-platform'),
            __('Battle Sports', 'battle-sports-platform'),
            'manage_options',
            self::MENU_SLUG,
            [self::class, 'render_page'],
            'dashicons-store',
            30
        );
    }

    /**
     * Handles "Reset Product" button submission.
     *
     * @return void
     */
    public static function handle_reset_product(): void {
        if (!isset($_POST['bsp_reset_submission_fee_product']) || !wp_verify_nonce(
            isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '',
            'bsp_reset_submission_fee_product'
        )) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        delete_option('bsp_submission_fee_product_id');
        \BattleSports\Plugin::create_submission_fee_product();

        add_settings_error(
            'battle_sports_settings',
            'bsp_submission_fee_reset',
            __('Submission fee product has been recreated.', 'battle-sports-platform'),
            'success'
        );
    }

    /**
     * Renders the admin settings page.
     *
     * @return void
     */
    public static function render_page(): void {
        if (!function_exists('wc_get_product')) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('WooCommerce is required for the submission fee product.', 'battle-sports-platform') . '</p></div>';
            return;
        }

        $product_id = (int) get_option('bsp_submission_fee_product_id', 0);
        $product    = $product_id > 0 ? wc_get_product($product_id) : null;
        $price      = $product && $product->exists() ? $product->get_price() : '';
        $exists     = $product && $product->exists();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Battle Sports Settings', 'battle-sports-platform'); ?></h1>

            <?php settings_errors('battle_sports_settings'); ?>

            <form method="post" action="">
                <?php wp_nonce_field('bsp_reset_submission_fee_product'); ?>

                <h2><?php esc_html_e('Submission Fee Product', 'battle-sports-platform'); ?></h2>
                <p class="description">
                    <?php esc_html_e('The $50 uniform design submission fee is charged via WooCommerce when customers submit an intake form.', 'battle-sports-platform'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Product ID', 'battle-sports-platform'); ?></th>
                            <td>
                                <?php if ($exists) : ?>
                                    <code><?php echo esc_html((string) $product_id); ?></code>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit')); ?>">
                                        <?php esc_html_e('Edit in WooCommerce', 'battle-sports-platform'); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="bsp-status-missing">
                                        <?php
                                        echo $product_id > 0
                                            ? esc_html__('Product was deleted. Click "Reset Product" to recreate.', 'battle-sports-platform')
                                            : esc_html__('Not set. Click "Reset Product" to create.', 'battle-sports-platform');
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Price', 'battle-sports-platform'); ?></th>
                            <td>
                                <?php if ($exists && $price !== '') : ?>
                                    <?php echo esc_html(wc_price($price)); ?>
                                <?php else : ?>
                                    <span class="description"><?php esc_html_e('$50.00', 'battle-sports-platform'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Reset Product', 'battle-sports-platform'); ?></th>
                            <td>
                                <p class="description">
                                    <?php esc_html_e('If the product was accidentally deleted, click below to recreate it.', 'battle-sports-platform'); ?>
                                </p>
                                <p>
                                    <button type="submit" name="bsp_reset_submission_fee_product" class="button button-secondary">
                                        <?php esc_html_e('Reset Product', 'battle-sports-platform'); ?>
                                    </button>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }
}
