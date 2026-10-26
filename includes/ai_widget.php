<?php
declare(strict_types=1);

/**
 * Floating AI chat widget (homepage / public portal).
 * Requires bootstrap already loaded.
 */
$aiWidgetEnabled = function_exists('gf_ai_is_configured') && gf_ai_is_configured();
$aiWidgetCssVer = (string) @filemtime(dirname(__DIR__) . '/assets/css/ai_widget.css');
$aiWidgetJsVer = (string) @filemtime(dirname(__DIR__) . '/assets/js/ai_widget.js');
$aiChips = [
    'Gym Quận 7 giá dưới 500 nghìn',
    'HLV Yoga rating cao',
    'PT giảm cân cho người mới',
];
?>
<link rel="stylesheet" href="<?= gf_h(gf_url('assets/css/ai_widget.css')) ?>?v=<?= gf_h($aiWidgetCssVer) ?>">
<div
    id="gfAiWidget"
    class="gf-ai-widget"
    data-enabled="<?= $aiWidgetEnabled ? '1' : '0' ?>"
    data-api-url="<?= gf_h(gf_url('api/ai/recommend')) ?>"
    data-csrf-url="<?= gf_h(gf_url('api/auth/csrf')) ?>"
    data-csrf="<?= gf_h(gf_csrf_token()) ?>"
>
    <div class="gf-ai-panel" id="gfAiPanel" role="dialog" aria-label="Trợ lý AI GYMFINDER" aria-hidden="true">
        <div class="gf-ai-panel-head">
            <div class="gf-ai-panel-head-icon"><i class="fa-solid fa-robot"></i></div>
            <div>
                <h2>Trợ lý GYMFINDER</h2>
                <p>Gợi ý phòng tập &amp; HLV</p>
            </div>
            <button type="button" class="gf-ai-panel-close" aria-label="Đóng chat"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="gf-ai-messages" id="gfAiMessages">
            <div class="gf-ai-bubble gf-ai-bubble--bot">Xin chào! Hỏi mình về phòng tập hoặc HLV tại TP.HCM nhé (quận, giá, Yoga…).</div>
        </div>
        <div class="gf-ai-chips">
            <?php foreach ($aiChips as $chip): ?>
                <button type="button" class="gf-ai-chip" data-chip="<?= gf_h($chip) ?>"><?= gf_h($chip) ?></button>
            <?php endforeach; ?>
        </div>
        <form class="gf-ai-form" autocomplete="off">
            <input
                type="text"
                class="gf-ai-input"
                maxlength="500"
                placeholder="<?= $aiWidgetEnabled ? 'Nhập câu hỏi…' : 'AI chưa cấu hình' ?>"
                <?= $aiWidgetEnabled ? '' : 'disabled' ?>
                aria-label="Câu hỏi cho trợ lý AI"
            >
            <button type="submit" class="gf-ai-send" aria-label="Gửi" <?= $aiWidgetEnabled ? '' : 'disabled' ?>>
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
        <a class="gf-ai-footer-link" href="<?= gf_h(gf_url('pages/ai/chat.php')) ?>">Mở trang chat đầy đủ</a>
    </div>
    <button type="button" class="gf-ai-fab" aria-expanded="false" aria-controls="gfAiPanel" title="Trợ lý AI">
        <i class="fa-solid fa-robot" aria-hidden="true"></i>
    </button>
</div>
<script src="<?= gf_h(gf_url('assets/js/ai_widget.js')) ?>?v=<?= gf_h($aiWidgetJsVer) ?>" defer></script>
