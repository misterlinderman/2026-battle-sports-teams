/**
 * Battle Sports Platform — Multi-step intake form.
 *
 * Manages step transitions, validation, conditional fields, and file upload preview.
 * State preserved in JS object, serialized to hidden field on final submit.
 *
 * Requires bspIntake from wp_localize_script.
 */
(function () {
    'use strict';

    const formEl = document.getElementById('bsp-intake-form');
    if (!formEl || !formEl.closest('.bsp-intake')) return;

    const form = document.getElementById('bsp-intake-form-element');
    const stateInput = document.getElementById('bsp-intake-state');
    const stepInput = document.getElementById('bsp-intake-step');
    const prevBtn = document.getElementById('bsp-intake-prev');
    const nextBtn = document.getElementById('bsp-intake-next');
    const submitBtn = document.getElementById('bsp-intake-submit');
    const config = window.bspIntake || {};
    const maxUploadBytes = config.maxUploadBytes || 52428800;
    const allowedTypes = config.allowedTypes || ['jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps'];

    /** @type { { product: string, customer: Object, team: Object, design: Object, roster: Object } } */
    const formState = {
        product: formEl.dataset.product || config.product || '7v7',
        customer: {},
        team: {},
        design: {},
        roster: {},
    };

    let currentStep = 1;
    const totalSteps = 4;

    // ----- Step management -----

    function goToStep(n) {
        if (n < 1 || n > totalSteps) return;
        currentStep = n;
        stepInput.value = String(n);

        // Update panes
        form.querySelectorAll('.bsp-intake__pane').forEach((pane) => {
            const step = parseInt(pane.dataset.step, 10);
            const active = step === n;
            pane.classList.toggle('bsp-intake__pane--active', active);
            pane.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        // Update progress indicator
        formEl.querySelectorAll('.bsp-intake__step-item').forEach((item) => {
            const step = parseInt(item.dataset.step, 10);
            item.classList.toggle('bsp-intake__step-item--active', step === n);
            item.classList.toggle('bsp-intake__step-item--complete', step < n);
        });
        const progressBar = formEl.querySelector('.bsp-intake__progress');
        if (progressBar) progressBar.setAttribute('aria-valuenow', String(n));

        // Update buttons
        if (prevBtn) prevBtn.setAttribute('aria-hidden', n <= 1 ? 'true' : 'false');
        if (nextBtn) nextBtn.setAttribute('aria-hidden', n >= totalSteps ? 'true' : 'false');
        if (submitBtn) submitBtn.setAttribute('aria-hidden', n < totalSteps ? 'true' : 'false');
        if (n >= totalSteps && submitBtn) submitBtn.focus();
    }

    function nextStep() {
        if (!validateStep(currentStep)) return;
        collectStepState(currentStep);
        if (currentStep < totalSteps) goToStep(currentStep + 1);
    }

    function prevStep() {
        if (currentStep > 1) goToStep(currentStep - 1);
    }

    // ----- State collection -----

    function collectStepState(step) {
        const pane = form.querySelector(`.bsp-intake__pane[data-step="${step}"]`);
        if (!pane) return;

        if (step === 1) {
            formState.customer = {
                first_name: getVal('bsp-customer-first-name'),
                last_name: getVal('bsp-customer-last-name'),
                role: getVal('bsp-customer-role'),
                street: getVal('bsp-customer-street'),
                city: getVal('bsp-customer-city'),
                state: getVal('bsp-customer-state'),
                zip: getVal('bsp-customer-zip'),
                email: getVal('bsp-customer-email'),
                phone: getVal('bsp-customer-phone') || '',
            };
        } else if (step === 2) {
            const colorsType = form.querySelector('input[name="bsp_team_colors_type"]:checked')?.value || 'standard';
            formState.team = {
                org_name: getVal('bsp-team-org-name'),
                team_name: getVal('bsp-team-name'),
                age_group: getVal('bsp-team-age-group'),
                age_other: getVal('bsp-team-age-other') || '',
                colors_type: colorsType,
                primary_standard: form.querySelector('input[name="bsp_team_primary_standard"]:checked')?.value || '',
                secondary_standard: form.querySelector('input[name="bsp_team_secondary_standard"]:checked')?.value || '',
                primary_custom: getVal('bsp-team-primary-custom') || '',
                secondary_custom: getVal('bsp-team-secondary-custom') || '',
                logo_file: null, // Actual file sent with form submit
            };
        } else if (step === 3) {
            formState.design = {};
            pane.querySelectorAll('input, select, textarea').forEach((el) => {
                if (!el.name || !el.name.startsWith('bsp_design_')) return;
                if (el.type === 'radio') {
                    if (el.checked) formState.design[el.name] = el.value;
                } else if (el.type === 'checkbox') {
                    formState.design[el.name] = el.checked;
                } else if (el.type === 'file') {
                    formState.design[el.name] = el.files?.length ? Array.from(el.files).map((f) => f.name).join(', ') : '';
                } else {
                    formState.design[el.name] = (el.value || '').trim();
                }
            });
        }
    }

    function getVal(id) {
        const el = document.getElementById(id);
        return el ? (el.value || '').trim() : '';
    }

    function serializeState() {
        if (stateInput) stateInput.value = JSON.stringify(formState);
    }

    // ----- Validation -----

    /**
     * @param {number} step
     * @returns {boolean}
     */
    function validateStep(step) {
        clearStepErrors(step);
        let valid = true;
        const pane = form.querySelector(`.bsp-intake__pane[data-step="${step}"]`);
        if (!pane) return true;

        pane.querySelectorAll('[data-validate]').forEach((field) => {
            const conditional = field.closest('[data-condition-field], [data-conditional-for]');
            if (conditional && (conditional.getAttribute('aria-hidden') === 'true' || conditional.classList.contains('bsp-intake__conditional--hidden'))) {
                return;
            }
            const rules = (field.dataset.validate || '').split(',').map((r) => r.trim()).filter(Boolean);
            if (rules.length === 0) return;

            for (const rule of rules) {
                const msg = validateField(field, rule);
                if (msg) {
                    showFieldError(field, msg);
                    valid = false;
                    break;
                }
            }
        });

        // Step 2: if age group is Other, require age_other
        if (step === 2 && getVal('bsp-team-age-group') === 'Other') {
            const otherEl = document.getElementById('bsp-team-age-other');
            if (otherEl && !otherEl.value.trim()) {
                showFieldError(otherEl, 'Please enter the age group.');
                valid = false;
            }
        }

        // Step 2: if custom colors, require primary and secondary custom
        if (step === 2) {
            const colorsType = form.querySelector('input[name="bsp_team_colors_type"]:checked')?.value;
            if (colorsType === 'custom') {
                const primary = document.getElementById('bsp-team-primary-custom');
                const secondary = document.getElementById('bsp-team-secondary-custom');
                if (primary && !primary.value.trim()) {
                    showFieldError(primary, 'Primary custom color is required.');
                    valid = false;
                }
                if (secondary && !secondary.value.trim()) {
                    showFieldError(secondary, 'Secondary custom color is required.');
                    valid = false;
                }
            }
        }

        // Step 3: conditional required fields
        if (step === 3) {
            const frontTemplate = getFieldValue('bsp_design_front_template');
            if (frontTemplate === 'Team Text' || frontTemplate === 'Team Logo and Team Text') {
                const frontText = document.getElementById('bsp-design-front-text');
                if (frontText && !frontText.value.trim()) {
                    showFieldError(frontText, 'Text for front is required.');
                    valid = false;
                }
            }
            const nameplate = getFieldValue('bsp_design_nameplate');
            if (nameplate === 'One Phrase for All') {
                const phrase = document.getElementById('bsp-design-nameplate-phrase');
                if (phrase && !phrase.value.trim()) {
                    showFieldError(phrase, 'Phrase is required.');
                    valid = false;
                }
            }
        }

        if (!valid) {
            const firstError = pane.querySelector('.bsp-intake__field--error');
            firstError?.querySelector('input, select, textarea')?.focus();
        }

        return valid;
    }

    /**
     * @param {HTMLElement} field
     * @param {string} rule
     * @returns {string|null} Error message or null if valid
     */
    function validateField(field, rule) {
        const val = (field.value || '').trim();
        const tag = field.tagName.toLowerCase();
        const isChecked = tag === 'input' && field.type === 'radio' ? form.querySelector(`input[name="${field.name}"]:checked`) : field.checked;

        switch (rule) {
            case 'required':
                if (tag === 'input' && field.type === 'radio') {
                    const group = form.querySelectorAll(`input[name="${field.name}"]`);
                    const anyChecked = Array.from(group).some((r) => r.checked);
                    return anyChecked ? null : 'This field is required.';
                }
                return val ? null : 'This field is required.';
            case 'email':
                if (!val) return null;
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) ? null : 'Please enter a valid email address.';
            default:
                return null;
        }
    }

    /**
     * @param {HTMLElement} field
     * @param {string} message
     */
    function showFieldError(field, message) {
        const wrap = field.closest('.bsp-intake__field');
        if (wrap) wrap.classList.add('bsp-intake__field--error');
        const errEl = wrap?.querySelector('.bsp-intake__error');
        if (errEl) errEl.textContent = message;
    }

    function clearStepErrors(step) {
        const pane = form.querySelector(`.bsp-intake__pane[data-step="${step}"]`);
        if (!pane) return;
        pane.querySelectorAll('.bsp-intake__field--error').forEach((f) => f.classList.remove('bsp-intake__field--error'));
        pane.querySelectorAll('.bsp-intake__error').forEach((e) => (e.textContent = ''));
    }

    // ----- Conditional fields -----
    // Supports: data-condition-field + data-condition-value (single)
    //           data-condition-field + data-condition-values (pipe-separated OR)
    //           data-condition-field + data-condition-any (show when field has any value)
    // Legacy:   data-conditional-for + data-conditional-value

    function getFieldValue(fieldName) {
        const fields = form.querySelectorAll(`[name="${fieldName}"]`);
        for (const f of fields) {
            if (f.type === 'radio' && f.checked) return f.value;
            if (f.type !== 'radio') return f.value;
        }
        return '';
    }

    function setupConditionalLogic() {
        const blocks = form.querySelectorAll(
            '[data-condition-field], [data-conditional-for][data-conditional-value]'
        );

        blocks.forEach((block) => {
            const fieldName = block.dataset.conditionField || block.dataset.conditionalFor;
            const showValue = block.dataset.conditionValue || block.dataset.conditionalValue;
            const showValues = block.dataset.conditionValues;
            const showAny = block.dataset.conditionAny === 'true';
            const fields = form.querySelectorAll(`[name="${fieldName}"]`);

            if (!fields.length) return;

            function update() {
                const current = getFieldValue(fieldName);
                let show = false;

                if (showAny) {
                    show = current !== '';
                } else if (showValues) {
                    const allowed = showValues.split('|').map((v) => v.trim());
                    show = allowed.includes(current);
                } else if (showValue !== undefined) {
                    show = current === showValue;
                }

                block.classList.toggle('bsp-intake__conditional--hidden', !show);
                block.setAttribute('aria-hidden', show ? 'false' : 'true');
            }

            fields.forEach((f) => {
                f.addEventListener('change', update);
                f.addEventListener('input', update);
            });
            update();
        });
    }

    // ----- File upload preview -----
    // Handles all inputs with a sibling [data-preview-for="inputId"]

    function formatFileSize(bytes) {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    }

    function setupFilePreview() {
        form.querySelectorAll('[data-preview-for]').forEach((previewEl) => {
            const inputId = previewEl.dataset.previewFor;
            const input = document.getElementById(inputId) || form.querySelector(`[name="${inputId}"]`);
            if (!input || input.type !== 'file') return;

            input.addEventListener('change', () => {
                const files = input.files;
                if (!files?.length) {
                    previewEl.textContent = '';
                    previewEl.classList.remove('bsp-intake__file-preview--error');
                    return;
                }

                const fileList = Array.from(files);
                const invalid = fileList.some((f) => {
                    const ext = f.name.split('.').pop()?.toLowerCase() || '';
                    const allowed = allowedTypes.map((t) => t.toLowerCase());
                    return !allowed.includes(ext) || f.size > maxUploadBytes;
                });

                if (invalid) {
                    const ext = fileList[0]?.name.split('.').pop()?.toLowerCase() || '';
                    const allowed = allowedTypes.map((t) => t.toLowerCase());
                    if (!allowed.includes(ext)) {
                        previewEl.textContent = `Invalid format. Allowed: ${allowed.join(', ')}`;
                    } else {
                        previewEl.textContent = `File too large. Max ${Math.round(maxUploadBytes / 1024 / 1024)}MB.`;
                    }
                    previewEl.classList.add('bsp-intake__file-preview--error');
                    return;
                }

                const parts = fileList.map((f) => `${f.name} (${formatFileSize(f.size)})`);
                previewEl.textContent = parts.join(', ');
                previewEl.classList.remove('bsp-intake__file-preview--error');
            });
        });
    }

    // ----- Form submit -----

    function handleSubmit(e) {
        e.preventDefault();
        if (!validateStep(currentStep)) return;
        for (let s = 1; s <= currentStep; s++) collectStepState(s);
        serializeState();
        form.submit();
    }

    // ----- Init -----

    function init() {
        goToStep(1);
        setupConditionalLogic();
        setupFilePreview();

        if (prevBtn) prevBtn.addEventListener('click', prevStep);
        if (nextBtn) nextBtn.addEventListener('click', nextStep);
        if (form) form.addEventListener('submit', handleSubmit);
    }

    init();
})();
