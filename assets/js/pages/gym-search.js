// assets/js/pages/gym-list.js
$(document).ready(function() {
    
    const container = $('#gymsListingContainer');
    const urlParams = new URLSearchParams(window.location.search);
    const categoryFilter = urlParams.get('category');
    const keywordFilter = urlParams.get('keyword');
    
    if (container.length && typeof gymsData !== 'undefined') {
        let filteredGyms = gymsData;
        
        // Filter by category slug if present
        if (categoryFilter && typeof categoriesData !== 'undefined') {
            const selectedCategory = categoriesData.find(c => c.slug === categoryFilter);
            if (selectedCategory) {
                filteredGyms = filteredGyms.filter(g => g.categoryId === selectedCategory.id);
                
                // Automatically select the checkbox in the UI
                $(`#cat${selectedCategory.id}`).prop('checked', true);
                
                // Show a small active filter pill in the UI (we can add it to the filter section if it exists, or above the results)
                const filterTitle = $('.filter-title');
                if (filterTitle.length) {
                    filterTitle.after(`
                        <div class="mb-3">
                            <span class="badge bg-primary-light text-primary border p-2 fw-medium d-inline-flex align-items-center gap-2">
                                Đang lọc: ${selectedCategory.name}
                                <a href="search.html" class="text-primary text-decoration-none ms-1"><i class="fa-solid fa-xmark"></i></a>
                            </span>
                        </div>
                    `);
                }
            }
        }
        
        // Filter by keyword if present
        if (keywordFilter) {
            const kw = keywordFilter.toLowerCase();
            filteredGyms = filteredGyms.filter(g => 
                g.name.toLowerCase().includes(kw) || 
                (g.district && g.district.toLowerCase().includes(kw))
            );
        }

        if (filteredGyms.length === 0) {
            container.append(`
                <div class="col-12 text-center py-5">
                    <div class="text-secondary mb-3">
                        <i class="fa-solid fa-store-slash" style="font-size: 48px;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Không tìm thấy phòng tập</h4>
                    <p class="text-secondary mb-4">Vui lòng thay đổi từ khóa hoặc bộ lọc của bạn.</p>
                    <a href="search.html" class="btn btn-outline-primary rounded-pill px-4">Xóa tất cả bộ lọc</a>
                </div>
            `);
        } else {
            filteredGyms.forEach(gym => {
                container.append(window.UI.renderGymCard(gym, false));
            });
        }
    }

    // Handle filter pills
    $('.filter-pill').on('click', function(e) {
        if(!$(e.target).hasClass('btn-close-pill')) {
            $(this).toggleClass('active');
        }
    });

    // Handle close pill click
    $('.btn-close-pill').on('click', function(e) {
        e.stopPropagation();
        $(this).parent('.filter-pill').removeClass('active');
    });

});
