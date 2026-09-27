// assets/js/core/ui.js
window.UI = {
    renderGymCard: function(gym, isRoot = false) {
        let favs = [];
        try { favs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
        const isFav = favs.includes(gym.id);
        const heartIcon = isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart" style="color: #cbd5e1;"></i>';
        const heartStyle = isFav ? 'color: #ef4444;' : 'color: #cbd5e1;';
        const detailUrl = isRoot ? `pages/gyms/detail.html?id=${gym.id}` : `../gyms/detail.html?id=${gym.id}`;

        let tagsHtml = '';
        if (gym.tags) {
            gym.tags.forEach(t => { tagsHtml += `<span class="feature-tag">${t}</span>`; });
        }

        return `
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="gym-card bg-white h-100 d-flex flex-column rounded-4 border-0 shadow-sm" style="border: 1px solid #f1f5f9 !important;">
                    <div class="card-img-wrapper" style="height: 180px;">
                        <img src="${gym.image}" class="card-img-top" alt="${gym.name}" onerror="this.src='https://via.placeholder.com/400x300?text=Gym+Image'">
                        ${gym.badge ? gym.badge : ''}
                        <button class="btn-favorite bg-white d-flex align-items-center justify-content-center border-0 shadow-sm" data-id="${gym.id}" style="position:absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%;">
                            ${heartIcon}
                        </button>
                        ${gym.distance ? `<div class="position-absolute text-white px-2 py-1 rounded" style="bottom: 10px; left: 10px; font-size: 10px; font-weight: 700; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px);"><i class="fa-solid fa-location-arrow me-1"></i> ${gym.distance}</div>` : ''}
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center" style="font-size: 12px;">
                                <i class="fa-solid fa-star text-primary me-1"></i> 
                                <strong class="text-dark">${gym.rating}</strong> 
                                <span class="text-secondary ms-1">(${gym.reviewCount} đánh giá)</span>
                            </div>
                            <div class="badge bg-primary-light text-primary fw-bold" style="font-size: 10px;">${gym.district}</div>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark" style="font-size: 16px; line-height: 1.4;">${gym.name}</h5>
                        <p class="text-secondary mb-3 d-flex align-items-start" style="font-size: 12px; line-height: 1.5;">
                            <i class="fa-solid fa-location-dot mt-1 me-2 text-primary opacity-50"></i> 
                            <span class="text-truncate d-block w-100">${gym.address ? gym.address : gym.district}</span>
                        </p>
                        ${tagsHtml ? `<div class="mb-3 d-flex flex-wrap">${tagsHtml}</div>` : ''}
                        <div class="mt-auto pt-3 border-top">
                            <div class="mb-3">
                                <div class="text-secondary mb-1" style="font-size: 10px; font-weight: 600;">Khoảng giá tham khảo</div>
                                <div class="fw-bold text-dark" style="font-size: 14px;">${gym.price}</div>
                            </div>
                            <a href="${detailUrl}" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold d-flex justify-content-center align-items-center" style="font-size: 13px;">Xem chi tiết <i class="fa-solid fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderTrainerCard: function(trainer, isRoot = false) {
        let favs = [];
        try { favs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
        const isFav = favs.includes(trainer.id);
        const heartIcon = isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart" style="color: #cbd5e1;"></i>';
        const detailUrl = isRoot ? `pages/trainers/detail.html?id=${trainer.id}` : `../trainers/detail.html?id=${trainer.id}`;

        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="trainer-card bg-white h-100 d-flex flex-column rounded-4 border-0 shadow-sm p-4" style="border: 1px solid #f1f5f9 !important; position: relative;">
                    <button class="btn-trainer-favorite position-absolute border-0 bg-transparent" data-id="${trainer.id}" style="top: 15px; right: 15px; font-size: 20px; z-index: 2;">
                        ${heartIcon}
                    </button>
                    <div class="d-flex flex-column align-items-center text-center mb-3">
                        <img src="${trainer.avatar}" alt="${trainer.name}" class="rounded-circle object-fit-cover mb-3 shadow-sm" style="width: 100px; height: 100px; border: 3px solid #f8fafc;" onerror="this.src='https://placehold.co/100x100?text=Trainer';">
                        <h5 class="fw-bold mb-1">${trainer.name}</h5>
                        <div class="text-primary fw-medium small mb-2">${trainer.specialization}</div>
                        <div class="d-flex align-items-center justify-content-center gap-2 small text-secondary mb-1">
                            <div><i class="fa-solid fa-star text-warning"></i> <span class="text-dark fw-bold">${trainer.rating}</span> (${trainer.reviewCount})</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-3 small text-secondary">
                            <div><i class="fa-solid fa-briefcase opacity-75 me-1"></i> ${trainer.experience}</div>
                            <div><i class="fa-solid fa-location-dot opacity-75 me-1"></i> ${trainer.location}</div>
                        </div>
                    </div>
                    <p class="text-secondary small text-center mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${trainer.about}
                    </p>
                    <div class="d-flex gap-2 mt-auto">
                        <a href="${detailUrl}" class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold btn-trainer-view-profile" style="font-size: 14px; border-radius: 8px;">Xem hồ sơ</a>
                        <button class="btn btn-primary flex-grow-1 py-2 fw-semibold btn-trainer-contact" style="font-size: 14px; border-radius: 8px;"><i class="fa-solid fa-phone me-1"></i> Liên hệ</button>
                    </div>
                </div>
            </div>
        `;
    },

    bindFavoriteEvents: function() {
        // Gym favorites
        $(document).on('click', '.btn-favorite', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!window.Auth.requireAuth()) return;

            const gymId = parseInt($(this).data('id'));
            let favs = [];
            try { favs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
            
            const icon = $(this).find('i');
            if (icon.hasClass('text-danger')) {
                icon.removeClass('fa-solid text-danger').addClass('fa-regular').css('color', '#cbd5e1');
                favs = favs.filter(id => id !== gymId);
                
                // If on favorites page, remove the card immediately
                if (window.location.pathname.includes('favorites.html')) {
                    $(this).closest('.col-12').remove();
                    window.dispatchEvent(new Event('favoritesUpdated'));
                }
            } else {
                icon.removeClass('fa-regular').addClass('fa-solid text-danger').css('color', '#ef4444');
                if (!favs.includes(gymId)) favs.push(gymId);
            }
            localStorage.setItem('gymFavorites', JSON.stringify(favs));
        });

        // Trainer favorites
        $(document).on('click', '.btn-trainer-favorite', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!window.Auth.requireAuth()) return;

            const trainerId = parseInt($(this).data('id'));
            let favs = [];
            try { favs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
            
            const icon = $(this).find('i');
            if (icon.hasClass('text-danger')) {
                icon.removeClass('fa-solid text-danger').addClass('fa-regular').css('color', '#cbd5e1');
                favs = favs.filter(id => id !== trainerId);
                
                // If on favorites page, remove the card immediately
                if (window.location.pathname.includes('favorites.html')) {
                    $(this).closest('.col-12').remove();
                    window.dispatchEvent(new Event('favoritesUpdated'));
                }
            } else {
                icon.removeClass('fa-regular').addClass('fa-solid text-danger').css('color', '#ef4444');
                if (!favs.includes(trainerId)) favs.push(trainerId);
            }
            localStorage.setItem('trainerFavorites', JSON.stringify(favs));
        });
        
        // Trainer Contact
        $(document).on('click', '.btn-trainer-contact', function(e) {
            e.preventDefault();
            if (!window.Auth.requireAuth()) return;
            alert('Yêu cầu liên hệ đã được gửi đến Huấn luyện viên! Chúng tôi sẽ gọi lại cho bạn sớm nhất.');
        });

        // Trainer View Profile
        $(document).on('click', '.btn-trainer-view-profile', function(e) {
            if (!window.Auth.isLoggedIn()) {
                e.preventDefault();
                window.Auth.requireAuth();
            }
        });
    }
};

$(document).ready(function() {
    window.UI.bindFavoriteEvents();
});
