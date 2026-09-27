// assets/js/pages/trainer-list.js
$(document).ready(function() {
    // 1. Render Trainer List
    const trainerListContainer = $('#trainerListContainer');
    if (trainerListContainer.length && typeof trainersData !== 'undefined') {
        renderTrainers(trainersData);

    // Handle Search/Filter
    $('#trainerSearchForm').on('submit', function(e) {
        e.preventDefault();
        const keyword = $('input[name="keyword"]').val().toLowerCase();
        const specialization = $('select[name="specialization"]').val().toLowerCase();
        const location = $('select[name="location"]').val();
        const experience = parseInt($('select[name="experience"]').val()) || 0;
        const rating = parseFloat($('select[name="rating"]').val()) || 0;

        const filtered = trainersData.filter(t => {
            const matchKeyword = t.name.toLowerCase().includes(keyword) || t.specialization.toLowerCase().includes(keyword);
            const matchSpec = specialization ? t.specialization.toLowerCase().includes(specialization) : true;
            const matchLocation = location ? t.location.includes(location) : true;
            
            // parse trainer experience string e.g. "5 năm kinh nghiệm"
            const tExperience = parseInt(t.experience) || 0;
            const matchExperience = experience > 0 ? (tExperience >= experience) : true;
            
            const matchRating = rating > 0 ? (t.rating >= rating) : true;
            
            return matchKeyword && matchSpec && matchLocation && matchExperience && matchRating;
        });

        renderTrainers(filtered);
    });

        // Handle Clear Filters
        $('#btnClearFilters').on('click', function() {
            $('#trainerSearchForm')[0].reset();
            renderTrainers(trainersData);
        });
    }

    function renderTrainers(data) {
        const trainerListContainer = $('#trainerListContainer');
        trainerListContainer.empty();
        if (data.length === 0) {
            trainerListContainer.append(`
                <div class="col-12 text-center py-5">
                    <div class="text-secondary mb-3">
                        <i class="fa-solid fa-users-slash" style="font-size: 48px;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Không tìm thấy huấn luyện viên</h4>
                    <p class="text-secondary mb-4">Vui lòng thay đổi từ khóa hoặc bộ lọc của bạn.</p>
                    <button class="btn btn-outline-primary rounded-pill px-4" onclick="$('#btnClearFilters').click()">Xóa bộ lọc</button>
                </div>
            `);
            return;
        }

        data.forEach(trainer => {
            trainerListContainer.append(window.UI.renderTrainerCard(trainer, false));
        });
    }
});
