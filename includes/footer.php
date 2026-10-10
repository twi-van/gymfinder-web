<footer class="border-top py-5 mt-auto" style="background-color: #f8fafc;">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-12 col-lg-4 pe-lg-5">
                <a href="<?= gf_h(gf_url('index.php')) ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-4">
                    <span class="border rounded d-flex align-items-center justify-content-center text-primary bg-white shadow-sm" style="width:32px;height:32px;border-color:#f3f4f6!important;">
                        <i class="fa-solid fa-dumbbell"></i>
                    </span>
                    <span class="fw-bold" style="font-size: 20px; color: #1e293b;">GYM<span class="text-primary">FINDER</span></span>
                </a>
                <p class="text-secondary mb-4" style="font-size: 14px; line-height: 1.6;">Nền tảng tìm kiếm và so sánh các phòng tập gym, tiện ích, loại hình tập luyện và đội ngũ huấn luyện viên.</p>
                <div class="d-flex align-items-center gap-2 text-secondary" style="font-size: 14px;">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>TP. Hồ Chí Minh</span>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="row g-4">
                    <div class="col-6 col-md-4">
                        <h5 class="fw-bold text-dark mb-4" style="font-size: 15px;">Phòng tập</h5>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3" style="font-size: 14px;">
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="text-secondary text-decoration-none">Tất cả phòng tập</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="text-secondary text-decoration-none">Tìm phòng tập</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php?featured=1')) ?>" class="text-secondary text-decoration-none">Gym nổi bật</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/gyms/search.php?has_pt=1')) ?>" class="text-secondary text-decoration-none">Phòng tập có PT</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md-4">
                        <h5 class="fw-bold text-dark mb-4" style="font-size: 15px;">Huấn luyện viên</h5>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3" style="font-size: 14px;">
                            <li><a href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>" class="text-secondary text-decoration-none">Tìm huấn luyện viên</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/trainers/index.php?featured=1')) ?>" class="text-secondary text-decoration-none">HLV nổi bật</a></li>
                            <li><a href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>" class="text-secondary text-decoration-none">Theo bộ môn</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-md-4">
                        <h5 class="fw-bold text-dark mb-4" style="font-size: 15px;">Liên kết</h5>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3" style="font-size: 14px;">
                            <li><a href="#about" class="text-secondary text-decoration-none">Về chúng tôi</a></li>
                            <li><a href="#contact" class="text-secondary text-decoration-none">Liên hệ</a></li>
                            <li><a href="#terms" class="text-secondary text-decoration-none">Điều khoản</a></li>
                            <li><a href="#privacy" class="text-secondary text-decoration-none">Bảo mật</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-top pt-4">
            <p class="text-secondary mb-0" style="font-size: 14px;">© 2026 GYMFINDER. All rights reserved.</p>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
            window.alert(error.message || 'Không thể kết nối tới máy chủ.');
        } finally {
            button.disabled = false;
        }
    });
});
</script>
</body>
</html>
