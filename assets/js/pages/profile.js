// assets/js/pages/profile.js
$(document).ready(function() {
    
    // Check Auth - if not logged in, redirect and show modal
    if (!window.Auth.isLoggedIn()) {
        window.Auth.requireAuth();
        // Redirect to home if guest tries to stay here
        setTimeout(() => {
            window.location.href = '../../index.html';
        }, 100);
        return;
    }

    // Show content since we are logged in
    $('#profileContent').show();

    // Default mock user
    const defaultUser = {
        id: 1,
        name: "Nguyễn Tuấn",
        email: "nguyentuan@gmail.com",
        phone: "0901234567",
        avatar: "", // Will be dynamically generated if empty
        fitness_goals: [
            "weight_loss",
            "muscle_gain"
        ]
    };

    let currentUser = {};
    let pendingAvatarBase64 = null; // Store selected avatar before saving

    // Generate dynamic avatar URL if none is set
    function getAvatarUrl(name, storedAvatar) {
        if (storedAvatar && storedAvatar.trim() !== "") {
            return storedAvatar;
        }
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'User')}&background=00c9b1&color=fff&rounded=true`;
    }
    
    // Load from LocalStorage or use default
    function loadData() {
        try {
            const stored = localStorage.getItem('profileUser');
            if (stored) {
                currentUser = JSON.parse(stored);
            } else {
                currentUser = { ...defaultUser };
                localStorage.setItem('profileUser', JSON.stringify(currentUser));
            }
        } catch (e) {
            currentUser = { ...defaultUser };
        }
    }

    // Populate UI with currentUser data
    function renderProfile() {
        $('#profileNameDisplay').text(currentUser.name || "Chưa cập nhật");
        $('#profileEmailDisplay').text(currentUser.email || "Chưa cập nhật");
        
        const avatarToDisplay = getAvatarUrl(currentUser.name, currentUser.avatar);
        $('#profileAvatar').attr('src', avatarToDisplay);

        $('#inputName').val(currentUser.name);
        $('#inputPhone').val(currentUser.phone);
        $('#inputEmail').val(currentUser.email);

        // Reset checkboxes
        $('.goal-checkbox-input').prop('checked', false);

        // Check goals
        if (currentUser.fitness_goals && Array.isArray(currentUser.fitness_goals)) {
            currentUser.fitness_goals.forEach(goal => {
                $(`#goal_${goal}`).prop('checked', true);
            });
        }
        
        // Ensure Header is also in sync
        if ($('#headerName').length) {
            $('#headerName').text(currentUser.name);
            $('#headerAvatar').attr('src', getAvatarUrl(currentUser.name, currentUser.avatar));
        }
        
        // Reset pending avatar and error
        pendingAvatarBase64 = null;
        $('#avatarError').addClass('d-none').text('');
        $('#inputAvatarFile').val('');
    }

    // Initialize
    loadData();
    renderProfile();

    // View / Edit Toggle State
    let isEditing = false;

    function setEditMode(edit) {
        isEditing = edit;
        if (isEditing) {
            // Enter Edit Mode
            $('#inputName, #inputPhone').removeClass('profile-readonly-field').addClass('profile-edit-field').prop('readonly', false);
            $('.goal-checkbox-input').prop('disabled', false);
            $('.edit-only-element').removeClass('d-none');
            
            $('#viewActions').addClass('d-none');
            $('#editActions').removeClass('d-none');
        } else {
            // Exit Edit Mode (Read Only)
            $('#inputName, #inputPhone').removeClass('profile-edit-field').addClass('profile-readonly-field').prop('readonly', true);
            $('.goal-checkbox-input').prop('disabled', true);
            $('.edit-only-element').addClass('d-none');
            
            $('#viewActions').removeClass('d-none');
            $('#editActions').addClass('d-none');
            
            // Remove validation errors
            $('.is-invalid').removeClass('is-invalid');
            $('#goalsFeedback').addClass('d-none');
        }
    }

    $('#btnEditProfile').on('click', function() {
        setEditMode(true);
    });

    $('#btnCancelEdit').on('click', function() {
        // Discard changes by re-rendering from saved data
        renderProfile();
        setEditMode(false);
    });

    // Save Profile
    $('#btnSaveProfile').on('click', function() {
        let isValid = true;
        
        // Reset validation UI
        $('.is-invalid').removeClass('is-invalid');
        $('#goalsFeedback').addClass('d-none');

        const newName = $('#inputName').val().trim();
        const newPhone = $('#inputPhone').val().trim();
        
        // Validation: Name
        if (!newName) {
            $('#inputName').addClass('is-invalid');
            isValid = false;
        }

        // Validation: Phone
        const phoneRegex = /^[0-9]{10,11}$/;
        if (!newPhone || !phoneRegex.test(newPhone)) {
            $('#inputPhone').addClass('is-invalid');
            isValid = false;
        }

        // Validation: Fitness Goals (at least 1)
        const selectedGoals = [];
        $('.goal-checkbox-input:checked').each(function() {
            selectedGoals.push($(this).val());
        });

        if (selectedGoals.length === 0) {
            $('#goalsFeedback').removeClass('d-none');
            isValid = false;
        }

        if (!isValid) return;

        // Save to currentUser
        currentUser.name = newName;
        currentUser.phone = newPhone;
        currentUser.fitness_goals = selectedGoals;
        
        // Save pending avatar if selected
        if (pendingAvatarBase64 !== null) {
            currentUser.avatar = pendingAvatarBase64;
        }

        // Save to LocalStorage
        localStorage.setItem('profileUser', JSON.stringify(currentUser));
        
        // Update UI
        renderProfile();
        setEditMode(false);
    });
    
    // --- AVATAR HANDLER COMPONENT ---
    // In Stage 2, `saveAvatar` can be replaced with a fetch/axios call to a PHP API endpoint.
    const AvatarManager = {
        MAX_SIZE_MB: 2,
        ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'],

        init: function() {
            const self = this;
            
            // Trigger file input when clicking camera button or avatar image in edit mode
            $('#btnChangeAvatar, #profileAvatar').on('click', function(e) {
                if (!isEditing) return; // Only allow clicking if in edit mode
                e.preventDefault();
                $('#inputAvatarFile').click();
            });

            // Handle remove avatar
            $('#btnRemoveAvatar').on('click', function(e) {
                e.preventDefault();
                pendingAvatarBase64 = ""; // Empty string means "remove"
                $('#profileAvatar').attr('src', getAvatarUrl($('#inputName').val(), ""));
                $('#inputAvatarFile').val('');
                $('#avatarError').addClass('d-none').text('');
            });

            // Handle file selection
            $('#inputAvatarFile').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                self.processFile(file);
            });
        },

        processFile: function(file) {
            const errorEl = $('#avatarError');
            errorEl.addClass('d-none').text('');

            // Validate Type
            if (!this.ALLOWED_TYPES.includes(file.type)) {
                errorEl.removeClass('d-none').text('Chỉ hỗ trợ định dạng JPG, PNG, WEBP.');
                $('#inputAvatarFile').val('');
                return;
            }

            // Validate Size
            const sizeMB = file.size / (1024 * 1024);
            if (sizeMB > this.MAX_SIZE_MB) {
                errorEl.removeClass('d-none').text(`Dung lượng ảnh tối đa là ${this.MAX_SIZE_MB}MB.`);
                $('#inputAvatarFile').val('');
                return;
            }

            // Preview & Store Base64
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64String = e.target.result;
                $('#profileAvatar').attr('src', base64String);
                pendingAvatarBase64 = base64String;
            };
            reader.readAsDataURL(file);
        }
    };

    // Initialize the Avatar Manager
    AvatarManager.init();

});
