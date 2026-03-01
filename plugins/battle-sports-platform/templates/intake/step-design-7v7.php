<?php
/**
 * Step 3: Product Design — 7v7 / Battle Flag
 *
 * Jersey, Shorts, Fonts & Numbers, Additional Info.
 * Used for product slugs: 7v7, flag, battle-flag, battle-7v7.
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
                <span class="bsp-intake__label"><?php esc_html_e('Jersey Material', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_material" value="Standard Compression" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Standard Compression', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_material" value="Stretch Mesh" class="bsp-intake__radio"> <?php esc_html_e('Stretch Mesh', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Jersey Style', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_style" value="Sleeveless" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Sleeveless', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_style" value="Short Sleeve" class="bsp-intake__radio"> <?php esc_html_e('Short Sleeve', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_style" value="Long Sleeve" class="bsp-intake__radio"> <?php esc_html_e('Long Sleeve', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Jersey Collar', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_collar" value="Crew Neck" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Crew Neck', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_collar" value="V-Neck" class="bsp-intake__radio"> <?php esc_html_e('V-Neck', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_collar" value="Mock Neck" class="bsp-intake__radio"> <?php esc_html_e('Mock Neck', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Jersey Design Template', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_template" value="Standard A" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Standard A', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_template" value="Standard B" class="bsp-intake__radio"> <?php esc_html_e('Standard B', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_template" value="Standard C" class="bsp-intake__radio"> <?php esc_html_e('Standard C', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_template" value="Standard D" class="bsp-intake__radio"> <?php esc_html_e('Standard D', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_template" value="Custom Design" class="bsp-intake__radio"> <?php esc_html_e('Custom Design', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_jersey_template" data-condition-value="Custom Design" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-jersey-custom-upload" class="bsp-intake__label"><?php esc_html_e('Upload reference images', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-jersey-custom-upload" name="bsp_design_jersey_custom_upload" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps" multiple>
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-jersey-custom-upload" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-design-jersey-custom-instructions" class="bsp-intake__label"><?php esc_html_e('Custom instructions', 'battle-sports-platform'); ?></label>
                    <textarea id="bsp-design-jersey-custom-instructions" name="bsp_design_jersey_custom_instructions" class="bsp-intake__textarea" rows="3"></textarea>
                    <span class="bsp-intake__error"></span>
                </div>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Names on back', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_names" value="Yes - names on back" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Yes - names on back', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_jersey_names" value="No names on back" class="bsp-intake__radio"> <?php esc_html_e('No names on back', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>
        </fieldset>

        <fieldset class="bsp-intake__fieldset">
            <legend class="bsp-intake__legend"><?php esc_html_e('Shorts Design', 'battle-sports-platform'); ?></legend>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Short Base Color', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <?php foreach ($colors as $c) : ?>
                        <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_short_base_color" value="<?php echo esc_attr($c); ?>" class="bsp-intake__radio" data-validate="required"> <?php echo esc_html($c); ?></label>
                    <?php endforeach; ?>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Shorts Style', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shorts_style" value="7v7 Short" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('7v7 Short', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shorts_style" value="5 Inch Inseam" class="bsp-intake__radio"> <?php esc_html_e('5 Inch Inseam', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shorts_style" value="9 Inch Inseam" class="bsp-intake__radio"> <?php esc_html_e('9 Inch Inseam', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__field">
                <span class="bsp-intake__label"><?php esc_html_e('Shorts Design Template', 'battle-sports-platform'); ?> <span class="bsp-intake__required">*</span></span>
                <div class="bsp-intake__radios" role="radiogroup">
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shorts_template" value="Standard A" class="bsp-intake__radio" data-validate="required"> <?php esc_html_e('Standard A', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shorts_template" value="Standard B" class="bsp-intake__radio"> <?php esc_html_e('Standard B', 'battle-sports-platform'); ?></label>
                    <label class="bsp-intake__radio-label"><input type="radio" name="bsp_design_shorts_template" value="Custom Design" class="bsp-intake__radio"> <?php esc_html_e('Custom Design', 'battle-sports-platform'); ?></label>
                </div>
                <span class="bsp-intake__error"></span>
            </div>

            <div class="bsp-intake__conditional" data-condition-field="bsp_design_shorts_template" data-condition-value="Custom Design" aria-hidden="true">
                <div class="bsp-intake__field">
                    <label for="bsp-design-shorts-custom-upload" class="bsp-intake__label"><?php esc_html_e('Upload reference images', 'battle-sports-platform'); ?></label>
                    <input type="file" id="bsp-design-shorts-custom-upload" name="bsp_design_shorts_custom_upload" class="bsp-intake__file" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps" multiple>
                    <div class="bsp-intake__file-preview" data-preview-for="bsp-design-shorts-custom-upload" aria-live="polite"></div>
                    <span class="bsp-intake__error"></span>
                </div>
                <div class="bsp-intake__field">
                    <label for="bsp-design-shorts-custom-instructions" class="bsp-intake__label"><?php esc_html_e('Custom instructions', 'battle-sports-platform'); ?></label>
                    <textarea id="bsp-design-shorts-custom-instructions" name="bsp_design_shorts_custom_instructions" class="bsp-intake__textarea" rows="3"></textarea>
                    <span class="bsp-intake__error"></span>
                </div>
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

            <div class="bsp-intake__conditional bsp-intake__conditional--number-style" data-condition-field="bsp_design_font" data-condition-any="true" aria-hidden="true">
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
