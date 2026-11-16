<?php
declare(strict_types=1);

// Healthcheck không cần MySQL — để Railway không restart khi DB chưa sẵn.
header('Content-Type: text/plain; charset=utf-8');
http_response_code(200);
echo "ok\n";
