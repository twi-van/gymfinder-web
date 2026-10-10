<?php
/** @var string $pageTitle */
/** @var string[] $extraCss */
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= gf_h($pageTitle ?? 'GYMFINDER') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= gf_h(gf_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= gf_h(gf_url('assets/css/components.css')) ?>">
    <?php foreach ($extraCss as $href): ?>
        <link rel="stylesheet" href="<?= gf_h(gf_url($href)) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= gf_h($bodyClass) ?>">
