// assets/js/pages/trainer-detail.js
$(document).ready(function() {
    
    // Get ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    let trainerId = parseInt(urlParams.get('id'));



    if (isNaN(trainerId)) {
        $('#breadcrumbTrainerName').text('Không tìm thấy');
        $('#tName').text('Huấn luyện viên không tồn tại');
        return;
    }

    const trainer = trainersData.find(t => t.id === trainerId);

    if (!trainer) {
        $('#breadcrumbTrainerName').text('Không tìm thấy');
        $('#tName').text('Huấn luyện viên không tồn tại');
        return;
    }

    // Render Data
    $('#breadcrumbTrainerName').text(trainer.name);
    $('#tName').text(trainer.name);
    $('#tAvatar').attr('src', trainer.avatar).attr('onerror', "this.src='https://placehold.co/400x400?text=Trainer'");
    $('#tRating').text(trainer.rating);
    $('#tReviewCount').text(`(${trainer.reviewCount} đánh giá)`);
    let specString = Array.isArray(trainer.specialization) ? trainer.specialization.join(', ') : trainer.specialization;
    let address = 'Không xác định';
    if (trainer.gymId && typeof gymsData !== 'undefined') {
        const gym = gymsData.find(g => g.id === trainer.gymId);
        if (gym) {
            address = gym.address;
            
            // Render Associated Gym
            $('#tGymCard').removeClass('d-none');
            $('#tGymName').text(gym.name);
            $('#tGymAddress').text(gym.address);
            $('#tGymImage').attr('src', gym.image).attr('onerror', "this.src='https://placehold.co/60x60?text=Gym'");
            $('#tGymLink').attr('href', `../gyms/detail.html?id=${gym.id}`);
        }
    } else if (trainer.location) {
        address = trainer.location;
    }

    $('#tLocation').text(address);
    $('#tExp').text(trainer.experience);
    $('#tSpec').text(specString);
    $('#tAbout').text(trainer.about);
    $('#tStyle').text(trainer.trainingStyle);
    
    // Setup contact button
    if (trainer.contact) {
        $('#tContactBtn').attr('data-phone', trainer.contact);
    }

    const disciplinesContainer = $('#tDisciplines');
    disciplinesContainer.empty();
    trainer.disciplines.forEach(d => {
        disciplinesContainer.append(`<span class="badge bg-light text-dark border px-3 py-2 me-2 mb-2 rounded-pill fw-normal"><i class="fa-solid fa-check text-success me-1"></i> ${d}</span>`);
    });

    // Initialize Favorite Button State
    let favs = [];
    if (window.Auth && window.Auth.isLoggedIn()) {
        try { favs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
    }
    if (favs.includes(trainer.id)) {
        $('#btnDetailFav').addClass('active').css('color', '#ef4444')
            .find('i').removeClass('fa-regular').addClass('fa-solid text-danger');
    }

    $('#btnDetailFav').on('click', function() {
        if (!window.Auth.requireAuth()) return;

        $(this).toggleClass('active');
        const icon = $(this).find('i');
        let currentFavs = [];
        try { currentFavs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
        
        if($(this).hasClass('active')) {
            icon.removeClass('fa-regular').addClass('fa-solid text-danger');
            $(this).css('color', '#ef4444');
            if(!currentFavs.includes(trainer.id)) currentFavs.push(trainer.id);
        } else {
            icon.removeClass('fa-solid text-danger').addClass('fa-regular');
            $(this).css('color', 'inherit');
            currentFavs = currentFavs.filter(id => id !== trainer.id);
        }
        localStorage.setItem('trainerFavorites', JSON.stringify(currentFavs));
    });

    $('#btnWriteReview').on('click', function() {
        if (!window.Auth.requireAuth()) return;
        alert("Chức năng viết đánh giá đang được phát triển.");
    });

});
