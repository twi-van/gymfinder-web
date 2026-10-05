// assets/js/core/auth.js
$(document).ready(function() {
    // Helper for checking auth
    window.Auth = {
        isLoggedIn: function() {
            return localStorage.getItem('isLoggedIn') === 'true';
        },
        updateHeader: function() {
            const isLoggedIn = this.isLoggedIn();
            // Update Header
            if (isLoggedIn && $('#headerAuth').length > 0) {
                // Calculate correct path based on if we are in root or a subdirectory
                const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
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
        },
        requireAuth: function(redirectUrl) {
            if (!this.isLoggedIn()) {
                const showModal = () => {
                    const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
                    const loginUrlBase = isRoot ? 'pages/auth/login.html' : '../auth/login.html';
                    $('#authModalLoginBtn').attr('href', loginUrlBase + '?redirect=' + encodeURIComponent(redirectUrl || window.location.href));
                    $('#authRequiredModal').modal('show');
                };

                if ($('#authRequiredModal').length > 0) {
                    showModal();
                } else {
                    const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
                    let modalUrl = 'components/login-required-modal.html';
                    if (!isRoot && window.location.pathname.includes('/pages/')) {
                        const depth = window.location.pathname.split('/pages/')[1].split('/').length;
                        modalUrl = '../'.repeat(depth) + 'components/login-required-modal.html';
                    }
                    $.get(modalUrl, function(data) {
                        $('body').append(data);
                        showModal();
                    });
                }
                return false;
            }
            return true;
        }
    };

    // Initialize header auth (if loaded synchronously)
    window.Auth.updateHeader();

    // Re-initialize when header is loaded dynamically
    $(document).on('headerLoaded', function() {
        window.Auth.updateHeader();
    });

    $(document).on('click', '.btn-logout', function(e) {
        e.preventDefault();
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('gymFavorites');
        localStorage.removeItem('trainerFavorites');
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
});
