(() => {
    const root = document.getElementById('aiChatRoot');
    if (!root) return;

    const enabled = root.dataset.enabled === '1';
    const apiUrl = root.dataset.apiUrl || '';
    const historyUrl = root.dataset.historyUrl || '';
    const csrfUrl = root.dataset.csrfUrl || '';
    const searchGymUrl = root.dataset.searchGymUrl || '';
    const searchPtUrl = root.dataset.searchPtUrl || '';

    const thread = document.getElementById('aiChatThread');
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiMessage');
    const sendBtn = form?.querySelector('.gf-ai-chat-send');
    const headerActions = document.getElementById('aiChatHeaderActions');
    const welcome = document.getElementById('aiChatWelcome');

    let csrf = root.dataset.csrf || '';
    let busy = false;
    let hasTurns = root.dataset.hasHistory === '1';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function scrollBottom() {
        if (thread) thread.scrollTop = thread.scrollHeight;
    }

    function resizeInput() {
        if (!input) return;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    function setBusy(on) {
        busy = on;
        if (sendBtn) {
            sendBtn.disabled = on || !enabled;
            sendBtn.innerHTML = on
                ? '<i class="fa-solid fa-spinner fa-spin"></i>'
                : '<i class="fa-solid fa-paper-plane"></i>';
        }
        if (input) input.disabled = on || !enabled;
        document.querySelectorAll('.gf-ai-chat-chip').forEach((b) => {
            b.disabled = on || !enabled;
        });
    }

    function ensureClearButton() {
        if (!headerActions || headerActions.querySelector('[data-ai-clear]')) return;
        headerActions.innerHTML = '';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gf-ai-chat-top-link border-0 bg-transparent p-0';
        btn.title = 'Xóa hội thoại';
        btn.setAttribute('data-ai-clear', '1');
        btn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i>Xóa';
        btn.addEventListener('click', clearHistory);
        headerActions.appendChild(btn);
    }

    function removeWelcome() {
        welcome?.remove();
    }

    function appendUser(text) {
        removeWelcome();
        const wrap = document.createElement('div');
        wrap.className = 'gf-ai-msg gf-ai-msg--user';
        wrap.innerHTML =
            '<div class="gf-ai-msg-avatar"><i class="fa-solid fa-user"></i></div>' +
            '<div class="gf-ai-msg-col">' +
            '<div class="gf-ai-msg-name">Bạn</div>' +
            '<div class="gf-ai-msg-bubble"></div>' +
            '</div>';
        wrap.querySelector('.gf-ai-msg-bubble').textContent = text;
        thread.appendChild(wrap);
        scrollBottom();
    }

    function cardHtml(item, isTrainer) {
        const imgClass = isTrainer || item.is_avatar ? ' class="is-round"' : '';
        const meta = item.meta ? `<p class="gf-ai-result-card-meta">${esc(item.meta)}</p>` : '';
        return (
            `<a class="gf-ai-result-card" href="${esc(item.url || '#')}">` +
            `<img${imgClass} src="${esc(item.image || '')}" alt="">` +
            `<div class="gf-ai-result-card-body">` +
            `<p class="gf-ai-result-card-title">${esc(item.title || '')}</p>` +
            `<p class="gf-ai-result-card-sub">${esc(item.subtitle || '')}</p>` +
            meta +
            `</div>` +
            `<i class="fa-solid fa-chevron-right text-secondary"></i>` +
            `</a>`
        );
    }

    function appendBot({ reply, gyms, trainers, suggestRefine, isError }) {
        removeWelcome();
        const col = document.createElement('div');
        col.className = 'gf-ai-msg-col';

        const name = document.createElement('div');
        name.className = 'gf-ai-msg-name';
        name.textContent = 'Trợ lý';
        col.appendChild(name);

        const bubble = document.createElement('div');
        bubble.className = 'gf-ai-msg-bubble';
        bubble.textContent = reply || '';
        col.appendChild(bubble);

        if (isError) {
            const links = document.createElement('div');
            links.className = 'd-flex flex-wrap gap-2';
            links.innerHTML =
                `<a class="btn btn-sm btn-outline-secondary rounded-pill" href="${esc(searchGymUrl)}">Tìm phòng tập</a>` +
                `<a class="btn btn-sm btn-outline-secondary rounded-pill" href="${esc(searchPtUrl)}">Tìm HLV</a>`;
            col.appendChild(links);
        }

        (gyms || []).forEach((g) => {
            const tmp = document.createElement('div');
            tmp.innerHTML = cardHtml(g, false);
            col.appendChild(tmp.firstElementChild);
        });
        (trainers || []).forEach((t) => {
            const tmp = document.createElement('div');
            tmp.innerHTML = cardHtml(t, true);
            col.appendChild(tmp.firstElementChild);
        });

        if (!isError && suggestRefine && !(gyms || []).length && !(trainers || []).length) {
            const hint = document.createElement('p');
            hint.className = 'small text-secondary mb-0 px-1';
            hint.textContent = 'Chưa gắn gym/HLV cụ thể — thử hỏi rõ quận / giá / chuyên môn hơn.';
            col.appendChild(hint);
        }

        const wrap = document.createElement('div');
        wrap.className = 'gf-ai-msg gf-ai-msg--bot';
        wrap.innerHTML = '<div class="gf-ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>';
        wrap.appendChild(col);
        thread.appendChild(wrap);
        scrollBottom();
    }

    function appendLoading() {
        const el = document.createElement('div');
        el.className = 'gf-ai-msg gf-ai-msg--bot';
        el.dataset.loading = '1';
        el.innerHTML =
            '<div class="gf-ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>' +
            '<div class="gf-ai-msg-col">' +
            '<div class="gf-ai-msg-name">Trợ lý</div>' +
            '<div class="gf-ai-msg-bubble"><i class="fa-solid fa-spinner fa-spin me-1"></i>Đang suy nghĩ…</div>' +
            '</div>';
        thread.appendChild(el);
        scrollBottom();
        return el;
    }

    async function refreshCsrf() {
        if (!csrfUrl) return csrf;
        try {
            const res = await fetch(csrfUrl, { credentials: 'same-origin' });
            const json = await res.json();
            csrf = json?.data?.csrf_token || csrf;
            const hidden = form?.querySelector('input[name="_csrf"]');
            if (hidden && csrf) hidden.value = csrf;
        } catch (_) {
            /* keep */
        }
        return csrf;
    }

    async function ask(text) {
        if (!enabled || busy) return;
        const message = (text || '').trim();
        if (!message) return;

        appendUser(message);
        if (input) {
            input.value = '';
            resizeInput();
        }
        hasTurns = true;
        ensureClearButton();

        const loading = appendLoading();
        setBusy(true);

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
                body: JSON.stringify({ message, save_history: true }),
            });
            const json = await res.json().catch(() => ({}));
            loading.remove();

            if (!res.ok || json?.error) {
                const msg =
                    json?.error?.message || json?.message || 'Không gọi được trợ lý AI. Thử lại sau.';
                appendBot({ reply: msg, gyms: [], trainers: [], suggestRefine: false, isError: true });
                return;
            }

            const data = json.data || {};
            appendBot({
                reply: data.reply || 'Đây là một số gợi ý.',
                gyms: data.gyms || [],
                trainers: data.trainers || [],
                suggestRefine: !!data.suggest_refine,
                isError: false,
            });
        } catch (_) {
            loading.remove();
            appendBot({
                reply: 'Lỗi mạng. Kiểm tra kết nối và thử lại.',
                gyms: [],
                trainers: [],
                suggestRefine: false,
                isError: true,
            });
        } finally {
            setBusy(false);
            input?.focus();
        }
    }

    async function clearHistory() {
        if (!confirm('Xóa toàn bộ hội thoại?')) return;
        setBusy(true);
        try {
            await refreshCsrf();
            const res = await fetch(historyUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                },
            });
            if (!res.ok) {
                alert('Không xóa được hội thoại. Thử lại.');
                return;
            }
            // Soft reset UI without full reload
            thread.querySelectorAll('.gf-ai-msg').forEach((n) => n.remove());
            thread.querySelectorAll('.gf-ai-chat-alert').forEach((n) => {
                /* keep config warning */
            });
            if (!document.getElementById('aiChatWelcome') && enabled) {
                const w = document.createElement('div');
                w.className = 'gf-ai-msg gf-ai-msg--bot';
                w.id = 'aiChatWelcome';
                w.innerHTML =
                    '<div class="gf-ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>' +
                    '<div class="gf-ai-msg-col">' +
                    '<div class="gf-ai-msg-name">Trợ lý</div>' +
                    '<div class="gf-ai-msg-bubble">Xin chào! Mình gợi ý <strong>phòng tập</strong> và <strong>HLV</strong> trong hệ thống GYMFINDER (TP.HCM). Hỏi theo quận, giá, Yoga… hoặc chọn gợi ý bên dưới.</div>' +
                    '</div>';
                thread.appendChild(w);
            }
            hasTurns = false;
            if (headerActions) {
                headerActions.innerHTML = `<a class="gf-ai-chat-top-link" href="${esc(searchGymUrl)}">Tìm kiếm</a>`;
            }
            scrollBottom();
        } catch (_) {
            alert('Lỗi mạng khi xóa hội thoại.');
        } finally {
            setBusy(false);
        }
    }

    input?.addEventListener('input', resizeInput);
    resizeInput();

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        ask(input?.value || '');
    });

    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form?.requestSubmit();
        }
    });

    document.querySelectorAll('.gf-ai-chat-chip').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!enabled || busy) return;
            ask(btn.getAttribute('data-chip') || '');
        });
    });

    headerActions?.querySelector('[data-ai-clear]')?.addEventListener('click', clearHistory);

    scrollBottom();
})();
