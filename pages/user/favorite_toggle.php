<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$user = gf_require_login();
$type = $_POST['target_type'] ?? 'gym';
$id = (int) ($_POST['target_id'] ?? 0);
if ($id) {
    gf_toggle_fav((int) $user['id'], $type === 'trainer' ? 'trainer' : 'gym', $id);
}
$back = (string) ($_POST['redirect'] ?? gf_url('pages/user/favorites.php'));
$parts = parse_url($back);
if ($back === '' || !str_starts_with($back, '/') || isset($parts['scheme']) || isset($parts['host'])) {
    $back = gf_url('pages/user/favorites.php');
}
$anchorType = $type === 'trainer' ? 'trainer' : 'gym';
gf_redirect($back . '#favorite-' . $anchorType . '-' . $id);
