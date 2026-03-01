<?php
/**
 * Step 3: Product Design — Charlie Tackle
 *
 * Jersey (front, logo, shoulder, nameplate), Pants, Fonts & Numbers.
 *
 * @package BattleSports
 */

defined('ABSPATH') || exit;

$colors = $standard_colors ?? ['White', 'Stone', 'Lead', 'Black', 'Purple'];
?>
<div class="bsp-intake__pane" data-step="3" aria-hidden="true">
    <h2 class="bsp-intake__pane-title"><?php esc_html_e('Product Design', 'battle-sports-platform'); ?></h2>

    <div class="bsp-intake__fields">
        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Jersey Design', 'battle-sports-platform'); ?></legend>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Jersey Base Color', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <?php foreach ($colors as $c) : ?>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_base_color" value="<?php echo esc_attr($c); ?>" class="bsp-intake__radio" data-validate="required"> <?php echo esc_html($c); ?></label>
                    <?php endforeach; ?>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Front Design Template', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_front_template" value="Team Logo" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Team Logo', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_front_template" value="Team Text" class="bsp-intake__radio"> <?php esc_html_e('Team Text', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_front_template" value="Team Logo and Team Text" class="bsp-intake__radio"> <?php esc_html_e('Team Logo and Team Text', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_front_template" value="Custom Design" class="bsp-intake__radio"> <?php esc_html_e('Custom Design', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_front_template" data-condition-values="Team Text|Team Logo and Team Text" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-front-text" class="bsp-intake__label"><?php esc_html_e('Text for Front', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></label>
                    <input type="text" id="bsp-design-front-text" name="bsp_design_front_text" class="bsp-intake__input" data-validate="required">
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Jersey Logo', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_logo" value="Use Team Logo" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Use Team Logo', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_logo" value="Upload Special Logo" class="bsp-intake__radio"> <?php esc_html_e('Upload Special Logo', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_logo" value="Custom Logo" class="bsp-intake__radio"> <?php esc_html_e('Custom Logo', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_jersey_logo" data-condition-value="Upload Special Logo" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-jersey-special-logo" class="bsp-intake__label"><?php esc_html_e('Upload special logo', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-jersey-special-logo" name="bsp_design_jersey_special_logo" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-jersey-special-logo" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_jersey_logo" data-condition-value="Custom Logo" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-jersey-custom-logo-upload" class="bsp-intake__label"><?php esc_html_e('Upload custom logo', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-jersey-custom-logo-upload" name="bsp_design_jersey_custom_logo_upload" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-jersey-custom-logo-upload" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-design-jersey-custom-logo-instructions" class="bsp-intake__label"><?php esc_html_e('Custom logo instructions', 'battle-sports-platform'); ?></label>
                    <textarea id="bsp-design-jersey-custom-logo-instructions" name="bsp_design_jersey_custom_logo_instructions" class="bsp-intake__textarea" rows="3"></textarea>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_front_template" data-condition-value="Custom Design" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-front-custom-upload" class="bsp-intake__label"><?php esc_html_e('Upload reference images', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-front-custom-upload" name="bsp_design_front_custom_upload" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps" multiple>
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-front-custom-upload" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-design-front-custom-instructions" class="bsp-intake__label"><?php esc_html_e('Custom instructions', 'battle-sports-platform'); ?></label>
                    <textarea id="bsp-design-front-custom-instructions" name="bsp_design_front_custom_instructions" class="bsp-intake__textarea" rows="3"></textarea>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Shoulder Design', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder" value="Number" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Number', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder" value="Stripe" class="bsp-intake__radio"> <?php esc_html_e('Stripe', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder" value="Number and Stripe" class="bsp-intake__radio"> <?php esc_html_e('Number and Stripe', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder" value="Custom Design" class="bsp-intake__radio"> <?php esc_html_e('Custom Design', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_shoulder" data-condition-values="Stripe|Number and Stripe" aria-hidden="true">
                <div class="bsp-intake__field">
                    <span class="bsp-intake__label"><?php esc_html_e('Shoulder Stripe', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                    <div class="bsp-intake__radios" role="radiogroup">
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder_stripe" value="Fast Stripe" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Fast Stripe', 'battle-sports-platform'); ?></label>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder_stripe" value="Speed 1" class="bsp-intake__radio"> <?php esc_html_e('Speed 1', 'battle-sports-platform'); ?></label>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder_stripe" value="Speed 2" class="bsp-intake__radio"> <?php esc_html_e('Speed 2', 'battle-sports-platform'); ?></label>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder_stripe" value="One Stripe" class="bsp-intake__radio"> <?php esc_html_e('One Stripe', 'battle-sports-platform'); ?></label>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shoulder_stripe" value="Two Stripe" class="bsp-intake__radio"> <?php esc_html_e('Two Stripe', 'battle-sports-platform'); ?></label>
                    </div>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Nameplate', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_nameplate" value="No Nameplate" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('No Nameplate', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_nameplate" value="Last Names" class="bsp-intake__radio"> <?php esc_html_e('Last Names', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_nameplate" value="One Phrase for All" class="bsp-intake__radio"> <?php esc_html_e('One Phrase for All', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_nameplate" data-condition-value="One Phrase for All" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-nameplate-phrase" class="bsp-intake__label"><?php esc_html_e('Phrase', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></label>
                    <input type="text" id="bsp-design-nameplate-phrase" name="bsp_design_nameplate_phrase" class="bsp-intake__input" data-validate="required">
                    <span class="bsp-intake__error"></span>
                </div>
            </div>
        </fieldset>

        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Pants Design', 'battle-sports-platform'); ?></legend>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Pants Base Color', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <?php foreach ($colors as $c) : ?>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pants_base_color" value="<?php echo esc_attr($c); ?>" class="bsp-intake__radio" data-validate="required"> <?php echo esc_html($c); ?></label>
                    <?php endforeach; ?>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Pants Config', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pants_config" value="Logo Only" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Logo Only', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pants_config" value="Stripe Only" class="bsp-intake__radio"> <?php esc_html_e('Stripe Only', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pants_config" value="Logo and Stripe" class="bsp-intake__radio"> <?php esc_html_e('Logo and Stripe', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pants_config" value="Custom Design" class="bsp-intake__radio"> <?php esc_html_e('Custom Design', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Pant Logo', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_logo" value="Use Team Logo" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Use Team Logo', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_logo" value="Upload Special Logo" class="bsp-intake__radio"> <?php esc_html_e('Upload Special Logo', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_logo" value="Custom Logo" class="bsp-intake__radio"> <?php esc_html_e('Custom Logo', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_pant_logo" data-condition-value="Upload Special Logo" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-pant-special-logo" class="bsp-intake__label"><?php esc_html_e('Upload special logo', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-pant-special-logo" name="bsp_design_pant_special_logo" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-pant-special-logo" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_pant_logo" data-condition-value="Custom Logo" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-pant-custom-logo-upload" class="bsp-intake__label"><?php esc_html_e('Upload custom logo', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-pant-custom-logo-upload" name="bsp_design_pant_custom_logo_upload" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-pant-custom-logo-upload" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-design-pant-custom-logo-instructions" class="bsp-intake__label"><?php esc_html_e('Custom logo instructions', 'battle-sports-platform'); ?></label>
                    <textarea id="bsp-design-pant-custom-logo-instructions" name="bsp_design_pant_custom_logo_instructions" class="bsp-intake__textarea" rows="3"></textarea>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Pant Stripe', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_stripe" value="Fast Stripe" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Fast Stripe', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_stripe" value="Speed 1" class="bsp-intake__radio"> <?php esc_html_e('Speed 1', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_stripe" value="Speed 2" class="bsp-intake__radio"> <?php esc_html_e('Speed 2', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_stripe" value="One Stripe" class="bsp-intake__radio"> <?php esc_html_e('One Stripe', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_pant_stripe" value="Two Stripe" class="bsp-intake__radio"> <?php esc_html_e('Two Stripe', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>
        </fieldset>

        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Fonts & Numbers', 'battle-sports-platform'); ?></legend>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Uniform Font', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="Bebas" class="bsp-intake__radio" data-validate="required"> Bebas</label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="Block" class="bsp-intake__radio"> Block</label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="Gravity" class="bsp-intake__radio"> Gravity</label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="Grid" class="bsp-intake__radio"> Grid</label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="League" class="bsp-intake__radio"> League</label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="Onramp" class="bsp-intake__radio"> Onramp</label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_font" value="Superstar" class="bsp-intake__radio"> Superstar</label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_font" data-condition-any="true" aria-hidden="true">
                <div class="bsp-intake__field">
                    <span class="bsp-intake__label"><?php esc_html_e('Number Style', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                    <div class="bsp-intake__radios" role="radiogroup">
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_number_style" value="Solid" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Solid', 'battle-sports-platform'); ?></label>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_number_style" value="Solid with Outline" class="bsp-intake__radio"> <?php esc_html_e('Solid with Outline', 'battle-sports-platform'); ?></label>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_number_style" value="No Numbers" class="bsp-intake__radio"> <?php esc_html_e('No Numbers', 'battle-sports-platform'); ?></label>
                    </div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <span class="bsp-intake__label"><?php esc_html_e('Number Fill Color', 'battle-sports-platform'); ?></span>
                    <div class="bsp-intake__radios" role="radiogroup">
                        <?php foreach ($colors as $c) : ?>
                            <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_number_fill_color" value="<?php echo esc_attr($c); ?>" class="bsp-intake__radio"> <?php echo esc_html($c); ?></label>
                        <?php endforeach; ?>
                    </div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <span class="bsp-intake__label"><?php esc_html_e('Number Outline Color', 'battle-sports-platform'); ?></span>
                    <div class="bsp-intake__radios" role="radiogroup">
                        <?php foreach ($colors as $c) : ?>
                            <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_number_outline_color" value="<?php echo esc_attr($c); ?>" class="bsp-intake__radio"> <?php echo esc_html($c); ?></label>
                        <?php endforeach; ?>
                    </div>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>
        </fieldset>

        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Additional Information', 'battle-sports-platform'); ?></legend>
            <div class="bsp-intake__field">
                <label for="bsp-design-inspiration" class="bsp-intake__label"><?php esc_html_e('Design Inspiration/References', 'battle-sports-platform'); ?></label>
                <input type="file" id="bsp-design-inspiration" name="bsp_design_inspiration" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps" multiple>
                <div class="bsp-intake__file-preview" data-preview-for="bsp-design-inspiration" aria-live="polite"></div>
                <span class="bsp-intake__error"></span>
            </div>
            <div class="bsp-intake__field">
                <label for="bsp-design-notes" class="bsp-intake__label"><?php esc_html_e('Additional Design Notes', 'battle-sports-platform'); ?></label>
                <textarea id="bsp-design-notes" name="bsp_design_notes" class="bsp-intake__textarea" rows="3"></textarea>
                <span class="bsp-intake__error"></span>
            </div>
        </fieldset>
    </div>
</div>
