# Kasir Cashless Skanilan

Sistem kasir berbasis RFID untuk kantin sekolah menggunakan:
- **Backend**: Laravel 11 + Filament v3 + Laravel Reverb (WebSocket)
- **Database**: SQLite (dev & test), PostgreSQL (produksi, opsional)
- **Hardware**: ESP8266 NodeMCU v3 + RFID RC522 + LCD I2C Qapass + Buzzer
- **Auth**: Spatie Laravel-permission (Admin & Kasir roles)

## Struktur Project

```
kasirCashlessSkanilan/
├── backend/                 # Laravel 11 Application
│   ├── app/
│   │   ├── Http/Controllers/Api/    # Device & Transaction API
│   │   ├── Livewire/Kasir/          # Kasir POS UI
│   │   ├── Models/                  # User, Device, Product, RfidCard, Transaction
│   │   ├── Services/                # TransactionService
│   │   ├── Events/                  # Broadcast events
│   │   ├── Policies/                # Authorization policies
│   │   └── Filament/Resources/      # Admin panel resources
│   ├── database/migrations/         # 5 migrations
│   ├── database/seeders/            # Demo data seeder
│   ├── routes/api.php               # API routes
│   ├── routes/channels.php          # Broadcasting channels
│   └── .github/workflows/ci.yml     # CI/CD pipeline
├── firmware/                # ESP8266 PlatformIO Project
│   ├── src/main.cpp         # Entry point
│   ├── include/             # Headers (config, state_machine, hardware, network, display)
│   ├── src/                 # Implementations
│   └── platformio.ini       # PlatformIO config
├── docs/
│   └── adr/0001-supabase-to-vps-migration.md
├── AGENTS.md                # Agent skills config
└── README.md
```

## Fitur Utama

### Admin Panel (Filament)
- **Device Management**: CRUD device, assign ke kasir, reset token, monitor status online/offline
- **Product Management**: Kasir CRUD produk sendiri (scoped ke device mereka)
- **RFID Card Management**: Admin CRUD kartu, top-up saldo, blokir, input UID manual
- **Transaction Monitoring**: Real-time feed, filter per device, export
- **Dashboard Widgets**: Device status grid, live transaction feed, chart omzet

### Kasir POS (Livewire di `/admin/pos`)
- Halaman Filament `/admin/pos`; `/kasir/pos` dialihkan (301) ke halaman panel
- Pilih produk → total otomatis → klik "Bayar" → tunggu tap RFID
- Real-time polling/WebSocket untuk status transaksi
- Timeout 30 detik auto-cancel
- Feedback visual: sukses/gagal dengan detail saldo

### ESP8266 Firmware (State Machine 7 State)
1. `BOOT` → init hardware
2. `CONNECTING_WIFI` → WiFiManager captive portal
3. `REGISTERING` → register ke API, dapat token
4. `IDLE` → LCD "Siap", loop baca RFID + WS
5. `WAIT_TAP` (30s) → LCD "Silakan Tap", terima UID
6. `PROCESSING` → kirim UID ke API
7. `SUCCESS`/`FAILED` → LCD + buzzer feedback → `IDLE`
8. `ERROR_NET` → retry 3x → `BOOT`

### API Endpoints

Semua endpoint dilindungi autentikasi **dan** rate limit (120/menit per console
untuk `/api/device`, 300/menit untuk `/api/transactions`).

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/device/register` | - | Register device |
| GET | `/api/device/{id}/pending-transaction` | Device Token | Check pending |
| POST | `/api/device/{id}/tap` | Device Token | Process tap |
| POST | `/api/device/{id}/heartbeat` | Device Token | Status update |
| POST | `/api/transactions` | Device Token atau session kasir | Buat transaksi pending (hanya untuk console pemanggil) |
| GET | `/api/transactions/{id}/status` | Device Token atau session kasir | Status transaksi (dipakai POS untuk polling) |
| POST | `/api/transactions/{id}/cancel` | Session kasir pemilik / admin | Batalkan transaksi pending |
| WS | `/ws/device/{id}` | Device Token | Real-time push |

`/api/transactions` menerima **token device atau session kasir**, karena POS
memanggilnya dari browser lewat `fetch()`. Transaksi milik console lain dijawab
**404** (bukan 403) supaya nomor transaksi tidak bisa ditebak satu per satu.
Untuk menerima session di route `/api`, host POS harus terdaftar di
`SANCTUM_STATEFUL_DOMAINS` - nilainya sudah memakai `localhost:8000`; kalau
dipindah ke domain sekolah, sesuaikan variabel ini.

## Quick Start

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
# Edit .env: DB_* untuk database lokal, atau DATABASE_URL untuk Postgres terkelola
php artisan key:generate
php artisan migrate --seed
npm install && npm run build   # hanya wajib bila mengubah CSS/JS
php artisan serve
# Terminal 2: php artisan reverb:start
```

