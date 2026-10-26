/**
 * Validate form toàn site: tắt tooltip browser, hiện lỗi đỏ tiếng Việt dưới field.
 * Form: class "js-form-validate" hoặc "js-auth-validate" + novalidate
 */
(() => {
    function messageFor(input) {
        if (input.validity.valueMissing) {
            return input.getAttribute('data-required-msg') || 'Vui lòng điền thông tin này.';
        }
        if (input.validity.typeMismatch) {
            return input.getAttribute('data-type-msg') || 'Giá trị không đúng định dạng.';
        }
        if (input.validity.tooShort) {
            const min = input.getAttribute('minlength') || '';
            return input.getAttribute('data-minlength-msg') || `Tối thiểu ${min} ký tự.`;
        }
        if (input.validity.patternMismatch) {
            return input.getAttribute('data-pattern-msg') || 'Giá trị không hợp lệ.';
        }
        return 'Giá trị không hợp lệ.';
    }

    function fieldWrapFor(input) {
        return (
            input.closest('[data-auth-field], [data-field], .mb-3, .mb-4, .mb-5, .mb-2, .col-md-6, .col-md-4, .col-md-12, .col-12, .form-check, .col') ||
            input.parentElement
        );
    }

    function ensureErrorEl(wrap) {
        let err = wrap.querySelector(':scope > .gf-field-error, :scope > .auth-field-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'gf-field-error';
            err.hidden = true;
            wrap.appendChild(err);
        }
        return err;
    }

    function clearField(input) {
        input.classList.remove('is-invalid');
        const wrap = fieldWrapFor(input);
        const err = wrap?.querySelector(':scope > .gf-field-error, :scope > .auth-field-error');
        if (err) {
            err.hidden = true;
            err.textContent = '';
        }
    }

    function showField(input, msg) {
        input.classList.add('is-invalid');
        const wrap = fieldWrapFor(input);
        if (!wrap) return;
        const err = ensureErrorEl(wrap);
        err.textContent = msg;
        err.hidden = false;
    }

    function validateForm(form) {
        let firstInvalid = null;
        form.querySelectorAll('input, select, textarea').forEach((input) => {
            if (input.type === 'hidden' || input.disabled || input.type === 'submit' || input.type === 'button') {
                return;
            }
            // Skip file inputs without required
            clearField(input);
            if (!input.checkValidity()) {
                showField(input, messageFor(input));
                if (!firstInvalid) firstInvalid = input;
            }
        });
        const agree = form.querySelector('input[name="agree"][required], input[name="agree"].js-required');
        if (agree && !agree.checked) {
            showField(agree, agree.getAttribute('data-required-msg') || 'Bạn cần đồng ý điều khoản.');
            if (!firstInvalid) firstInvalid = agree;
        }
        return firstInvalid;
    }

    function bindForm(form) {
        if (form.dataset.gfValidateBound === '1') return;
        form.dataset.gfValidateBound = '1';
        form.setAttribute('novalidate', 'novalidate');
        form.addEventListener(
            'submit',
            (e) => {
                const bad = validateForm(form);
                if (bad) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    bad.focus?.();
                }
            },
            true
        );
        form.querySelectorAll('input, select, textarea').forEach((input) => {
            input.addEventListener('input', () => clearField(input));
            input.addEventListener('change', () => clearField(input));
        });
    }

    function init(root = document) {
        root.querySelectorAll('form.js-form-validate, form.js-auth-validate').forEach(bindForm);
    }

    window.gfFormValidate = { init, validateForm, clearField, showField };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init());
    } else {
        init();
    }
})();
