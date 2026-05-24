<?php

$env = [];
$envPath = __DIR__ . '/../.env';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");
            if ($key !== '') {
                $env[$key] = $value;
            }
        }
    }
}

$getEnv = function (string $key, $default = null) use ($env) {
    if (array_key_exists($key, $env) && $env[$key] !== '') {
        return $env[$key];
    }
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return $default;
};

return [
    'app_url' => $getEnv('APP_URL', 'http://localhost/PRO-PFE-YOPY/yopy-platform'),
    'mail_from' => $getEnv('MAIL_FROM', 'no-reply@yopy.local'),
    'mail_from_name' => $getEnv('MAIL_FROM_NAME', 'YOPY'),
    'smtp_host' => $getEnv('SMTP_HOST', ''),
    'smtp_port' => (int) $getEnv('SMTP_PORT', 587),
    'smtp_username' => $getEnv('SMTP_USERNAME', ''),
    'smtp_password' => $getEnv('SMTP_PASSWORD', ''),
    'smtp_encryption' => $getEnv('SMTP_ENCRYPTION', 'tls'),
];