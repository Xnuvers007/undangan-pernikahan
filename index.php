<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';

send_security_headers();

$config = require __DIR__ . '/config/app.php';
date_default_timezone_set((string) ($config['site']['timezone'] ?? 'Asia/Jakarta'));

$site = is_array($config['site'] ?? null) ? $config['site'] : [];
$intro = is_array($config['intro'] ?? null) ? $config['intro'] : [];
$wedding = is_array($config['wedding'] ?? null) ? $config['wedding'] : [];
$groom = is_array($config['groom'] ?? null) ? $config['groom'] : [];
$bride = is_array($config['bride'] ?? null) ? $config['bride'] : [];
$story = is_array($config['story'] ?? null) ? $config['story'] : [];
$nuance = is_array($config['nuance'] ?? null) ? $config['nuance'] : [];
$doaAlmarhum = is_array($config['doa_almarhum'] ?? null) ? $config['doa_almarhum'] : [];
$assets = is_array($config['assets'] ?? null) ? $config['assets'] : [];
$music = is_array($config['music'] ?? null) ? $config['music'] : [];
$giftAccounts = is_array($config['gift_accounts'] ?? null) ? $config['gift_accounts'] : [];

$signature = (string) ($site['signature'] ?? 'Mery & Bima');
$monogram = (string) ($site['monogram'] ?? 'M|B');

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$scheme = $isHttps ? 'https' : 'http';
$hostRaw = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$host = preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{2,5})?$/', $hostRaw) ? $hostRaw : 'localhost';
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if ($requestPath === '') {
    $requestPath = '/';
}

$baseUrl = $scheme . '://' . $host;
$currentUrl = $baseUrl . $requestPath;

$canonicalUrl = trim((string) ($site['canonical_url'] ?? ''));
if ($canonicalUrl === '' || filter_var($canonicalUrl, FILTER_VALIDATE_URL) === false) {
    $canonicalUrl = $currentUrl;
}

$metaTitle = (string) ($site['share_title'] ?? ($site['title'] ?? 'Undangan Pernikahan'));
$metaDescription = (string) ($site['share_description'] ?? ($site['meta_description'] ?? 'Undangan pernikahan digital.'));
$metaKeywords = (string) ($site['meta_keywords'] ?? 'undangan pernikahan digital');
$metaAuthor = (string) ($site['author'] ?? $signature);
$faviconPath = (string) ($assets['favicon'] ?? 'assets/images/favicon.ico');
$shareImagePath = (string) ($assets['share_image'] ?? ($assets['couple_photo'] ?? ''));
$shareImageUrl = $shareImagePath;
if ($shareImagePath !== '' && !preg_match('/^https?:\/\//i', $shareImagePath)) {
    $shareImageUrl = $baseUrl . '/' . ltrim($shareImagePath, '/');
}

$now = new DateTimeImmutable('now');
$guestName = get_guest_name_from_query($_GET);
$invitationContext = resolve_invitation_context($_GET, $wedding, $now);
$activeInvitationDateHuman = (string) ($invitationContext['date_human'] ?? (string) ($wedding['date_human'] ?? '10 Mei 2026'));
$activeInvitationDateIso = (string) ($invitationContext['date_iso'] ?? (string) ($wedding['date_iso'] ?? ''));
$activeInvitationLabel = (string) ($invitationContext['label'] ?? 'Akad Nikah');
$activeInvitationDateTime = $invitationContext['datetime'] ?? null;
$isSelectedInvitationPassed = $activeInvitationDateTime instanceof DateTimeImmutable && $activeInvitationDateTime <= $now;
$countdownNote = $isSelectedInvitationPassed
    ? $activeInvitationLabel . ' telah berlangsung.'
    : 'Menuju ' . $activeInvitationLabel;
$rsvpClosed = is_rsvp_closed($wedding, $now);

