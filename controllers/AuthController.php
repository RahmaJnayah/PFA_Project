<?php

namespace Controllers;

use Models\UserModel;
use Models\PasswordResetModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class AuthController
{
    private const MAX_EMAIL_LENGTH = 254;
    private const MAX_USERNAME_LENGTH = 50;

    private static function getBasePath()
    {
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        return $basePath === '/' ? '' : $basePath;
    }

    private static function sanitizeEmail($email)
    {
        $email = trim((string) $email);
        if ($email === '' || strlen($email) > self::MAX_EMAIL_LENGTH || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        return $email;
    }

    private static function sanitizeUsername($username)
    {
        $username = trim((string) $username);
        if ($username === '' || strlen($username) > self::MAX_USERNAME_LENGTH) {
            return '';
        }

        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            return '';
        }

        return $username;
    }

    private static function stashRegisterOld(array $input)
    {
        $_SESSION['register_old'] = [
            'username' => trim((string) ($input['username'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
        ];
    }

    private static function getAppConfig()
    {
        $configPath = __DIR__ . '/../config/app.php';
        if (file_exists($configPath)) {
            $config = include $configPath;
            if (is_array($config)) {
                return $config;
            }
        }
        return [];
    }

    private static function buildResetLink($basePath, $email, $selector, $token)
    {
        $config = self::getAppConfig();
        $appUrl = $config['app_url'] ?? '';
        if ($appUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $appUrl = $scheme . '://' . $host;
        }
        $appUrl = rtrim($appUrl, '/');
        $baseUrl = $appUrl;
        if ($basePath !== '' && substr($appUrl, -strlen($basePath)) !== $basePath) {
            $baseUrl = $appUrl . $basePath;
        }
        $emailParam = urlencode($email);
        $selectorParam = urlencode($selector);
        $tokenParam = urlencode($token);
        return $baseUrl . '/auth/reset?selector=' . $selectorParam . '&token=' . $tokenParam . '&email=' . $emailParam;
    }

    private static function sendResetEmail($toEmail, $resetLink)
    {
        $config = self::getAppConfig();
        $fromEmail = $config['mail_from'] ?? 'no-reply@yopy.local';
        $fromName = $config['mail_from_name'] ?? 'YOPY';
        $smtpHost = $config['smtp_host'] ?? '';
        $smtpPort = $config['smtp_port'] ?? 587;
        $smtpUser = $config['smtp_username'] ?? '';
        $smtpPass = $config['smtp_password'] ?? '';
        $smtpEncryption = $config['smtp_encryption'] ?? 'tls';
        $subject = 'Reset your YOPY password';
        $message = "We received a request to reset your password.\n\n";
        $message .= "Use the link below to set a new password (valid for 30 minutes):\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "If you did not request this, you can ignore this email.\n";

        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            self::logResetEmail($toEmail, $resetLink);
            self::logAppEvent('Composer autoload not found.');
            return false;
        }
        if ($smtpHost === '') {
            self::logResetEmail($toEmail, $resetLink);
            self::logAppEvent('SMTP host is not configured.');
            return false;
        }

        require_once $autoloadPath;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int) $smtpPort;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            self::logResetEmail($toEmail, $resetLink);
            self::logAppEvent('PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    private static function logAppEvent($message)
    {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            return;
        }
        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($logDir . '/app.log', $entry, FILE_APPEND);
    }

    private static function logResetEmail($toEmail, $resetLink)
    {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            return;
        }
        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $toEmail . ' ' . $resetLink . PHP_EOL;
        @file_put_contents($logDir . '/reset_emails.log', $entry, FILE_APPEND);
    }

    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $basePath = self::getBasePath();
        

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['login_error'] = 'Invalid session token. Please try again.';
                header('Location: ' . $basePath . '/auth/login');
                exit;
            }

            $email = self::sanitizeEmail($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                $_SESSION['login_error'] = 'Please enter a valid email and password.';
                $_SESSION['old_email'] = trim((string) ($_POST['email'] ?? ''));
                header('Location: ' . $basePath . '/auth/login');
                exit;
            }

            try {
                $user = UserModel::findByEmail($email);
            } catch (\Throwable $e) {
                self::logAppEvent('Login failed with backend error: ' . $e->getMessage());
                $_SESSION['login_error'] = 'Unable to process login right now. Please try again later.';
                $_SESSION['old_email'] = $email;
                header('Location: ' . $basePath . '/auth/login');
                exit;
            }

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                header('Location: ' . $basePath . '/views/auth/modeChose.php');
                exit;
            } else {
                $_SESSION['login_error'] = 'Invalid email or password.';
                $_SESSION['old_email'] = $email;
                header('Location: ' . $basePath . '/auth/login');
                exit;
            }
        }

        include __DIR__ . '/../views/auth/login.php';
    }

    public function register()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $basePath = self::getBasePath();
        $config = include __DIR__ . '/../config/database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['register_error'] = 'Invalid session token. Please try again.';
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

            $username = self::sanitizeUsername($_POST['username'] ?? '');
            $email = self::sanitizeEmail($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $pin = trim($_POST['pin'] ?? '');
            $confirmPin = trim($_POST['confirm_pin'] ?? '');

            if ($username === '' || $email === '') {
                $_SESSION['register_error'] = 'Please provide a valid username and email address.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

            if (strlen($password) < 8) {
                $_SESSION['register_error'] = 'Password must be at least 8 characters.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

            if ($password !== $confirmPassword) {
                $_SESSION['register_error'] = 'Passwords do not match.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

            // Validate PIN if provided (required from the form)
            if ($pin === '' || !preg_match('/^\d{4}$/', $pin)) {
                $_SESSION['register_error'] = 'PIN must be exactly 4 digits.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }
            if ($pin !== $confirmPin) {
                $_SESSION['register_error'] = 'PINs do not match.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

            try {
                if (UserModel::findByEmail($email)) {
                    $_SESSION['register_error'] = 'Email is already registered. Please log in or use another email.';
                    self::stashRegisterOld($_POST);
                    header('Location: ' . $basePath . '/auth/register');
                    exit;
                }

                if (UserModel::findByUsername($username)) {
                    $_SESSION['register_error'] = 'Username is already taken. Please choose another.';
                    self::stashRegisterOld($_POST);
                    header('Location: ' . $basePath . '/auth/register');
                    exit;
                }
            } catch (\Throwable $e) {
                self::logAppEvent('Register lookup failed: ' . $e->getMessage());
                $_SESSION['register_error'] = 'Unable to validate account details right now. Please try again later.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            try {
                $userId = UserModel::create([
    'username' => $username,
    'email' => $email,
    'password_hash' => $hashedPassword,
    'role' => 'parent',
]);
            } catch (\Throwable $e) {
                self::logAppEvent('Register create failed: ' . $e->getMessage());
                $_SESSION['register_error'] = 'Unable to create account right now. Please try again later.';
                self::stashRegisterOld($_POST);
                header('Location: ' . $basePath . '/auth/register');
                exit;
            }

// If a PIN was supplied, update the user's pin_hash after create
if ($userId && $pin !== '') {
    $pinHash = password_hash($pin, PASSWORD_BCRYPT);
    try {
        $conn2 = new \mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );
        if (!$conn2->connect_error) {
            $up = $conn2->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
            $up->bind_param('si', $pinHash, $userId);
            $up->execute();
            $up->close();
        }
        $conn2->close();
    } catch (\Exception $e) {
        error_log("Failed to save PIN for user $userId: " . $e->getMessage());
    }
}

if ($userId) {
    // Insert corresponding parent record
    try {
        $conn = new \mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO parents (user_id) VALUES (?)");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
    } catch (\Exception $e) {
        // Log error but still proceed – the parent can still be used with the fallback in modeChose.php
        error_log("Failed to create parents record for user $userId: " . $e->getMessage());
    }

    $_SESSION['register_success'] = 'Account created successfully. Please log in.';
    header('Location: ' . $basePath . '/auth/login');
    exit;
} else {
    $_SESSION['register_error'] = 'Failed to create account. Please try again.';
    $_SESSION['register_old'] = $_POST;
    header('Location: ' . $basePath . '/auth/register');
    exit;
}
        }

        include __DIR__ . '/../views/auth/register.php';
    }

    public function forgotPassword()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $basePath = self::getBasePath();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::logAppEvent('Forgot password POST received.');
            if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                self::logAppEvent('Forgot password CSRF failed.');
                $_SESSION['forgot_error'] = 'Invalid session token. Please try again.';
                header('Location: ' . $basePath . '/auth/forgot');
                exit;
            }

            $email = self::sanitizeEmail($_POST['email'] ?? '');
            $user = null;
            if ($email !== '') {
                try {
                    $user = UserModel::findByEmail($email);
                } catch (\Throwable $e) {
                    self::logAppEvent('Forgot password lookup failed: ' . $e->getMessage());
                }
            }
            self::logAppEvent('Forgot password user found: ' . ($user ? 'yes' : 'no'));

            if ($user) {
                PasswordResetModel::deleteByEmail($email);
                $selector = bin2hex(random_bytes(8));
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);
                PasswordResetModel::create($email, $selector, $tokenHash, $expiresAt);

                $resetLink = self::buildResetLink($basePath, $email, $selector, $token);
                self::sendResetEmail($email, $resetLink);
            }

            $_SESSION['forgot_message'] = 'If that email is in our system, you will receive a link shortly.';
            header('Location: ' . $basePath . '/auth/forgot');
            exit;
        }

        include __DIR__ . '/../views/auth/forgot.php';
    }

    public function resetPassword()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $basePath = self::getBasePath();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['reset_error'] = 'Invalid session token. Please try again.';
                header('Location: ' . $basePath . '/auth/forgot');
                exit;
            }

            $email = self::sanitizeEmail($_POST['email'] ?? '');
            $selector = $_POST['selector'] ?? '';
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($email === '' || !preg_match('/^[a-f0-9]{16}$/i', $selector) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
                $_SESSION['reset_error'] = 'Link expired or invalid.';
                header('Location: ' . $basePath . '/auth/forgot');
                exit;
            }

            if (strlen($password) < 8) {
                $_SESSION['reset_error'] = 'Password must be at least 8 characters.';
                header('Location: ' . $basePath . '/auth/reset?selector=' . urlencode($selector) . '&token=' . urlencode($token) . '&email=' . urlencode($email));
                exit;
            }

            if ($password === '' || $password !== $confirmPassword) {
                $_SESSION['reset_error'] = 'Passwords do not match.';
                header('Location: ' . $basePath . '/auth/reset?selector=' . urlencode($selector) . '&token=' . urlencode($token) . '&email=' . urlencode($email));
                exit;
            }

            try {
                $reset = PasswordResetModel::findValidByEmailAndSelector($email, $selector);
                $tokenHash = hash('sha256', $token);
                if (!$reset || !hash_equals($reset['token_hash'], $tokenHash)) {
                    $_SESSION['reset_error'] = 'Link expired or invalid.';
                    header('Location: ' . $basePath . '/auth/forgot');
                    exit;
                }

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                UserModel::updatePasswordByEmail($email, $hashedPassword);
                PasswordResetModel::deleteById($reset['reset_id']);
            } catch (\Throwable $e) {
                self::logAppEvent('Reset password failed: ' . $e->getMessage());
                $_SESSION['reset_error'] = 'Unable to reset password right now. Please try again later.';
                header('Location: ' . $basePath . '/auth/forgot');
                exit;
            }

            $_SESSION['login_success'] = 'Password updated successfully. Please log in.';
            header('Location: ' . $basePath . '/auth/login');
            exit;
        }

        $resetEmail = self::sanitizeEmail($_GET['email'] ?? '');
        $selector = $_GET['selector'] ?? '';
        $token = $_GET['token'] ?? '';
        $isValid = false;

        if (
            $resetEmail !== ''
            && preg_match('/^[a-f0-9]{16}$/i', $selector)
            && preg_match('/^[a-f0-9]{64}$/i', $token)
        ) {
            try {
                $reset = PasswordResetModel::findValidByEmailAndSelector($resetEmail, $selector);
                $tokenHash = hash('sha256', $token);
                if ($reset && hash_equals($reset['token_hash'], $tokenHash)) {
                    $isValid = true;
                } else {
                    $_SESSION['reset_error'] = 'Link expired or invalid.';
                }
            } catch (\Throwable $e) {
                self::logAppEvent('Reset password link validation failed: ' . $e->getMessage());
                $_SESSION['reset_error'] = 'Link expired or invalid.';
            }
        }

        include __DIR__ . '/../views/auth/reset.php';
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $basePath = self::getBasePath();
        session_destroy();
        header('Location: ' . $basePath . '/auth/login');
        exit;
    }
}

?>