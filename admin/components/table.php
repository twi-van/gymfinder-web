<?php
declare(strict_types=1);

/**
 * Reusable Admin Table UI Component - GYMFINDER
 *
 * Cung cấp giao diện bảng quản trị dùng chung chuẩn hóa:
 * - Khung bảng bo góc nhẹ, viền mảnh, shadow tinh tế
 * - Vùng cuộn responsive mượt mà với thanh cuộn hiện đại
 * - Header cột chữ hoa rõ ràng, typography phân cấp
 * - Padding, đường kẻ giữa các hàng và hiệu ứng hover đồng bộ
 * - Hỗ trợ tùy biến cột, độ rộng, căn lề, tiêu đề và footer phân trang
 */

if (!function_exists('gf_admin_table_open')) {
    /**
     * Mở khung bảng quản trị dùng chung
     *
     * @param array $config [
     *   'id'            => 'gymsTable',
     *   'class'         => 'admin-gyms-table',
     *   'min_width'     => '1140px',
     *   'columns'       => [
     *       ['label' => 'ID', 'width' => '55px', 'align' => 'center'],
     *       ['label' => 'PHÒNG TẬP', 'width' => '330px'],
     *       ...
     *   ]
     * ]
     */
    function gf_admin_table_open(array $config): void
    {
        $tableId = htmlspecialchars($config['id'] ?? '', ENT_QUOTES, 'UTF-8');
        $extraClass = htmlspecialchars($config['class'] ?? '', ENT_QUOTES, 'UTF-8');
        $minWidth = htmlspecialchars($config['min_width'] ?? '1080px', ENT_QUOTES, 'UTF-8');
        $columns = $config['columns'] ?? [];
        ?>
        <div class="admin-card admin-table-card overflow-hidden shadow-sm border mb-4">
            <div class="table-responsive admin-table-responsive admin-drag-scroll">
                <table <?= $tableId ? 'id="' . $tableId . '"' : '' ?> class="table admin-table admin-common-table <?= $extraClass ?> mb-0" style="min-width: <?= $minWidth ?>;">
                    <?php if (!empty($columns)): ?>
                        <colgroup>
                            <?php foreach ($columns as $idx => $col): 
                                $colWidth = !empty($col['width']) ? 'width: ' . htmlspecialchars($col['width'], ENT_QUOTES, 'UTF-8') . ';' : '';
                                $colClass = htmlspecialchars($col['col_class'] ?? ('col-' . $idx), ENT_QUOTES, 'UTF-8');
                            ?>
                                <col class="<?= $colClass ?>" style="<?= $colWidth ?>">
                            <?php endforeach; ?>
                        </colgroup>
                        <thead>
                            <tr>
                                <?php foreach ($columns as $col): 
                                    $align = $col['align'] ?? 'left';
                                    $alignClass = $align === 'center' ? 'text-center' : ($align === 'right' ? 'text-end' : '');
                                    $customClass = htmlspecialchars($col['th_class'] ?? $col['class'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $finalClass = trim($alignClass . ' ' . $customClass);
                                ?>
                                    <th <?= $finalClass ? 'class="' . $finalClass . '"' : '' ?>><?= $col['label'] ?? '' ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                    <?php endif; ?>
                    <tbody>
        <?php
    }
}

if (!function_exists('gf_admin_table_close')) {
    /**
     * Đóng khung bảng quản trị dùng chung và render footer phân trang (nếu có)
     *
     * @param array $options [
     *   'footer_html'   => '...',
     *   'pagination'    => ['page' => 1, 'limit' => 8, 'total' => 20, 'query_params' => []],
     *   'empty'         => false,
     *   'empty_message' => 'Không tìm thấy dữ liệu phù hợp.',
     *   'colspan'       => 8,
     * ]
     */
    function gf_admin_table_close(array $options = []): void
    {
        $isEmpty = !empty($options['empty']);
        $emptyMsg = htmlspecialchars($options['empty_message'] ?? 'Không tìm thấy dữ liệu phù hợp.', ENT_QUOTES, 'UTF-8');
        $colspan = (int) ($options['colspan'] ?? 8);
        $footerHtml = $options['footer_html'] ?? null;

        // Tự động render phân trang nếu truyền cấu hình 'pagination' mà chưa có 'footer_html'
        if ($footerHtml === null && isset($options['pagination']) && is_array($options['pagination'])) {
            $p = $options['pagination'];
            $footerHtml = gf_render_admin_pagination(
                (int)($p['page'] ?? 1),
                (int)($p['limit'] ?? 8),
                (int)($p['total'] ?? 0),
                (array)($p['query_params'] ?? array_diff_key($_GET, ['page' => '']))
            );
        }
        ?>
                    <?php if ($isEmpty): ?>
                        <tr>
                            <td colspan="<?= $colspan ?>" class="text-center py-5 text-secondary">
                                <div class="empty-state py-4">
                                    <i class="fa-solid fa-inbox fs-1 text-muted opacity-50 mb-3 d-block"></i>
                                    <p class="mb-0 fw-medium"><?= $emptyMsg ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($footerHtml) && trim((string)$footerHtml) !== ''): ?>
                <div class="admin-table-footer px-4 py-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white">
                    <?= $footerHtml ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('gf_render_admin_pagination')) {
    /**
     * Render thanh phân trang chuẩn hóa giao diện GYMFINDER
     *
     * Quy tắc:
     * - Mặc định 8 bản ghi mỗi trang
     * - Chỉ hiển thị khi tổng số bản ghi lớn hơn giới hạn (mặc định > 8)
     * - Định dạng: "Hiển thị 1–8 trên tổng N bản ghi"
     * - Giữ nguyên query params khi chuyển trang
     */
    function gf_render_admin_pagination(int $currentPage, int $limit = 8, int $total = 0, array $queryParams = []): string
    {
        $limit = max(1, $limit);

        // Chỉ hiển thị phân trang khi tổng số bản ghi lớn hơn limit (mặc định 8)
        if ($total <= $limit || $total <= 0) {
            return '';
        }

        $totalPages = max(1, (int) ceil($total / $limit));
        $currentPage = max(1, min($currentPage, $totalPages));
        $from = ($currentPage - 1) * $limit + 1;
        $to = min($currentPage * $limit, $total);

        $cleanParams = array_diff_key($queryParams, ['page' => '']);

        ob_start();
        ?>
        <span class="small text-secondary">
            Hiển thị <?= $from ?>–<?= $to ?> trên tổng <?= $total ?> bản ghi
        </span>
        <div class="d-flex align-items-center gap-1">
            <?php
            $prevParams = array_merge($cleanParams, ['page' => max(1, $currentPage - 1)]);
            $nextParams = array_merge($cleanParams, ['page' => min($totalPages, $currentPage + 1)]);
            ?>
            <a class="admin-pagination-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="?<?= htmlspecialchars(http_build_query($prevParams), ENT_QUOTES, 'UTF-8') ?>" title="Trang trước">
                <i class="fa-solid fa-chevron-left" style="font-size: 11px;"></i>
            </a>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $currentPage): ?>
                    <span class="admin-pagination-btn active" aria-current="page"><?= $p ?></span>
                <?php elseif ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 1): ?>
                    <a class="admin-pagination-btn" href="?<?= htmlspecialchars(http_build_query(array_merge($cleanParams, ['page' => $p])), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                <?php elseif (abs($p - $currentPage) === 2): ?>
                    <span class="text-muted px-1">...</span>
                <?php endif; ?>
            <?php endfor; ?>
            <a class="admin-pagination-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="?<?= htmlspecialchars(http_build_query($nextParams), ENT_QUOTES, 'UTF-8') ?>" title="Trang sau">
                <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i>
            </a>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
