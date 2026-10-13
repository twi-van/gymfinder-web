// assets/js/pages/home.js
$(document).ready(function() {
    

    // 2. Render Featured Gyms (First 4)
    if (typeof gymsData !== 'undefined') {
        const gymContainer = $('#gymContainer');
        gymsData.slice(0, 4).forEach(gym => {
            gymContainer.append(window.UI.renderGymCard(gym, true));
        });
    }

    // 3. Render Featured Trainers (First 3)
    if (typeof trainersData !== 'undefined') {
        const trainerContainer = $('#trainerContainer');
        trainersData.slice(0, 3).forEach(trainer => {
            trainerContainer.append(window.UI.renderTrainerCard(trainer, true));
        });
    }
});
