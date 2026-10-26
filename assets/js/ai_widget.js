(() => {
    const root = document.getElementById('gfAiWidget');
    if (!root) return;

    const apiUrl = root.dataset.apiUrl || '';
    const csrfUrl = root.dataset.csrfUrl || '';
    const enabled = root.dataset.enabled === '1';

    const fab = root.querySelector('.gf-ai-fab');
    const panel = root.querySelector('.gf-ai-panel');
    const closeBtn = root.querySelector('.gf-ai-panel-close');
    const messages = root.querySelector('.gf-ai-messages');
    const form = root.querySelector('.gf-ai-form');
    const input = root.querySelector('.gf-ai-input');
    const sendBtn = root.querySelector('.gf-ai-send');

    let csrf = root.dataset.csrf || '';
    let busy = false;

    function setOpen(open) {
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            input?.focus();
        }
    }

    function addBubble(text, role) {
        const el = document.createElement('div');
        el.className = 'gf-ai-bubble gf-ai-bubble--' + role;
        el.textContent = text;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    }

    function addCards(items, kind) {
        if (!Array.isArray(items) || items.length === 0) return;
        const wrap = document.createElement('div');
        wrap.className = 'gf-ai-cards';
        items.forEach((item) => {
            const a = document.createElement('a');
            a.className = 'gf-ai-card';
            a.href = item.url || '#';
            const img = document.createElement('img');
            img.src = item.image || '';
            img.alt = '';
            if (kind === 'trainer') img.classList.add('is-avatar');
            const body = document.createElement('div');
            body.className = 'gf-ai-card-body';
            const title = document.createElement('p');
            title.className = 'gf-ai-card-title';
            title.textContent = item.title || '';
            const sub = document.createElement('p');
            sub.className = 'gf-ai-card-sub';
            sub.textContent = item.subtitle || '';
            body.appendChild(title);
            body.appendChild(sub);
            a.appendChild(img);
            a.appendChild(body);
            wrap.appendChild(a);
        });
        const host = document.createElement('div');
        host.className = 'gf-ai-bubble gf-ai-bubble--bot';
        host.appendChild(wrap);
        messages.appendChild(host);
        messages.scrollTop = messages.scrollHeight;
    }

    async function refreshCsrf() {
        if (!csrfUrl) return csrf;
        try {
            const res = await fetch(csrfUrl, { credentials: 'same-origin' });
            const json = await res.json();
            csrf = json?.data?.csrf_token || csrf;
        } catch (_) {
            /* keep old */
        }
        return csrf;
    }

    async function ask(message) {
        if (!enabled) {
            addBubble('AI chưa cấu hình trên máy này. Xem hướng dẫn trong GymFinder_HuongDanChay.md.', 'bot');
            return;
        }
        if (busy) return;
        const text = (message || '').trim();
        if (!text) return;

        busy = true;
        if (sendBtn) sendBtn.disabled = true;
        addBubble(text, 'user');
        const loading = addBubble('Đang tìm…', 'meta');

        try {
            await refreshCsrf();
            const res = await fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                },
                body: JSON.stringify({ message: text }),
            });
            const json = await res.json().catch(() => ({}));
            loading.remove();
            if (!res.ok || json?.error) {
                const msg = json?.error?.message || json?.message || 'Không gọi được trợ lý AI. Thử lại sau.';
                addBubble(msg, 'bot');
                return;
            }
            const data = json.data || {};
            addBubble(data.reply || 'Đây là một số gợi ý.', 'bot');
            addCards(data.gyms || [], 'gym');
            addCards(data.trainers || [], 'trainer');
            if ((data.gyms || []).length === 0 && (data.trainers || []).length === 0) {
                /* reply already explains */
            }
        } catch (err) {
            loading.remove();
            addBubble('Lỗi mạng. Kiểm tra kết nối và thử lại.', 'bot');
        } finally {
            busy = false;
            if (sendBtn) sendBtn.disabled = false;
            input?.focus();
        }
    }

    fab?.addEventListener('click', () => {
        setOpen(!panel.classList.contains('is-open'));
    });
    closeBtn?.addEventListener('click', () => setOpen(false));

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        const val = input?.value || '';
        if (input) input.value = '';
        ask(val);
    });

    root.querySelectorAll('.gf-ai-chip').forEach((btn) => {
        btn.addEventListener('click', () => {
            const chip = btn.getAttribute('data-chip') || '';
            if (input) input.value = '';
            setOpen(true);
            ask(chip);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panel.classList.contains('is-open')) {
            setOpen(false);
        }
    });
})();
