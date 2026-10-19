<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gf_redirect(gf_url('index.php'));
}
gf_logout();
gf_redirect(gf_url('index.php'));
