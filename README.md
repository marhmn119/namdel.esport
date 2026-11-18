# NAMDEL ESPORT

Website tim esports SMAN 68 Jakarta untuk jadwal pertandingan, statistik, roster MLBB dan HOK, recap turnamen, pendaftaran pemain, partnership, dan pengelolaan admin.

## Aplikasi utama

Laravel 13 adalah aplikasi utama. Kode Flask lama (`app.py`) masih disimpan sebagai cadangan, tetapi tidak dijalankan oleh konfigurasi Docker maupun petunjuk lokal di bawah.

### Fitur

- Jadwal dan hasil MLBB/HOK, countdown, prediksi pertandingan, dan statistik tim per game.
- Roster MLBB/HOK dengan profil pemain, statistik gaya bermain, dan hero andalan.
- Recap turnamen MLBB/HOK berisi bracket, hasil, statistik tim, serta rekap pick/ban hero.
- Admin untuk login, CRUD jadwal, roster, hero, recap, scrim, prestasi, galeri, ticker, pendaftar, dan pengaturan video.
- Registrasi pemain, pengelolaan status dan penghapusan pendaftar, ekspor CSV, serta inquiry sponsor.
- Analytics agregat, security headers, perlindungan CSRF, rate limiting, dan sesi Laravel.
- Tampilan publik untuk statistik pick hero dan leaderboard scrim internal tetap disembunyikan sesuai permintaan; datanya masih bisa dikelola dari admin.

## Menjalankan lokal di Windows

Gunakan PHP 8.4 atau lebih baru dan Composer. Jalankan perintah dari PowerShell:

```powershell
Set-Location .\laravel
composer install

# Hanya jika database Laravel belum ada: salin database Flask sebagai sumber migrasi.
if (-not (Test-Path .\database\namdel.sqlite)) {
    Copy-Item ..\namdel.db .\database\namdel.sqlite
}

if (-not (Test-Path .\.env)) {
    Copy-Item .env.example .env
    php artisan key:generate
}
```

Isi `ADMIN_USER` dan `ADMIN_PASS` di `laravel/.env` dengan kredensial admin yang kuat. Pastikan `APP_DEBUG=false`, lalu jalankan migrasi dan server:

```powershell
php artisan migrate
php artisan serve --host=127.0.0.1 --port=5000
```

Buka `http://127.0.0.1:5000`; portal admin ada di `/login`. Laravel memakai `laravel/database/namdel.sqlite`, salinan terpisah dari `namdel.db`, sehingga pengujian tidak mengubah database Flask sumber. Jangan menghapus atau menimpa database yang sudah berisi data. Migration untuk tabel lama sengaja tidak menghapus tabel saat rollback agar data impor tidak terhapus.

Root `.env` adalah konfigurasi aplikasi Flask lama; Laravel membaca `laravel/.env`. Jangan commit `.env`, database, atau upload pengguna.

## Menjalankan dengan Docker

Docker menjalankan Laravel pada port 5000. Siapkan `laravel/.env`, database `laravel/database/namdel.sqlite`, dan kredensial admin terlebih dahulu seperti langkah lokal, lalu:

```powershell
docker compose up --build -d
```

Database SQLite dan unggahan dipasang sebagai volume agar tetap tersimpan ketika container dibuat ulang. Untuk penggunaan melalui HTTPS, atur `APP_URL` ke domain HTTPS dan `SESSION_SECURE_COOKIE=true` di `laravel/.env`, lalu letakkan reverse proxy TLS di depan container. Isi `TRUSTED_PROXIES` dengan alamat IP proxy yang benar agar Laravel mengenali koneksi HTTPS; jangan gunakan wildcard untuk proxy yang tidak tepercaya.

## Pengembangan dan pemeriksaan

Jalankan tes Laravel dan format PHP dari folder `laravel`:

```powershell
php artisan test
composer exec pint -- --format agent
```

Tes memakai SQLite in-memory dan tidak mengubah database situs.

## Notifikasi opsional

- Discord: isi `WEBHOOK_URL` di `laravel/.env`.
- Telegram: isi `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID`.

## Privasi

Analytics menyimpan hash alamat IP, user-agent, waktu, dan halaman yang dilihat; bukan alamat IP mentah. Bersihkan data analytics sesuai kebijakan privasi organisasi.

## Lisensi

Belum ada lisensi proyek yang ditentukan. Lisensi dependensi pihak ketiga tercatat di [THIRD-PARTY-NOTICES.md](./THIRD-PARTY-NOTICES.md).
