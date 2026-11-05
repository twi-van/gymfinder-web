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
        $extraFile = dirname(__DIR__) . '/lang/en_pages.php';
        $extra = is_file($extraFile) ? require $extraFile : [];
        $dict = array_merge(is_array($loaded) ? $loaded : [], is_array($extra) ? $extra : []);
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
    $mapped = gf_i18n_en()[$vi] ?? null;
    if ($mapped !== null && $mapped !== '') {
        return $mapped;
    }
    return gf_i18n_pattern($vi);
}

function gf_i18n_pattern(string $text): string
{
    if (preg_match('/^Phòng tập (.+) tại (.+) có khu tập luyện tiện nghi, nhiều mức giá, đánh giá hội viên và HLV hỗ trợ\.$/u', $text, $m)) {
        return $m[1] . ' in ' . $m[2] . ' has a well-equipped training floor, several price levels, member reviews, and trainer support.';
    }
    if (preg_match('/^Phòng tập (.+) tại (.+) phục vụ dữ liệu demo GYMFINDER với mức giá, đặc điểm và tiện ích đa dạng\.$/u', $text, $m)) {
        return $m[1] . ' in ' . $m[2] . ' is listed on GYMFINDER with a range of prices, features, and amenities.';
    }
    if (preg_match('/^Phòng tập (.+) tại (.+) sở hữu không gian tập luyện chuyên nghiệp, trang bị máy tập cao cấp, không gian rộng rãi thoáng mát cùng nhiều tiện ích đẳng cấp\.$/u', $text, $m)) {
        return $m[1] . ' in ' . $m[2] . ' has a professional training floor, modern equipment, an open space, and full amenities.';
    }
    if (preg_match('/^Huấn luyện viên (.+) có (\d+) năm kinh nghiệm thực chiến trong lĩnh vực thể hình và fitness, chuyên sâu xây dựng giáo án cá nhân hóa và hướng dẫn kỹ thuật chuẩn xác\.$/u', $text, $m)) {
        $years = (int) $m[2];
        $yearLabel = $years === 1 ? '1 year' : ($years . ' years');
        return $m[1] . ' is a trainer with ' . $yearLabel . ' of hands-on fitness experience, focused on personal programs and correct technique.';
    }
    if (preg_match('/^(\d+) sao$/u', $text, $m)) {
        return $m[1] . ((int) $m[1] === 1 ? ' star' : ' stars');
    }
    if (preg_match('/^Từ (\d+)★ trở lên$/u', $text, $m) || preg_match('/^Từ (\d+) sao trở lên$/u', $text, $m)) {
        return 'From ' . $m[1] . '★ up';
    }
    if (preg_match('/^(\d+)★ trở lên$/u', $text, $m)) {
        return $m[1] . '★ and up';
    }
    if (preg_match('/^Từ ([\d\.]+)đ$/u', $text, $m)) {
        return 'From ' . str_replace('.', ',', $m[1]) . ' VND';
    }
    if (preg_match('/^Đến ([\d\.]+)đ$/u', $text, $m)) {
        return 'Up to ' . str_replace('.', ',', $m[1]) . ' VND';
    }
    if (preg_match('/^(\d+) năm kinh nghiệm$/u', $text, $m)) {
        return $m[1] . ((int) $m[1] === 1 ? ' year of experience' : ' years of experience');
    }
    if (preg_match('/^(\d+) năm KN$/u', $text, $m)) {
        return $m[1] . ' yrs exp.';
    }
    if (preg_match('/^(\d+) năm$/u', $text, $m)) {
        return $m[1] . ((int) $m[1] === 1 ? ' year' : ' years');
    }
    if (preg_match('/^(\d+) đánh giá đã công khai$/u', $text, $m)) {
        return $m[1] . ' public reviews';
    }
    if (preg_match('/^(\d+) đánh giá$/u', $text, $m)) {
        return $m[1] . ((int) $m[1] === 1 ? ' review' : ' reviews');
    }
    if (preg_match('/^(\d+) đã công khai$/u', $text, $m)) {
        return $m[1] . ' public';
    }
    if (preg_match('/^(\d+) lượt$/u', $text, $m)) {
        return $m[1] . ((int) $m[1] === 1 ? ' turn' : ' turns');
    }
    if (preg_match('/^(\d+) phòng tập được tìm thấy$/u', $text, $m)) {
        return $m[1] . ' gyms found';
    }
    if (preg_match('/^(.+) \+(\d+) phòng tập$/u', $text, $m)) {
        $n = (int) $m[2];
        return $m[1] . ' +' . $n . ($n === 1 ? ' gym' : ' gyms');
    }
    if (preg_match('/^(\d+) phòng tập$/u', $text, $m)) {
        return $m[1] . ((int) $m[1] === 1 ? ' gym' : ' gyms');
    }
    if (preg_match('/^(\d+) huấn luyện viên$/u', $text, $m)) {
        return $m[1] . ' trainers';
    }
    if (preg_match('/^Ảnh phòng tập (.+)$/u', $text, $m)) {
        return 'Photo of ' . $m[1];
    }
    if (preg_match('/^OTP chữ số (\d+)$/u', $text, $m)) {
        return 'OTP digit ' . $m[1];
    }
    if (preg_match('/^Không thể xóa: còn (\d+) phòng tập đang gắn loại này\. Hãy gỡ danh mục khỏi các phòng tập hoặc dùng Ẩn\.$/u', $text, $m)) {
        return 'Cannot delete: ' . $m[1] . ' gyms still use this category. Remove it from those gyms, or hide it instead.';
    }
    if (preg_match('/^Không thể xóa: còn (\d+) huấn luyện viên đang dùng chuyên môn này\.$/u', $text, $m)) {
        return 'Cannot delete: ' . $m[1] . ' trainers still use this specialty.';
    }
    if (preg_match('/^Không thể xóa: còn (\d+) phòng tập đang gắn tiện ích này\.$/u', $text, $m)) {
        return 'Cannot delete: ' . $m[1] . ' gyms still use this amenity.';
    }
    if (preg_match('/^Không thể xóa: còn (\d+) phòng tập thuộc khu vực này\.$/u', $text, $m)) {
        return 'Cannot delete: ' . $m[1] . ' gyms are in this area.';
    }
    if (preg_match('/^Không thể xóa: còn (\d+) huấn luyện viên gắn khu vực này\.$/u', $text, $m)) {
        return 'Cannot delete: ' . $m[1] . ' trainers are linked to this area.';
    }
    if (preg_match('/^Loại này còn (\d+) phòng tập đang gắn\. Hãy gỡ danh mục khỏi các phòng hoặc dùng Ẩn\.$/u', $text, $m)) {
        return 'This category is still on ' . $m[1] . ' gyms. Remove it from those gyms, or hide it instead.';
    }
    return $text;
}

/** Địa danh giữ nguyên ở cả hai ngôn ngữ. */
function gf_place_name(string $name): string
{
    return $name;
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
