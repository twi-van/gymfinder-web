<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$from = (($_GET['from'] ?? '') === 'register') ? 'register' : 'login';
gf_google_begin($from, (string) ($_GET['redirect'] ?? ''));
