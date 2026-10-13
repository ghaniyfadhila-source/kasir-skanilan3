# Kasir Cashless Skanilan

Sistem kasir berbasis RFID untuk kantin sekolah menggunakan:
- **Backend**: Laravel 11 + Filament v3 + Laravel Reverb (WebSocket)
- **Database**: PostgreSQL (Supabase untuk dev, VPS untuk prod)
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

### Kasir POS (Livewire)
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
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/device/register` | Device Token | Register device |
| GET | `/api/device/{id}/pending-transaction` | Device Token | Check pending |
| POST | `/api/device/{id}/tap` | Device Token | Process tap |
| POST | `/api/device/{id}/heartbeat` | Device Token | Status update |
| POST | `/api/transactions` | Sanctum (kasir) | Create transaction |
| POST | `/api/transactions/{id}/cancel` | Sanctum (kasir) | Cancel transaction |
| WS | `/ws/device/{id}` | Device Token | Real-time push |

## Quick Start

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
# Edit .env dengan DATABASE_URL Supabase
php artisan key:generate
php artisan migrate --seed
php artisan serve
# Terminal 2: php artisan reverb:start
```

### Firmware (ESP8266)
```bash
cd firmware
# Install PlatformIO (VS Code extension atau CLI)
# Edit include/config.h dengan WiFi & server URL
pio run -e nodemcuv3 -t upload
pio device monitor -b 115200
```

### Hardware Wiring (NodeMCU v3)

| Komponen | Pin NodeMCU | GPIO |
|----------|-------------|------|
| RC522 SDA/SS | D8 | 15 |
| RC522 SCK | D5 | 14 |
| RC522 MOSI | D7 | 13 |
| RC522 MISO | D6 | 12 |
| RC522 RST | D3 | 0* |
| LCD SDA | D2 | 4 |
| LCD SCL | D1 | 5 |
| Buzzer | D0 | 16 |
| LED Built-in | D4 | 2 (status) |

*RST ke D3 (GPIO0) butuh pull-up 10k ke 3.3V agar boot normal.

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
# Backend test
cd backend && php artisan test

# Lint
cd backend && php vendor/bin/pint

# Firmware build check
cd firmware && pio run -e nodemcuv3
```

## License

MIT