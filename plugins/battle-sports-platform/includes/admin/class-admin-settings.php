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
    private const OPTION_EMPHASIS_COLOR = 'bsp_emphasis_color';
    private const DEFAULT_EMPHASIS_COLOR = '#ceff00';

    /**
     * Registers admin menu and settings.
     *
     * @return void
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu_page']);
        add_action('admin_bar_menu', [self::class, 'add_admin_bar_link'], 80);
        add_action('admin_init', [self::class, 'handle_reset_product']);
        add_action('admin_init', [self::class, 'handle_create_missing_pages']);
        add_action('admin_init', [self::class, 'handle_integrations_save']);
        add_action('admin_init', [self::class, 'handle_appearance_save']);
        add_action('wp_ajax_bsp_test_monday_connection', [self::class, 'ajax_test_monday_connection']);
        add_action('wp_head', [self::class, 'output_emphasis_css'], 5);
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
            29
        );
    }

    /**
     * Adds Battle Sports link to the admin bar for administrators.
     *
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
     * @return void
     */
    public static function add_admin_bar_link(\WP_Admin_Bar $wp_admin_bar): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $wp_admin_bar->add_node([
            'id'    => 'battle-sports',
            'title' => __('Battle Sports', 'battle-sports-platform'),
            'href'  => admin_url('admin.php?page=' . self::MENU_SLUG),
            'meta'  => ['title' => __('Battle Sports Settings', 'battle-sports-platform')],
        ]);
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
     * Handles "Create Missing Pages" button.
     *
     * @return void
     */
    public static function handle_create_missing_pages(): void {
        if (!isset($_POST['bsp_create_missing_pages']) || !wp_verify_nonce(
            isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '',
            'bsp_create_missing_pages'
        )) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        \BattleSports\CustomerPortal\CoachRegistration::create_pages_on_activation();

        add_settings_error(
            'battle_sports_settings',
            'bsp_pages_created',
            __('Missing portal pages (Add Team, etc.) have been created.', 'battle-sports-platform'),
            'success'
        );
    }

    /**
     * Outputs emphasis color as CSS custom property in head.
     *
     * @return void
     */
    public static function output_emphasis_css(): void {
        $color = get_option(self::OPTION_EMPHASIS_COLOR, self::DEFAULT_EMPHASIS_COLOR);
        $color = sanitize_hex_color($color) ?: self::DEFAULT_EMPHASIS_COLOR;
        echo '<style id="bsp-emphasis-color">:root{--bsp-emphasis:' . esc_attr($color) . ';}</style>' . "\n";
    }

    /**
     * Handles Appearance tab form save.
     *
     * @return void
     */
    public static function handle_appearance_save(): void {
        if (!isset($_POST['bsp_appearance_save']) || !wp_verify_nonce(
            isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '',
            'bsp_appearance_save'
        )) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $color = isset($_POST['bsp_emphasis_color']) ? sanitize_hex_color(sanitize_text_field(wp_unslash($_POST['bsp_emphasis_color']))) : '';
        if (!$color && isset($_POST['bsp_emphasis_color_hex'])) {
            $hex = sanitize_text_field(wp_unslash($_POST['bsp_emphasis_color_hex']));
            $color = preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : sanitize_hex_color($hex);
        }
        if ($color) {
            update_option(self::OPTION_EMPHASIS_COLOR, $color);
        }

        add_settings_error(
            'battle_sports_settings',
            'bsp_appearance_saved',
            __('Appearance settings saved.', 'battle-sports-platform'),
            'success'
        );
    }

    /**
     * Handles Integrations tab form save.
     *
     * @return void
     */
    public static function handle_integrations_save(): void {
        if (!isset($_POST['bsp_integrations_save']) || !wp_verify_nonce(
            isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '',
            'bsp_integrations_save'
        )) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['bsp_make_webhook_url'])) {
            update_option('bsp_make_webhook_url', esc_url_raw(sanitize_text_field(wp_unslash($_POST['bsp_make_webhook_url']))));
        }
        if (isset($_POST['bsp_make_webhook_secret'])) {
            $secret = sanitize_text_field(wp_unslash($_POST['bsp_make_webhook_secret']));
            if ($secret !== '') {
                update_option('bsp_make_webhook_secret', $secret);
            }
        }
        if (isset($_POST['bsp_monday_api_key'])) {
            $key = sanitize_text_field(wp_unslash($_POST['bsp_monday_api_key']));
            if ($key !== '') {
                update_option('bsp_monday_api_key', $key);
            }
        }
        if (isset($_POST['bsp_monday_board_id'])) {
            update_option('bsp_monday_board_id', sanitize_text_field(wp_unslash($_POST['bsp_monday_board_id'])));
        }

        $config = get_option('bsp_monday_config', '{}');
        $decoded = is_string($config) ? json_decode($config, true) : [];
        $decoded = is_array($decoded) ? $decoded : [];
        $decoded['board_id'] = get_option('bsp_monday_board_id', '');
        update_option('bsp_monday_config', wp_json_encode($decoded));

        add_settings_error(
            'battle_sports_settings',
            'bsp_integrations_saved',
            __('Integrations settings saved.', 'battle-sports-platform'),
            'success'
        );
    }

    /**
     * AJAX handler for Monday.com "Test Connection" button.
     *
     * @return void
     */
    public static function ajax_test_monday_connection(): void {
        check_ajax_referer('bsp_test_monday', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Permission denied.', 'battle-sports-platform')]);
        }

        $api = new \BattleSports\MondayApi();
        $result = $api->test_connection();

        if ($result['success']) {
            wp_send_json_success(['board_name' => $result['board_name']]);
        }

        wp_send_json_error(['message' => $result['error'] ?? __('Connection failed.', 'battle-sports-platform')]);
    }

    /**
     * Renders the admin settings page.
     *
     * @return void
     */
    public static function render_page(): void {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Battle Sports Settings', 'battle-sports-platform'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&tab=general')); ?>"
                   class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', 'battle-sports-platform'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&tab=appearance')); ?>"
                   class="nav-tab <?php echo $tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Appearance', 'battle-sports-platform'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&tab=integrations')); ?>"
                   class="nav-tab <?php echo $tab === 'integrations' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Integrations', 'battle-sports-platform'); ?>
                </a>
            </nav>

            <?php settings_errors('battle_sports_settings'); ?>

            <?php if ($tab === 'integrations') : ?>
                <?php self::render_integrations_tab(); ?>
            <?php elseif ($tab === 'appearance') : ?>
                <?php self::render_appearance_tab(); ?>
            <?php else : ?>
                <?php self::render_general_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renders the General tab (Submission Fee Product).
     *
     * @return void
     */
    private static function render_general_tab(): void {
        if (!function_exists('wc_get_product')) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('WooCommerce is required for the submission fee product.', 'battle-sports-platform') . '</p></div>';
            return;
        }

        $product_id = (int) get_option('bsp_submission_fee_product_id', 0);
        $product    = $product_id > 0 ? wc_get_product($product_id) : null;
        $price      = $product && $product->exists() ? $product->get_price() : '';
        $exists     = $product && $product->exists();
        ?>
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
                        <tr>
                            <th scope="row"><?php esc_html_e('Portal Pages', 'battle-sports-platform'); ?></th>
                            <td>
                                <p class="description">
                                    <?php esc_html_e('If the Add Team page (or other portal pages) is missing or returns 404, click below to create them.', 'battle-sports-platform'); ?>
                                </p>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('bsp_create_missing_pages'); ?>
                                    <button type="submit" name="bsp_create_missing_pages" class="button button-secondary">
                                        <?php esc_html_e('Create Missing Pages', 'battle-sports-platform'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        <?php
    }

    /**
     * Renders the Appearance tab (emphasis color).
     *
     * @return void
     */
    private static function render_appearance_tab(): void {
        $emphasis = get_option(self::OPTION_EMPHASIS_COLOR, self::DEFAULT_EMPHASIS_COLOR);
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('bsp_appearance_save'); ?>
            <input type="hidden" name="bsp_appearance_save" value="1">

            <h2><?php esc_html_e('Brand Colors', 'battle-sports-platform'); ?></h2>
            <p class="description">
                <?php esc_html_e('Emphasis color is used for key CTAs and interactive elements across the site.', 'battle-sports-platform'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Emphasis Color', 'battle-sports-platform'); ?></th>
                    <td>
                        <input type="color" name="bsp_emphasis_color" id="bsp-emphasis-color" value="<?php echo esc_attr($emphasis); ?>" style="width:4rem;height:2rem;vertical-align:middle;cursor:pointer;">
                        <input type="text" name="bsp_emphasis_color_hex" id="bsp-emphasis-hex" value="<?php echo esc_attr($emphasis); ?>" class="regular-text" maxlength="7" placeholder="#ceff00" style="max-width:8rem;margin-left:0.5rem;">
                        <p class="description"><?php esc_html_e('Hex color for buttons, links, and key actions. Default: #ceff00', 'battle-sports-platform'); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="bsp_appearance_save" class="button button-primary"
                       value="<?php esc_attr_e('Save Appearance', 'battle-sports-platform'); ?>">
            </p>
        </form>
        <script>
        (function(){
            var color = document.getElementById('bsp-emphasis-color');
            var hex = document.getElementById('bsp-emphasis-hex');
            if (!color || !hex) return;
            color.addEventListener('input', function(){ hex.value = this.value; });
            hex.addEventListener('input', function(){
                var v = this.value;
                if (/^#[0-9A-Fa-f]{6}$/.test(v)) color.value = v;
            });
            hex.addEventListener('change', function(){
                var v = this.value;
                if (v && v.charAt(0) !== '#') this.value = '#' + v;
            });
        })();
        </script>
        <?php
    }

    /**
     * Renders the Integrations tab (Make.com, Monday.com, webhook log).
     *
     * @return void
     */
    private static function render_integrations_tab(): void {
        $webhook_url   = get_option('bsp_make_webhook_url', '');
        $webhook_secret = get_option('bsp_make_webhook_secret', '');
        $monday_key    = get_option('bsp_monday_api_key', '');
        $monday_board  = get_option('bsp_monday_board_id', '');
        $log_entries   = \BattleSports\Webhooks::get_webhook_log(20);
        $ajax_url      = admin_url('admin-ajax.php');
        $nonce         = wp_create_nonce('bsp_test_monday');
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('bsp_integrations_save'); ?>

            <h2><?php esc_html_e('Make.com', 'battle-sports-platform'); ?></h2>
            <p class="description">
                <?php esc_html_e('Outbound webhooks are sent to Make.com when artwork events occur.', 'battle-sports-platform'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Webhook URL', 'battle-sports-platform'); ?></th>
                    <td>
                        <input type="url" name="bsp_make_webhook_url" value="<?php echo esc_attr($webhook_url); ?>"
                               class="regular-text" placeholder="https://hook.eu2.make.com/...">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Webhook Secret', 'battle-sports-platform'); ?></th>
                    <td>
                        <input type="password" name="bsp_make_webhook_secret" value=""
                               class="regular-text" autocomplete="new-password" placeholder="<?php echo $webhook_secret ? '••••••••' : esc_attr__('Enter secret', 'battle-sports-platform'); ?>">
                        <p class="description"><?php echo $webhook_secret ? esc_html__('Secret is configured. Enter a new value to change.', 'battle-sports-platform') : esc_html__('Used for X-BSP-Signature.', 'battle-sports-platform'); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('Monday.com', 'battle-sports-platform'); ?></h2>
            <p class="description">
                <?php esc_html_e('Connect to Monday.com for workflow automation.', 'battle-sports-platform'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('API Key', 'battle-sports-platform'); ?></th>
                    <td>
                        <input type="password" name="bsp_monday_api_key" value=""
                               class="regular-text" autocomplete="new-password" placeholder="<?php echo $monday_key ? '••••••••' : esc_attr__('Enter API key', 'battle-sports-platform'); ?>">
                        <p class="description"><?php echo $monday_key ? esc_html__('API key is configured. Enter a new value to change.', 'battle-sports-platform') : esc_html__('Get from Monday.com → Profile → API.', 'battle-sports-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Board ID', 'battle-sports-platform'); ?></th>
                    <td>
                        <input type="text" name="bsp_monday_board_id" value="<?php echo esc_attr($monday_board); ?>"
                               class="regular-text" placeholder="1234567890">
                        <p class="description"><?php esc_html_e('Numeric ID from the board URL.', 'battle-sports-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Test Connection', 'battle-sports-platform'); ?></th>
                    <td>
                        <button type="button" id="bsp-test-monday" class="button button-secondary">
                            <?php esc_html_e('Test Connection', 'battle-sports-platform'); ?>
                        </button>
                        <span id="bsp-test-result"></span>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="bsp_integrations_save" class="button button-primary"
                       value="<?php esc_attr_e('Save Settings', 'battle-sports-platform'); ?>">
            </p>
        </form>

        <h2><?php esc_html_e('Webhook Log', 'battle-sports-platform'); ?></h2>
        <p class="description"><?php esc_html_e('Last 20 outbound webhook calls to Make.com.', 'battle-sports-platform'); ?></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'battle-sports-platform'); ?></th>
                    <th><?php esc_html_e('Event', 'battle-sports-platform'); ?></th>
                    <th><?php esc_html_e('HTTP', 'battle-sports-platform'); ?></th>
                    <th><?php esc_html_e('Error', 'battle-sports-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log_entries)) : ?>
                    <tr><td colspan="4"><?php esc_html_e('No webhooks sent yet.', 'battle-sports-platform'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($log_entries as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->created_at ?? ''); ?></td>
                            <td><?php echo esc_html($row->event ?? ''); ?></td>
                            <td><?php echo esc_html((string) ($row->http_code ?? 0)); ?></td>
                            <td><?php echo esc_html($row->error_msg ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <script>
        document.getElementById('bsp-test-monday').addEventListener('click', function() {
            var btn = this;
            var span = document.getElementById('bsp-test-result');
            btn.disabled = true;
            span.textContent = '<?php echo esc_js(__('Testing...', 'battle-sports-platform')); ?>';
            fetch('<?php echo esc_url($ajax_url); ?>?action=bsp_test_monday_connection', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'nonce=<?php echo esc_js($nonce); ?>'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    span.innerHTML = '<span style="color:green">✓ ' + (data.data.board_name || 'OK') + '</span>';
                } else {
                    span.innerHTML = '<span style="color:red">✗ ' + (data.data && data.data.message ? data.data.message : '<?php echo esc_js(__('Failed', 'battle-sports-platform')); ?>') + '</span>';
                }
            })
            .catch(function() {
                span.innerHTML = '<span style="color:red"><?php echo esc_js(__('Request failed', 'battle-sports-platform')); ?></span>';
            })
            .finally(function() { btn.disabled = false; });
        });
        </script>
        <?php
    }
}
