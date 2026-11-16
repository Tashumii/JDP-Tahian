<?php

function get_mail_settings() {
    static $settings = null;
    if ($settings !== null) return $settings;

    $localPath = __DIR__ . '/../config/mail.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) $local = [];

    $value = function ($environmentKey, $localKey, $default = '') use ($local) {
        $environmentValue = getenv($environmentKey);
        if ($environmentValue !== false && $environmentValue !== '') return $environmentValue;
        return $local[$localKey] ?? $default;
    };

    $settings = [
        'smtp_host' => trim((string)$value('SMTP_HOST', 'smtp_host')),
        'smtp_port' => (int)$value('SMTP_PORT', 'smtp_port', 587),
        'smtp_username' => (string)$value('SMTP_USERNAME', 'smtp_username'),
        'smtp_password' => (string)$value('SMTP_PASSWORD', 'smtp_password'),
        'smtp_encryption' => strtolower(trim((string)$value('SMTP_ENCRYPTION', 'smtp_encryption', 'tls'))),
        'from_email' => trim((string)$value('MAIL_FROM_EMAIL', 'from_email')),
        'from_name' => trim((string)$value('MAIL_FROM_NAME', 'from_name', 'Joey Tahian')),
        'app_base_url' => rtrim(trim((string)$value('APP_BASE_URL', 'app_base_url')), '/')
    ];

    if ($settings['from_email'] === '' && $settings['smtp_username'] !== '') {
        $settings['from_email'] = $settings['smtp_username'];
    }

    if ($settings['app_base_url'] === '') {
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if ($serverName !== '' && preg_match('/^[A-Za-z0-9.-]+$/', $serverName)
            && preg_match('~/backend/api/orders/[^/]+$~', $scriptName)) {
            $rootPath = preg_replace('~/backend/api/orders/[^/]+$~', '', $scriptName);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $settings['app_base_url'] = $scheme . '://' . $serverName . rtrim($rootPath, '/') . '/frontend';
        }
    }

    return $settings;
}

function smtp_write_all($socket, $value) {
    $length = strlen($value);
    $written = 0;
    while ($written < $length) {
        $count = fwrite($socket, substr($value, $written));
        if ($count === false || $count === 0) {
            throw new RuntimeException('The SMTP connection closed while sending data.');
        }
        $written += $count;
    }
}

function smtp_read_response($socket, $expectedCodes) {
    $response = '';
    do {
        $line = fgets($socket, 515);
        if ($line === false) {
            throw new RuntimeException('No response was received from the SMTP server.');
        }
        $response .= $line;
    } while (strlen($line) >= 4 && $line[3] === '-');

    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP server rejected a command: ' . trim($response));
    }
    return $response;
}

function smtp_command($socket, $command, $expectedCodes) {
    smtp_write_all($socket, $command . "\r\n");
    return smtp_read_response($socket, $expectedCodes);
}

function send_smtp_email($recipient, $recipientName, $subject, $body) {
    $settings = get_mail_settings();
    $baseUrlParts = parse_url($settings['app_base_url']);
    if ($settings['smtp_host'] === '' || $settings['smtp_port'] < 1
        || !filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL)
        || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
        || !is_array($baseUrlParts)
        || !in_array($baseUrlParts['scheme'] ?? '', ['http', 'https'], true)
        || empty($baseUrlParts['host'])) {
        throw new RuntimeException('SMTP or public app URL is not configured.');
    }
    if (!in_array($settings['smtp_encryption'], ['tls', 'ssl', 'none'], true)) {
        throw new RuntimeException('SMTP_ENCRYPTION must be tls, ssl, or none.');
    }
    if (($settings['smtp_username'] === '') !== ($settings['smtp_password'] === '')) {
        throw new RuntimeException('Both SMTP username and password must be configured.');
    }
    if ($settings['smtp_username'] !== '' && $settings['smtp_encryption'] === 'none') {
        throw new RuntimeException('SMTP authentication requires TLS or SSL encryption.');
    }
    if (preg_match('/[\r\n]/', $settings['from_name'])) {
        throw new RuntimeException('MAIL_FROM_NAME cannot contain line breaks.');
    }

    $host = $settings['smtp_host'];
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
        throw new RuntimeException('SMTP_HOST is invalid.');
    }
    $transport = $settings['smtp_encryption'] === 'ssl' ? 'ssl://' : 'tcp://';
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host
        ]
    ]);

    $socket = @stream_socket_client(
        $transport . $host . ':' . $settings['smtp_port'],
        $errorNumber,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if ($socket === false) {
        throw new RuntimeException('Could not connect to the SMTP server: ' . $errorMessage);
    }

    stream_set_timeout($socket, 15);
    try {
        smtp_read_response($socket, [220]);
        $heloName = preg_replace('/[^A-Za-z0-9.-]/', '', gethostname() ?: 'localhost');
        smtp_command($socket, 'EHLO ' . ($heloName ?: 'localhost'), [250]);

        if ($settings['smtp_encryption'] === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            $cryptoEnabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoEnabled !== true) {
                throw new RuntimeException('Could not establish TLS with the SMTP server.');
            }
            smtp_command($socket, 'EHLO ' . ($heloName ?: 'localhost'), [250]);
        }

        if ($settings['smtp_username'] !== '') {
            smtp_command($socket, 'AUTH LOGIN', [334]);
            smtp_command($socket, base64_encode($settings['smtp_username']), [334]);
            smtp_command($socket, base64_encode($settings['smtp_password']), [235]);
        }

        smtp_command($socket, 'MAIL FROM:<' . $settings['from_email'] . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $fromName = $settings['from_name'] !== ''
            ? '=?UTF-8?B?' . base64_encode($settings['from_name']) . '?= '
            : '';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $fromName . '<' . $settings['from_email'] . '>',
            'To: ' . ($recipientName !== '' ? '=?UTF-8?B?' . base64_encode($recipientName) . '?= ' : '') . '<' . $recipient . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64'
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n"
            . rtrim(chunk_split(base64_encode($body), 76, "\r\n"), "\r\n");
        smtp_write_all($socket, $message . "\r\n.\r\n");
        smtp_read_response($socket, [250]);
        smtp_command($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

function send_order_confirmation_email($recipient, $customerName, $orderNo, $total, $confirmationToken, $items = []) {
    $settings = get_mail_settings();
    $confirmationUrl = $settings['app_base_url'] . '/confirm-order.html?token=' . rawurlencode($confirmationToken);
    $subject = 'Confirm your Joey Tahian order ' . $orderNo;
    $itemLines = [];
    foreach ($items as $item) {
        $itemLines[] = '- ' . $item['name'] . ' × ' . (int)$item['qty']
            . ' at ₱' . number_format((float)$item['unit_price'], 2);
    }
    $itemsText = $itemLines ? "\nItems:\n" . implode("\n", $itemLines) . "\n" : '';
    $body = "Hello $customerName,\n\n"
        . "We received your order $orderNo for ₱" . number_format((float)$total, 2) . ".\n"
        . $itemsText
        . "Confirm the order using this link within 24 hours:\n$confirmationUrl\n\n"
        . "Your order will remain pending until you confirm it. If you did not place this order, you can ignore this email.\n\n"
        . "Joey Tahian";

    send_smtp_email($recipient, $customerName, $subject, $body);
}
