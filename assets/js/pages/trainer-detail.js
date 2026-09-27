// assets/js/pages/trainer-detail.js
$(document).ready(function() {
    
    // Get ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    let trainerId = parseInt(urlParams.get('id'));

    // Block Guest Access
    if (!window.Auth.isLoggedIn()) {
        window.Auth.requireAuth();
        // Redirect back to trainer list after a short delay so they don't stay on the blank detail page
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);
        return;
    }

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
    $('#tLocation').text(trainer.location);
    $('#tExp').text(trainer.experience);
    $('#tSpec').text(trainer.specialization);
    $('#tAbout').text(trainer.about);
    $('#tStyle').text(trainer.trainingStyle);

    const disciplinesContainer = $('#tDisciplines');
    disciplinesContainer.empty();
    trainer.disciplines.forEach(d => {
        disciplinesContainer.append(`<span class="badge bg-light text-dark border px-3 py-2 me-2 mb-2 rounded-pill fw-normal"><i class="fa-solid fa-check text-success me-1"></i> ${d}</span>`);
    });

    // Initialize Favorite Button State
    let favs = [];
    try { favs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
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

});
