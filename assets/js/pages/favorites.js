// assets/js/pages/favorites.js
$(document).ready(function() {
    
    // Check if logged in
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';

    if (!isLoggedIn) {
        // Show guest state
        $('#guestState').removeClass('d-none');
        $('#userState').addClass('d-none');
        window.Auth.requireAuth();
        return;
    }

    // User is logged in, show user state
    $('#guestState').addClass('d-none');
    $('#userState').removeClass('d-none');

    // Render Data
    function loadFavorites() {
        // Load Gyms
        let gymFavs = [];
        try { gymFavs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
        
        // Load Trainers
        let trainerFavs = [];
        try { trainerFavs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
        
        let validGymFavs = [];
        gymFavs.forEach(id => {
            const gym = gymsData.find(g => g.id === id);
            if(gym) validGymFavs.push(gym);
        });

        let validTrainerFavs = [];
        trainerFavs.forEach(id => {
            const trainer = trainersData.find(t => t.id === id);
            if(trainer) validTrainerFavs.push(trainer);
        });

        // Global Empty State Toggle
        if (validGymFavs.length === 0 && validTrainerFavs.length === 0) {
            $('#emptyFavoritesState').removeClass('d-none');
            $('#favoriteTabs').addClass('d-none');
            $('#favoriteTabsContent').addClass('d-none');
            return;
        } else {
            $('#emptyFavoritesState').addClass('d-none');
            $('#favoriteTabs').removeClass('d-none');
            $('#favoriteTabsContent').removeClass('d-none');
        }

        // Render Gyms
        const favGymsContainer = $('#favGymsContainer');
        favGymsContainer.empty();
        if (validGymFavs.length === 0) {
            favGymsContainer.html(`
                <div class="col-12 text-center py-5">
                    <p class="text-secondary mb-0">Chưa có phòng tập yêu thích.</p>
                </div>
            `);
        } else {
            validGymFavs.forEach(gym => {
                favGymsContainer.append(window.UI.renderGymCard(gym, false));
            });
        }

        // Render Trainers
        const favTrainersContainer = $('#favTrainersContainer');
        favTrainersContainer.empty();
        if (validTrainerFavs.length === 0) {
            favTrainersContainer.html(`
                <div class="col-12 text-center py-5">
                    <p class="text-secondary mb-0">Chưa có huấn luyện viên yêu thích.</p>
                </div>
            `);
        } else {
            validTrainerFavs.forEach(trainer => {
                favTrainersContainer.append(window.UI.renderTrainerCard(trainer, false));
            });
        }
    }

    loadFavorites();

    // Re-render empty states when a card is removed
    window.addEventListener('favoritesUpdated', function() {
        let gymFavs = [];
        try { gymFavs = JSON.parse(localStorage.getItem('gymFavorites')) || []; } catch(e) {}
        
        let trainerFavs = [];
        try { trainerFavs = JSON.parse(localStorage.getItem('trainerFavorites')) || []; } catch(e) {}
        
        const favGymsContainer = $('#favGymsContainer');
        const favTrainersContainer = $('#favTrainersContainer');

        // Check gyms
        let hasGyms = false;
        gymFavs.forEach(id => {
            if(gymsData.find(g => g.id === id)) hasGyms = true;
        });

        if (!hasGyms && favGymsContainer.children('.col-12:not(.text-center)').length === 0) {
            favGymsContainer.html(`
                <div class="col-12 text-center py-5">
                    <p class="text-secondary mb-0">Chưa có phòng tập yêu thích.</p>
                </div>
            `);
        }

        // Check trainers
        let hasTrainers = false;
        trainerFavs.forEach(id => {
            if(trainersData.find(t => t.id === id)) hasTrainers = true;
        });

        if (!hasTrainers && favTrainersContainer.children('.col-12:not(.text-center)').length === 0) {
            favTrainersContainer.html(`
                <div class="col-12 text-center py-5">
                    <p class="text-secondary mb-0">Chưa có huấn luyện viên yêu thích.</p>
                </div>
            `);
        }

        // Global Empty State
        if (!hasGyms && !hasTrainers) {
            $('#emptyFavoritesState').removeClass('d-none');
            $('#favoriteTabs').addClass('d-none');
            $('#favoriteTabsContent').addClass('d-none');
        }
    });
});
