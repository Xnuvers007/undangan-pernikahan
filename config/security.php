<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function send_security_headers(): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?? '';
    $host = trim($host, '[]');
    $isLoopbackHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.localhost');

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('X-Download-Options: noopen');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Origin-Agent-Cluster: ?1');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if ($isHttps || $isLoopbackHost) {
        header('Cross-Origin-Opener-Policy: same-origin');
    }

    header('Cross-Origin-Resource-Policy: same-origin');

    $csp = "default-src 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; "
        . "img-src 'self' data:; "
        . "style-src 'self' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; "
        . "script-src 'self'; "
        . "connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; "
        . "form-action 'self'";

    if ($isHttps) {
        $csp .= '; upgrade-insecure-requests';
    }

    header('Content-Security-Policy: ' . $csp);

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalize_text(string $value, int $maxLength = 255): string
{
    $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    if (mb_strlen($value) > $maxLength) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function generate_csrf_token(): string
{
    $ttl = 7200;
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_issued_at']) || (time() - (int) $_SESSION['csrf_issued_at']) > $ttl) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_issued_at'] = time();
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals((string) $_SESSION['csrf_token'], (string) $token);
}

function get_guest_name_from_query(array $query): string
{
    $keys = ['tamu', 'to', 'dear', 'kepada'];
    foreach ($keys as $key) {
        if (array_key_exists($key, $query)) {
            $value = is_scalar($query[$key]) ? (string) $query[$key] : '';
            $value = strip_tags($value);
            $name = normalize_text($value, 80);
            $name = preg_replace('/[^\p{L}\p{N}\s&\.\'\-]/u', '', $name) ?? '';
            $name = normalize_text($name, 80);
            return $name !== '' ? $name : 'Tamu Undangan';
        }
    }

    return 'Tamu Undangan';
}

function parse_datetime_input(?string $value): ?DateTimeImmutable
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    $normalized = str_replace('T', ' ', $raw);

    try {
        return new DateTimeImmutable($normalized);
    } catch (Throwable $e) {
        return null;
    }
}

function build_invitation_schedule(array $wedding): array
{
    $akadIso = (string) ($wedding['akad_date_iso'] ?? $wedding['date_iso'] ?? '');
    $resepsiIso = (string) ($wedding['reseption_date_iso'] ?? $wedding['reception_date_iso'] ?? '');
    if ($resepsiIso === '') {
        $resepsiIso = (string) ($wedding['date_iso'] ?? '');
    }

    return [
        'akad' => [
            'type' => 'akad',
            'label' => 'Akad Nikah',
            'date_human' => (string) ($wedding['date_human'] ?? ''),
            'date_iso' => $akadIso,
            'datetime' => parse_datetime_input($akadIso),
        ],
        'resepsi' => [
            'type' => 'resepsi',
            'label' => 'Resepsi',
            'date_human' => (string) ($wedding['reseption_date_human'] ?? $wedding['reception_date_human'] ?? ''),
            'date_iso' => $resepsiIso,
            'datetime' => parse_datetime_input($resepsiIso),
        ],
    ];
}

function get_invitation_type_from_query(array $query): ?string
{
    $value = null;
    if (array_key_exists('invitation', $query) && is_scalar($query['invitation'])) {
        $value = (string) $query['invitation'];
    } elseif (array_key_exists('acara', $query) && is_scalar($query['acara'])) {
        $value = (string) $query['acara'];
    }

    $normalized = strtolower(normalize_text((string) $value, 20));
    if ($normalized === 'akad') {
        return 'akad';
    }

    if (in_array($normalized, ['resepsi', 'reseption', 'reception'], true)) {
        return 'resepsi';
    }

    return null;
}

