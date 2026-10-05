// assets/js/pages/gym-search.js
$(document).ready(function() {
    const container = $('#gymsListingContainer');
    
    // Check if we have data
    if (container.length === 0 || typeof gymsData === 'undefined') return;

    // Define amenity mappings (based on HTML IDs)
    const amenityMap = {
        'amenity1': 'Xông hơi khô',
        'amenity2': 'Xông hơi ướt',
        'amenity3': 'Locker',
        'amenity4': 'Bãi đỗ xe',
        'amenity5': 'Điều hòa',
        'amenity6': 'Phòng tắm',
        'amenity7': 'Nước uống miễn phí'
    };

    // State
    let currentKeyword = '';

    // Initialize from URL (if coming from another page)
    const urlParams = new URLSearchParams(window.location.search);
    const initialKeyword = urlParams.get('keyword');
    const initialDistrict = urlParams.get('district');
    const initialPrice = urlParams.get('price'); // '500' or '1000'
    const initialCategory = urlParams.get('category'); // slug

    if (initialKeyword) {
        currentKeyword = initialKeyword;
        $('#searchInput').val(currentKeyword);
    }
    if (initialDistrict) {
        $('#districtFilter').val(initialDistrict);
    }
    if (initialPrice) {
        if (initialPrice === '500') {
            $('#priceMax').val(500000);
        } else if (initialPrice === '1000') {
            $('#priceMin').val(500000);
            $('#priceMax').val(1000000);
        }
    }
    if (initialCategory && typeof categoriesData !== 'undefined') {
        const cat = categoriesData.find(c => c.slug === initialCategory);
        if (cat) {
            $(`#cat${cat.id}`).prop('checked', true);
        }
    }

    // Main Filter Function
    function applyFilters() {
        let filtered = gymsData;
        const chips = [];

        // 1. Keyword
        if (currentKeyword) {
            const kw = currentKeyword.toLowerCase();
            filtered = filtered.filter(g => 
                g.name.toLowerCase().includes(kw) || 
                (g.district && g.district.toLowerCase().includes(kw))
            );
            // We don't make a chip for keyword as per spec: "Không tạo chip cho: Search keyword nếu project hiện tại đang xử lý keyword riêng."
        }

        // 2. District
        const district = $('#districtFilter').val();
        if (district) {
            filtered = filtered.filter(g => g.district === district);
            chips.push({ id: 'chip-district', text: district, type: 'select', targetId: 'districtFilter' });
        }

        // 3. Price
        const minPriceStr = $('#priceMin').val();
        const maxPriceStr = $('#priceMax').val();
        const minP = minPriceStr ? parseInt(minPriceStr) : null;
        const maxP = maxPriceStr ? parseInt(maxPriceStr) : null;

        if (minP !== null || maxP !== null) {
            filtered = filtered.filter(g => {
                if (!g.price) return false;
                const matches = g.price.match(/\d+(\.\d+)?/g);
                if (!matches || matches.length === 0) return true;
                const getVal = (str) => parseInt(str.replace(/\./g, ''));
                const gMin = getVal(matches[0]);
                const gMax = matches.length > 1 ? getVal(matches[1]) : gMin;
                
                if (minP !== null && gMax < minP) return false;
                if (maxP !== null && gMin > maxP) return false;
                return true;
            });
            
            let priceText = '';
            if (minP !== null && maxP !== null) priceText = `${minP.toLocaleString('vi-VN')} - ${maxP.toLocaleString('vi-VN')}đ`;
            else if (minP !== null) priceText = `Từ ${minP.toLocaleString('vi-VN')}đ`;
            else if (maxP !== null) priceText = `Đến ${maxP.toLocaleString('vi-VN')}đ`;
            
            chips.push({ id: 'chip-price', text: priceText, type: 'price' });
        }

        // 4. Rating
        const ratingInput = $('input[name="rating"]:checked');
        if (ratingInput.length && ratingInput.val() !== 'on' && ratingInput.attr('id') !== 'rating0') { // rating0 is "Tất cả"
            const ratingVal = parseFloat(ratingInput.val());
            filtered = filtered.filter(g => parseFloat(g.rating) >= ratingVal);
            chips.push({ 
                id: 'chip-rating', 
                text: `${ratingVal}★ trở lên`, 
                type: 'radio', 
                targetName: 'rating',
                defaultId: 'rating0'
            });
        }

        // 5. Categories
        $('.filter-category:checked').each(function() {
            const catId = parseInt($(this).val());
            const catName = $(this).next('label').text().trim();
            // Assuming multiple categories act as OR (or AND depending on strictness. We'll do OR for categories)
            // Wait, GYMFINDER gyms have a single categoryId in data.
            // But UI allows checkboxes. Let's do OR for category.
            // Wait, we need to filter the whole set at the end for categories if we do OR.
            // Let's just collect allowed categories.
            chips.push({ id: `chip-cat-${catId}`, text: catName, type: 'checkbox', targetId: $(this).attr('id') });
        });
        
        const checkedCats = $('.filter-category:checked').map(function(){ return parseInt($(this).val()); }).get();
        if (checkedCats.length > 0) {
            filtered = filtered.filter(g => checkedCats.includes(g.categoryId));
        }

        // 6. Amenities
        const requiredAmenities = [];
        for (const [amenityId, amenityName] of Object.entries(amenityMap)) {
            if ($(`#${amenityId}`).is(':checked')) {
                requiredAmenities.push(amenityName);
                chips.push({ id: `chip-amenity-${amenityId}`, text: amenityName, type: 'checkbox', targetId: amenityId });
            }
        }
        
        if (requiredAmenities.length > 0) {
            filtered = filtered.filter(g => {
                if (!g.facilities) return false;
                // AND condition: must have all selected amenities
                return requiredAmenities.every(a => g.facilities.includes(a));
            });
        }

        // Render Chips
        renderChips(chips);

        // Render Results
        renderResults(filtered);
    }

    function renderChips(chips) {
        const chipsContainer = $('#activeFilterChips');
        const wrapper = $('#activeFilterChipsContainer');
        
        chipsContainer.empty();
        
        if (chips.length > 0) {
            wrapper.removeClass('d-none');
            chips.forEach(chip => {
                const chipHtml = `
                    <span class="badge bg-primary-light text-primary border border-primary-subtle px-3 py-2 fw-medium rounded-pill d-inline-flex align-items-center gap-2" style="font-size: 13px;">
                        ${chip.text}
                        <i class="fa-solid fa-xmark chip-remove" style="cursor: pointer; opacity: 0.7;" data-type="${chip.type}" data-target="${chip.targetId || ''}" data-name="${chip.targetName || ''}" data-default="${chip.defaultId || ''}"></i>
                    </span>
                `;
                chipsContainer.append(chipHtml);
            });
        } else {
            wrapper.addClass('d-none');
        }
    }

    function renderResults(gyms) {
        container.empty();
        $('#resultCountText').text(`${gyms.length} phòng tập được tìm thấy`);

        if (gyms.length === 0) {
            container.append(`
                <div class="col-12 text-center py-5">
                    <div class="text-secondary mb-3">
                        <i class="fa-solid fa-store-slash" style="font-size: 48px;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Không tìm thấy phòng tập</h4>
                    <p class="text-secondary mb-4">Vui lòng thay đổi từ khóa hoặc bộ lọc của bạn.</p>
                </div>
            `);
        } else {
            gyms.forEach(gym => {
                container.append(window.UI.renderGymCard(gym, false));
            });
        }
    }

    // Initialize initial render
    applyFilters();

    // ---------------------------------
    // Events
    // ---------------------------------

    // Top Search
    $('#btnSearch').on('click', function() {
        currentKeyword = $('#searchInput').val().trim();
        applyFilters();
    });
    $('#searchInput').on('keypress', function(e) {
        if (e.which == 13) {
            currentKeyword = $(this).val().trim();
            applyFilters();
        }
    });

    // Sidebar Filters Update (Button only)
    $('#btnFilter').on('click', function() {
        applyFilters();
    });

    // Clear All Filters
    $('#btnClearFilter, #btnClearAllFilters').on('click', function(e) {
        e.preventDefault();
        
        // Reset UI
        $('#districtFilter').val('');
        $('#priceMin').val('');
        $('#priceMax').val('');
        $('#rating0').prop('checked', true); // Check "Tất cả"
        $('.filter-category').prop('checked', false);
        $('input[id^="amenity"]').prop('checked', false);
        
        applyFilters();
    });

    // Remove single chip
    $(document).on('click', '.chip-remove', function() {
        const type = $(this).data('type');
        
        if (type === 'select') {
            const targetId = $(this).data('target');
            $(`#${targetId}`).val('');
        } else if (type === 'price') {
            $('#priceMin').val('');
            $('#priceMax').val('');
        } else if (type === 'checkbox') {
            const targetId = $(this).data('target');
            $(`#${targetId}`).prop('checked', false);
        } else if (type === 'radio') {
            const defaultId = $(this).data('default');
            $(`#${defaultId}`).prop('checked', true);
        }

        applyFilters();
    });

});
