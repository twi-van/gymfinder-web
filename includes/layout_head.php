<?php
/** @var string $pageTitle */
/** @var string[] $extraCss */
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? '';
$styleVersion = (string) @filemtime(dirname(__DIR__) . '/assets/css/style.css');
?>
<!DOCTYPE html>
<html lang="<?= gf_lang() === 'en' ? 'en' : 'vi' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= gf_h(gf_t($pageTitle ?? 'GYMFINDER')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= gf_h(gf_url('assets/images/favicon.svg')) ?>?v=3">
    <link rel="alternate icon" href="<?= gf_h(gf_url('favicon.ico')) ?>?v=3">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= gf_h(gf_url('assets/css/style.css')) ?>?v=<?= gf_h($styleVersion) ?>">
    <link rel="stylesheet" href="<?= gf_h(gf_url('assets/css/components.css')) ?>">
    <?php foreach ($extraCss as $href): ?>
        <link rel="stylesheet" href="<?= gf_h(gf_url($href)) ?>">
    <?php endforeach; ?>
    <script>
    window.GF_LANG = <?= json_encode(gf_lang()) ?>;
    window.GF_I18N = <?= gf_lang() === 'en' ? json_encode(gf_i18n_en(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) : '{}' ?>;
    window.gfT = function (s) {
        if (window.GF_LANG !== 'en' || typeof s !== 'string' || s === '') return s;
        if (Object.prototype.hasOwnProperty.call(window.GF_I18N, s)) return window.GF_I18N[s];
        return s;
    };
    </script>
</head>
<body class="<?= gf_h($bodyClass) ?>">
