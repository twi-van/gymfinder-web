<footer class="border-top py-5 mt-auto" style="background-color: #f8fafc;">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-12 col-md-5 col-lg-5 pe-lg-4">
                <?= gf_logo('footer', null, 'mb-4') ?>
                <p class="text-secondary mb-4" style="font-size: 14px; line-height: 1.6; max-width: 380px;">Nền tảng tìm kiếm và so sánh các phòng tập gym, tiện ích, loại hình tập luyện và đội ngũ huấn luyện viên.</p>
                <div class="d-flex align-items-center gap-2 text-secondary" style="font-size: 14px;">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>TP. Hồ Chí Minh</span>
                </div>
            </div>
            <div class="col-12 col-md-7 col-lg-6 offset-lg-1">
                <div class="row g-4 justify-content-between">
                    <div class="col-6 col-sm-6">
                        <h5 class="fw-bold text-dark mb-4" style="font-size: 15px;">Phòng tập</h5>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3" style="font-size: 14px;">
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="text-secondary text-decoration-none">Tất cả phòng tập</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="text-secondary text-decoration-none">Tìm phòng tập</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php?featured=1')) ?>" class="text-secondary text-decoration-none">Gym nổi bật</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php?has_pt=1')) ?>" class="text-secondary text-decoration-none">Phòng tập có PT</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-sm-6">
                        <h5 class="fw-bold text-dark mb-4" style="font-size: 15px;">Huấn luyện viên</h5>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3" style="font-size: 14px;">
                            <li><a href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>" class="text-secondary text-decoration-none">Tìm huấn luyện viên</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/trainers/index.php?featured=1')) ?>" class="text-secondary text-decoration-none">HLV nổi bật</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>" class="text-secondary text-decoration-none">Theo bộ môn</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-top pt-4 d-flex flex-column flex-sm-row justify-content-between gap-2">
            <p class="text-secondary mb-0" style="font-size: 14px;">© 2026 GYMFINDER. All rights reserved.</p>
            <p class="text-secondary mb-0 d-flex flex-wrap gap-3" style="font-size: 14px;">
                <a href="<?= gf_h(gf_url('pages/legal/terms.php')) ?>" class="text-secondary text-decoration-none">Điều khoản dịch vụ</a>
                <a href="<?= gf_h(gf_url('pages/legal/privacy.php')) ?>" class="text-secondary text-decoration-none">Chính sách bảo mật</a>
            </p>
        </div>
    </div>
</footer>
<div class="modal fade" id="authRequiredModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0"><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body text-center p-4 pt-0">
                <div class="mb-3"><i class="fa-solid fa-lock text-primary opacity-75" style="font-size:48px"></i></div>
                <h4 class="fw-bold mb-2">Đăng nhập để tiếp tục</h4>
                <p class="text-secondary mb-4">Bạn cần đăng nhập để lưu phòng tập hoặc huấn luyện viên vào danh sách yêu thích.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="#" id="authModalLoginBtn" class="btn btn-primary px-4 fw-semibold rounded-pill">Đăng nhập</a>
                    <button type="button" class="btn btn-outline-secondary px-4 fw-semibold rounded-pill" data-bs-dismiss="modal">Để sau</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/contact_modal.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/ui_feedback.php'; ?>
<script>
document.querySelectorAll('.js-favorite-toggle').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const type = form.querySelector('[name="target_type"]')?.value;
        const id = form.querySelector('[name="target_id"]')?.value;
        const csrf = form.querySelector('[name="_csrf"]')?.value;
        const wasFavorited = form.dataset.favorited === '1';
        if (!button || !type || !id || !csrf) return;

        button.disabled = true;
        try {
            const response = await fetch(
                wasFavorited ? `${form.dataset.apiUrl}/${encodeURIComponent(type)}/${encodeURIComponent(id)}` : form.dataset.apiUrl,
                {
                    method: wasFavorited ? 'DELETE' : 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': csrf,
                        ...(wasFavorited ? {} : { 'Content-Type': 'application/json' }),
                    },
                    ...(wasFavorited ? {} : { body: JSON.stringify({ target_type: type, target_id: Number(id) }) }),
                }
            );

            if (response.status === 401) {
                const returnTo = form.querySelector('[name="redirect"]')?.value || window.location.pathname;
                const loginButton = document.getElementById('authModalLoginBtn');
                const modalElement = document.getElementById('authRequiredModal');
                if (loginButton && modalElement && window.bootstrap?.Modal) {
                    loginButton.href = `${form.dataset.loginUrl}?redirect=${encodeURIComponent(returnTo)}`;
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                } else {
                    window.location.href = `${form.dataset.loginUrl}?redirect=${encodeURIComponent(returnTo)}`;
                }
                return;
            }
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.error?.message || 'Không thể cập nhật yêu thích.');
            }

            const isFavorited = !wasFavorited;
            form.dataset.favorited = isFavorited ? '1' : '0';
            button.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-solid', 'fa-regular', 'text-danger');
                icon.classList.add(isFavorited ? 'fa-solid' : 'fa-regular');
                if (isFavorited) icon.classList.add('text-danger');
            } else {
                button.textContent = isFavorited ? 'Bỏ yêu thích' : 'Lưu yêu thích';
            }
        } catch (error) {
            if (typeof window.gfToast === 'function') {
                window.gfToast(error.message || 'Không thể kết nối tới máy chủ.', 'danger');
            }
        } finally {
            button.disabled = false;
        }
    });
});

