// assets/js/pages/auth-register.js
$(document).ready(function() {
    // Toggle Password Visibility
    $('.auth-toggle-password').on('click', function() {
        const passwordInput = $(this).siblings('input');
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    // Handle Register Submit
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        const fullname = $('#regFullname').val().trim();
        const username = $('#regUsername').val().trim();
        const password = $('#regPassword').val().trim();
        const confirmPassword = $('#regConfirmPassword').val().trim();
        const agreeTerms = $('#agreeTerms').is(':checked');
        
        if (!fullname || !username || !password || !confirmPassword) {
            alert('Vui lòng điền đầy đủ thông tin!');
            return;
        }

        if (password !== confirmPassword) {
            alert('Mật khẩu xác nhận không khớp!');
            return;
        }

        if (!agreeTerms) {
            alert('Vui lòng đồng ý với Điều khoản dịch vụ!');
            return;
        }

        // Mock Registration Success
        alert('Đăng ký thành công! Vui lòng đăng nhập.');
        window.location.href = 'login.html';
    });
});
