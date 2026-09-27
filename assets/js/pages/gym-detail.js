// assets/js/pages/gym-detail.js
$(document).ready(function() {
    
    // Get ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    let gymId = parseInt(urlParams.get('id'));

    if (isNaN(gymId)) {
        $('#breadcrumbGymName').text('Không tìm thấy');
        $('#gymName').text('Không tìm thấy phòng tập');
        return;
    }

    const gym = gymsData.find(g => g.id === gymId);

    if (!gym) {
        $('#breadcrumbGymName').text('Không tìm thấy');
        $('#gymName').text('Phòng tập không tồn tại');
        return;
    }

    // Populate Data
    $('#breadcrumbGymName').text(gym.name);
    $('#gymName').text(gym.name);
    $('#gymRating').text(gym.rating);
    $('#gymReviewCount').text(`(${gym.reviewCount} đánh giá)`);
    $('#gymAddress').text(gym.address);
    $('#gymPrice').text(gym.price);
    $('#gymCategory').text(gym.category);
    $('#gymDescription').text(gym.description);
    
    $('#gymOpeningHours').text(gym.openingHours);
    $('#gymCategoryDetail').text(gym.category);
    $('#gymDistrictDetail').text(gym.district);
    $('#gymPriceDetail').text(gym.price);

    // Populate Facilities
    const facilitiesList = $('#gymFacilities');
    facilitiesList.empty();
    gym.facilities.forEach(fac => {
        facilitiesList.append(`<li class="mb-2"><i class="fa-solid fa-check text-primary me-2"></i> ${fac}</li>`);
    });

    // Populate Images
    $('#mainImage').attr('src', gym.images[0]).attr('onerror', "this.src='https://placehold.co/800x400?text=Gym'");
    const thumbContainer = $('#thumbnailContainer');
    thumbContainer.empty();
    gym.images.slice(1, 5).forEach((img, index) => {
        let extraOverlay = '';
        if(index === 3 && gym.images.length > 5) {
            extraOverlay = `<div class="position-absolute w-100 h-100 top-0 start-0 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); color: white; font-weight: bold; font-size: 18px; cursor: pointer;">+${gym.images.length - 5}</div>`;
        }
        
        thumbContainer.append(`
            <div class="col-3 position-relative">
                <img src="${img}" class="img-fluid rounded gym-thumb" style="height: 80px; width: 100%; object-fit: cover; cursor: pointer;" onclick="changeMainImage('${img}')" onerror="this.src='https://placehold.co/100x80?text=Gym'">
                ${extraOverlay}
            </div>
        `);
    });

    window.changeMainImage = function(src) {
        $('#mainImage').attr('src', src);
    };

    // Populate Trainers
    const trainersContainer = $('#trainersContainer');
    trainersContainer.empty();
    gym.trainers.forEach(trainer => {
        trainersContainer.append(`
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="${trainer.avatar}" class="rounded-circle object-fit-cover" width="60" height="60" alt="${trainer.name}" onerror="this.src='https://placehold.co/60x60?text=Trainer'">
                        <div>
                            <h5 class="fw-bold m-0" style="font-size: 16px;">${trainer.name}</h5>
                            <div class="text-secondary" style="font-size: 13px;">${trainer.role}</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-secondary" style="font-size: 13px;">
                        <span><i class="fa-solid fa-star text-warning"></i> ${trainer.rating}</span>
                        <span><i class="fa-solid fa-clock text-primary opacity-75"></i> ${trainer.exp}</span>
                    </div>
                    <button class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold btn-detail-auth" data-url="../trainers/detail.html?id=${trainer.id}">Xem hồ sơ</button>
                </div>
            </div>
        `);
    });

    // Populate Reviews
    const reviewsContainer = $('#reviewsContainer');
    reviewsContainer.empty();
    gym.reviews.forEach(review => {
        let stars = '';
        for(let i=0; i<5; i++) {
            stars += `<i class="fa-solid fa-star ${i < review.rating ? 'text-warning' : 'text-light'}"></i>`;
        }
        reviewsContainer.append(`
            <div class="border-bottom py-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="fw-bold text-dark">${review.name}</div>
                    <div class="text-secondary" style="font-size: 12px;">${review.date}</div>
                </div>
                <div class="mb-2" style="font-size: 12px;">${stars}</div>
                <p class="text-secondary m-0" style="font-size: 14px;">"${review.text}"</p>
            </div>
        `);
    });

    // Initialize Favorite Button State
    let favs = [];
    try { favs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
    if (favs.includes(gym.id)) {
        $('#btnFavorite').addClass('active').css('color', '#ef4444')
            .find('i').removeClass('fa-regular').addClass('fa-solid text-danger');
    }

    $('#btnFavorite').on('click', function() {
        if (!window.Auth.requireAuth()) return;

        $(this).toggleClass('active');
        const icon = $(this).find('i');
        let currentFavs = [];
        try { currentFavs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
        
        if($(this).hasClass('active')) {
            icon.removeClass('fa-regular').addClass('fa-solid text-danger');
            $(this).css('color', '#ef4444');
            if(!currentFavs.includes(gym.id)) currentFavs.push(gym.id);
        } else {
            icon.removeClass('fa-solid text-danger').addClass('fa-regular');
            $(this).css('color', 'inherit');
            currentFavs = currentFavs.filter(id => id !== gym.id);
        }
        localStorage.setItem('gymFavorites', JSON.stringify(currentFavs));
    });

    $('#btnWriteReview').on('click', function() {
        if (!window.Auth.requireAuth()) return;
        alert('Hiển thị form viết đánh giá...');
    });

    // Related cards Auth logic
    $(document).on('click', '.btn-detail-auth', function(e) {
        e.preventDefault();
        const url = $(this).attr('data-url');
        if (window.Auth.requireAuth(url)) {
            window.location.href = url;
        }
    });

});
