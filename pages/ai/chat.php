<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$chips = [
    'Gym Quận 7 giá dưới 500 nghìn',
    'HLV Yoga rating cao',
    'PT giảm cân cho người mới',
    'Phòng tập có Yoga và HLV kèm',
];

$history = gf_ai_chat_history_get();
$aiReady = gf_ai_is_configured();
$pageTitle = 'Trợ lý AI - GYMFINDER';
$bodyClass = 'gf-ai-chat-page d-flex flex-column min-vh-100';
$extraCss = ['assets/css/ai_chat.css'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<main class="flex-grow-1">
    <div
        class="gf-ai-chat-shell"
        id="aiChatRoot"
        data-enabled="<?= $aiReady ? '1' : '0' ?>"
        data-has-history="<?= $history !== [] ? '1' : '0' ?>"
        data-api-url="<?= gf_h(gf_url('api/ai/recommend')) ?>"
        data-history-url="<?= gf_h(gf_url('api/ai/history')) ?>"
        data-csrf-url="<?= gf_h(gf_url('api/auth/csrf')) ?>"
        data-csrf="<?= gf_h(gf_csrf_token()) ?>"
        data-search-gym-url="<?= gf_h(gf_url('pages/gyms/search.php')) ?>"
        data-search-pt-url="<?= gf_h(gf_url('pages/trainers/index.php')) ?>"
    >
        <div class="gf-ai-chat-frame">
            <header class="gf-ai-chat-top">
                <div class="gf-ai-chat-top-avatar" aria-hidden="true"><i class="fa-solid fa-robot"></i></div>
                <div class="min-w-0">
                    <h1>Trợ lý GYMFINDER</h1>
                    <p class="gf-ai-chat-top-status <?= $aiReady ? '' : 'is-offline' ?>">
                        <?= $aiReady ? 'Đang sẵn sàng · gym & HLV' : 'Chưa cấu hình AI' ?>
                    </p>
                </div>
                <div class="ms-auto" id="aiChatHeaderActions">
                    <?php if ($history !== []): ?>
                        <button type="button" class="gf-ai-chat-top-link border-0 bg-transparent p-0" data-ai-clear title="Xóa hội thoại">
                            <i class="fa-solid fa-trash-can me-1"></i>Xóa
                        </button>
                    <?php else: ?>
                        <a class="gf-ai-chat-top-link" href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>">Tìm kiếm</a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="gf-ai-chat-thread" id="aiChatThread">
                <?php if (!$aiReady): ?>
                    <div class="alert alert-warning gf-ai-chat-alert mb-0">
                        <strong>AI chưa cấu hình.</strong>
                        Copy <code>backend/ai.local.php.example</code> → <code>ai.local.php</code> và thêm API key.
                    </div>
                <?php endif; ?>

                <?php if ($history === []): ?>
                    <div class="gf-ai-msg gf-ai-msg--bot" id="aiChatWelcome">
                        <div class="gf-ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>
                        <div class="gf-ai-msg-col">
                            <div class="gf-ai-msg-name">Trợ lý</div>
                            <div class="gf-ai-msg-bubble">Xin chào! Mình gợi ý <strong>phòng tập</strong> và <strong>HLV</strong> trong hệ thống GYMFINDER (TP.HCM). Hỏi theo quận, giá, Yoga… hoặc chọn gợi ý bên dưới. Hội thoại được giữ khi bạn hỏi tiếp — kéo lên để xem tin cũ.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($history as $turn): ?>
                    <?php
                    $userText = (string) ($turn['user'] ?? '');
                    $botText = (string) ($turn['bot'] ?? '');
                    $isError = !empty($turn['is_error']);
                    $gyms = is_array($turn['gyms'] ?? null) ? $turn['gyms'] : [];
                    $trainers = is_array($turn['trainers'] ?? null) ? $turn['trainers'] : [];
                    $suggestRefine = !empty($turn['suggest_refine']);
                    ?>
                    <?php if ($userText !== ''): ?>
                        <div class="gf-ai-msg gf-ai-msg--user">
                            <div class="gf-ai-msg-avatar"><i class="fa-solid fa-user"></i></div>
                            <div class="gf-ai-msg-col">
                                <div class="gf-ai-msg-name">Bạn</div>
                                <div class="gf-ai-msg-bubble"><?= gf_h($userText) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="gf-ai-msg gf-ai-msg--bot">
                        <div class="gf-ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>
                        <div class="gf-ai-msg-col">
                            <div class="gf-ai-msg-name">Trợ lý</div>
                            <div class="gf-ai-msg-bubble"><?= gf_h($botText) ?></div>

                            <?php if ($isError): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>">Tìm phòng tập</a>
                                    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>">Tìm HLV</a>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($gyms as $g): ?>
                                <?php
                                $gid = (int) $g['id'];
                                if ($gid <= 0) {
                                    continue;
                                }
                                $detailUrl = gf_url('pages/gyms/detail.php?id=' . $gid);
                                $cover = gf_cover_image($g['cover_image_url'] ?? null, $gid);
                                $price = gf_format_price((int) ($g['price_min'] ?? 0), (int) ($g['price_max'] ?? 0));
                                $cats = is_array($g['categories'] ?? null) ? $g['categories'] : [];
                                ?>
                                <a class="gf-ai-result-card" href="<?= gf_h($detailUrl) ?>">
                                    <img src="<?= gf_h($cover) ?>" alt="">
                                    <div class="gf-ai-result-card-body">
                                        <p class="gf-ai-result-card-title"><?= gf_h((string) $g['name']) ?></p>
                                        <p class="gf-ai-result-card-sub">
                                            <i class="fa-solid fa-location-dot me-1"></i><?= gf_h((string) ($g['district'] ?? '')) ?>
                                            <?php if ($cats !== []): ?> · <?= gf_h(implode(', ', array_slice($cats, 0, 2))) ?><?php endif; ?>
                                        </p>
                                        <p class="gf-ai-result-card-meta">
                                            <?= gf_h($price) ?>
                                            · <i class="fa-solid fa-star text-warning"></i> <?= number_format((float) ($g['avg_rating'] ?? 0), 1) ?>
                                        </p>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-secondary"></i>
                                </a>
                            <?php endforeach; ?>

                            <?php foreach ($trainers as $t): ?>
                                <?php
                                $tid = (int) $t['id'];
                                if ($tid <= 0) {
                                    continue;
                                }
                                $detailUrl = gf_url('pages/trainers/detail.php?id=' . $tid);
                                $avatar = gf_avatar($t['avatar_url'] ?? null, $tid, (string) ($t['full_name'] ?? 'HLV'));
                                $specs = is_array($t['specialties'] ?? null) ? $t['specialties'] : [];
                                $districts = is_array($t['districts'] ?? null) ? $t['districts'] : [];
                                ?>
                                <a class="gf-ai-result-card" href="<?= gf_h($detailUrl) ?>">
                                    <img class="is-round" src="<?= gf_h($avatar) ?>" alt="">
                                    <div class="gf-ai-result-card-body">
                                        <p class="gf-ai-result-card-title"><?= gf_h((string) $t['full_name']) ?></p>
                                        <p class="gf-ai-result-card-sub">
                                            <?php if ($districts !== []): ?>
                                                <i class="fa-solid fa-location-dot me-1"></i><?= gf_h(implode(', ', array_slice($districts, 0, 2))) ?> ·
                                            <?php endif; ?>
                                            <?= (int) ($t['years_experience'] ?? 0) ?> năm KN
                                        </p>
                                        <p class="gf-ai-result-card-meta">
                                            <?= gf_h(implode(' · ', array_slice($specs, 0, 2))) ?>
                                            · <i class="fa-solid fa-star text-warning"></i> <?= number_format((float) ($t['avg_rating'] ?? 0), 1) ?>
                                        </p>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-secondary"></i>
                                </a>
                            <?php endforeach; ?>

                            <?php if (!$isError && $suggestRefine && $gyms === [] && $trainers === []): ?>
                                <p class="small text-secondary mb-0 px-1">Chưa gắn gym/HLV cụ thể — thử hỏi rõ quận / giá / chuyên môn hơn.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="gf-ai-chat-composer">
                <div class="gf-ai-chat-chips" role="list">
                    <?php foreach ($chips as $chip): ?>
                        <button
                            type="button"
                            class="gf-ai-chat-chip"
                            data-chip="<?= gf_h($chip) ?>"
                            <?= $aiReady ? '' : 'disabled' ?>
                        ><?= gf_h($chip) ?></button>
                    <?php endforeach; ?>
                </div>
                <form id="aiChatForm" class="gf-ai-chat-input-row" action="javascript:void(0)">
                    <?= gf_csrf_field() ?>
                    <textarea
                        id="aiMessage"
                        name="message"
                        rows="1"
                        maxlength="500"
                        placeholder="<?= $aiReady ? 'Nhập câu hỏi về gym hoặc HLV…' : 'AI chưa cấu hình' ?>"
                        <?= $aiReady ? '' : 'disabled' ?>
                    ></textarea>
                    <button type="submit" class="gf-ai-chat-send" <?= $aiReady ? '' : 'disabled' ?> aria-label="Gửi">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
                <p class="gf-ai-chat-hint">Giữ tối đa 12 lượt gần nhất · Không reload trang · Nút Xóa để bắt đầu lại</p>
            </div>
        </div>
    </div>
</main>
<?php
$aiChatJsVer = (string) @filemtime(dirname(__DIR__, 2) . '/assets/js/ai_chat.js');
?>
<script src="<?= gf_h(gf_url('assets/js/ai_chat.js')) ?>?v=<?= gf_h($aiChatJsVer) ?>"></script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
