<?php
declare(strict_types=1);

/**
 * GYMFINDER Standardized Brand Logo Component
 *
 * Parameters (can be passed via local variables before include, or extracted):
 * @var string|null $logoVariant   'header' (default) | 'admin' | 'login' | 'footer'
 * @var string|null $logoHref      Custom target URL. Defaults based on variant.
 * @var string|null $logoClass     Additional custom CSS classes
 * @var bool|null   $logoAsLink    Whether to output as <a> link (default: true)
 * @var string|null $logoId        Optional HTML id attribute
 * @var string|null $logoTitle     Optional HTML title attribute
 */

$variant = (string)($logoVariant ?? 'header');
$asLink = (bool)($logoAsLink ?? true);
$extraClass = trim((string)($logoClass ?? ''));
$idAttr = !empty($logoId) ? ' id="' . gf_h($logoId) . '"' : '';
$titleAttr = !empty($logoTitle) ? ' title="' . gf_h($logoTitle) . '"' : '';

// Resolve target URL
if (isset($logoHref) && $logoHref !== null) {
    $targetHref = (string)$logoHref;
} else {
    $targetHref = ($variant === 'admin') ? gf_url('admin/pages/dashboard.php') : gf_url('index.php');
}

// Map modifier classes (supporting both logo--* and brand-logo--* BEM patterns)
$modifierClass = match ($variant) {
    'login', 'auth', 'register' => 'logo--login brand-logo--login',
    'admin'                     => 'logo--admin brand-logo--admin',
    'footer'                    => 'logo--footer brand-logo--footer',
    default                     => 'logo--header brand-logo--header',
};

// Standard Dumbbell SVG Vector
$svgHtml = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-logo-svg logo-svg" aria-hidden="true">'
    . '<line x1="8" y1="12" x2="16" y2="12"></line>'
    . '<rect x="6" y="6" width="2" height="12" rx="1"></rect>'
    . '<rect x="3" y="8" width="2" height="8" rx="1"></rect>'
    . '<path d="M1.5 12h1.5"></path>'
    . '<rect x="16" y="6" width="2" height="12" rx="1"></rect>'
    . '<rect x="19" y="8" width="2" height="8" rx="1"></rect>'
    . '<path d="M21 12h1.5"></path>'
    . '</svg>';

$markHtml = '<div class="brand-logo-mark logo-mark">' . $svgHtml . '</div>';
$textHtml = '<span class="brand-logo-text logo-text">GYM<span class="text-primary">FINDER</span></span>';

$allClasses = 'brand-logo logo ' . $modifierClass . ($extraClass !== '' ? ' ' . $extraClass : '');

if ($asLink && $targetHref !== '') {
    echo '<a href="' . gf_h($targetHref) . '" class="' . gf_h($allClasses) . '"' . $idAttr . $titleAttr . ' translate="no">' . $markHtml . $textHtml . '</a>';
} else {
    echo '<div class="' . gf_h($allClasses) . '"' . $idAttr . $titleAttr . ' translate="no">' . $markHtml . $textHtml . '</div>';
}
