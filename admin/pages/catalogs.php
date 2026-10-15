<?php
/** Tương thích link cũ → gộp vào trang Danh mục. */
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$tab = (string) ($_GET['tab'] ?? 'specialties');
$allowed = ['specialties', 'amenities', 'districts', 'categories'];
if (!in_array($tab, $allowed, true)) {
    $tab = 'specialties';
}
$query = ['tab' => $tab];
foreach (['q', 'page', 'created', 'updated', 'deleted'] as $key) {
    if (isset($_GET[$key]) && (string) $_GET[$key] !== '') {
        $query[$key] = (string) $_GET[$key];
    }
}
gf_redirect(gf_url('admin/pages/categories.php?' . http_build_query($query)));
