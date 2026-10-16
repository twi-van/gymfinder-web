(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
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
