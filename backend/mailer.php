<?php
declare(strict_types=1);

/** Return true when SMTP has enough settings to send real email. */
function gf_smtp_is_configured(): bool
{
    $smtp = $GLOBALS['GF_CONFIG']['smtp'] ?? [];
    foreach (['host', 'port', 'username', 'password', 'from_email'] as $key) {
        if (empty($smtp[$key])) {
            return false;
        }
    }
    return filter_var((string) $smtp['from_email'], FILTER_VALIDATE_EMAIL) !== false;
}

/** Read one complete SMTP response, including multiline replies. */
function gf_smtp_read_response($socket): array
{
    $response = '';
    $code = 0;
    for ($i = 0; $i < 100; $i++) {
        $line = fgets($socket, 515);
        if ($line === false) {
            $meta = stream_get_meta_data($socket);
            throw new RuntimeException(!empty($meta['timed_out']) ? 'SMTP response timed out.' : 'SMTP connection closed unexpectedly.');
        }
        $response .= $line;
        if (preg_match('/^(\d{3})([ -])/', $line, $match)) {
            $code = (int) $match[1];
            if ($match[2] === ' ') {
                return [$code, trim($response)];
            }
        }
    }
    throw new RuntimeException('SMTP returned an invalid response.');
}

/** Send one SMTP command and validate its response code. */
function gf_smtp_command($socket, string $command, array $expectedCodes): string
{
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('Could not write to SMTP connection.');
    }
    [$code, $response] = gf_smtp_read_response($socket);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP command failed with response ' . $code . '.');
    }
    return $response;
}

/** Send the password-reset OTP using the configured SMTP server. */
function gf_send_password_reset_otp_email(string $recipient, string $otp): bool
{
    $smtp = $GLOBALS['GF_CONFIG']['smtp'] ?? [];
    if (!gf_smtp_is_configured()) {
        return false;
    }
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)
        || preg_match('/[\r\n]/', $recipient)
        || preg_match('/[\r\n]/', (string) ($smtp['from_name'] ?? ''))) {
        throw new InvalidArgumentException('Invalid email address or sender name.');
    }

    $host = (string) $smtp['host'];
    $port = (int) $smtp['port'];
    $encryption = (string) ($smtp['encryption'] ?? 'tls');
    if (!in_array($encryption, ['tls', 'ssl'], true) || $port < 1 || $port > 65535) {
        throw new InvalidArgumentException('Invalid SMTP encryption or port setting.');
    }
    if (!preg_match('/^[A-Za-z0-9.-]+$/D', $host)) {
        throw new InvalidArgumentException('Invalid SMTP host setting.');
    }

    $scheme = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ],
    ]);
    $socket = @stream_socket_client($scheme . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!is_resource($socket)) {
        throw new RuntimeException('SMTP connection failed (' . $errno . '): ' . $errstr);
    }

    stream_set_timeout($socket, 15);
    try {
        [, $greeting] = gf_smtp_read_response($socket);
        // Re-read code from the greeting to validate it through the standard command helper.
        if (!preg_match('/^220\b/', $greeting)) {
            throw new RuntimeException('SMTP server did not accept the connection.');
        }

        $clientName = preg_replace('/[^a-zA-Z0-9.-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
        gf_smtp_command($socket, 'EHLO ' . $clientName, [250]);
        if ($encryption === 'tls') {
            gf_smtp_command($socket, 'STARTTLS', [220]);
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto !== true) {
                throw new RuntimeException('Could not establish SMTP TLS encryption.');
            }
            gf_smtp_command($socket, 'EHLO ' . $clientName, [250]);
        }

        gf_smtp_command($socket, 'AUTH LOGIN', [334]);
        gf_smtp_command($socket, base64_encode((string) $smtp['username']), [334]);
        gf_smtp_command($socket, base64_encode((string) $smtp['password']), [235]);

        $fromEmail = (string) $smtp['from_email'];
        gf_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        gf_smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        gf_smtp_command($socket, 'DATA', [354]);

        $fromName = (string) ($smtp['from_name'] ?? 'GYMFINDER');
        $subject = mb_encode_mimeheader('Mã OTP đặt lại mật khẩu GYMFINDER', 'UTF-8', 'B');
        $boundary = 'gf_' . bin2hex(random_bytes(16));
        $plain = "Xin chào,\n\nMã OTP đặt lại mật khẩu GYMFINDER của bạn là: {$otp}\nMã có hiệu lực trong 10 phút và chỉ sử dụng một lần.\n\nNếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.";
        $html = '<!doctype html><html lang="vi"><body style="font-family:Arial,sans-serif;color:#1e293b">'
            . '<h2>Đặt lại mật khẩu GYMFINDER</h2><p>Mã xác nhận của bạn:</p>'
            . '<p style="font-size:30px;font-weight:bold;letter-spacing:8px;color:#00bfae">' . $otp . '</p>'
            . '<p>Mã có hiệu lực trong <strong>10 phút</strong> và chỉ sử dụng một lần.</p>'
            . '<p>Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.</p></body></html>';
        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($plain), 76, "\r\n")
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html), 76, "\r\n")
            . '--' . $boundary . '--';
        $headers = [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . mb_encode_mimeheader($fromName, 'UTF-8', 'B') . ' <' . $fromEmail . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $host . '>',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $message = preg_replace('/(?m)^\./', '..', $message) ?? $message;
        if (fwrite($socket, $message . "\r\n.\r\n") === false) {
            throw new RuntimeException('Could not send email message to SMTP server.');
        }
        [$sendCode, $sendResponse] = gf_smtp_read_response($socket);
        if ($sendCode !== 250) {
            throw new RuntimeException('SMTP server rejected the email message (' . $sendCode . ').');
        }
        // The message is accepted after the 250 response; a QUIT failure must not
        // make the caller treat an already-delivered email as a failed send.
        @fwrite($socket, "QUIT\r\n");
        return true;
    } finally {
        fclose($socket);
    }
}
