<?php
declare(strict_types=1);

/**
 * VI / EN. Mặc định tiếng Việt.
 * ?lang=en|vi đặt cookie + session rồi redirect (bỏ query lang).
 */
function gf_lang(): string
{
    $lang = (string) ($_SESSION['gf_lang'] ?? $_COOKIE['gf_lang'] ?? 'vi');
    return $lang === 'en' ? 'en' : 'vi';
}

function gf_lang_apply_request(): void
{
    $lang = (string) ($_GET['lang'] ?? '');
    if ($lang !== 'vi' && $lang !== 'en') {
        if (!isset($_SESSION['gf_lang']) && isset($_COOKIE['gf_lang']) && ($_COOKIE['gf_lang'] === 'vi' || $_COOKIE['gf_lang'] === 'en')) {
            $_SESSION['gf_lang'] = $_COOKIE['gf_lang'];
        }
        return;
    }
    $_SESSION['gf_lang'] = $lang;
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('gf_lang', $lang, [
        'expires' => time() + 365 * 86400,
        'path' => '/',
        'secure' => $secure,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $qs = $_GET;
    unset($qs['lang']);
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $target = $path . ($qs !== [] ? '?' . http_build_query($qs) : '');
    header('Location: ' . $target);
    exit;
}

/** @return array<string,string> */
function gf_i18n_en(): array
{
    static $dict = null;
    if ($dict === null) {
        $file = dirname(__DIR__) . '/lang/en.php';
        $loaded = is_file($file) ? require $file : [];
        $dict = is_array($loaded) ? $loaded : [];
    }
    return $dict;
}

function gf_t(string $vi, ?string $en = null): string
{
    if (gf_lang() !== 'en') {
        return $vi;
    }
    if ($en !== null && $en !== '') {
        return $en;
    }
    return gf_i18n_en()[$vi] ?? $vi;
}

function gf_place_name(string $name): string
{
    if (gf_lang() !== 'en') {
        return $name;
    }
    if (preg_match('/^Quận\s+(\d+)$/u', $name, $m)) {
        return 'District ' . $m[1];
    }
    return gf_t($name);
}

function gf_lang_url(string $lang): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $qs = $_GET;
    unset($qs['lang']);
    $qs['lang'] = $lang === 'en' ? 'en' : 'vi';
    return $path . '?' . http_build_query($qs);
}

function gf_lang_switch_html(): string
{
    $vi = gf_lang() === 'vi' ? ' is-active' : '';
    $en = gf_lang() === 'en' ? ' is-active' : '';
    return '<div class="gf-lang-switch" role="group" aria-label="Language">'
        . '<a class="' . trim($vi) . '" href="' . gf_h(gf_lang_url('vi')) . '" hreflang="vi">VI</a>'
        . '<a class="' . trim($en) . '" href="' . gf_h(gf_lang_url('en')) . '" hreflang="en">EN</a>'
        . '</div>';
}
