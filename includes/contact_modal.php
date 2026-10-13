<!-- Modal hiển thị thông tin Liên hệ (Dùng chung cho Phòng gym và Huấn luyện viên) -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-3">
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body text-center px-4 pt-1 pb-4">
                <!-- Avatar / Ảnh đối tượng -->
                <div class="position-relative d-inline-block mb-3">
                    <img id="contactModalAvatar" src="" alt="" class="shadow-sm border border-3 border-white object-fit-cover" style="width: 88px; height: 88px; border-radius: 50%; background-color: #f1f5f9;">
                    <div id="contactModalTypeBadge" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px; font-size: 13px;">
                        <i class="fa-solid fa-phone" id="contactModalTypeIcon"></i>
                    </div>
                </div>

                <!-- Phân loại / Chuyên môn -->
                <div class="mb-2">
                    <span id="contactModalCategory" class="badge bg-primary-light text-primary rounded-pill px-3 py-1 fw-semibold small">Huấn luyện viên</span>
                </div>

                <!-- Tên phòng tập hoặc HLV -->
                <h4 class="fw-bold mb-1 text-dark" id="contactModalTitle" style="font-size: 20px;">Tên liên hệ</h4>
                <p class="text-secondary small mb-3" id="contactModalSubtitle">Thông tin chi tiết</p>

                <!-- Khung hiển thị số điện thoại -->
                <div class="p-3 rounded-4 mb-3 text-center" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                    <div class="text-secondary small mb-1 fw-medium">
                        <i class="fa-solid fa-phone me-1 text-primary"></i>Số điện thoại liên hệ
                    </div>
                    <div id="contactModalPhoneWrapper">
                        <div id="contactModalPhone" class="fw-bold fs-4 text-dark" style="letter-spacing: 0.5px;">--</div>
                    </div>
                    <div id="contactModalNoPhone" class="text-muted fst-italic small d-none py-1">
                        Chưa cập nhật số điện thoại
                    </div>
                </div>

                <!-- Thông báo sao chép thành công -->
                <div id="contactModalCopyAlert" class="alert alert-success py-2 px-3 small rounded-pill mb-3 d-none align-items-center justify-content-center gap-2 border-0 shadow-sm" style="background-color: #ecfdf5; color: #047857;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Đã sao chép số điện thoại vào bộ nhớ tạm!</span>
                </div>

                <!-- Nút thao tác -->
                <div class="d-flex flex-column gap-2">
                    <button type="button" id="contactModalCopyBtn" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-regular fa-copy" id="contactModalCopyIcon"></i>
                        <span id="contactModalCopyText">Sao chép số điện thoại</span>
                    </button>
                    <a id="contactModalCallBtn" href="#" class="btn btn-outline-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-phone-volume"></i>
                        <span>Gọi trực tiếp</span>
                    </a>
                    <button type="button" class="btn btn-light border text-secondary w-100 rounded-pill py-2 fw-semibold" data-bs-dismiss="modal">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
