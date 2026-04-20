# Undangan Pernikahan Adat Minang

Aplikasi undangan pernikahan digital berbasis PHP dengan tema adat Minang, lengkap dengan countdown, musik latar, navigasi mengambang, dan form RSVP yang sudah dilengkapi validasi keamanan.

## Fitur Utama

- Tampilan undangan satu halaman dengan desain adat Minang.
- Cover pembuka dan preloader sebelum konten utama ditampilkan.
- Floating navigation untuk lompat cepat ke tiap section.
- Tombol musik On/Off untuk audio latar.
- Countdown dinamis berdasarkan acara yang dipilih (akad atau resepsi).
- Link Google Maps terpisah untuk lokasi akad dan resepsi.
- Form RSVP dengan proteksi CSRF, honeypot, rate limit, dan validasi input.
- Penyimpanan ucapan RSVP ke database MySQL dengan prepared statements.
- Meta SEO dan social sharing (Open Graph dan Twitter card).
- Responsif untuk mobile, tablet, laptop, sampai layar besar.

## Teknologi

- PHP 8+
- MySQL / MariaDB
- HTML, CSS, JavaScript (vanilla)
- Apache (direkomendasikan untuk memanfaatkan .htaccess)

## Struktur Proyek

```text
.
|-- index.php
|-- db_undangan_minang.sql
|-- .htaccess
|-- action/
|   `-- submit_resvp.php
|-- assets/
|   |-- css/
|   |   `-- style.css
|   |-- js/
|   |   `-- app.js
|   |-- images/
|   `-- music/
`-- config/
    |-- app.php
    |-- database.php
    `-- security.php
```

## Persiapan dan Instalasi

1. Pastikan server sudah memiliki PHP 8+ dan MySQL/MariaDB.
2. Import database dari file SQL:

```bash
mysql -u root -p < db_undangan_minang.sql
```

3. Atur koneksi database melalui environment variable (opsional) atau gunakan nilai default:

- DB_HOST (default: 127.0.0.1)
- DB_NAME (default: db_undangan_minang)
- DB_USER (default: root)
- DB_PASS (default: kosong)

4. Jalankan project:

- Opsi A (cepat untuk development):

```bash
php -S 127.0.0.1:9999
```

- Opsi B (produksi): gunakan Apache/Nginx + PHP-FPM.

5. Buka aplikasi di browser:

```text
http://127.0.0.1:9999
```

## Parameter URL Undangan

Gunakan parameter query untuk personalisasi tamu dan pemilihan konteks acara:

- tamu: nama penerima undangan
- invitation: akad atau resepsi

Contoh:

```text
http://127.0.0.1:9999/?tamu=Nama%20Tamu&invitation=akad
http://127.0.0.1:9999/?tamu=Nama%20Tamu&invitation=resepsi
```

## Konfigurasi Konten

Semua konten utama bisa diubah dari file berikut:

- config/app.php

Yang bisa diubah antara lain:

- Metadata situs dan social share
- Data mempelai
- Jadwal akad dan resepsi
- Batas penutupan RSVP
- Link Google Maps
- Data rekening hadiah
- Gambar aset dan musik
- Kutipan, doa, dan cerita perjalanan

## Keamanan RSVP

Implementasi keamanan utama:

- CSRF token pada form RSVP.
- Honeypot field untuk deteksi bot.
- Validasi waktu submit form (anti-bot submit instan).
- Rate limit berbasis IP pada sesi.
- Sanitasi dan normalisasi input.
- Prepared statements pada query insert database.

## Catatan Deployment

- File .htaccess berisi aturan rewrite, header keamanan, kompresi, cache, dan redirect HTTPS.
- Jika deploy di Apache tanpa SSL aktif, redirect HTTPS di .htaccess dapat menyebabkan loop/gagal akses. Sesuaikan konfigurasi server atau aktifkan SSL.

## Troubleshooting Singkat

- RSVP gagal simpan:
  - Cek koneksi database di config/database.php atau env var DB_*
  - Pastikan tabel rsvp_messages sudah ada

- Tampilan tidak berubah setelah edit CSS/JS:
  - Lakukan hard refresh browser (Ctrl+F5)
  - Bersihkan cache browser

## Pengembangan Lanjutan

Rekomendasi peningkatan berikutnya:

- Tambah panel admin moderasi ucapan RSVP.
- Integrasi notifikasi WhatsApp/Email untuk RSVP baru.
- Pisahkan konfigurasi environment per dev/staging/prod.

## Lisensi

Belum ditentukan. Tambahkan lisensi sesuai kebutuhan proyek.