$flashMap = [
    'success' => ['type' => 'ok', 'text' => 'Terima kasih, ucapan Anda sudah tersimpan.'],
    'invalid' => ['type' => 'error', 'text' => 'Data RSVP tidak valid. Mohon cek kembali.'],
    'rate' => ['type' => 'error', 'text' => 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.'],
    'security' => ['type' => 'error', 'text' => 'Validasi keamanan gagal. Silakan muat ulang halaman.'],
    'closed' => ['type' => 'error', 'text' => 'RSVP telah ditutup karena rangkaian acara sudah selesai.'],
    'db' => ['type' => 'error', 'text' => 'Layanan RSVP sedang tidak tersedia.'],
];

$flash = null;
$flashKey = isset($_GET['rsvp']) ? (string) $_GET['rsvp'] : '';
if (isset($flashMap[$flashKey])) {
    $flash = $flashMap[$flashKey];
}

$wishList = [];
if ($pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare('SELECT guest_name, attendance_status, message, created_at FROM rsvp_messages WHERE is_approved = 1 ORDER BY id DESC LIMIT 50');
        $stmt->execute();
        $wishList = $stmt->fetchAll();
    } catch (Throwable $e) {
        $wishList = [];
    }
}

$csrfToken = $rsvpClosed ? '' : generate_csrf_token();
$formTime = $rsvpClosed ? 0 : time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e((string) ($site['meta_description'] ?? 'Undangan pernikahan digital.')) ?>">
    <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <meta name="author" content="<?= e($metaAuthor) ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="theme-color" content="#7b252d">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:site_name" content="<?= e((string) ($site['title'] ?? 'Undangan Pernikahan')) ?>">
    <?php if ($shareImageUrl !== ''): ?>
    <meta property="og:image" content="<?= e($shareImageUrl) ?>">
    <meta property="og:image:alt" content="<?= e($signature) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <?php if ($shareImageUrl !== ''): ?>
    <meta name="twitter:image" content="<?= e($shareImageUrl) ?>">
    <?php endif; ?>
    <title><?= e((string) ($site['title'] ?? 'Undangan Pernikahan')) ?> | <?= e((string) ($bride['full_name'] ?? 'Mery Jayanti')) ?> &amp; <?= e((string) ($groom['full_name'] ?? 'Bima Yusuf Nurhanadi')) ?></title>
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Cormorant+Garamond:wght@400;500;700&family=Great+Vibes&family=Manrope:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="<?= e($faviconPath) ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= e($faviconPath) ?>">
</head>
<body class="is-locked is-preloading minang-theme">
    <div class="songket-top" aria-hidden="true"></div>
    <div class="ambient ambient-a" aria-hidden="true"></div>
    <div class="ambient ambient-b" aria-hidden="true"></div>
    <div class="ambient ambient-c" aria-hidden="true"></div>

    <div class="preload-screen" id="preloadScreen" role="status" aria-live="polite" aria-label="Memuat undangan">
        <div class="preload-ornament" aria-hidden="true">
            <span class="preload-ring preload-ring-a"></span>
            <span class="preload-ring preload-ring-b"></span>
            <span class="preload-core"><?= e($monogram) ?></span>
        </div>
        <p class="preload-title">Baralek Minang</p>
        <p class="preload-copy">Menyiapkan halaman undangan...</p>
    </div>

    <div class="cover-overlay" id="coverOverlay">
        <div class="cover-pattern" aria-hidden="true"></div>
        <div class="curtain-layer curtain-back" aria-hidden="true"></div>
        <div class="curtain-layer curtain-front" aria-hidden="true"></div>
        <div class="cover-card">
            <p class="eyebrow">Undangan Pernikahan</p>
            <h1 class="cover-title"><?= e((string) ($bride['full_name'] ?? 'Mery Jayanti')) ?> <span>&amp;</span> <?= e((string) ($groom['full_name'] ?? 'Bima Yusuf Nurhanadi')) ?></h1>
            <p class="cover-date"><?= e($activeInvitationDateHuman) ?></p>
            <p class="cover-subtitle">Kepada Yth.</p>
            <h2 class="cover-guest" id="guestName"><?= e($guestName) ?></h2>
            <p class="cover-monogram"><?= e($signature) ?> <span><?= e($monogram) ?></span></p>
            <button type="button" class="btn-open" id="openInvitationButton">Buka Undangan</button>
        </div>
    </div>

    <main class="page-shell" id="top">
        <section class="hero reveal scene-section" id="home" data-parallax-speed="0.014">
            <div class="hero-side hero-side-left" aria-hidden="true">
                <img src="<?= e((string) ($bride['photo'] ?? 'assets/images/perempuan_minang.png')) ?>" alt="">
            </div>
            <div class="hero-side hero-side-right" aria-hidden="true">
                <img src="<?= e((string) ($groom['photo'] ?? 'assets/images/laki_minang.png')) ?>" alt="">
            </div>

            <p class="hero-monogram"><?= e($monogram) ?></p>
            <p class="eyebrow">The Wedding Of</p>
            <h2 class="hero-names"><?= e((string) ($bride['full_name'] ?? 'Mery Jayanti')) ?> <span>&amp;</span> <?= e((string) ($groom['full_name'] ?? 'Bima Yusuf Nurhanadi')) ?></h2>
            <p class="hero-date"><?= e($activeInvitationDateHuman) ?></p>
            <img class="hero-butterfly parallax-layer" data-parallax-speed="0.03" src="<?= e((string) ($assets['butterfly'] ?? 'assets/images/kupu_kupu.gif')) ?>" alt="Ornamen kupu-kupu">

            <div class="countdown" data-countdown="<?= e($activeInvitationDateIso !== '' ? $activeInvitationDateIso : (string) ($wedding['date_iso'] ?? '')) ?>" data-label="<?= e($activeInvitationLabel) ?>">
                <div class="count-box"><span class="count-num" data-unit="days">0</span><span class="count-label">Hari</span></div>
                <div class="count-box"><span class="count-num" data-unit="hours">0</span><span class="count-label">Jam</span></div>
                <div class="count-box"><span class="count-num" data-unit="minutes">0</span><span class="count-label">Menit</span></div>
                <div class="count-box"><span class="count-num" data-unit="seconds">0</span><span class="count-label">Detik</span></div>
            </div>
            <p class="countdown-note" id="countdownNote"><?= e($countdownNote) ?></p>
        </section>

        <section class="intro reveal scene-section" id="intro" data-parallax-speed="0.012">
            <p class="bismillah"><?= e((string) ($intro['bismillah'] ?? 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيْمِ')) ?></p>
            <p class="intro-copy"><?= e((string) ($intro['message'] ?? '')) ?></p>
        </section>

        <section class="nuance reveal scene-section" id="nuance" data-parallax-speed="0.01">
            <h3 class="section-title">Nuansa Adat & Restu</h3>
            <blockquote class="love-quote"><?= e((string) ($nuance['love_quote'] ?? '')) ?></blockquote>
            <p class="pantun-minang"><?= e((string) ($nuance['pantun_minang'] ?? '')) ?></p>
            <p class="pantun-indonesia"><?= e((string) ($nuance['pantun_indonesia'] ?? '')) ?></p>
            <br />
            <br />
            <p class="restu-copy"><?= e((string) ($nuance['restu'] ?? '')) ?></p>
        </section>

        <section class="memorial reveal scene-section" id="memorial" data-parallax-speed="0.011">
            <h3 class="section-title"><?= e((string) ($doaAlmarhum['title'] ?? 'Doa Untuk Almarhum/Almarhumah Keluarga')) ?></h3>
            <p class="doa-arabic" dir="rtl" lang="ar"><?= e((string) ($doaAlmarhum['arabic'] ?? '')) ?></p>
            <p class="doa-latin"><strong>Latin:</strong> <?= e((string) ($doaAlmarhum['latin'] ?? '')) ?></p>
            <p class="doa-meaning"><strong>Artinya:</strong> <?= e((string) ($doaAlmarhum['meaning'] ?? '')) ?></p>
        </section>

        <section class="couple reveal scene-section" id="couple" data-parallax-speed="0.01">
            <h3 class="section-title">Mempelai</h3>
            <p class="section-subtitle">Dengan penuh syukur kepada Allah SWT, kami mempersembahkan putra-putri terbaik keluarga.</p>
            <div class="couple-grid">
                <article class="person-card">
                    <div class="person-photo-frame">
                        <img class="person-photo" src="<?= e((string) ($bride['photo'] ?? 'assets/images/perempuan_minang.png')) ?>" alt="Foto mempelai wanita <?= e((string) ($bride['full_name'] ?? '')) ?>">
                    </div>
                    <p class="person-role">Mempelai Wanita</p>
                    <h4 class="person-name"><?= e((string) ($bride['full_name'] ?? 'Mery Jayanti')) ?></h4>
                    <p class="lineage"><?= e((string) ($bride['lineage_label'] ?? 'Putri dari')) ?></p>
                    <p class="parent-name"><?= e((string) ($bride['father_full'] ?? 'Bapak Syafrizal Caniago')) ?></p>
                    <p class="amp">&amp;</p>
                    <p class="parent-name"><?= e((string) ($bride['mother_full'] ?? 'Ibu Ayang')) ?></p>
                </article>

                <div class="couple-emblem" aria-label="Logo pasangan">
                    <span>&amp;</span>
                    <small><?= e($monogram) ?></small>
                </div>

                <article class="person-card">
                    <div class="person-photo-frame">
                        <img class="person-photo" src="<?= e((string) ($groom['photo'] ?? 'assets/images/laki_minang.png')) ?>" alt="Foto mempelai pria <?= e((string) ($groom['full_name'] ?? '')) ?>">
                    </div>
                    <p class="person-role">Mempelai Pria</p>
                    <h4 class="person-name"><?= e((string) ($groom['full_name'] ?? 'Bima Yusuf Nurhanadi')) ?></h4>
                    <p class="lineage"><?= e((string) ($groom['lineage_label'] ?? 'Putra dari')) ?></p>
                    <p class="parent-name"><?= e((string) ($groom['father_full'] ?? 'alm. Bapak Ambyah Wiryadi')) ?></p>
                    <p class="amp">&amp;</p>
                    <p class="parent-name"><?= e((string) ($groom['mother_full'] ?? 'Ibu Kurniasih')) ?></p>
                </article>
            </div>
        </section>

        <section class="events reveal scene-section" id="events" data-parallax-speed="0.012">
            <h3 class="section-title">Rangkaian Acara</h3>
            <p class="event-blessing"><?= e((string) ($nuance['restu'] ?? '')) ?></p>
            <div class="event-grid">
                <article class="event-card">
                    <h4>Akad Nikah</h4>
                    <p><?= e((string) ($wedding['date_human'] ?? '10 Mei 2026')) ?></p>
                    <p>Pukul <?= e((string) ($wedding['akad_time'] ?? '08.00 WIB')) ?></p>
                    <p><?= e((string) ($wedding['venue_name'] ?? 'Gedung Serbaguna Minang')) ?></p>
                    <p><?= e((string) ($wedding['akad_location'] ?? '')) ?></p>
                    <a class="map-button" href="<?= e((string) ($wedding['map_akad'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer">Petunjuk Lokasi Akad</a>
                </article>
                <article class="event-card">
                    <h4>Resepsi</h4>
                    <p><?= e((string) ($wedding['reseption_date_human'] ?? '4 juni 2026')) ?></p>
                    <p>Pukul <?= e((string) ($wedding['reception_time'] ?? '11.00 WIB - Selesai')) ?></p>
                    <p><?= e((string) ($wedding['venue_name'] ?? 'Gedung Serbaguna Minang')) ?></p>
                    <p><?= e((string) ($wedding['reception_location'] ?? '')) ?></p>
                    <a class="map-button" href="<?= e((string) ($wedding['map_reseption'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer">Petunjuk Lokasi Resepsi</a>
                </article>
            </div>
        </section>

        <section class="gift reveal scene-section" id="gift" data-parallax-speed="0.01">
            <h3 class="section-title">Hadiah Pernikahan</h3>
            <p class="gift-copy">Doa restu Anda adalah hadiah utama. Jika berkenan, Anda dapat mengirim tanda kasih melalui rekening berikut.</p>

            <div class="gift-grid">
                <?php foreach ($giftAccounts as $account): ?>
                    <article class="gift-card">
                        <?php if (!empty($account['logo'])): ?>
                            <img class="bank-logo" src="<?= e((string) $account['logo']) ?>" alt="Logo bank <?= e((string) ($account['bank'] ?? '')) ?>">
                        <?php endif; ?>
                        <p class="gift-bank"><?= e((string) ($account['bank'] ?? 'Bank')) ?></p>
                        <p class="gift-number"><?= e((string) ($account['number'] ?? '-')) ?></p>
                        <p class="gift-holder">a.n. <?= e((string) ($account['holder'] ?? '-')) ?></p>
                        <button type="button" class="copy-btn" data-copy="<?= e((string) ($account['number'] ?? '')) ?>">Salin Nomor</button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="story reveal scene-section" id="story" data-parallax-speed="0.012">
            <h3 class="section-title">Love Story</h3>
            <div class="timeline">
                <?php foreach ($story as $item): ?>
                    <article class="timeline-item reveal">
                        <h4><?= e((string) $item['title']) ?></h4>
                        <p><?= e((string) $item['content']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rsvp reveal scene-section" id="rsvp" data-parallax-speed="0.01">
            <h3 class="section-title">RSVP & Ucapan</h3>

            <?php if ($flash): ?>
                <div class="flash flash-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['text']) ?></div>
            <?php endif; ?>

            <?php if (!($pdo instanceof PDO)): ?>
                <div class="flash flash-error"><?= e((string) ($dbConnectionError ?? 'Database belum tersambung.')) ?></div>
            <?php endif; ?>

            <?php if ($rsvpClosed): ?>
                <?php if ($flashKey !== 'closed'): ?>
                <div class="flash flash-error">RSVP telah ditutup karena rangkaian acara sudah selesai.</div>
                <?php endif; ?>
            <?php else: ?>
                <form class="rsvp-form" action="action/submit_resvp.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="form_time" value="<?= e((string) $formTime) ?>">
                    <div class="hp-wrap" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <label for="nama">Nama</label>
                    <input id="nama" name="nama" type="text" minlength="2" maxlength="120" required placeholder="Nama Anda">

                    <label for="kehadiran">Konfirmasi Kehadiran</label>
                    <select id="kehadiran" name="kehadiran" required>
                        <option value="">Pilih konfirmasi</option>
                        <option value="Hadir">Hadir</option>
                        <option value="Tidak Hadir">Tidak Hadir</option>
                        <option value="Masih Ragu">Masih Ragu</option>
                    </select>

                    <label for="pesan">Ucapan</label>
                    <textarea id="pesan" name="pesan" rows="4" minlength="5" maxlength="1000" required placeholder="Tulis doa dan ucapan terbaik..."></textarea>

                    <button type="submit" class="submit-btn">Kirim Ucapan</button>
                </form>
            <?php endif; ?>

            <div class="wish-list">
                <?php if (count($wishList) === 0): ?>
                    <p class="empty-wish">Belum ada ucapan. Jadilah yang pertama mengirim doa terbaik.</p>
                <?php else: ?>
                    <?php foreach ($wishList as $wish): ?>
                        <article class="wish-item">
                            <div class="wish-head">
                                <strong><?= e((string) $wish['guest_name']) ?></strong>
                                <span class="wish-badge"><?= e((string) $wish['attendance_status']) ?></span>
                            </div>
                            <p><?= nl2br(e((string) $wish['message'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <footer class="footer reveal scene-section" id="thanks" data-parallax-speed="0.008">
            <img class="closing-couple" src="<?= e((string) ($assets['couple_photo'] ?? 'assets/images/couple.png')) ?>" alt="Pasangan pengantin adat Minang">
            <p class="closing-copy"><?= e((string) ($intro['closing_message'] ?? '')) ?></p>
            <h3><?= e($signature) ?></h3>
            <p class="closing-monogram"><?= e($monogram) ?></p>
            <p class="footer-credit">
                Ingin membuat website seperti ini? hubungi:
                <a href="https://instagram.com/indradwi.25" target="_blank" rel="noopener noreferrer">https://instagram.com/indradwi.25</a>
                atau email
                <a href="mailto:xnuversh1kar4@gmail.com">xnuversh1kar4@gmail.com</a>
            </p>
        </footer>
    </main>

    <audio id="backgroundMusic" preload="metadata" loop>
        <source src="<?= e((string) ($music['source'] ?? 'assets/music/Urang_Minang_Baralek_Gadang.mp3')) ?>" type="audio/mpeg">
    </audio>

    <button type="button" class="music-toggle" id="musicToggleButton" aria-label="Kontrol musik">Musik: Off</button>

    <nav class="floating-nav" aria-label="Navigasi Undangan">
        <a href="#home">Home</a>
        <a href="#intro">Doa</a>
        <a href="#couple">Mempelai</a>
        <a href="#events">Acara</a>
        <a href="#gift">Gift</a>
        <a href="#rsvp">RSVP</a>
    </nav>

    <script src="assets/js/app.js" defer></script>
</body>
</html>