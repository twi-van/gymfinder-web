// assets/js/core/ui.js
window.UI = {
    renderGymCard: function(gym, isRoot = false) {
        let favs = [];
        if (window.Auth && window.Auth.isLoggedIn()) {
            try { favs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
        }
        const isFav = favs.includes(gym.id);
        const heartIcon = isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart" style="color: #cbd5e1;"></i>';
        const detailUrl = isRoot ? `pages/gyms/detail.html?id=${gym.id}` : `../gyms/detail.html?id=${gym.id}`;

        // Badge Logic
        let badgeHtml = '';
        if (gym.categoryId === 3) badgeHtml = '<div class="position-absolute badge bg-danger text-white px-2 py-1 rounded" style="top: 10px; left: 10px; font-size: 11px; font-weight: 600;">24/7</div>';
        else if (gym.categoryId === 2) badgeHtml = '<div class="position-absolute badge bg-dark text-warning px-2 py-1 rounded" style="top: 10px; left: 10px; font-size: 11px; font-weight: 600;"><i class="fa-solid fa-crown me-1"></i>Cao cấp</div>';
        else if (gym.categoryId === 7) badgeHtml = '<div class="position-absolute badge bg-info text-white px-2 py-1 rounded" style="top: 10px; left: 10px; font-size: 11px; font-weight: 600;"><i class="fa-solid fa-person-dress me-1"></i>Cho nữ</div>';

        // Extract and format facilities + PT
        let allTags = [];
        if (gym.facilities && gym.facilities.length > 0) {
            allTags = [...gym.facilities];
        }
        // Check for PT
        if (typeof trainersData !== 'undefined') {
            const gymTrainers = trainersData.filter(t => t.gymId === gym.id);
            if (gymTrainers.length > 0) allTags.push('Có PT');
        } else if (gym.categoryId === 5) {
            allTags.push('Có PT');
        }

        let amenitiesHtml = '';
        if (allTags.length > 0) {
            const topTags = allTags.slice(0, 3);
            topTags.forEach(a => {
                amenitiesHtml += `<span class="badge bg-light text-secondary border fw-normal" style="font-size: 11px;">${a}</span>`;
            });
            if (allTags.length > 3) {
                amenitiesHtml += `<span class="badge bg-light text-secondary border fw-normal" style="font-size: 11px;">+${allTags.length - 3} tiện ích</span>`;
            }
        }

        const ratingDisplay = gym.rating ? `<i class="fa-solid fa-star text-warning me-1" style="font-size: 12px;"></i><strong class="text-dark">${gym.rating}</strong>` : '<span class="text-secondary" style="font-size: 13px;">Chưa có đánh giá</span>';
        const reviewCountDisplay = gym.reviewCount ? `<span class="text-secondary ms-1">(${gym.reviewCount} đánh giá)</span>` : '';
        const priceDisplay = gym.price ? gym.price : `<a href="#" class="text-primary text-decoration-none btn-gym-contact" style="position: relative; z-index: 2;">Liên hệ</a>`;
        const imageDisplay = gym.image ? gym.image : 'https://placehold.co/400x300?text=Gym+Image';

        return `
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="gym-card bg-white h-100 d-flex flex-column rounded-4 border-0 shadow-sm position-relative" style="border: 1px solid #f1f5f9 !important;">
                    <div class="card-img-wrapper position-relative" style="height: 180px;">
                        <img src="${imageDisplay}" class="card-img-top w-100 h-100 object-fit-cover rounded-top-4" alt="${gym.name}" onerror="this.src='https://placehold.co/400x300?text=Gym+Image'">
                        ${badgeHtml}
                        <button class="btn-favorite bg-white d-flex align-items-center justify-content-center border-0 shadow-sm" data-id="${gym.id}" style="position:absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; z-index: 2;" aria-label="Favorite">
                            ${heartIcon}
                        </button>
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <h5 class="fw-bold mb-2 text-dark" style="font-size: 16px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 44px;">
                            <a href="${detailUrl}" class="text-decoration-none text-dark stretched-link">${gym.name}</a>
                        </h5>
                        
                        <div class="d-flex align-items-center mb-2" style="font-size: 13px;">
                            ${ratingDisplay} ${reviewCountDisplay}
                        </div>
                        
                        <p class="text-secondary mb-3 d-flex align-items-start" style="font-size: 13px;">
                            <i class="fa-solid fa-location-dot mt-1 me-2 text-primary opacity-75"></i> 
                            <span>${gym.district}, TP.HCM</span>
                        </p>
                        
                        <div class="fw-bold text-dark mb-3" style="font-size: 14px;">
                            ${priceDisplay}
                        </div>
                        
                        ${amenitiesHtml ? `<div class="mb-3 d-flex flex-wrap gap-1 position-relative" style="z-index: 2;">${amenitiesHtml}</div>` : ''}
                        
                        <div class="mt-auto pt-3 border-top position-relative" style="z-index: 2;">
                            <a href="${detailUrl}" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold d-flex justify-content-center align-items-center" style="font-size: 13px;">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    renderTrainerCard: function(trainer, isRoot = false) {
        let favs = [];
        if (window.Auth && window.Auth.isLoggedIn()) {
            try { favs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
        }
        const isFav = favs.includes(trainer.id);
        const heartIcon = isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart" style="color: #cbd5e1;"></i>';
        const detailUrl = isRoot ? `pages/trainers/detail.html?id=${trainer.id}` : `../trainers/detail.html?id=${trainer.id}`;
        
        let specString = Array.isArray(trainer.specialization) ? trainer.specialization.join(', ') : trainer.specialization;
        
        let address = 'Không xác định';
        if (trainer.gymId && typeof gymsData !== 'undefined') {
            const gym = gymsData.find(g => g.id === trainer.gymId);
            if (gym) address = gym.address;
        } else if (trainer.location) {
            address = trainer.location;
        }

        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="trainer-card bg-white h-100 d-flex flex-column rounded-4 border-0 shadow-sm p-4" style="border: 1px solid #f1f5f9 !important; position: relative;">
                    <button class="btn-trainer-favorite position-absolute border-0 bg-transparent" data-id="${trainer.id}" style="top: 15px; right: 15px; font-size: 20px; z-index: 2;">
                        ${heartIcon}
                    </button>
                    <div class="d-flex flex-column align-items-center text-center mb-3">
                        <img src="${trainer.avatar}" alt="${trainer.name}" class="rounded-circle object-fit-cover mb-3 shadow-sm" style="width: 100px; height: 100px; border: 3px solid #f8fafc;" onerror="this.src='https://placehold.co/100x100?text=Trainer';">
                        <h5 class="fw-bold mb-1">${trainer.name}</h5>
                        <div class="text-primary fw-medium small mb-2">${specString}</div>
                        <div class="d-flex align-items-center justify-content-center gap-2 small text-secondary mb-1">
                            <div><i class="fa-solid fa-star text-warning"></i> <span class="text-dark fw-bold">${trainer.rating}</span> (${trainer.reviewCount})</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-3 small text-secondary">
                            <div><i class="fa-solid fa-briefcase opacity-75 me-1"></i> ${trainer.experience}</div>
                            <div class="text-truncate" style="max-width: 150px;" title="${address}"><i class="fa-solid fa-location-dot opacity-75 me-1"></i> ${address}</div>
                        </div>
                    </div>
                    <p class="text-secondary small text-center mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${trainer.about}
                    </p>
                    <div class="d-flex gap-2 mt-auto">
                        <a href="${detailUrl}" class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold btn-trainer-view-profile" style="font-size: 14px; border-radius: 8px;">Xem hồ sơ</a>
                        <button class="btn btn-primary flex-grow-1 py-2 fw-semibold btn-global-contact" data-phone="${trainer.contact || '0901 234 567'}" style="font-size: 14px; border-radius: 8px;"><i class="fa-solid fa-phone me-1"></i> Liên hệ</button>
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
        
        // Global Contact Button Logic
        $(document).on('click', '.btn-global-contact', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!window.Auth.requireAuth()) return;
            
            const phone = $(this).data('phone') || '1900 1508';
            const originalClasses = $(this).attr('class').replace('btn-primary', 'btn-success text-white'); // Make it green to indicate success
            
            $(this).replaceWith(`<a href="tel:${phone.replace(/\s+/g, '')}" class="${originalClasses} text-decoration-none d-flex justify-content-center align-items-center"><i class="fa-solid fa-phone me-2"></i> ${phone}</a>`);
        });

        // Gym Contact
        $(document).on('click', '.btn-gym-contact', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!window.Auth.requireAuth()) return;
            
            $(this).html('<i class="fa-solid fa-phone me-1"></i> 1900 1508')
                   .removeClass('text-primary')
                   .addClass('text-success fw-bold');
        });

    }
};

$(document).ready(function() {
    window.UI.bindFavoriteEvents();
});
