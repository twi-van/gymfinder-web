// assets/js/pages/auth-login.js
$(document).ready(function() {
    // Handle Quick Test Accounts
    $('.test-card').on('click', function() {
        const email = $(this).data('email');
        const password = $(this).data('password');
        
        // Remove active class from all, add to clicked
        $('.test-card').removeClass('active');
        $(this).addClass('active');

        // Fill form
        $('#username').val(email);
        $('#password').val(password);
    });

    // Toggle Password Visibility
    $('#btnTogglePassword').on('click', function() {
        const passwordInput = $('#password');
        const icon = $('#togglePasswordIcon');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    // Handle Login Submit
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val().trim();
        const password = $('#password').val().trim();
        
        if (!username || !password) {
            alert('Vui lòng nhập đầy đủ email và mật khẩu');
            return;
        }

        // Mock Login Logic
        if (username === 'admin@gymfinder.vn' && password === 'admin123') {
            // Admin logic
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userRole', 'admin');
            window.location.href = '../../admin/pages/dashboard.html';
        } else if (username === 'user@gymfinder.vn' && password === 'user123') {
            // User logic
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userRole', 'user');
            
            // Check for redirect url in query params
            const urlParams = new URLSearchParams(window.location.search);
            const redirect = urlParams.get('redirect');
            if (redirect) {
                window.location.href = redirect;
            } else {
                window.location.href = '../../index.html';
            }
        } else {
            alert('Tài khoản hoặc mật khẩu không chính xác!');
        }
    });
});
