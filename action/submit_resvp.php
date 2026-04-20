<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

send_security_headers();

$config = require __DIR__ . '/../config/app.php';
$site = is_array($config['site'] ?? null) ? $config['site'] : [];
$wedding = is_array($config['wedding'] ?? null) ? $config['wedding'] : [];
date_default_timezone_set((string) ($site['timezone'] ?? 'Asia/Jakarta'));

function redirect_with_status(string $status): void
{
    header('Location: ../index.php?rsvp=' . urlencode($status) . '#rsvp');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php#rsvp');
    exit;
}

if (is_rsvp_closed($wedding)) {
    redirect_with_status('closed');
}

if (!enforce_rate_limit('rsvp_submit', 5, 600)) {
    redirect_with_status('rate');
}

$honeypotInput = $_POST['website'] ?? '';
$honeypot = is_scalar($honeypotInput) ? trim((string) $honeypotInput) : '__invalid__';
if ($honeypot !== '') {
    redirect_with_status('security');
}

$formTimeInput = $_POST['form_time'] ?? null;
if (!is_scalar($formTimeInput) || !preg_match('/^\d{1,10}$/', (string) $formTimeInput)) {
    redirect_with_status('security');
}

$formTime = (int) $formTimeInput;
$elapsed = time() - $formTime;
if ($formTime <= 0 || $elapsed < 2 || $elapsed > 7200) {
    redirect_with_status('security');
}

$csrfTokenInput = $_POST['csrf_token'] ?? null;
if (!is_scalar($csrfTokenInput) || !verify_csrf_token((string) $csrfTokenInput)) {
    redirect_with_status('security');
}

$validation = validate_rsvp_payload($_POST);
if (!$validation['ok']) {
    redirect_with_status('invalid');
}

if (!($pdo instanceof PDO)) {
    redirect_with_status('db');
}

$clean = $validation['data'];
$userAgent = normalize_text((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255);

try {
    $stmt = $pdo->prepare(
        'INSERT INTO rsvp_messages (guest_name, attendance_status, message, ip_address, user_agent)
         VALUES (:guest_name, :attendance_status, :message, :ip_address, :user_agent)'
    );

    $stmt->execute([
        ':guest_name' => $clean['name'],
        ':attendance_status' => $clean['status'],
        ':message' => $clean['message'],
        ':ip_address' => client_ip(),
        ':user_agent' => $userAgent,
    ]);

    unset($_SESSION['csrf_token'], $_SESSION['csrf_issued_at']);
    redirect_with_status('success');
} catch (Throwable $e) {
    redirect_with_status('db');
}