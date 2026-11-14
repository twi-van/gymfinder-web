(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function initAdminDragScroll(scroller) {
        if (!scroller || scroller.dataset.dragScrollBound === '1') return;
        scroller.dataset.dragScrollBound = '1';
        var startX = 0;
        var startScroll = 0;
        var dragged = false;
        var suppressClick = false;

        scroller.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 || scroller.scrollWidth <= scroller.clientWidth) return;
            if (event.target.closest('button, a, input, select, textarea, form, label')) return;
            startX = event.clientX;
            startScroll = scroller.scrollLeft;
            dragged = false;
            scroller.setPointerCapture(event.pointerId);
            scroller.classList.add('is-dragging');
        });
        scroller.addEventListener('pointermove', function (event) {
            if (!scroller.hasPointerCapture(event.pointerId)) return;
            var delta = event.clientX - startX;
            if (Math.abs(delta) > 4) dragged = true;
            if (dragged) {
                event.preventDefault();
                scroller.scrollLeft = startScroll - delta;
            }
        });
        function finishDrag(event) {
            if (scroller.hasPointerCapture(event.pointerId)) {
                scroller.releasePointerCapture(event.pointerId);
            }
            scroller.classList.remove('is-dragging');
            if (dragged) {
                suppressClick = true;
                window.setTimeout(function () { suppressClick = false; }, 0);
            }
            dragged = false;
        }
        scroller.addEventListener('pointerup', finishDrag);
        scroller.addEventListener('pointercancel', finishDrag);
        scroller.addEventListener('click', function (event) {
            if (!suppressClick) return;
            event.preventDefault();
            event.stopPropagation();
        }, true);
    }

    function initSidebarCollapse() {
        var sidebar = document.getElementById('sidebar');
        var btn = document.getElementById('sidebarCollapse');
        if (!sidebar || !btn) return;

        var desktop = window.matchMedia('(min-width: 992px)');
        var storageKey = 'gfAdminSidebarCollapsed';

        function storedCollapsed() {
            try {
                return localStorage.getItem(storageKey) === '1';
            } catch (e) {
                return false;
            }
        }

        function sync() {
            var collapsed = desktop.matches && storedCollapsed();
            document.documentElement.classList.toggle('admin-sidebar-collapsed', collapsed);
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            var label = collapsed ? btn.dataset.labelExpand : btn.dataset.labelCollapse;
            if (label) {
                btn.setAttribute('aria-label', label);
                btn.title = label;
            }
            sidebar.querySelectorAll('a.nav-link[data-label]').forEach(function (link) {
                if (collapsed) link.title = link.dataset.label;
                else link.removeAttribute('title');
            });
        }

        sync();
        btn.addEventListener('click', function () {
            if (!desktop.matches) return;
            try {
                localStorage.setItem(storageKey, storedCollapsed() ? '0' : '1');
            } catch (e) {}
            sync();
        });

        function onDesktopChange() {
            sync();
        }
        if (typeof desktop.addEventListener === 'function') {
            desktop.addEventListener('change', onDesktopChange);
        } else if (typeof desktop.addListener === 'function') {
            desktop.addListener(onDesktopChange);
        }
    }

    function initActionTooltips() {
        function apply() {
            if (!window.bootstrap || typeof bootstrap.Tooltip !== 'function') return false;
            document.querySelectorAll('.btn-action[title], .btn-action[data-bs-original-title]').forEach(function (el) {
                var tip = bootstrap.Tooltip.getOrCreateInstance(el, {
                    container: 'body',
                    placement: 'top',
                    trigger: 'hover',
                    delay: { show: 120, hide: 0 }
                });
                if (el.dataset.gfTipBound === '1') return;
                el.dataset.gfTipBound = '1';
                el.addEventListener('click', function () {
                    tip.hide();
                });
            });
            return true;
        }
        if (apply()) return;
        // Bootstrap CDN thường nằm cuối trang; thử lại sau khi script đó kịp load
        window.addEventListener('load', apply);
        setTimeout(apply, 0);
    }

    /** Tra cứu: nếu ô tìm kiếm trống và không chọn bộ lọc nào → toast + không submit. */
    function initFilterSearchToast() {
        document.querySelectorAll('form').forEach(function (form) {
            if (!form.querySelector('button.btn-filter, .btn-filter')) return;
            if (form.dataset.gfFilterToastBound === '1') return;
            form.dataset.gfFilterToastBound = '1';

            form.addEventListener('submit', function (e) {
                var q = form.querySelector('input[name="q"]');
                if (!q) return;

                var qEmpty = String(q.value || '').trim() === '';
                var hasOtherFilter = false;
                form.querySelectorAll('select, input').forEach(function (el) {
                    if (el === q || el.disabled || el.type === 'hidden' || el.type === 'submit' || el.type === 'button') {
                        return;
                    }
                    if (el.tagName === 'SELECT') {
                        if (String(el.value || '') !== '') hasOtherFilter = true;
                        return;
                    }
                    if (String(el.value || '').trim() !== '') hasOtherFilter = true;
                });

                if (!qEmpty || hasOtherFilter) {
                    q.classList.remove('is-invalid');
                    return;
                }

                e.preventDefault();
                var msg = typeof gfT === 'function'
                    ? gfT('Vui lòng nhập từ khóa tìm kiếm.')
                    : 'Vui lòng nhập từ khóa tìm kiếm.';
                if (typeof window.gfToast === 'function') {
                    window.gfToast(msg, 'warning');
                }
                q.classList.add('is-invalid');
                q.focus();
            });

            var qInput = form.querySelector('input[name="q"]');
            if (qInput) {
                qInput.addEventListener('input', function () {
                    qInput.classList.remove('is-invalid');
                });
            }
        });
    }

    ready(function () {
        document.querySelectorAll('.admin-drag-scroll').forEach(initAdminDragScroll);
        initSidebarCollapse();
        initActionTooltips();
        initFilterSearchToast();

        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var openBtn = document.getElementById('sidebarOpen');
        var closeBtn = document.getElementById('sidebarClose');
        if (!sidebar || !overlay || !openBtn) return;

        var mq = window.matchMedia('(max-width: 991.98px)');

        function setOpen(open) {
            if (!mq.matches) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                overlay.hidden = true;
                document.body.classList.remove('admin-sidebar-open');
                openBtn.setAttribute('aria-expanded', 'false');
                sidebar.removeAttribute('aria-hidden');
                return;
            }
            sidebar.classList.toggle('show', open);
            overlay.classList.toggle('show', open);
            overlay.hidden = !open;
            document.body.classList.toggle('admin-sidebar-open', open);
            openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        // Mobile/tablet: sidebar ẩn mặc định
        if (mq.matches) {
            setOpen(false);
        }

        openBtn.addEventListener('click', function () {
            setOpen(true);
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setOpen(false);
            });
        }
        overlay.addEventListener('click', function () {
            setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mq.matches) setOpen(false);
            });
        });

        function onViewportChange() {
            setOpen(false);
        }
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', onViewportChange);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(onViewportChange);
        }
    });
})();
