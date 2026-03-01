<?php
/**
 * Step 2: Team Information
 *
 * Organization, team name, age group, colors (Standard vs Custom), logo upload.
 * Standard colors come from data-standard-colors on the form (configurable per product).
 * Tackle forms: [White, Lead, Charcoal, Black, Purple]
 * Flag forms: [White, Stone, Lead, Black, Purple]
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$standard_colors = $standard_colors ?? \BattleSports\Intake\IntakeForm::get_standard_colors_for_product('7v7');
?>
<div class="bsp-intake__pane" data-step="2" aria-hidden="true">
    <h2 class="bsp-intake__pane-title"><?php esc_html_e('Team Information', 'battle-sports-platform'); ?></h2>

    <div class="bsp-intake__fields">
        <div class="bsp-intake__field">
            <label for="bsp-team-org-name" class="bsp-intake__label">
                <?php esc_html_e('Organization Name', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
            </label>
            <input type="text" id="bsp-team-org-name" name="bsp_team_org_name" class="bsp-intake__input" required data-validate="required">
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>

        <div class="bsp-intake__field">
            <label for="bsp-team-name" class="bsp-intake__label">
                <?php esc_html_e('Team Name', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
            </label>
            <input type="text" id="bsp-team-name" name="bsp_team_name" class="bsp-intake__input" required data-validate="required">
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>

        <div class="bsp-intake__field">
            <label for="bsp-team-age-group" class="bsp-intake__label">
                <?php esc_html_e('Age Group', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span>
            </label>
            <select id="bsp-team-age-group" name="bsp_team_age_group" class="bsp-intake__select" required data-validate="required">
                <option value=""><?php esc_html_e('Select…', 'battle-sports-platform'); ?></option>
                <option value="18U">18U</option>
                <option value="17-18U">17-18U</option>
                <option value="16U">16U</option>
                <option value="15U">15U</option>
                <option value="14U">14U</option>
                <option value="Other"><?php esc_html_e('Other', 'battle-sports-platform'); ?></option>
            </select>
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>

        <div class="bsp-intake__field bsp-intake__field--conditional" id="bsp-age-other-wrap" data-conditional-for="bsp_team_age_group" data-conditional-value="Other" aria-hidden="true">
            <label for="bsp-team-age-other" class="bsp-intake__label">
                <?php esc_html_e('Enter Age Group', 'battle-sports-platform'); ?>
            </label>
            <input type="text" id="bsp-team-age-other" name="bsp_team_age_other" class="bsp-intake__input" data-validate="" placeholder="<?php esc_attr_e('e.g. 12U, 10U', 'battle-sports-platform'); ?>">
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>

        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Team Colors', 'battle-sports-platform'); ?></legend>
            <div class="bsp-intake__field">
                <div class="bsp-intake__radios" role="radiogroup" aria-label="<?php esc_attr_e('Color type', 'battle-sports-platform'); ?>">
                    <label class="bsp-intake__radio-label">
                        <input type="radio" name="bsp_team_colors_type" value="standard" class="bsp-intake__radio" checked>
                        <?php esc_html_e('Standard Battle Colors', 'battle-sports-platform'); ?>
                    </label>
                    <label class="bsp-intake__radio-label">
                        <input type="radio" name="bsp_team_colors_type" value="custom" class="bsp-intake__radio">
                        <?php esc_html_e('Custom Colors', 'battle-sports-platform'); ?>
                    </label>
                </div>
            </div>

            <div class="bsp-intake__conditional" id="bsp-colors-standard-wrap" data-conditional-for="bsp_team_colors_type" data-conditional-value="standard">
                <div class="bsp-intake__field">
                    <span class="bsp-intake__label"><?php esc_html_e('Primary Color', 'battle-sports-platform'); ?></span>
                    <div class="bsp-intake__radios bsp-intake__radios--colors" role="radiogroup" aria-label="<?php esc_attr_e('Primary color', 'battle-sports-platform'); ?>">
                        <?php foreach ($standard_colors as $color) : ?>
                            <label class="bsp-intake__radio-label">
                                <input type="radio" name="bsp_team_primary_standard" value="<?php echo esc_attr($color); ?>" class="bsp-intake__radio">
                                <?php echo esc_html($color); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bsp-intake__field">
                    <span class="bsp-intake__label"><?php esc_html_e('Secondary Color', 'battle-sports-platform'); ?></span>
                    <div class="bsp-intake__radios bsp-intake__radios--colors" role="radiogroup" aria-label="<?php esc_attr_e('Secondary color', 'battle-sports-platform'); ?>">
                        <?php foreach ($standard_colors as $color) : ?>
                            <label class="bsp-intake__radio-label">
                                <input type="radio" name="bsp_team_secondary_standard" value="<?php echo esc_attr($color); ?>" class="bsp-intake__radio">
                                <?php echo esc_html($color); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="bsp-intake__conditional bsp-intake__conditional--hidden" id="bsp-colors-custom-wrap" data-conditional-for="bsp_team_colors_type" data-conditional-value="custom" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-team-primary-custom" class="bsp-intake__label">
                        <?php esc_html_e('Primary Custom Color', 'battle-sports-platform'); ?>
                    </label>
                    <input type="text" id="bsp-team-primary-custom" name="bsp_team_primary_custom" class="bsp-intake__input" placeholder="<?php esc_attr_e('e.g. Navy Blue, #001f3f', 'battle-sports-platform'); ?>">
                    <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-team-secondary-custom" class="bsp-intake__label">
                        <?php esc_html_e('Secondary Custom Color', 'battle-sports-platform'); ?>
                    </label>
                    <input type="text" id="bsp-team-secondary-custom" name="bsp_team_secondary_custom" class="bsp-intake__input" placeholder="<?php esc_attr_e('e.g. White, #ffffff', 'battle-sports-platform'); ?>">
                    <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
                </div>
            </div>
        </fieldset>

        <div class="bsp-intake__field">
            <label for="bsp-team-logo" class="bsp-intake__label">
                <?php esc_html_e('Logo Artwork Upload', 'battle-sports-platform'); ?>
            </label>
            <input type="file" id="bsp-team-logo" name="bsp_team_logo" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps" data-max-bytes="52428800" data-allowed-types="jpg,jpeg,png,pdf,ai,eps">
            <p class="bsp-intake__hint"><?php esc_html_e('Accepted formats: JPG, PNG, PDF, AI, EPS. Max 50MB.', 'battle-sports-platform'); ?></p>
            <div class="bsp-intake__file-preview" id="bsp-team-logo-preview" data-preview-for="bsp-team-logo" aria-live="polite"></div>
            <span class="bsp-intake__error" role="alert" aria-live="polite"></span>
        </div>
    </div>
</div>
