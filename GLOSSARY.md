# Glossary - Kasir Cashless Skanilan

> Single-context domain vocabulary. Update inline when terms are resolved.

## Core Concepts

| Term | Definition | Aliases / Not To Be Confused With |
|------|------------|-----------------------------------|
| **Device** | Hardware console (ESP8266 + RFID + LCD + Buzzer) yang dipasang di meja kasir. Identifikasi unik via `device_id` (MAC address). | ≠ "Console", ≠ "Terminal", ≠ "Reader" |
| **Kasir** | User (penjual) yang login ke panel, mengelola produk miliknya, memantau console dan riwayat transaksinya, lalu memulai transaksi di POS. Punya 1 akun + 1 device. | ≠ "Penjual" (role), ≠ "Admin" |
| **Panel Kasir** | Halaman Filament `/admin` yang dipakai bersama admin dan kasir. Kasir hanya melihat produk, transaksi, chart, dan status console miliknya; admin melihat semuanya. | ≠ "POS" (halaman checkout), ≠ "Admin Panel" (khusus admin) |
| **Admin** | User dengan akses penuh: CRUD device, assign device ke kasir, monitoring semua transaksi, CRUD kartu RFID. | ≠ "Superadmin" |
| **Pembeli** | Orang yang membeli, dibawa kartu RFID. Bisa siswa/guru/staff. Tidak punya login web. | ≠ "Customer", ≠ "User" |
| **RFID Card** | Kartu fisik berisi UID unik. Saldo & status aktif disimpan di database (bukan di kartu). | ≠ "Kartu", ≠ "Tag", ≠ "E-Wallet" |
| **Product** | Item yang dijual oleh satu kasir. Harga, stok, kategori per-kasir. | ≠ "Menu", ≠ "Item" (global) |
| **Transaction** | Satu kali proses beli: kasir pilih produk → total → pembeli tap kartu → saldo dipotong → status success/failed/cancelled. | ≠ "Order", ≠ "Payment", ≠ "Sale" |
| **Transaction Item** | Baris detail dalam transaksi: product_id, qty, price, subtotal. | ≠ "Line Item", ≠ "Order Detail" |
| **Pending Transaction** | Transaksi status `pending` menunggu tap kartu. Jendela tap 30 detik baru mulai saat console pertama kali mengambil tagihan; selama console offline tagihan tetap pending tanpa batas otomatis. | ≠ "Open Transaction" |
| **Device Token** | API token panjang (`dev_` + 64 hex) untuk autentikasi device ke backend. Disimpan di `devices.api_token`. | ≠ "API Key", ≠ "JWT" |
| **Heartbeat** | Periodik kirim status device (RSSI, heap, uptime) ke backend tiap 30 detik. | ≠ "Ping", ≠ "Keep-alive" |

## States & Statuses

| Term | Valid Values | Notes |
|------|--------------|-------|
| **Device Status** | `active`, `inactive`, `maintenance` | Admin set manual |
| **Transaction Status** | `pending`, `success`, `failed`, `cancelled` | Flow: pending → success/failed/cancelled |
| **RFID Card Status** | `active`, `inactive` (blokir) | Admin toggle |
| **Product Status** | `active`, `inactive` | Kasir toggle |
| **Device State (Firmware)** | `BOOT`, `CONNECTING_WIFI`, `REGISTERING`, `IDLE`, `WAIT_TAP`, `PROCESSING`, `SUCCESS`, `FAILED`, `ERROR_NET`, `ERROR_HARDWARE` | 10 state, lihat `state_machine.h` |

## Events (Broadcast)

| Event | Channel | Payload Keys |
|-------|---------|--------------|
| `transaction.created` | `private-device.{deviceId}` | `id`, `total`, `items`, `expires_at` |
| `transaction.completed` | `private-device.{deviceId}`, `private-admin.transactions` | `id`, `status`, `total`, `balance_before`, `balance_after`, `card` |
| `transaction.cancelled` | `private-device.{deviceId}` | `id`, `message` |

## Hardware Terms

| Term | Spec |
|------|------|
| **NodeMCU v3** | ESP8266 development board (CP2102 USB-UART) |
| **RC522** | RFID reader module (SPI, 13.56 MHz, MIFARE Classic) |
| **LCD Qapass I2C** | 16x2 atau 20x4 character LCD, PCF8574 backpack, address `0x27`/`0x3F` |
| **Buzzer** | Active buzzer (GPIO, PWM capable) |
| **UID** | 4-byte / 7-byte hex string dari kartu MIFARE (contoh: `A1B2C3D4`) |

## Flows

| Flow | Steps |
|------|-------|
| **Register Device** | ESP8266 boot → WiFi → POST `/api/device/register` → dapat `api_token` → WS connect |
| **Kasir Checkout** | Kasir pilih produk → klik "Bayar" → POST `/api/transactions` → broadcast `transaction.created` → device terima → LCD "Silakan Tap" |
| **Pembeli Tap** | Device baca UID → POST `/api/device/{id}/tap` → backend validasi saldo → kurangi → broadcast `transaction.completed` → device tampil sukses/gagal |
| **Heartbeat** | Device tiap 30s POST `/api/device/{id}/heartbeat` + WS keep-alive |
| **Admin Monitoring** | Filament widgets subscribe `private-admin.transactions` → live feed |