### Firmware (ESP8266)
```bash
cd firmware
# Install PlatformIO (VS Code extension atau CLI)
cp include/config.example.h include/config.h   # lalu isi WiFi & API_BASE_URL
pio run -e nodemcuv2 -t upload
pio device monitor -b 115200
```

Panduan lengkap (wiring, BOM, provisioning, checklist hardware) ada di
[`firmware/README.md`](firmware/README.md).

### Hardware Wiring (NodeMCU v3)

| Komponen | Pin NodeMCU | GPIO |
|----------|-------------|------|
| RC522 3.3V | 3V3 | — |
| RC522 SDA/SS | D8 | 15 |
| RC522 SCK | D5 | 14 |
| RC522 MOSI | D7 | 13 |
| RC522 MISO | D6 | 12 |
| RC522 RST | D3 | 0* |
| LCD SDA | D2 | 4 |
| LCD SCL | D1 | 5 |
| Buzzer | D0 | 16 |
| LED Built-in | D4 | 2 (status) |

*RST ke D3 (GPIO0) butuh pull-up 10k ke 3.3V agar boot normal. LCD sebaiknya
diberi 3.3V (pull-up I2C mengikuti VCC; ESP8266 tidak tahan 5V).

## GitHub Issues

14 issues sudah dibuat di [github.com/ghaniyfadhila-source/kasir-skanilan3/issues](https://github.com/ghaniyfadhila-source/kasir-skanilan3/issues):

| # | Title | Label |
|---|-------|-------|
| 2 | Setup Laravel + Supabase + Reverb | ready-for-agent |
| 3 | Database Migrations & Models | ready-for-agent |
| 4 | Auth & Role System | ready-for-agent |
| 5 | Device Management (Admin) | ready-for-agent |
| 6 | Product Management (Kasir) | ready-for-agent |
| 7 | RFID Card Management (Admin) | ready-for-agent |
| 8 | Transaction API & WebSocket | ready-for-agent |
| 9 | Transaction Flow (Kasir UI) | ready-for-agent |
| 10 | Admin Dashboard & Monitoring | ready-for-agent |
| 11 | ESP8266 Firmware Skeleton | ready-for-agent |
| 12 | Pin Mapping & Hardware Bring-up | needs-info |
| 13 | ADR-0001: Supabase to VPS Migration | ready-for-human |
| 14 | CI/CD Pipeline (GitHub Actions) | ready-for-agent |
| 15 | E2E Test Scenarios | needs-triage |

## Migrasi ke VPS (ADR-0001)

Lihat [`docs/adr/0001-supabase-to-vps-migration.md`](docs/adr/0001-supabase-to-vps-migration.md) untuk checklist lengkap:
1. `pg_dump` Supabase → `pg_restore` VPS PostgreSQL
2. Laravel Sanctum + JWT device tokens (sudah dipakai)
3. Laravel Reverb (sudah dipakai) → ganti Supabase Realtime
4. MinIO/R2 untuk storage (jika butuh)
5. Rotasi secrets & device tokens

## Development

```bash
# Backend test (89 test, coverage ~66%)
cd backend && php artisan test

# Test + coverage seperti di CI
cd backend && php artisan test --coverage --min=50

# Lint
cd backend && php vendor/bin/pint

# Firmware build check
cd firmware && pio run -e nodemcuv2
```

### Test browser (Cypress)

E2E lokal butuh dua server yang sudah jalan lebih dulu (`artisan serve` di
terminal 1, `artisan reverb:start` di terminal 2):

```bash
# Semua test, mode interaktif (buka browser Cypress)
npm run cypress:open

# Headless
npm run cypress:run

# Per spec
npm run cypress:run:admin    # panel admin Filament (16 test)
npm run cypress:run:kasir    # POS kasir, API dimock (13 test)
npm run cypress:run:console  # console ESP, API nyata (4 test)
```

Spec `console-flow` meng-impersonasi firmware lewat `cy.request` (register →
poll tagihan → tap → heartbeat) terhadap server dan database sungguhan, lalu
memastikan POS menampilkan hasilnya. Ini bukti kontrak firmware ↔ backend
tanpa hardware.

Di GitHub Actions, job **E2E (Cypress)** menjalankan `npx cypress run` pada
database SQLite yang baru di-seed, dengan `artisan serve` di port 8000. Server
Reverb tidak dijalankan: console memakai polling, dan spec kasir mem-*mock*
API transaksi. Screenshot kegagalan diunggah sebagai artifact.

### Backup & reset database

`backend/database/database.sqlite` ikut di-commit, jadi isinya ikut berubah
setiap ada transaksi. Kalau mau mengembalikan ke data demo:

```bash
# Cadangkan dulu
cp backend/database/database.sqlite ~/backup-kasir.sqlite

# Kembalikan ke data demo (admin, kasir, device, 8 produk, 4 kartu, 0 transaksi)
cd backend && php artisan migrate:fresh --seed
```

Kalau `git status` menunjukkan `database.sqlite` berubah setelah reset, itu
wajar - commit ulang bila memang ingin menyimpan kondisi tersebut.

## Requirements

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | **>= 8.4** | `composer.lock` memakai Symfony 8.1 yang butuh PHP 8.4.1+. PHP 8.5 dipakai di CI. |
| Composer | 2.x | Wajib untuk `composer install`. |
| Node.js | 20+ | Untuk Vite/Tailwind ( aset hasil build ikut dikomit, jadi `npm install` hanya perlu saat mengubah CSS/JS). |
| Database | SQLite (dev) / PostgreSQL (CI & produksi) | MySQL tidak dipakai. |
| Redis | tidak wajib | Default `.env.example` memakai `file`/`sync`. |

## Catatan Teknis

### Advisory keamanan Laravel 11 (known issue)

`composer audit` melaporkan 4 advisory pada `laravel/framework` **11.57**:

| Advisory | Severity | Kenapa? |
|---|---|---|
| CRLF injection pada aturan validasi `email` (PKSA-3r5d-mb8f-1qw9, CVE-2026-48019) | high | Hanya relevan bila alamat email dipakai untuk menyisipkan header mail. Proyek ini **tidak mengirim email**. |
| Temporary Signed URL path confusion (PKSA-m5cs-t1y6-qpcs) | medium | Proyek ini **tidak memakai** signed URL / temporary route. |
| XSS pada halaman debug (PKSA-d5tc-s1qs-h781) | low | Hanya versi 12.69+/13.30+ yang terkena; **versi kita tidak kena**. |

**Kenapa tidak di-upgrade:** Laravel 11 sudah tidak mendapat patch keamanan; perbaikannya ada di Laravel 12/13. Naik ke sana berarti Filament v3 (yang dipakai sekarang) tidak lagi cocok dan seluruh Resource/Widget harus ditulis ulang ke Filament v4 — pekerjaan yang jauh melebihi ruang lingkup proyek sekolah ini. Semua jalur terdampak tidak dipakai, jadi risikonya praktis nol.

**Kapan harus jadi prioritas upgrade:** kalau nanti menambah fitur yang mengirim email, memakai signed URL, atau ketika proyek mulai dipakai di server produksi sungguhan.

### Deprecation PHP 8.5

Di log CI setiap test yang membuka koneksi database ditandai `deprecated`, dengan pesan `Constant PDO::MYSQL_ATTR_SSL_CA is deprecated`. Penyebabnya: Laravel me-merge `vendor/laravel/framework/config/database.php` milik framework saat boot, dan file itu menyentuh `PDO::MYSQL_ATTR_SSL_CA` hanya ketika ekstensi `pdo_mysql` terpasang — yang memang ada di runner CI. PHP 8.5 sudah deprecated konstanta tersebut.

Proyek ini tidak pernah terhubung ke MySQL (SQLite untuk dev, PostgreSQL untuk CI/produksi), jadi **pesan ini aman dan boleh diabaikan** — semua test tetap jalan, tidak ada yang gagal. Notifikasi ini tidak bisa dimatikan lewat konfigurasi PHPUnit (sudah dicoba `ignoreSelf/Direct/IndirectDeprecations` dan `restrictDeprecations`), jadi sengaja dibiarkan terlihat daripada disembunyikan dengan pengaturan yang tidak berfungsi. Hilang otomatis setelah proyek naik ke Laravel 12/13.

## License

MIT