/**
 * GYMFINDER — shared toast + confirm modal helpers (Bootstrap 5).
 */
(function () {
    'use strict';

    function ensureToastHost() {
        let host = document.getElementById('gfToastHost');
        if (!host) {
            host = document.createElement('div');
            host.id = 'gfToastHost';
            host.className = 'gf-toast-host';
            host.setAttribute('aria-live', 'polite');
            host.setAttribute('aria-atomic', 'true');
            document.body.appendChild(host);
        }
        return host;
    }

    /**
     * @param {string} message
     * @param {'success'|'danger'|'warning'|'info'} [type]
     * @param {number} [durationMs]
     */
    window.gfToast = function gfToast(message, type, durationMs) {
        const text = String(message || '').trim();
        if (!text) return;

        const kind = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'info';
        const host = ensureToastHost();
        const el = document.createElement('div');
        el.className = `gf-toast gf-toast-${kind} shadow-sm`;
        el.setAttribute('role', 'alert');

        const icons = {
            success: 'fa-circle-check',
            danger: 'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info',
        };
        el.innerHTML = `
            <i class="fa-solid ${icons[kind]} flex-shrink-0"></i>
            <div class="gf-toast-text"></div>
            <button type="button" class="btn-close btn-close-sm" aria-label=gfT("Đóng")></button>
        `;
        el.querySelector('.gf-toast-text').textContent = text;

        const close = () => {
            el.classList.add('gf-toast-hide');
            window.setTimeout(() => el.remove(), 200);
        };
        el.querySelector('.btn-close')?.addEventListener('click', close);
        host.appendChild(el);
        window.requestAnimationFrame(() => el.classList.add('gf-toast-show'));
        window.setTimeout(close, Math.max(2000, durationMs || 4200));
    };

    let confirmResolver = null;
    let confirmModal = null;

    function getConfirmModal() {
        const el = document.getElementById('gfConfirmModal');
        if (!el || !window.bootstrap?.Modal) return null;
        if (!confirmModal) {
            confirmModal = window.bootstrap.Modal.getOrCreateInstance(el);
            const okBtn = document.getElementById('gfConfirmOk');
            const cancelBtns = el.querySelectorAll('[data-gf-confirm-cancel]');
            okBtn?.addEventListener('click', () => {
                const resolve = confirmResolver;
                confirmResolver = null;
                confirmModal.hide();
                if (resolve) resolve(true);
            });
            cancelBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const resolve = confirmResolver;
                    confirmResolver = null;
                    if (resolve) resolve(false);
                });
            });
            el.addEventListener('hidden.bs.modal', () => {
                if (confirmResolver) {
                    const resolve = confirmResolver;
                    confirmResolver = null;
                    resolve(false);
                }
                const ok = document.getElementById('gfConfirmOk');
                if (ok) {
                    ok.disabled = false;
                    ok.innerHTML = ok.dataset.defaultLabel || gfT('Xác nhận');
                }
            });
        }
        return confirmModal;
    }

    /**
     * @param {{title?:string, message?:string, confirmLabel?:string, cancelLabel?:string, danger?:boolean}} opts
     * @returns {Promise<boolean>}
     */
    window.gfConfirm = function gfConfirm(opts) {
        const options = opts || {};
        const modalApi = getConfirmModal();
        if (!modalApi) {
            return Promise.resolve(window.confirm(options.message || gfT('Bạn có chắc chắn?')));
        }

        const titleEl = document.getElementById('gfConfirmTitle');
        const bodyEl = document.getElementById('gfConfirmMessage');
        const okBtn = document.getElementById('gfConfirmOk');
        const cancelBtn = document.getElementById('gfConfirmCancel');

        if (titleEl) {
            titleEl.innerHTML = options.danger
                ? `<i class="fa-solid fa-triangle-exclamation me-2"></i>${escapeHtml(options.title || gfT('Xác nhận'))}`
                : escapeHtml(options.title || gfT('Xác nhận'));
            titleEl.classList.toggle('text-danger', !!options.danger);
        }
        if (bodyEl) {
            bodyEl.textContent = options.message || gfT('Bạn có chắc chắn muốn thực hiện thao tác này?');
        }
        if (cancelBtn) {
            cancelBtn.textContent = options.cancelLabel || gfT('Hủy');
        }
        if (okBtn) {
            const label = options.confirmLabel || gfT('Xác nhận');
            okBtn.dataset.defaultLabel = label;
            okBtn.textContent = label;
            okBtn.className = options.danger
                ? 'btn btn-danger rounded-pill px-4 fw-semibold'
                : 'btn btn-primary rounded-pill px-4 fw-semibold';
            okBtn.disabled = false;
        }

        return new Promise((resolve) => {
            confirmResolver = resolve;
            modalApi.show();
        });
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Wire forms with class js-confirm-submit.
     * data-confirm-title, data-confirm-message, data-confirm-label, data-confirm-danger="1"
     */
    function wireConfirmForms() {
        document.querySelectorAll('form.js-confirm-submit').forEach((form) => {
            if (form.dataset.gfConfirmBound === '1') return;
            form.dataset.gfConfirmBound = '1';
            form.addEventListener('submit', async (event) => {
                if (form.dataset.gfConfirmAccepted === '1') {
                    form.dataset.gfConfirmAccepted = '0';
                    const submitBtn = form.querySelector('[type="submit"], button:not([type="button"])');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        const original = submitBtn.innerHTML;
                        submitBtn.dataset.originalHtml = original;
                        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>' + gfT('Đang xử lý...');
                    }
                    return;
                }
                event.preventDefault();
                const ok = await window.gfConfirm({
                    title: form.dataset.confirmTitle || gfT('Xác nhận'),
                    message: form.dataset.confirmMessage || gfT('Bạn có chắc chắn muốn thực hiện thao tác này?'),
                    confirmLabel: form.dataset.confirmLabel || gfT('Xác nhận'),
                    cancelLabel: form.dataset.confirmCancel || gfT('Hủy'),
                    danger: form.dataset.confirmDanger === '1',
                });
                if (!ok) return;
                form.dataset.gfConfirmAccepted = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        });
    }

    /**
     * Form lọc (user): submit khi không có từ khóa/bộ lọc nào → toast + chặn gửi.
     * Đánh dấu form bằng class js-filter-empty-toast.
     */
    function formHasActiveFilter(form) {
        const ignoreNames = { sort: true, page: true, _csrf: true };
        let active = false;
        form.querySelectorAll('input, select, textarea').forEach((el) => {
            if (active || el.disabled) return;
            const name = String(el.name || '');
            if (!name || ignoreNames[name]) return;
            const type = (el.type || '').toLowerCase();
            if (type === 'submit' || type === 'button' || type === 'reset' || type === 'file' || type === 'image') {
                return;
            }
            if (type === 'checkbox' || type === 'radio') {
                if (el.checked && String(el.value || '').trim() !== '') {
                    active = true;
                }
                return;
            }
            if (String(el.value || '').trim() !== '') {
                active = true;
            }
        });
        return active;
    }

    function wireEmptyFilterToast() {
        document.querySelectorAll('form.js-filter-empty-toast').forEach((form) => {
            if (form.dataset.gfEmptyFilterBound === '1') return;
            form.dataset.gfEmptyFilterBound = '1';
            form.addEventListener('submit', (event) => {
                if (formHasActiveFilter(form)) return;
                event.preventDefault();
                const msg =
                    typeof gfT === 'function'
                        ? gfT('Vui lòng nhập từ khóa hoặc chọn ít nhất một bộ lọc.')
                        : 'Vui lòng nhập từ khóa hoặc chọn ít nhất một bộ lọc.';
                if (typeof window.gfToast === 'function') {
                    window.gfToast(msg, 'warning');
                }
                const focusEl =
                    form.querySelector('input[name="keyword"]:not([type="hidden"])') ||
                    form.querySelector('input[name="q"]:not([type="hidden"])') ||
                    form.querySelector('select:not([disabled])') ||
                    form.querySelector('input:not([type="hidden"]):not([type="submit"]):not([type="button"])');
                focusEl?.focus?.();
            });
        });
    }

    function bootUiFeedback() {
        wireConfirmForms();
        wireEmptyFilterToast();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootUiFeedback);
    } else {
        bootUiFeedback();
    }
    window.gfWireConfirmForms = wireConfirmForms;
    window.gfWireEmptyFilterToast = wireEmptyFilterToast;
})();
