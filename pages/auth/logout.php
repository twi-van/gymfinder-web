<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
gf_logout();
gf_redirect(gf_url('index.php'));
