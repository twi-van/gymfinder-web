<?php
declare(strict_types=1);

/**
 * Reusable User Portal Pagination Component - GYMFINDER
 *
 * Chuẩn hóa phân trang cho các danh sách User Portal:
 * - Mặc định 8 bản ghi/trang
 * - Chỉ hiển thị khi tổng số bản ghi lớn hơn giới hạn (> 8)
 * - Nút Trước, Sau và số trang, đánh dấu trang hiện tại
 * - Tự động rút gọn dấu ba chấm (...) khi có nhiều trang
 * - Bảo toàn toàn bộ bộ lọc và từ khóa tìm kiếm khi chuyển trang
 * - Responsive mượt mà trên thiết bị di động
 */

if (!function_exists('gf_render_user_pagination')) {
    /**
     * Render điều khiển phân trang User Portal
     *
     * @param int $currentPage  Trang hiện tại (1-indexed)
     * @param int $limit        Số lượng bản ghi mỗi trang (mặc định 8)
     * @param int $total        Tổng số bản ghi
     * @param array $queryParams Mảng tham số URL hiện tại để duy trì bộ lọc
     * @param string $pageParam Tên param trên URL (mặc định 'page')
     * @return string HTML của phân trang, hoặc chuỗi rỗng nếu tổng bản ghi <= limit
     */
    function gf_render_user_pagination(
        int $currentPage,
        int $limit = 8,
        int $total = 0,
        array $queryParams = [],
        string $pageParam = 'page'
    ): string {
        $limit = max(1, $limit);

        // Khi dữ liệu ít hơn hoặc bằng giới hạn 1 trang, không hiển thị điều khiển phân trang
        if ($total <= $limit || $total <= 0) {
            return '';
        }

        $totalPages = max(1, (int) ceil($total / $limit));
        $currentPage = max(1, min($currentPage, $totalPages));

        // Loại bỏ param trang hiện tại để tạo link sạch sẽ
        $cleanParams = array_diff_key($queryParams, [$pageParam => '']);

        ob_start();
        ?>
        <nav aria-label="Điều hướng phân trang" class="pagination-container my-4">
            <ul class="pagination justify-content-center flex-wrap gap-1 mb-0">
                <?php
                $prevParams = array_merge($cleanParams, [$pageParam => max(1, $currentPage - 1)]);
                $nextParams = array_merge($cleanParams, [$pageParam => min($totalPages, $currentPage + 1)]);
                ?>
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= htmlspecialchars(http_build_query($prevParams), ENT_QUOTES, 'UTF-8') ?>" <?= $currentPage <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                        <i class="fa-solid fa-arrow-left me-2"></i>Trước
                    </a>
                </li>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p === $currentPage): ?>
                        <li class="page-item active" aria-current="page">
                            <span class="page-link"><?= $p ?></span>
                        </li>
                    <?php elseif ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query(array_merge($cleanParams, [$pageParam => $p])), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                        </li>
                    <?php elseif (abs($p - $currentPage) === 2): ?>
                        <li class="page-item disabled d-none d-sm-inline-block">
                            <span class="page-link border-0 bg-transparent text-muted">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>

                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= htmlspecialchars(http_build_query($nextParams), ENT_QUOTES, 'UTF-8') ?>" <?= $currentPage >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                        Sau<i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('gf_render_pagination')) {
    /**
     * Alias dùng chung cho User Portal
     */
    function gf_render_pagination(int $currentPage, int $limit = 8, int $total = 0, array $queryParams = [], string $pageParam = 'page'): string
    {
        return gf_render_user_pagination($currentPage, $limit, $total, $queryParams, $pageParam);
    }
}
