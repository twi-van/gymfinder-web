// assets/js/core/auth.js
$(document).ready(function() {
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';

    // Update Header
    if (isLoggedIn && $('#headerAuth').length > 0) {
        // Calculate correct path based on if we are in root or a subdirectory
        const isRoot = window.location.pathname.endsWith('index.html') && !window.location.pathname.includes('pages/');
        const loginUrlBase = isRoot ? 'pages/auth/login.html' : '../auth/login.html';
        const profileUrlBase = isRoot ? 'pages/user/profile.html' : '../user/profile.html';
        const favoritesUrlBase = isRoot ? 'pages/user/favorites.html' : '../user/favorites.html';
        
        $('#headerAuth').html(`
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://ui-avatars.com/api/?name=Nguyen+Tuan&background=00c9b1&color=fff&rounded=true" id="headerAvatar" alt="Avatar" width="36" height="36" class="rounded-circle shadow-sm">
                    <span class="fw-semibold" id="headerName" style="font-size: 14px;">Nguyễn Tuấn</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2 rounded-3" style="font-size: 14px;">
                    <li><a class="dropdown-item py-2" href="${profileUrlBase}"><i class="fa-regular fa-user me-2 text-secondary"></i>Hồ sơ cá nhân</a></li>
                    <li><a class="dropdown-item py-2" href="${favoritesUrlBase}"><i class="fa-regular fa-heart me-2 text-secondary"></i>Đã lưu</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger btn-logout" href="#"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        `);
    }

    $(document).on('click', '.btn-logout', function(e) {
        e.preventDefault();
        localStorage.removeItem('isLoggedIn');
        window.location.reload();
    });

    $(document).on('click', '.btn-detail-auth', function(e) {
        e.preventDefault();
        const targetUrl = $(this).data('url') || $(this).attr('href');
        if (window.Auth.requireAuth(targetUrl)) {
            if (targetUrl && targetUrl !== '#') {
                window.location.href = targetUrl;
            }
        }
    });

    // Helper for checking auth
    window.Auth = {
        isLoggedIn: function() {
            return localStorage.getItem('isLoggedIn') === 'true';
        },
        requireAuth: function(redirectUrl) {
            if (!this.isLoggedIn()) {
                if ($('#authRequiredModal').length === 0) {
                    const isRoot = window.location.pathname.endsWith('index.html') && !window.location.pathname.includes('pages/');
                    const registerUrl = isRoot ? 'pages/auth/register.html' : '../auth/register.html';
                    
                    const modalHtml = `
                        <div class="modal fade" id="authRequiredModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <div class="modal-header border-0 pb-0">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center p-4 pt-0">
                                        <div class="mb-3">
                                            <i class="fa-solid fa-lock text-primary opacity-75" style="font-size: 48px;"></i>
                                        </div>
                                        <h4 class="fw-bold mb-2">Vui lòng đăng nhập</h4>
                                        <p class="text-secondary mb-4">Bạn cần đăng nhập để tiếp tục.</p>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="#" id="authModalLoginBtn" class="btn btn-primary px-4 fw-semibold rounded-pill">Đăng nhập</a>
                                            <a href="${registerUrl}" class="btn btn-outline-primary px-4 fw-semibold rounded-pill">Đăng ký</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(modalHtml);
                }
                
                const isRoot = window.location.pathname.endsWith('index.html') && !window.location.pathname.includes('pages/');
                const loginUrlBase = isRoot ? 'pages/auth/login.html' : '../auth/login.html';
                $('#authModalLoginBtn').attr('href', loginUrlBase + '?redirect=' + encodeURIComponent(redirectUrl || window.location.href));
                $('#authRequiredModal').modal('show');
                return false;
            }
            return true;
        }
    };
});
