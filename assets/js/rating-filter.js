(function () {
    function hintText(mode, value) {
        if (mode === 'rate') {
            return value > 0 ? (value + ' sao') : 'Chọn số sao';
        }
        return value > 0 ? ('Từ ' + value + '★ trở lên') : 'Mọi mức đánh giá';
    }

    function paint(root, mode, value, hover) {
        var fillTo = hover > 0 ? hover : value;
        root.querySelectorAll('.gf-star-filter-btn').forEach(function (btn) {
            var star = parseInt(btn.getAttribute('data-value'), 10);
            btn.classList.toggle('is-active', star <= value);
            btn.classList.toggle('is-hover', hover > 0 && star <= fillTo);
            btn.setAttribute('aria-pressed', star === value ? 'true' : 'false');
        });
        var hint = root.querySelector('.gf-star-filter-hint');
        if (hint) hint.textContent = hintText(mode, hover > 0 ? hover : value);
        var clear = root.querySelector('.gf-star-filter-clear');
        if (clear) clear.classList.toggle('d-none', value < 1);
    }

    function bind(root) {
        var input = root.querySelector('.gf-star-filter-input');
        if (!input) return;
        var mode = root.getAttribute('data-mode') === 'rate' ? 'rate' : 'filter';
        var value = parseInt(input.value, 10) || 0;
        if (mode === 'rate' && value < 1) {
            value = 5;
            input.value = '5';
        }

        root.querySelectorAll('.gf-star-filter-btn').forEach(function (btn) {
            btn.addEventListener('mouseenter', function () {
                paint(root, mode, value, parseInt(btn.getAttribute('data-value'), 10) || 0);
            });
            btn.addEventListener('focus', function () {
                paint(root, mode, value, parseInt(btn.getAttribute('data-value'), 10) || 0);
            });
            btn.addEventListener('click', function () {
                var star = parseInt(btn.getAttribute('data-value'), 10) || 0;
                if (mode === 'rate') {
                    value = star;
                } else {
                    value = value === star ? 0 : star;
                }
                input.value = value > 0 ? String(value) : '';
                paint(root, mode, value, 0);
            });
        });

        var stars = root.querySelector('.gf-star-filter-stars');
        if (stars) {
            stars.addEventListener('mouseleave', function () {
                paint(root, mode, value, 0);
            });
        }

        var clear = root.querySelector('.gf-star-filter-clear');
        if (clear && mode === 'filter') {
            clear.addEventListener('click', function () {
                value = 0;
                input.value = '';
                paint(root, mode, value, 0);
            });
        }

        paint(root, mode, value, 0);
    }

    function init() {
        document.querySelectorAll('[data-gf-star-filter]').forEach(bind);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
