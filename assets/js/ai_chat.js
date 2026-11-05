(() => {
    const root = document.getElementById('aiChatRoot');
    if (!root) return;

    const enabled = root.dataset.enabled === '1';
    const canPersist = root.dataset.canPersist === '1';
    const apiUrl = root.dataset.apiUrl || '';
    const historyUrl = root.dataset.historyUrl || '';
    const sessionsUrl = root.dataset.sessionsUrl || '';
    const csrfUrl = root.dataset.csrfUrl || '';
    const searchGymUrl = root.dataset.searchGymUrl || '';
    const searchPtUrl = root.dataset.searchPtUrl || '';
    const gymDetailUrl = root.dataset.gymDetailUrl || '';
    const trainerDetailUrl = root.dataset.trainerDetailUrl || '';
    const loginUrl = root.dataset.loginUrl || '';

    const thread = document.getElementById('aiChatThread');
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiMessage');
    const sendBtn = form?.querySelector('.gf-ai-chat-send');
    const headerActions = document.getElementById('aiChatHeaderActions');
    const sessionsList = document.getElementById('aiSessionsList');

    let csrf = root.dataset.csrf || '';
    let busy = false;
    let hasTurns = root.dataset.hasHistory === '1';
    let sessionId = Number(root.dataset.sessionId || 0) || 0;

    function gfPlace(name) {
        const text = String(name || '');
        if (window.GF_LANG !== 'en') return text;
        return text
            .replace(/Quận\s+(\d+)/g, 'District $1')
            .replace(/Thành phố Hồ Chí Minh|TP\.\s*Hồ Chí Minh|TP\.HCM|TPHCM/g, 'Ho Chi Minh City');
    }

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
        if (!headerActions) return;
        if (headerActions.querySelector('[data-ai-clear]')) return;
        headerActions.innerHTML = '';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gf-ai-chat-top-link border-0 bg-transparent p-0';
        btn.title = gfT('Xóa hội thoại đang mở');
        btn.setAttribute('data-ai-clear', '1');
        btn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i>' + gfT('Xóa');
        btn.addEventListener('click', clearHistory);
        headerActions.appendChild(btn);
    }

    function setHeaderSearchLink() {
        if (!headerActions) return;
        headerActions.innerHTML = `<a class="gf-ai-chat-top-link" href="${esc(searchGymUrl)}">${gfT('Tìm kiếm')}</a>`;
    }

    function welcomeHtml() {
        const persistHint = canPersist
            ? (' ' + gfT('Phiên chat được lưu khi bạn đã đăng nhập.'))
            : '';
        return (
            '<div class="gf-ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>' +
            '<div class="gf-ai-msg-col">' +
            '<div class="gf-ai-msg-name">' + gfT('Trợ lý') + '</div>' +
            '<div class="gf-ai-msg-bubble">' + gfT('Xin chào! Mình gợi ý phòng tập và HLV trong hệ thống GYMFINDER (TP.HCM). Hỏi theo quận, giá, Yoga… hoặc chọn gợi ý bên dưới.') +
            persistHint +
            '</div></div>'
        );
    }

    function resetThreadToWelcome() {
        thread.querySelectorAll('.gf-ai-msg').forEach((n) => n.remove());
        if (!document.getElementById('aiChatWelcome') && enabled) {
            const w = document.createElement('div');
            w.className = 'gf-ai-msg gf-ai-msg--bot';
            w.id = 'aiChatWelcome';
            w.innerHTML = welcomeHtml();
            thread.appendChild(w);
        }
        hasTurns = false;
        setHeaderSearchLink();
        scrollBottom();
    }

    function appendUser(text) {
        const wrap = document.createElement('div');
        wrap.className = 'gf-ai-msg gf-ai-msg--user';
        wrap.innerHTML =
            '<div class="gf-ai-msg-avatar"><i class="fa-solid fa-user"></i></div>' +
            '<div class="gf-ai-msg-col">' +
            '<div class="gf-ai-msg-name">' + gfT('Bạn') + '</div>' +
            '<div class="gf-ai-msg-bubble"></div>' +
            '</div>';
        wrap.querySelector('.gf-ai-msg-bubble').textContent = text;
        thread.appendChild(wrap);
        scrollBottom();
    }

    function mapGymCard(g) {
        if (g.title) return g;
        const cats = Array.isArray(g.categories) ? g.categories : [];
        const priceMin = Number(g.price_min || 0);
        const priceMax = Number(g.price_max || 0);
        const en = window.GF_LANG === 'en';
        const money = (n) => en
            ? `${Number(n).toLocaleString('en-US')} VND`
            : `${Number(n).toLocaleString('vi-VN')}₫`;
        let price = '';
        if (priceMin || priceMax) {
            price = priceMin === priceMax
                ? `${money(priceMin)}${en ? '/month' : ''}`
                : `${money(priceMin)} – ${money(priceMax)}${en ? '/month' : ''}`;
        }
        const catLabel = cats.slice(0, 2).map((name) => gfT(String(name))).join(', ');
        return {
            id: g.id,
            title: g.name || '',
            subtitle: [gfPlace(g.district || ''), catLabel].filter(Boolean).join(' · '),
            meta: `${price}${price ? ' · ' : ''}★ ${Number(g.avg_rating || 0).toFixed(1)}`,
            image: g.cover_image_url || g.image || '',
        };
    }

    function mapTrainerCard(t) {
        if (t.title) return t;
        const specs = Array.isArray(t.specialties) ? t.specialties : [];
        const districts = Array.isArray(t.districts) ? t.districts : [];
        return {
            id: t.id,
            title: t.full_name || '',
            subtitle: [
                districts.slice(0, 2).map((name) => gfPlace(String(name))).join(', '),
                `${Number(t.years_experience || 0)} ${gfT('năm KN')}`,
            ].filter(Boolean).join(' · '),
            meta: `${specs.slice(0, 2).map((name) => gfT(String(name))).join(' · ')} · ★ ${Number(t.avg_rating || 0).toFixed(1)}`,
            image: t.avatar_url || t.image || '',
            is_avatar: true,
        };
    }

    function cardHtml(item, isTrainer) {
        const mapped = isTrainer ? mapTrainerCard(item) : mapGymCard(item);
        const imgClass = isTrainer || mapped.is_avatar ? ' class="is-round"' : '';
        const meta = mapped.meta ? `<p class="gf-ai-result-card-meta">${esc(mapped.meta)}</p>` : '';
        const detailBase = isTrainer ? trainerDetailUrl : gymDetailUrl;
        const href = mapped.id && detailBase
            ? `${detailBase}?id=${encodeURIComponent(mapped.id)}`
            : (mapped.url || '#');
        return (
            `<a class="gf-ai-result-card" href="${esc(href)}">` +
            `<img${imgClass} src="${esc(mapped.image || '')}" alt="">` +
            `<div class="gf-ai-result-card-body">` +
            `<p class="gf-ai-result-card-title">${esc(mapped.title || '')}</p>` +
            `<p class="gf-ai-result-card-sub">${esc(mapped.subtitle || '')}</p>` +
            meta +
            `</div>` +
            `<i class="fa-solid fa-chevron-right text-secondary"></i>` +
            `</a>`
        );
    }

    function appendBot({ reply, gyms, trainers, suggestRefine, isError }) {
        const col = document.createElement('div');
        col.className = 'gf-ai-msg-col';

        const name = document.createElement('div');
        name.className = 'gf-ai-msg-name';
        name.textContent = gfT('Trợ lý');
        col.appendChild(name);

        const bubble = document.createElement('div');
        bubble.className = 'gf-ai-msg-bubble';
        bubble.textContent = reply || '';
        col.appendChild(bubble);

        if (isError) {
            const links = document.createElement('div');
            links.className = 'd-flex flex-wrap gap-2';
            links.innerHTML =
                `<a class="btn btn-sm btn-outline-secondary rounded-pill" href="${esc(searchGymUrl)}">${gfT('Tìm phòng tập')}</a>` +
                `<a class="btn btn-sm btn-outline-secondary rounded-pill" href="${esc(searchPtUrl)}">${gfT('Tìm HLV')}</a>`;
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
            hint.textContent = gfT('Chưa gắn gym/HLV cụ thể — thử hỏi rõ quận / giá / chuyên môn hơn.');
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
            '<div class="gf-ai-msg-name">' + gfT('Trợ lý') + '</div>' +
            '<div class="gf-ai-msg-bubble"><i class="fa-solid fa-spinner fa-spin me-1"></i>' + gfT('Đang suy nghĩ…') + '</div>' +
            '</div>';
        thread.appendChild(el);
        scrollBottom();
        return el;
    }

    function renderTurns(turns) {
        thread.querySelectorAll('.gf-ai-msg').forEach((n) => n.remove());
        const list = Array.isArray(turns) ? turns : [];
        if (!list.length) {
            resetThreadToWelcome();
            return;
        }
        list.forEach((turn) => {
            const userText = String(turn.user || '');
            if (userText) appendUser(userText);
            appendBot({
                reply: String(turn.bot || ''),
                gyms: turn.gyms || [],
                trainers: turn.trainers || [],
                suggestRefine: !!turn.suggest_refine,
                isError: !!turn.is_error,
            });
        });
        hasTurns = true;
        ensureClearButton();
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

    function markActiveSession(id) {
        sessionId = Number(id) || 0;
        root.dataset.sessionId = String(sessionId || '');
        sessionsList?.querySelectorAll('.gf-ai-session-item').forEach((btn) => {
            btn.classList.toggle('is-active', Number(btn.dataset.sessionId) === sessionId);
        });
    }

    function renderSessionsList(sessions, activeId) {
        if (!sessionsList) return;
        const items = Array.isArray(sessions) ? sessions : [];
        if (!items.length) {
            sessionsList.innerHTML =
                '<li class="gf-ai-sessions-empty" id="aiSessionsEmpty">' + gfT('Chưa có phiên lưu. Hỏi AI để tạo phiên đầu.') + '</li>';
            return;
        }
        sessionsList.innerHTML = items
            .map((s) => {
                const id = Number(s.id);
                const active = id === Number(activeId) ? ' is-active' : '';
                return (
                    `<li>` +
                    `<button type="button" class="gf-ai-session-item${active}" data-session-id="${id}" title="${esc(s.title || '')}">` +
                    `<span class="gf-ai-session-title">${esc(s.title || gfT('Hội thoại mới'))}</span>` +
                    `<span class="gf-ai-session-meta">${Number(s.turn_count || 0)} ${gfT('lượt')}</span>` +
                    `</button>` +
                    `<button type="button" class="gf-ai-session-del" data-session-delete="${id}" title="${gfT('Xóa phiên')}" aria-label="${gfT('Xóa phiên')}">` +
                    `<i class="fa-solid fa-trash-can"></i></button>` +
                    `</li>`
                );
            })
            .join('');
    }

    async function refreshSessionsList(preferActiveId) {
        if (!canPersist || !sessionsUrl) return;
        try {
            const res = await fetch(sessionsUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (res.status === 401) return;
            const json = await res.json().catch(() => ({}));
            const sessions = json?.data?.sessions || [];
            const active = preferActiveId != null ? preferActiveId : sessionId;
            renderSessionsList(sessions, active);
            markActiveSession(active);
        } catch (_) {
            /* ignore */
        }
    }

    async function loadSession(id) {
        if (!canPersist || !sessionsUrl || busy) return;
        const sid = Number(id);
        if (!sid) return;
        setBusy(true);
        try {
            const res = await fetch(`${sessionsUrl}/${sid}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (typeof window.gfToast === 'function') {
                    window.gfToast(json?.error?.message || gfT('Không mở được phiên.'), 'danger');
                }
                return;
            }
            markActiveSession(sid);
            renderTurns(json?.data?.turns || []);
        } catch (_) {
            if (typeof window.gfToast === 'function') {
                window.gfToast(gfT('Lỗi mạng khi mở phiên.'), 'danger');
            }
        } finally {
            setBusy(false);
        }
    }

    async function startNewSession() {
        if (!canPersist) {
            resetThreadToWelcome();
            sessionId = 0;
            return;
        }
        markActiveSession(0);
        resetThreadToWelcome();
        sessionsList?.querySelectorAll('.gf-ai-session-item').forEach((btn) => {
            btn.classList.remove('is-active');
        });
        if (typeof window.gfToast === 'function') {
            window.gfToast(gfT('Phiên mới — hỏi AI để bắt đầu lưu.'), 'info');
        }
    }

    async function deleteSessionById(id) {
        const sid = Number(id);
        if (!sid || !canPersist) return;
        const ok = typeof window.gfConfirm === 'function'
            ? await window.gfConfirm({
                title: gfT('Xóa phiên chat'),
                message: gfT('Xóa phiên này? Không thể hoàn tác.'),
                confirmLabel: gfT('Xóa phiên'),
                cancelLabel: gfT('Hủy'),
                danger: true,
            })
            : window.confirm(gfT('Xóa phiên này? Không thể hoàn tác.'));
        if (!ok) return;

        setBusy(true);
        try {
            await refreshCsrf();
            const res = await fetch(`${sessionsUrl}/${sid}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                },
            });
            if (!res.ok) {
                if (typeof window.gfToast === 'function') {
                    window.gfToast(gfT('Không xóa được phiên.'), 'danger');
                }
                return;
            }
            if (sessionId === sid) {
                markActiveSession(0);
                resetThreadToWelcome();
            }
            await refreshSessionsList(sessionId || 0);
            if (typeof window.gfToast === 'function') {
                window.gfToast(gfT('Đã xóa phiên.'), 'success');
            }
        } catch (_) {
            if (typeof window.gfToast === 'function') {
                window.gfToast(gfT('Lỗi mạng khi xóa phiên.'), 'danger');
            }
        } finally {
            setBusy(false);
        }
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
            const payload = {
                message,
                save_history: !canPersist,
            };
            if (canPersist) {
                payload.persist_session = true;
                if (sessionId > 0) payload.session_id = sessionId;
            }
            const res = await fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json().catch(() => ({}));
            loading.remove();

            const data = json.data || {};
            const returnedSessionId =
                Number(data.session_id || json?.error?.details?.session_id || 0) || 0;
            if (canPersist && returnedSessionId) {
                markActiveSession(returnedSessionId);
                await refreshSessionsList(returnedSessionId);
            }

            if (!res.ok || json?.error) {
                const msg =
                    json?.error?.message || json?.message || gfT('Không gọi được trợ lý AI. Thử lại sau.');
                appendBot({ reply: msg, gyms: [], trainers: [], suggestRefine: false, isError: true });
                return;
            }

            appendBot({
                reply: data.reply || gfT('Đây là một số gợi ý.'),
                gyms: data.gyms || [],
                trainers: data.trainers || [],
                suggestRefine: !!data.suggest_refine,
                isError: false,
            });
        } catch (_) {
            loading.remove();
            appendBot({
                reply: gfT('Lỗi mạng. Kiểm tra kết nối và thử lại.'),
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
        const ok = typeof window.gfConfirm === 'function'
            ? await window.gfConfirm({
                title: canPersist ? gfT('Xóa phiên đang mở') : gfT('Xóa hội thoại'),
                message: canPersist
                    ? gfT('Xóa phiên chat hiện tại khỏi danh sách đã lưu?')
                    : gfT('Xóa toàn bộ tin nhắn với Trợ lý AI? Bạn sẽ bắt đầu lại từ đầu.'),
                confirmLabel: gfT('Xóa'),
                cancelLabel: gfT('Hủy'),
                danger: true,
            })
            : window.confirm(gfT('Xóa toàn bộ tin nhắn với Trợ lý AI? Bạn sẽ bắt đầu lại từ đầu.'));
        if (!ok) return;

        setBusy(true);
        try {
            await refreshCsrf();
            const headers = {
                Accept: 'application/json',
                'X-CSRF-Token': csrf,
                'Content-Type': 'application/json',
            };
            const body = canPersist && sessionId > 0
                ? JSON.stringify({ session_id: sessionId })
                : undefined;
            const res = await fetch(historyUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers,
                body,
            });
            if (!res.ok) {
                if (typeof window.gfToast === 'function') {
                    window.gfToast(gfT('Không xóa được hội thoại. Thử lại.'), 'danger');
                }
                return;
            }
            const clearedId = sessionId;
            markActiveSession(0);
            resetThreadToWelcome();
            if (canPersist) {
                await refreshSessionsList(0);
            }
            if (typeof window.gfToast === 'function') {
                window.gfToast(clearedId ? gfT('Đã xóa phiên.') : gfT('Đã xóa hội thoại.'), 'success');
            }
        } catch (_) {
            if (typeof window.gfToast === 'function') {
                window.gfToast(gfT('Lỗi mạng khi xóa hội thoại.'), 'danger');
            }
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

    document.getElementById('aiSessionNew')?.addEventListener('click', () => {
        if (busy) return;
        startNewSession();
    });

    sessionsList?.addEventListener('click', (e) => {
        const del = e.target.closest('[data-session-delete]');
        if (del) {
            e.preventDefault();
            deleteSessionById(del.getAttribute('data-session-delete'));
            return;
        }
        const item = e.target.closest('.gf-ai-session-item');
        if (item) {
            e.preventDefault();
            const id = Number(item.dataset.sessionId);
            if (id && id !== sessionId) loadSession(id);
        }
    });

    scrollBottom();
})();