function resolve_invitation_context(array $query, array $wedding, ?DateTimeImmutable $now = null): array
{
    $schedule = build_invitation_schedule($wedding);
    $requestedType = get_invitation_type_from_query($query);
    if ($requestedType !== null && isset($schedule[$requestedType])) {
        return $schedule[$requestedType];
    }

    $now = $now ?? new DateTimeImmutable('now');

    $upcoming = [];
    foreach ($schedule as $event) {
        $eventDate = $event['datetime'];
        if ($eventDate instanceof DateTimeImmutable && $eventDate >= $now) {
            $upcoming[] = $event;
        }
    }

    if ($upcoming !== []) {
        usort(
            $upcoming,
            static fn(array $a, array $b): int => $a['datetime']->getTimestamp() <=> $b['datetime']->getTimestamp()
        );
        return $upcoming[0];
    }

    $dated = [];
    foreach ($schedule as $event) {
        if ($event['datetime'] instanceof DateTimeImmutable) {
            $dated[] = $event;
        }
    }

    if ($dated !== []) {
        usort(
            $dated,
            static fn(array $a, array $b): int => $a['datetime']->getTimestamp() <=> $b['datetime']->getTimestamp()
        );
        return $dated[count($dated) - 1];
    }

    return $schedule['akad'];
}

function get_rsvp_close_datetime(array $wedding): ?DateTimeImmutable
{
    $manualClose = parse_datetime_input((string) ($wedding['rsvp_close_iso'] ?? ''));
    if ($manualClose instanceof DateTimeImmutable) {
        return $manualClose;
    }

    $schedule = build_invitation_schedule($wedding);
    $dates = [];
    foreach ($schedule as $event) {
        if ($event['datetime'] instanceof DateTimeImmutable) {
            $dates[] = $event['datetime'];
        }
    }

    if ($dates === []) {
        return null;
    }

    usort($dates, static fn(DateTimeImmutable $a, DateTimeImmutable $b): int => $a->getTimestamp() <=> $b->getTimestamp());
    $closeDate = $dates[count($dates) - 1];

    $graceMinutes = (int) ($wedding['rsvp_close_grace_minutes'] ?? 0);
    if ($graceMinutes > 0) {
        $closeDate = $closeDate->modify('+' . $graceMinutes . ' minutes');
    }

    return $closeDate;
}

function is_rsvp_closed(array $wedding, ?DateTimeImmutable $now = null): bool
{
    $closeDate = get_rsvp_close_datetime($wedding);
    if (!($closeDate instanceof DateTimeImmutable)) {
        return false;
    }

    $now = $now ?? new DateTimeImmutable('now');
    return $now >= $closeDate;
}

function validate_rsvp_payload(array $input): array
{
    $errors = [];

    $name = normalize_text((string) ($input['nama'] ?? ''), 120);
    $status = normalize_text((string) ($input['kehadiran'] ?? ''), 20);
    $message = trim((string) ($input['pesan'] ?? ''));
    $message = preg_replace('/\R{3,}/u', "\n\n", $message) ?? '';
    if (mb_strlen($message) > 1000) {
        $message = mb_substr($message, 0, 1000);
    }

    $allowedStatus = ['Hadir', 'Tidak Hadir', 'Masih Ragu'];

    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Nama minimal 2 karakter.';
    }

    if (!in_array($status, $allowedStatus, true)) {
        $errors[] = 'Status kehadiran tidak valid.';
    }

    if ($message === '' || mb_strlen($message) < 5) {
        $errors[] = 'Ucapan minimal 5 karakter.';
    }

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'data' => [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ],
    ];
}

function client_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!$candidate) {
            continue;
        }
        $ip = trim(explode(',', $candidate)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    return '0.0.0.0';
}

function enforce_rate_limit(string $scope, int $maxAttempts = 6, int $windowSeconds = 600): bool
{
    $now = time();
    $bucket = $scope . ':' . hash('sha256', client_ip());

    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    if (!isset($_SESSION['rate_limit'][$bucket])) {
        $_SESSION['rate_limit'][$bucket] = [];
    }

    $_SESSION['rate_limit'][$bucket] = array_values(array_filter(
        $_SESSION['rate_limit'][$bucket],
        static fn($ts): bool => ($now - (int) $ts) <= $windowSeconds
    ));

    if (count($_SESSION['rate_limit'][$bucket]) >= $maxAttempts) {
        return false;
    }

    $_SESSION['rate_limit'][$bucket][] = $now;
    return true;
}
