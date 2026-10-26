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

    ready(function () {
        document.querySelectorAll('.admin-drag-scroll').forEach(initAdminDragScroll);

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
