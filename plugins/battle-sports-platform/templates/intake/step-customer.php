<?php
/**
 * Step 1: Customer Information
 *
 * Matches original Gravity Forms field structure exactly.
 * All fields required except phone. Email validation. US state dropdown.
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$states = \BattleSports\Intake\IntakeForm::get_us_states();
?>
<div class="bsp-intake__pane bsp-intake__pane--active" data-step="1" aria-hidden="false">
    <h2 class="bsp-intake__pane-title"><?php esc_html_e('Customer Information', 'battle-sports-platform'); ?></h2>

    <div class="bsp-intake__fields">
        <div class="bsp-intake__row bsp-intake__row--split">
            <div class="bsp-intake__field">
                <label for="bsp-customer-first-name" class="bsp-intake__label">
                    <?php esc_html_e('First Name', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
                </label>
                <input type="text" id="bsp-customer-first-name" name="bsp_customer_first_name" class="bsp-intake__input" required autocomplete="given-name" data-validate="required">
                <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
            </div>
            <div class="bsp-intake__field">
                <label for="bsp-customer-last-name" class="bsp-intake__label">
                    <?php esc_html_e('Last Name', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
                </label>
                <input type="text" id="bsp-customer-last-name" name="bsp_customer_last_name" class="bsp-intake__input" required autocomplete="family-name" data-validate="required">
                <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
            </div>
        </div>

        <div class="bsp-intake__field">
            <label for="bsp-customer-role" class="bsp-intake__label">
                <?php esc_html_e('Customer Role', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
            </label>
            <select id="bsp-customer-role" name="bsp_customer_role" class="bsp-intake__select" required data-validate="required">
                <option value=""><?php esc_html_e('Select…', 'battle-sports-platform'); ?></option>
                <option value="Coach/Director"><?php esc_html_e('Coach/Director', 'battle-sports-platform'); ?></option>
                <option value="Parent"><?php esc_html_e('Parent', 'battle-sports-platform'); ?></option>
                <option value="Player"><?php esc_html_e('Player', 'battle-sports-platform'); ?></option>
            </select>
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>

        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Shipping Address', 'battle-sports-platform'); ?></legend>
            <div class="bsp-intake__field">
                <label for="bsp-customer-street" class="bsp-intake__label">
                    <?php esc_html_e('Street Address', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
                </label>
                <input type="text" id="bsp-customer-street" name="bsp_customer_street" class="bsp-intake__input" required autocomplete="street-address" data-validate="required">
                <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
            </div>
            <div class="bsp-intake__row bsp-intake__row--split-3">
                <div class="bsp-intake__field">
                    <label for="bsp-customer-city" class="bsp-intake__label">
                        <?php esc_html_e('City', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
                    </label>
                    <input type="text" id="bsp-customer-city" name="bsp_customer_city" class="bsp-intake__input" required autocomplete="address-level2" data-validate="required">
                    <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-customer-state" class="bsp-intake__label">
                        <?php esc_html_e('State', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
                    </label>
                    <select id="bsp-customer-state" name="bsp_customer_state" class="bsp-intake__select" required autocomplete="address-level1" data-validate="required">
                        <option value=""><?php esc_html_e('Select…', 'battle-sports-platform'); ?></option>
                        <?php foreach ($states as $abbr => $name) : ?>
                            <option value="<?php echo esc_attr($abbr); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-customer-zip" class="bsp-intake__label">
                        <?php esc_html_e('ZIP Code', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
                    </label>
                    <input type="text" id="bsp-customer-zip" name="bsp_customer_zip" class="bsp-intake__input" required autocomplete="postal-code" pattern="[0-9]{5}(-[0-9]{4})?" data-validate="required" inputmode="numeric">
                    <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
                </div>
            </div>
        </fieldset>

        <div class="bsp-intake__field">
            <label for="bsp-customer-email" class="bsp-intake__label">
                <?php esc_html_e('Email', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
            </label>
            <input type="email" id="bsp-customer-email" name="bsp_customer_email" class="bsp-intake__input" required autocomplete="email" data-validate="required,email">
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>

        <div class="bsp-intake__field">
            <label for="bsp-customer-phone" class="bsp-intake__label">
                <?php esc_html_e('Phone', 'battle-sports-platform'); ?>
            </label>
            <input type="tel" id="bsp-customer-phone" name="bsp_customer_phone" class="bsp-intake__input" autocomplete="tel" data-validate="">
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>
    </div>
</div>
