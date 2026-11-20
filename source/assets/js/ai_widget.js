(() => {
    const root = document.getElementById('gfAiWidget');
    if (!root) return;

    const apiUrl = root.dataset.apiUrl || '';
    const csrfUrl = root.dataset.csrfUrl || '';
    const historyUrl = root.dataset.historyUrl || '';
    const enabled = root.dataset.enabled === '1';
    const canPersist = root.dataset.canPersist === '1';
    const gymDetailBase = root.dataset.gymDetailUrl || '';
    const trainerDetailBase = root.dataset.trainerDetailUrl || '';
    const GUEST_HISTORY_KEY = 'gf_ai_guest_turns';

    const fab = root.querySelector('.gf-ai-fab');
    const panel = root.querySelector('.gf-ai-panel');
    const closeBtn = root.querySelector('.gf-ai-panel-close');
    const messages = root.querySelector('.gf-ai-messages');
    const form = root.querySelector('.gf-ai-form');
    const input = root.querySelector('.gf-ai-input');
    const sendBtn = root.querySelector('.gf-ai-send');

    let csrf = root.dataset.csrf || '';
    let busy = false;
    let seeded = false;

    function readGuestTurns() {
        try {
            const raw = sessionStorage.getItem(GUEST_HISTORY_KEY);
            if (!raw) return [];
            const turns = JSON.parse(raw);
            return Array.isArray(turns) ? turns : [];
        } catch (_) {
            return [];
        }
    }

    function writeGuestTurns(turns) {
        try {
            sessionStorage.setItem(GUEST_HISTORY_KEY, JSON.stringify(Array.isArray(turns) ? turns : []));
        } catch (_) {
            /* quota / private mode */
        }
    }

    function appendGuestTurn(turn) {
        const turns = readGuestTurns();
        turns.push(turn);
        writeGuestTurns(turns.slice(-30));
    }

    function setOpen(open) {
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            input?.focus();
        }
    }

    const WELCOME_TEXT = 'Xin chào! Hỏi mình về phòng tập hoặc HLV tại TP.HCM nhé (quận, giá, Yoga…).';

    function clearMessagesKeepWelcome() {
        if (!messages) return;
        messages.innerHTML = '';
        const welcome = document.createElement('div');
        welcome.className = 'gf-ai-bubble gf-ai-bubble--bot gf-ai-welcome';
        welcome.textContent = gfT(WELCOME_TEXT);
        messages.appendChild(welcome);
    }

    function addBubble(text, role) {
        const el = document.createElement('div');
        el.className = 'gf-ai-bubble gf-ai-bubble--' + role;
        el.textContent = text;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    }

    function detailUrl(kind, id) {
        const n = Number(id) || 0;
        if (n <= 0) return '#';
        if (kind === 'trainer') {
            if (trainerDetailBase) {
                return trainerDetailBase.includes('?')
                    ? `${trainerDetailBase}&id=${n}`
                    : `${trainerDetailBase}?id=${n}`;
            }
            return `/pages/trainers/detail.php?id=${n}`;
        }
        if (gymDetailBase) {
            return gymDetailBase.includes('?')
                ? `${gymDetailBase}&id=${n}`
                : `${gymDetailBase}?id=${n}`;
        }
        return `/pages/gyms/detail.php?id=${n}`;
    }

    function gfPlace(name) {
        const text = String(name || '');
        if (window.GF_LANG !== 'en') return text;
        const match = text.match(/^Quận\s+(\d+)$/);
        if (match) return 'District ' + match[1];
        const places = {
            'Huyện Bình Chánh': 'Binh Chanh District',
            'Huyện Cần Giờ': 'Can Gio District',
            'Huyện Củ Chi': 'Cu Chi District',
            'Huyện Hóc Môn': 'Hoc Mon District',
            'Huyện Nhà Bè': 'Nha Be District',
            'Bình Tân': 'Binh Tan District',
            'Bình Thạnh': 'Binh Thanh District',
            'Gò Vấp': 'Go Vap District',
            'Phú Nhuận': 'Phu Nhuan District',
            'Tân Bình': 'Tan Binh District',
            'Tân Phú': 'Tan Phu District',
            'TP Thủ Đức': 'Thu Duc City',
            'Thủ Đức': 'Thu Duc City',
            'TP. Hồ Chí Minh': 'Ho Chi Minh City',
            'TP.HCM': 'Ho Chi Minh City',
            'TPHCM': 'Ho Chi Minh City',
        };
        return places[text] || text;
    }

    function mapGymCard(g) {
        const hasRaw = g?.name || Array.isArray(g?.categories) || g?.district;
        if (!hasRaw && g?.title) return g;
        const cats = Array.isArray(g?.categories) ? g.categories : [];
        return {
            id: g?.id,
            title: g?.name || g?.title || '',
            subtitle: [gfPlace(g?.district || ''), cats.slice(0, 2).map((n) => gfT(String(n))).join(', ')].filter(Boolean).join(' · '),
            image: g?.image || '',
            url: g?.url || detailUrl('gym', g?.id),
        };
    }

    function mapTrainerCard(t) {
        const hasRaw = t?.full_name || Array.isArray(t?.specialties) || Array.isArray(t?.districts) || t?.years_experience != null;
        if (!hasRaw && t?.title) return t;
        const specs = Array.isArray(t?.specialties) ? t.specialties : [];
        const districts = Array.isArray(t?.districts) ? t.districts : [];
        return {
            id: t?.id,
            title: t?.full_name || t?.title || '',
            subtitle: [
                districts.slice(0, 2).map((n) => gfPlace(String(n))).join(', '),
                t?.years_experience != null ? `${t.years_experience} ${gfT('năm KN')}` : '',
                specs.slice(0, 2).map((n) => gfT(String(n))).join(' · '),
            ]
                .filter(Boolean)
                .join(' · '),
            image: t?.image || '',
            url: t?.url || detailUrl('trainer', t?.id),
            is_avatar: true,
        };
    }

    function addCards(items, kind) {
        if (!Array.isArray(items) || items.length === 0) return;
        const wrap = document.createElement('div');
        wrap.className = 'gf-ai-cards';
        items.forEach((raw) => {
            const item = kind === 'trainer' ? mapTrainerCard(raw) : mapGymCard(raw);
            const a = document.createElement('a');
            a.className = 'gf-ai-card';
            a.href = item.url || '#';
            const img = document.createElement('img');
            img.src = item.image || '';
            img.alt = '';
            if (kind === 'trainer' || item.is_avatar) img.classList.add('is-avatar');
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

    function renderTurns(turns) {
        if (!Array.isArray(turns) || turns.length === 0) return false;
        clearMessagesKeepWelcome();
        turns.forEach((turn) => {
            const userText = (turn?.user || '').trim();
            const botText = (turn?.bot || '').trim();
            if (userText) addBubble(userText, 'user');
            if (botText) addBubble(botText, turn?.is_error ? 'bot' : 'bot');
            addCards(turn?.gyms || [], 'gym');
            addCards(turn?.trainers || [], 'trainer');
        });
        return true;
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

    async function clearServerHistorySoft() {
        if (!historyUrl) return;
        try {
            await refreshCsrf();
            await fetch(historyUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf,
                },
                body: JSON.stringify({}),
            });
        } catch (_) {
            /* ignore */
        }
    }

    async function loadSharedHistory() {
        if (seeded) return;
        seeded = true;

        if (!canPersist) {
            const localTurns = readGuestTurns();
            if (localTurns.length) {
                renderTurns(localTurns);
                return;
            }
            await clearServerHistorySoft();
            return;
        }

        const embedded = root.dataset.historyJson;
        if (embedded) {
            try {
                const turns = JSON.parse(embedded);
                if (renderTurns(turns)) return;
            } catch (_) {
                /* fall through */
            }
        }
        if (!historyUrl) return;
        try {
            const res = await fetch(historyUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (res.ok) {
                renderTurns(json?.data?.turns || []);
            }
        } catch (_) {
            /* ignore */
        }
    }

    async function ask(message) {
        if (!enabled) {
            addBubble(
                gfT('AI chưa cấu hình trên máy này. Xem hướng dẫn trong GymFinder_HuongDanChay.md.'),
                'bot'
            );
            return;
        }
        if (busy) return;
        const text = (message || '').trim();
        if (!text) return;

        busy = true;
        if (sendBtn) sendBtn.disabled = true;
        addBubble(text, 'user');
        const loading = addBubble(gfT('Đang tìm…'), 'meta');

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
                const msg =
                    json?.error?.message ||
                    json?.message ||
                    gfT('Không gọi được trợ lý AI. Thử lại sau.');
                addBubble(msg, 'bot');
                if (!canPersist) {
                    appendGuestTurn({
                        user: text,
                        bot: msg,
                        is_error: true,
                        gyms: [],
                        trainers: [],
                        suggest_refine: false,
                        at: Date.now(),
                    });
                }
                return;
            }
            const data = json.data || {};
            const reply = data.reply || gfT('Đây là một số gợi ý.');
            addBubble(reply, 'bot');
            addCards(data.gyms || [], 'gym');
            addCards(data.trainers || [], 'trainer');
            if (!canPersist) {
                appendGuestTurn({
                    user: text,
                    bot: reply,
                    is_error: false,
                    gyms: data.gyms || [],
                    trainers: data.trainers || [],
                    suggest_refine: !!data.suggest_refine,
                    at: Date.now(),
                });
            }
        } catch (err) {
            loading.remove();
            addBubble(gfT('Lỗi mạng. Kiểm tra kết nối và thử lại.'), 'bot');
            if (!canPersist) {
                appendGuestTurn({
                    user: text,
                    bot: gfT('Lỗi mạng. Kiểm tra kết nối và thử lại.'),
                    is_error: true,
                    gyms: [],
                    trainers: [],
                    suggest_refine: false,
                    at: Date.now(),
                });
            }
        } finally {
            busy = false;
            if (sendBtn) sendBtn.disabled = false;
            input?.focus();
        }
    }

    fab?.addEventListener('click', () => {
        const willOpen = !panel.classList.contains('is-open');
        setOpen(willOpen);
        if (willOpen) loadSharedHistory();
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
            loadSharedHistory().finally(() => ask(chip));
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panel.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    loadSharedHistory();
})();
