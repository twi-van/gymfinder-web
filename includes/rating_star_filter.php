<?php
/**
 * Bộ chọn sao dùng chung:
 * - mode=filter: lọc “từ X★ trở lên” (có thể bỏ chọn)
 * - mode=rate: chọn đúng X sao khi viết/sửa đánh giá (bắt buộc 1–5)
 *
 * @var string $ratingFilterName
 * @var string|int $ratingFilterValue
 * @var string $ratingFilterId
 * @var string $ratingFilterMode  filter|rate
 */
$ratingFilterName = $ratingFilterName ?? 'rating';
$ratingFilterValue = (string) ($ratingFilterValue ?? '');
$ratingFilterId = $ratingFilterId ?? 'ratingFilter';
$ratingFilterMode = ($ratingFilterMode ?? 'filter') === 'rate' ? 'rate' : 'filter';

if ($ratingFilterMode === 'rate') {
    $ratingSelected = in_array($ratingFilterValue, ['1', '2', '3', '4', '5'], true)
        ? (int) $ratingFilterValue
        : 5;
    $ratingHint = $ratingSelected . ' sao';
    $ratingAria = 'Chọn số sao đánh giá';
} else {
    $ratingSelected = in_array($ratingFilterValue, ['1', '2', '3', '4', '5'], true)
        ? (int) $ratingFilterValue
        : 0;
    $ratingHint = $ratingSelected > 0 ? ('Từ ' . $ratingSelected . '★ trở lên') : 'Mọi mức đánh giá';
    $ratingAria = 'Chọn mức đánh giá tối thiểu';
}
?>
<div class="gf-star-filter" data-gf-star-filter data-mode="<?= gf_h($ratingFilterMode) ?>" id="<?= gf_h($ratingFilterId) ?>">
    <input
        type="hidden"
        name="<?= gf_h($ratingFilterName) ?>"
        value="<?= $ratingFilterMode === 'rate' ? (string) $ratingSelected : ($ratingSelected > 0 ? (string) $ratingSelected : '') ?>"
        class="gf-star-filter-input"
        <?= $ratingFilterMode === 'rate' ? 'required' : '' ?>
    >
    <div class="gf-star-filter-stars" role="radiogroup" aria-label="<?= gf_h($ratingAria) ?>">
        <?php for ($star = 1; $star <= 5; $star++): ?>
            <button
                type="button"
                class="gf-star-filter-btn<?= $star <= $ratingSelected ? ' is-active' : '' ?>"
                data-value="<?= $star ?>"
                aria-label="<?= $ratingFilterMode === 'rate' ? ($star . ' sao') : ('Từ ' . $star . ' sao trở lên') ?>"
                aria-pressed="<?= $star === $ratingSelected ? 'true' : 'false' ?>"
            ><i class="fa-solid fa-star" aria-hidden="true"></i></button>
        <?php endfor; ?>
    </div>
    <div class="gf-star-filter-meta">
        <span class="gf-star-filter-hint"><?= gf_h($ratingHint) ?></span>
        <?php if ($ratingFilterMode === 'filter'): ?>
            <button type="button" class="gf-star-filter-clear<?= $ratingSelected > 0 ? '' : ' d-none' ?>">Bỏ chọn</button>
        <?php endif; ?>
    </div>
</div>
<?php
// Reset biến để lần include sau không bị dính mode
$ratingFilterMode = 'filter';
?>