// Xử lý modal Liên hệ (Dùng chung cho Gym & Huấn luyện viên)
(function() {
    let copyTimeout = null;
    const contactModalEl = document.getElementById('contactModal');
    if (!contactModalEl) return;

    const modalTitle = document.getElementById('contactModalTitle');
    const modalSubtitle = document.getElementById('contactModalSubtitle');
    const modalAvatar = document.getElementById('contactModalAvatar');
    const modalCategory = document.getElementById('contactModalCategory');
    const modalTypeIcon = document.getElementById('contactModalTypeIcon');
    const phoneWrapper = document.getElementById('contactModalPhoneWrapper');
    const phoneText = document.getElementById('contactModalPhone');
    const noPhoneNotice = document.getElementById('contactModalNoPhone');
    const copyBtn = document.getElementById('contactModalCopyBtn');
    const copyText = document.getElementById('contactModalCopyText');
    const copyIcon = document.getElementById('contactModalCopyIcon');
    const copyAlert = document.getElementById('contactModalCopyAlert');
    const callBtn = document.getElementById('contactModalCallBtn');

    let currentPhone = '';

    function resetCopyFeedback() {
        if (copyTimeout) {
            clearTimeout(copyTimeout);
            copyTimeout = null;
        }
        if (copyAlert) {
            copyAlert.classList.add('d-none');
            copyAlert.classList.remove('d-flex');
        }
        if (copyBtn) {
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-primary');
        }
        if (copyIcon) {
            copyIcon.className = 'fa-regular fa-copy';
        }
        if (copyText) {
            copyText.textContent = 'Sao chép số điện thoại';
        }
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-global-contact');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        resetCopyFeedback();

        const name = (btn.dataset.name || 'Thông tin liên hệ').trim();
        const type = (btn.dataset.type || 'trainer').trim();
        const typeLabel = (btn.dataset.typeLabel || (type === 'gym' ? 'Phòng tập thể hình' : 'Huấn luyện viên cá nhân')).trim();
        const avatar = (btn.dataset.avatar || '').trim();
        const subtitle = (btn.dataset.subtitle || '').trim();
        let phone = (btn.dataset.phone || '').trim();

        // Kiểm tra loại bỏ null/undefined
        if (phone.toLowerCase() === 'null' || phone.toLowerCase() === 'undefined') {
            phone = '';
        }
        currentPhone = phone;

        if (modalTitle) modalTitle.textContent = name;
        if (modalCategory) modalCategory.textContent = typeLabel;
        if (modalSubtitle) {
            modalSubtitle.textContent = subtitle;
            modalSubtitle.style.display = subtitle ? 'block' : 'none';
        }

        if (modalAvatar) {
            modalAvatar.alt = name;
            modalAvatar.src = avatar;
            if (type === 'gym') {
                modalAvatar.style.borderRadius = '16px';
                modalAvatar.style.width = '96px';
                modalAvatar.style.height = '80px';
                modalAvatar.onerror = function() {
                    this.onerror = null;
                    this.src = 'https://placehold.co/120x100?text=Gym';
                };
            } else {
                modalAvatar.style.borderRadius = '50%';
                modalAvatar.style.width = '88px';
                modalAvatar.style.height = '88px';
                modalAvatar.onerror = function() {
                    this.onerror = null;
                    this.src = 'https://placehold.co/100x100?text=Trainer';
                };
            }
        }

        if (modalTypeIcon) {
            modalTypeIcon.className = type === 'gym' ? 'fa-solid fa-dumbbell' : 'fa-solid fa-user';
        }

        if (phone) {
            if (phoneWrapper) phoneWrapper.classList.remove('d-none');
            if (phoneText) phoneText.textContent = phone;
            if (noPhoneNotice) noPhoneNotice.classList.add('d-none');
            if (copyBtn) {
                copyBtn.disabled = false;
                copyBtn.classList.remove('opacity-50');
            }
            if (callBtn) {
                callBtn.classList.remove('d-none');
                callBtn.classList.add('d-flex');
                callBtn.href = 'tel:' + phone.replace(/[^\d+]/g, '');
            }
        } else {
            if (phoneWrapper) phoneWrapper.classList.add('d-none');
            if (noPhoneNotice) {
                noPhoneNotice.classList.remove('d-none');
                noPhoneNotice.textContent = 'Chưa cập nhật số điện thoại';
            }
            if (copyBtn) {
                copyBtn.disabled = true;
                copyBtn.classList.add('opacity-50');
            }
            if (callBtn) {
                callBtn.classList.add('d-none');
                callBtn.classList.remove('d-flex');
            }
        }

        if (window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(contactModalEl).show();
        }
    });

    if (copyBtn) {
        copyBtn.addEventListener('click', async function() {
            if (!currentPhone) return;

            const cleanPhone = currentPhone.trim();
            let success = false;

            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(cleanPhone);
                    success = true;
                } catch (err) {
                    success = false;
                }
            }

            if (!success) {
                const ta = document.createElement('textarea');
                ta.value = cleanPhone;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                ta.style.top = '-9999px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try {
                    success = document.execCommand('copy');
                } catch (err) {
                    success = false;
                }
                ta.remove();
            }

            if (success) {
                if (copyIcon) copyIcon.className = 'fa-solid fa-check';
                if (copyText) copyText.textContent = 'Đã sao chép!';
                copyBtn.classList.remove('btn-primary');
                copyBtn.classList.add('btn-success');

                if (copyAlert) {
                    copyAlert.classList.remove('d-none');
                    copyAlert.classList.add('d-flex');
                }

                if (copyTimeout) clearTimeout(copyTimeout);
                copyTimeout = setTimeout(resetCopyFeedback, 2500);
            }
        });
    }

    contactModalEl.addEventListener('hidden.bs.modal', function() {
        resetCopyFeedback();
    });
})();
</script>
</body>
</html>
