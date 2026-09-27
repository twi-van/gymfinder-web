// admin/assets/js/admin.js

$(document).ready(function() {
    
    // 1. Authentication Guard (Mock)
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    const currentUser = localStorage.getItem('currentUser');
    
    // In a real app, you would check admin role. For now, we allow access for demo purposes.
    // if (!isLoggedIn || currentUser !== 'admin') {
    //     alert('Truy cập bị từ chối.');
    //     window.location.href = '../../pages/auth/login.html';
    //     return;
    // }

    // 2. Sidebar Toggle (Mobile)
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').addClass('show');
        $('#sidebarOverlay').addClass('show');
    });

    $('#sidebarOverlay').on('click', function() {
        $('#sidebar').removeClass('show');
        $('#sidebarOverlay').removeClass('show');
    });

    // 3. Logout
    $('#btnAdminLogout').on('click', function() {
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('currentUser');
        window.location.href = '../../index.html';
    });

    // --- SHARED MODAL LOGIC ---
    window.showModal = function(title, content) {
        $('#sharedModalTitle').text(title);
        $('#sharedModalBody').html(`<p class="mb-0 text-secondary">${content}</p>`);
        const modal = new bootstrap.Modal(document.getElementById('sharedAdminModal'));
        modal.show();
    };

    window.confirmDelete = function(itemName) {
        $('#sharedModalTitle').text('Xác nhận xóa');
        $('#sharedModalBody').html(`<p class="mb-0 text-secondary">Bạn có chắc chắn muốn xóa <strong>${itemName}</strong> không? Hành động này không thể hoàn tác.</p>`);
        
        // Change confirm button to danger
        const confirmBtn = $('#sharedModalConfirm');
        confirmBtn.removeClass('btn-primary').addClass('btn-danger').text('Xóa');
        
        const modal = new bootstrap.Modal(document.getElementById('sharedAdminModal'));
        modal.show();
        
        // Reset button on hide
        $('#sharedAdminModal').on('hidden.bs.modal', function () {
            confirmBtn.removeClass('btn-danger').addClass('btn-primary').text('Xác nhận');
        });
    };

    // --- 4. POPULATE MOCK DATA ---

    // A. Dashboard Stats & Charts
    if ($('#statGyms').length > 0) {
        // Setup stats
        let gymCount = typeof gymsData !== 'undefined' ? gymsData.length : 0;
        let trainerCount = typeof trainersData !== 'undefined' ? trainersData.length : 0;
        let userCount = typeof usersData !== 'undefined' ? usersData.length : 0;
        
        let reviewCount = 0;
        let allReviews = [];
        if (typeof gymsData !== 'undefined') {
            gymsData.forEach(gym => {
                if (gym.reviews) {
                    reviewCount += gym.reviews.length;
                    gym.reviews.forEach(r => allReviews.push({ ...r, gymName: gym.name }));
                }
            });
        }

        $('#statGyms').text(gymCount);
        $('#statTrainers').text(trainerCount);
        $('#statUsers').text(userCount);
        $('#statReviews').text(reviewCount);

        // Chart (Phòng tập theo khu vực)
        if (document.getElementById('gymRegionChart') && typeof gymsData !== 'undefined') {
            const ctx = document.getElementById('gymRegionChart').getContext('2d');
            
            // Count regions
            const regions = {};
            gymsData.forEach(gym => {
                regions[gym.district] = (regions[gym.district] || 0) + 1;
            });
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(regions),
                    datasets: [{
                        label: 'Số lượng phòng tập',
                        data: Object.values(regions),
                        backgroundColor: 'rgba(0, 201, 177, 0.8)',
                        borderRadius: 6,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Recent Reviews (Top 4)
        if ($('#recentReviewsList').length > 0 && allReviews.length > 0) {
            // Sort by latest assuming id or order means latest
            const recent = allReviews.slice(0, 4);
            let html = '';
            recent.forEach(r => {
                let stars = '';
                for (let i=0; i<5; i++) {
                    stars += `<i class="fa-solid fa-star" style="color: ${i < r.rating ? '#fbbf24' : '#e2e8f0'}"></i>`;
                }
                html += `
                <div class="p-3 border-bottom d-flex gap-3 align-items-start">
                    <img src="https://ui-avatars.com/api/?name=${r.name}&background=f8fafc&color=64748b" class="rounded-circle" width="40" height="40">
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark text-truncate d-block" style="max-width: 150px;">${r.name}</span>
                            <span class="text-secondary small" style="font-size: 11px;">${r.date}</span>
                        </div>
                        <div class="small mb-1">${stars}</div>
                        <p class="text-secondary small mb-1 text-truncate" style="max-width: 200px;">${r.text}</p>
                        <span class="badge badge-neutral bg-light border text-secondary" style="font-size: 10px;">${r.gymName}</span>
                    </div>
                </div>`;
            });
            $('#recentReviewsList').html(html);
        }

        // Featured Gyms (Top 3)
        if ($('#featuredGymsList').length > 0 && typeof gymsData !== 'undefined') {
            const topGyms = gymsData.sort((a,b) => b.rating - a.rating).slice(0, 3);
            let html = '';
            topGyms.forEach(g => {
                html += `
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center gap-3 p-3 border rounded-3 h-100 bg-light">
                        <img src="${g.image}" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="min-w-0">
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 150px;" title="${g.name}">${g.name}</div>
                            <div class="text-secondary small"><i class="fa-solid fa-location-dot me-1"></i>${g.district}</div>
                            <div class="text-warning small"><i class="fa-solid fa-star me-1"></i>${g.rating}</div>
                        </div>
                    </div>
                </div>`;
            });
            $('#featuredGymsList').html(html);
        }
    }

    // B. Gyms Management
    if ($('#gymTableBody').length > 0 && typeof gymsData !== 'undefined') {
        let html = '';
        gymsData.forEach(gym => {
            let catName = "Không rõ";
            if (typeof categoriesData !== 'undefined') {
                const cat = categoriesData.find(c => c.id === gym.categoryId);
                if (cat) catName = cat.name;
            }
            html += `
                <tr>
                    <td>${gym.id}</td>
                    <td class="fw-medium">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${gym.image}" class="rounded shadow-sm" style="width: 40px; height: 32px; object-fit: cover;">
                            <span class="text-truncate d-inline-block" style="max-width: 200px;">${gym.name}</span>
                        </div>
                    </td>
                    <td>${gym.district}</td>
                    <td><span class="admin-badge badge-neutral">${catName}</span></td>
                    <td class="fw-semibold text-primary" style="font-size: 13px;">${gym.price}</td>
                    <td class="text-warning fw-semibold"><i class="fa-solid fa-star me-1"></i>${gym.rating}</td>
                    <td><span class="admin-badge badge-success">Hoạt động</span></td>
                    <td class="text-end">
                        <button class="btn btn-action btn-action-view me-1" onclick="showModal('Chi tiết', '${gym.name}')"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-action btn-action-edit me-1" onclick="showModal('Sửa phòng tập', '${gym.name}')"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-action btn-action-delete" onclick="confirmDelete('${gym.name}')"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>`;
        });
        $('#gymTableBody').html(html);
    }

    // C. Trainers Management
    if ($('#trainerTableBody').length > 0 && typeof trainersData !== 'undefined') {
        let html = '';
        trainersData.forEach(trainer => {
            // Default avatar fallback
            const avatarSrc = trainer.avatar ? trainer.avatar : `https://ui-avatars.com/api/?name=${trainer.name}&background=e2e8f0&color=64748b`;
            
            html += `
                <tr>
                    <td>${trainer.id}</td>
                    <td class="fw-medium">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${avatarSrc}" onerror="this.src='https://ui-avatars.com/api/?name=${trainer.name}&background=e2e8f0&color=64748b'" class="rounded-circle shadow-sm" style="width: 36px; height: 36px; object-fit: cover;">
                            ${trainer.name}
                        </div>
                    </td>
                    <td><span class="admin-badge badge-neutral">${trainer.specialization}</span></td>
                    <td class="text-secondary">${trainer.gymName || 'Nhiều phòng tập'}</td>
                    <td class="text-warning fw-semibold"><i class="fa-solid fa-star me-1"></i>${trainer.rating}</td>
                    <td><span class="admin-badge badge-success">Hoạt động</span></td>
                    <td class="text-end">
                        <button class="btn btn-action btn-action-view me-1" onclick="showModal('Chi tiết', '${trainer.name}')"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-action btn-action-edit me-1" onclick="showModal('Sửa', '${trainer.name}')"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-action btn-action-delete" onclick="confirmDelete('${trainer.name}')"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>`;
        });
        $('#trainerTableBody').html(html);
    }

    // D. Users Management
    if ($('#userTableBody').length > 0 && typeof usersData !== 'undefined') {
        let html = '';
        usersData.forEach(user => {
            const roleBadge = user.role === 'Admin' ? '<span class="admin-badge badge-warning text-white">Admin</span>' : '<span class="admin-badge badge-neutral">User</span>';
            const statusBadge = user.status === 'Hoạt động' ? '<span class="admin-badge badge-success">Hoạt động</span>' : '<span class="admin-badge badge-danger">Bị khóa</span>';
            const lockIcon = user.status === 'Hoạt động' ? 'fa-lock' : 'fa-unlock';
            const lockAction = user.status === 'Hoạt động' ? 'Khóa' : 'Mở khóa';
            
            html += `
                <tr>
                    <td>${user.id}</td>
                    <td class="fw-medium">${user.name}</td>
                    <td class="text-secondary">${user.email}</td>
                    <td class="text-secondary">${user.phone}</td>
                    <td>${roleBadge}</td>
                    <td class="text-secondary">${user.date}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end">
                        <button class="btn btn-action btn-action-view me-1" onclick="showModal('Chi tiết User', '${user.name}')"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-action btn-action-${user.status === 'Hoạt động' ? 'delete' : 'edit'}" onclick="showModal('Xác nhận ${lockAction}', 'Bạn có chắc chắn muốn ${lockAction.toLowerCase()} tài khoản này?')"><i class="fa-solid ${lockIcon}"></i></button>
                    </td>
                </tr>`;
        });
        $('#userTableBody').html(html);
    }

    // E. Reviews Management
    if ($('#reviewTableBody').length > 0 && typeof gymsData !== 'undefined') {
        let html = '';
        gymsData.forEach(gym => {
            if (gym.reviews && gym.reviews.length > 0) {
                gym.reviews.forEach(review => {
                    let stars = '';
                    for (let i=0; i<5; i++) {
                        stars += `<i class="fa-solid fa-star" style="color: ${i < review.rating ? '#fbbf24' : '#e2e8f0'}"></i>`;
                    }
                    html += `
                        <tr>
                            <td class="fw-medium">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="https://ui-avatars.com/api/?name=${review.name}&background=f8fafc&color=64748b" class="rounded-circle" width="32" height="32">
                                    ${review.name}
                                </div>
                            </td>
                            <td class="text-secondary" style="font-size: 13px;">${gym.name}</td>
                            <td style="font-size: 11px;">${stars}</td>
                            <td class="text-secondary" style="max-width: 250px;"><div class="text-truncate">${review.text}</div></td>
                            <td class="text-secondary" style="font-size: 13px;">${review.date}</td>
                            <td><span class="admin-badge badge-success">Đã duyệt</span></td>
                            <td class="text-end">
                                <button class="btn btn-action btn-action-view me-1" onclick="showModal('Nội dung đánh giá', '${review.text}')"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-action btn-action-delete" onclick="confirmDelete('đánh giá của ${review.name}')"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>`;
                });
            }
        });
        
        if (html === '') {
            html = `<tr><td colspan="7">
                <div class="empty-state">
                    <i class="fa-regular fa-comment-dots"></i>
                    <h5>Chưa có đánh giá nào</h5>
                    <p>Các đánh giá của người dùng sẽ xuất hiện tại đây.</p>
                </div>
            </td></tr>`;
        }
        $('#reviewTableBody').html(html);
    }

    // F. Categories Management
    if ($('#categoryTableBody').length > 0 && typeof categoriesData !== 'undefined') {
        let html = '';
        categoriesData.forEach(cat => {
            let count = 0;
            if (typeof gymsData !== 'undefined') {
                count = gymsData.filter(g => g.categoryId === cat.id).length;
            }
            html += `
                <tr>
                    <td>${cat.id}</td>
                    <td class="fw-medium"><i class="${cat.icon} text-primary me-2"></i>${cat.name}</td>
                    <td class="text-secondary">${cat.description}</td>
                    <td class="text-center"><span class="badge badge-neutral">${count} phòng</span></td>
                    <td><span class="admin-badge badge-success">Hoạt động</span></td>
                    <td class="text-end">
                        <button class="btn btn-action btn-action-edit me-1" onclick="showModal('Sửa danh mục', '${cat.name}')"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-action btn-action-delete" onclick="confirmDelete('${cat.name}')"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>`;
        });
        $('#categoryTableBody').html(html);
    }
});
