<?php
/**
 * Shared toast host + confirm modal + ui-feedback.js
 * Require AFTER Bootstrap JS is loaded.
 */
if (!empty($GLOBALS['gf_ui_feedback_rendered'])) {
    return;
}
$GLOBALS['gf_ui_feedback_rendered'] = true;
?>
<div id="gfToastHost" class="gf-toast-host" aria-live="polite" aria-atomic="true"></div>

<div class="modal fade" id="gfConfirmModal" tabindex="-1" aria-labelledby="gfConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="gfConfirmTitle">Xác nhận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-gf-confirm-cancel aria-label="Đóng"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0" id="gfConfirmMessage">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" data-gf-confirm-cancel id="gfConfirmCancel">Hủy</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold" id="gfConfirmOk">Xác nhận</button>
            </div>
        </div>
    </div>
</div>
<script src="<?= gf_h(gf_url('assets/js/ui-feedback.js')) ?>"></script>
