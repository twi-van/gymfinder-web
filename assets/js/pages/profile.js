// assets/js/pages/profile.js
$(document).ready(function() {
    
    // Check Auth
    if (!window.Auth.isLoggedIn()) {
        window.Auth.requireAuth('pages/user/profile.html');
        // Prevent seeing the content, redirect out
        setTimeout(() => {
            window.location.href = '../auth/login.html?redirect=../user/profile.html';
        }, 500);
        return;
    }

    // Show content since we are logged in
    $('#profileContent').show();

    // Default user structure if none exists
    let defaultUser = {
        name: "Nguyễn Tuấn",
        email: "nguyentuan@example.com",
        phone: "0901234567",
        height: "",
        weight: "",
        fitnessGoal: ""
    };

    let currentUser = {};
    try {
        let stored = localStorage.getItem('currentUser');
        if (stored) {
            currentUser = JSON.parse(stored);
        } else {
            currentUser = defaultUser;
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
        }
    } catch (e) {
        currentUser = defaultUser;
    }

    // Populate data
    function loadUserData() {
        $('#profileNameDisplay').text(currentUser.name || "Người dùng");
        $('#inputName').val(currentUser.name || "");
        $('#inputEmail').val(currentUser.email || "");
        $('#inputPhone').val(currentUser.phone || "");
        $('#inputHeight').val(currentUser.height || "");
        $('#inputWeight').val(currentUser.weight || "");
        $('#inputFitnessGoal').val(currentUser.fitnessGoal || "");
        
        // Also update header name if it exists (for sync)
        if ($('#headerName').length) {
            $('#headerName').text(currentUser.name || "Người dùng");
        }
    }

    loadUserData();

    // Update Personal Info
    $('#btnUpdatePersonal').on('click', function() {
        const newName = $('#inputName').val().trim();
        const newPhone = $('#inputPhone').val().trim();
        
        if (!newName) {
            alert('Vui lòng nhập họ và tên!');
            return;
        }

        currentUser.name = newName;
        currentUser.phone = newPhone;
        
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        loadUserData();
        
        // Update header navbar if changed
        if ($('#headerName').length) {
            $('#headerName').text(newName);
        }
        
        alert('Cập nhật thông tin cá nhân thành công!');
    });

    // Update Physical Info
    $('#btnUpdatePhysical').on('click', function() {
        const height = $('#inputHeight').val().trim();
        const weight = $('#inputWeight').val().trim();
        const fitnessGoal = $('#inputFitnessGoal').val();
        
        currentUser.height = height ? parseInt(height) : "";
        currentUser.weight = weight ? parseInt(weight) : "";
        currentUser.fitnessGoal = fitnessGoal;
        
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        alert('Đã lưu thông tin thể chất và mục tiêu tập luyện!');
    });
});
