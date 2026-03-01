<?php

declare(strict_types=1);

namespace BattleSports\Intake;

defined('ABSPATH') || exit;

/**
 * Shared multi-step intake form for Battle Sports uniform orders.
 *
 * Replaces Gravity Forms for new intake flows. Renders a 4-step wizard:
 * Step 1: Customer Info | Step 2: Team Info | Step 3: Product Design | Step 4: Roster
 *
 * Shortcode: [bsp_intake_form product="7v7"] — product slug determines color options
 * and product-specific behavior.
 */
final class IntakeForm {

    private const SHORTCODE = 'bsp_intake_form';
    private const NONCE_ACTION = 'bsp_intake_submit';
    private const MAX_UPLOAD_BYTES = 52428800; // 50MB

    /**
     * Default Standard Battle Colors for flag products (7v7, Battle Flag, Women's Flag).
     */
    private const FLAG_COLORS = ['White', 'Stone', 'Lead', 'Black', 'Purple'];

    /**
     * Tackle product colors (Charlie, Alpha, Bravo).
     */
    private const TACKLE_COLORS = ['White', 'Lead', 'Charcoal', 'Black', 'Purple'];

    /**
     * US states for shipping address.
     *
     * @return array<string, string> State abbreviation => full name
     */
    public static function get_us_states(): array {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'DC' => 'District of Columbia',
        ];
    }

    /**
     * Gets the Step 3 design template filename for the given product.
     *
     * @param string $product Product slug (e.g. 7v7, charlie-tackle).
     * @return string Template filename (e.g. step-design-7v7.php).
     */
    public static function get_design_template_for_product(string $product): string {
        $product_lower = strtolower($product);
        $mapping = [
            'womens-flag'     => 'step-design-womens-flag.php',
            'battle-womens'   => 'step-design-womens-flag.php',
            'charlie-tackle'  => 'step-design-charlie-tackle.php',
            'alpha-tackle'    => 'step-design-alpha-tackle.php',
            'bravo-tackle'    => 'step-design-alpha-tackle.php',
        ];
        foreach ($mapping as $slug => $template) {
            if (str_contains($product_lower, $slug) || $product_lower === str_replace('-', '', $slug)) {
                return $template;
            }
        }
        return 'step-design-7v7.php';
    }

    /**
     * Gets Standard Battle Colors for the given product type.
     *
     * @param string $product Product slug (e.g. 7v7, charlie-tackle).
     * @return array<string>
     */
    public static function get_standard_colors_for_product(string $product): array {
        $tackle_products = ['charlie-tackle', 'alpha-tackle', 'bravo-tackle'];
        $product_lower = strtolower($product);

        foreach ($tackle_products as $tp) {
            if (str_contains($product_lower, $tp) || $product_lower === str_replace('-', '', $tp)) {
                return self::TACKLE_COLORS;
            }
        }

        return self::FLAG_COLORS;
    }

    /**
     * Initializes the intake form and registers the shortcode.
     *
     * @return void
     */
    public static function init(): void {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
    }

    /**
     * Renders the intake form shortcode output.
     *
     * @param array<string, string> $atts Shortcode attributes. Use product="7v7", etc.
     * @return string
     */
    public static function render(array $atts = []): string {
        $atts = shortcode_atts(
            [
                'product' => '7v7',
            ],
            $atts,
            self::SHORTCODE
        );

        $product = sanitize_key($atts['product']);
        $standard_colors = self::get_standard_colors_for_product($product);

        self::enqueue_assets($product, $standard_colors);

        ob_start();
        self::render_form_shell($product, $standard_colors);
        return ob_get_clean();
    }

    /**
     * Renders the form HTML shell with data attributes for JS.
     *
     * @param string         $product        Product slug.
     * @param array<string>  $standard_colors Standard color options for this product.
     * @return void
     */
    private static function render_form_shell(string $product, array $standard_colors): void {
        $nonce = wp_nonce_field(self::NONCE_ACTION, 'bsp_intake_nonce', true, false);
        $color_json = wp_json_encode($standard_colors);
        $color_attr = is_string($color_json) ? $color_json : '[]';
        ?>
        <div class="bsp-intake" id="bsp-intake-form" data-product="<?php echo esc_attr($product); ?>" data-standard-colors="<?php echo esc_attr($color_attr); ?>">
            <?php echo $nonce; ?>
            <input type="hidden" name="bsp_intake_product" value="<?php echo esc_attr($product); ?>">
            <input type="hidden" name="bsp_intake_state" id="bsp-intake-state" value="">
            <input type="hidden" name="bsp_intake_step" id="bsp-intake-step" value="1">

            <div class="bsp-intake__progress" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="4" aria-label="Form progress">
                <ol class="bsp-intake__steps">
                    <li class="bsp-intake__step-item bsp-intake__step-item--active" data-step="1">
                        <span class="bsp-intake__step-num">1</span>
                        <span class="bsp-intake__step-label">Customer Info</span>
                    </li>
                    <li class="bsp-intake__step-item" data-step="2">
                        <span class="bsp-intake__step-num">2</span>
                        <span class="bsp-intake__step-label">Team Info</span>
                    </li>
                    <li class="bsp-intake__step-item" data-step="3">
                        <span class="bsp-intake__step-num">3</span>
                        <span class="bsp-intake__step-label">Product Design</span>
                    </li>
                    <li class="bsp-intake__step-item" data-step="4">
                        <span class="bsp-intake__step-num">4</span>
                        <span class="bsp-intake__step-label">Roster</span>
                    </li>
                </ol>
            </div>

            <form class="bsp-intake__form" id="bsp-intake-form-element" method="post" enctype="multipart/form-data" novalidate>
                <div class="bsp-intake__panes">
                    <?php
                    $template_dir = BSP_PLUGIN_DIR . 'templates/intake/';
                    include $template_dir . 'step-customer.php';
                    include $template_dir . 'step-team.php';
                    $design_template = self::get_design_template_for_product($product);
                    include $template_dir . $design_template;
                    ?>
                    <div class="bsp-intake__pane" data-step="4" aria-hidden="true">
                        <h2 class="bsp-intake__pane-title">Roster</h2>
                        <p class="bsp-intake__pane-placeholder">Step 4 — Coming soon.</p>
                    </div>
                </div>

                <div class="bsp-intake__actions">
                    <button type="button" class="bsp-intake__btn bsp-intake__btn--prev" id="bsp-intake-prev" aria-hidden="true"><?php esc_html_e('Back', 'battle-sports-platform'); ?></button>
                    <button type="button" class="bsp-intake__btn bsp-intake__btn--next" id="bsp-intake-next"><?php esc_html_e('Next', 'battle-sports-platform'); ?></button>
                    <button type="submit" class="bsp-intake__btn bsp-intake__btn--submit" id="bsp-intake-submit" aria-hidden="true"><?php esc_html_e('Submit Order', 'battle-sports-platform'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Enqueues intake form script and styles.
     *
     * @param string        $product        Product slug.
     * @param array<string> $standard_colors Standard color options.
     * @return void
     */
    private static function enqueue_assets(string $product, array $standard_colors): void {
        $handle = 'bsp-intake-form';
        wp_enqueue_style(
            $handle . '-style',
            BSP_PLUGIN_URL . 'assets/src/css/intake.css',
            [],
            BSP_VERSION
        );
        wp_enqueue_script(
            $handle,
            BSP_PLUGIN_URL . 'assets/src/js/intake.js',
            [],
            BSP_VERSION,
            true
        );
        wp_localize_script(
            $handle,
            'bspIntake',
            [
                'nonce'           => wp_create_nonce('wp_rest'),
                'apiUrl'          => esc_url_raw(rest_url('battle-sports/v1')),
                'product'         => $product,
                'standardColors'  => $standard_colors,
                'maxUploadBytes'  => self::MAX_UPLOAD_BYTES,
                'allowedTypes'   => ['jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps'],
            ]
        );
    }

    /**
     * Validates server-side submission (called by IntakeHandler on form submit).
     *
     * @param array<string, mixed> $data Sanitized POST data.
     * @return array<int, string> List of error messages.
     */
    public static function validate_submission(array $data): array {
        $errors = [];

        if (empty($data['customer']['first_name'])) {
            $errors[] = __('First name is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['last_name'])) {
            $errors[] = __('Last name is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['role'])) {
            $errors[] = __('Customer role is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['street'])) {
            $errors[] = __('Street address is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['city'])) {
            $errors[] = __('City is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['state'])) {
            $errors[] = __('State is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['zip'])) {
            $errors[] = __('ZIP code is required.', 'battle-sports-platform');
        }
        if (empty($data['customer']['email'])) {
            $errors[] = __('Email is required.', 'battle-sports-platform');
        } elseif (!is_email($data['customer']['email'])) {
            $errors[] = __('Please enter a valid email address.', 'battle-sports-platform');
        }

        if (empty($data['team']['org_name'])) {
            $errors[] = __('Organization name is required.', 'battle-sports-platform');
        }
        if (empty($data['team']['team_name'])) {
            $errors[] = __('Team name is required.', 'battle-sports-platform');
        }
        if (empty($data['team']['age_group'])) {
            $errors[] = __('Age group is required.', 'battle-sports-platform');
        }

        return $errors;
    }
}
