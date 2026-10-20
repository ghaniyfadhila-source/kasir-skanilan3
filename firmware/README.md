# Console Firmware (ESP8266 / NodeMCU v3)

Firmware untuk console kasir: membaca kartu RFID, menampilkan status di LCD,
dan berbicara dengan API Laravel. Console menerima tagihan dengan **polling**
setiap 2 detik (jalur WebSocket disediakan tetapi dimatikan secara default).

## 1. Komponen

| Komponen | Spesifikasi | Catatan |
|---|---|---|
| NodeMCU v3 / ESP-12E | ESP8266, 4 MB flash, CH340 | target build `nodemcuv2` |
| RC522 | modul RFID 13.56 MHz, 3.3 V | MIFARE Classic 1K |
| LCD 16x2 I2C | backpack PCF8574, alamat `0x27`/`0x3F` | lihat peringatan 5 V di bawah |
| Buzzer pasif | 5–10 mm | firmware memakai `tone()`, bukan buzzer aktif |
| Kartu/tag RFID | UID 4 byte (contoh `E5F6G7H8`) | daftarkan di panel admin |

## 2. Wiring

| RC522 | NodeMCU | GPIO | Catatan |
|---|---|---|---|
| 3.3V | 3V3 | — | **jangan 5 V** |
| GND | GND | — | |
| SDA/SS | D8 | 15 | |
| SCK | D5 | 14 | SPI hardware |
| MOSI | D7 | 13 | |
| MISO | D6 | 12 | |
| RST | D3 | 0 | butuh pull-up 10k ke 3V3; GPIO0 harus HIGH saat boot |
| IRQ | — | — | tidak dipakai |

| LCD I2C | NodeMCU | GPIO |
|---|---|---|
| GND | GND | — |
| VCC | 3V3 | — |
| SDA | D2 | 4 |
| SCL | D1 | 5 |

| Buzzer | NodeMCU | Catatan |
|---|---|---|
| + | D0 (GPIO16) | lewat transistor NPN bila arus > 10 mA |
| − | GND | |

> **Peringatan LCD 5 V:** pull-up I2C pada modul biasanya mengikuti VCC. Jika LCD
> diberi 5 V, SDA/SCL ikut terangkat ke 5 V dan ESP8266 tidak tahan 5 V. Pakai
> 3V3, atau pastikan modul punya level shifter/regulator onboard.

```
RC522 3.3V/GND/SDA/SCK/MOSI/MISO/RST ──► 3V3,GND,D8,D5,D7,D6,D3
LCD   GND/VCC/SDA/SCL                 ──► GND,3V3,D2,D1
Buzzer +/−                            ──► D0, GND
LED status                            ──► built-in D4/GPIO2
```

## 3. Konfigurasi

```powershell
cd firmware
Copy-Item include\config.example.h include\config.h   # Windows PowerShell
# cp include/config.example.h include/config.h        # Linux/macOS
```

Isi `config.h`:

- `WIFI_SSID`, `WIFI_PASSWORD` — jaringan yang dipakai console.
- `API_BASE_URL` — **wajib** menyertakan skema, port, dan prefix `/api`:
  - LAN: `http://192.168.1.10:8000/api`
  - Produksi: `https://kasir.sekolah.sch.id/api`
- `DEVICE_ID` — biarkan `ESP8266_`; firmware menambahkan MAC address.
- `ENABLE_WEBSOCKET` — biarkan `0` (lihat bagian 7).

`config.h` tidak di-commit. Jangan mengubah pin tanpa mengubah PCB/wiring.

## 4. Build & flash

```powershell
pio run -e nodemcuv2                 # build (sama seperti CI)
pio run -e nodemcuv2 -t upload       # flash
pio device monitor -b 115200         # log serial
```

- Driver CH340 harus terpasang; cek port dengan `pio device list`.
- Upload gagal: tahan tombol **FLASH**, tekan **RST**, lepas FLASH, ulangi.
- Build CI memakai env `nodemcuv2`; tidak ada env `nodemcuv3`.

## 5. Provisioning WiFi

Saat pertama menyala (atau WiFi gagal), console membuka portal
**`KasirCashless_Setup`** (timeout 120 detik). Sambung dari HP/laptop, pilih
jaringan kasir, isi password. Kredensial disimpan oleh WiFiManager.

## 6. Alur & endpoint

| Tahap | Request firmware | Field yang dibaca |
|---|---|---|
| Registrasi | `POST /api/device/register` | `device.api_token` |
| Cek tagihan (2 dtk) | `GET /api/device/{id}/pending-transaction` | `has_pending`, `transaction.id`, `total`, `items[]`, `remaining_seconds` |
| Tap kartu | `POST /api/device/{id}/tap` `{uid}` | `success`, `code`, `message`, `transaction.balance_before/after`, `card.uid/user_name` |
| Heartbeat (30 dtk) | `POST /api/device/{id}/heartbeat` `{rssi,free_heap,uptime}` | `success` |

Catatan penting:

- `remaining_seconds` dipakai karena ESP8266 tidak punya jam/NTP; firmware
  menyimpan deadline `millis()` sendiri.
- Token tidak disimpan di flash. Setiap boot console mendaftar ulang; backend
  mengembalikan token yang sama untuk `device_id` yang sama.
- Saat tagihan dibatalkan kasir, polling berikutnya (≤2 dtk) mengembalikan
  `has_pending=false`, LCD menampilkan **Dibatalkan**.
- Tap ditolak (kartu tidak ada/saldo kurang) menandai transaksi `failed`;
  POS menampilkan alasan yang sama.

Log serial yang diharapkan:

```
WiFi connected: 192.168.1.42
Device registered, token: dev_...
[STATE] BOOT -> CONNECTING_WIFI
[STATE] CONNECTING_WIFI -> REGISTERING
[STATE] REGISTERING -> IDLE
[STATE] IDLE -> WAIT_TAP
[STATE] WAIT_TAP -> PROCESSING
[STATE] PROCESSING -> SUCCESS
```

## 7. WebSocket (belum aktif)

Kode Reverb/WebSocket masih ada, tetapi backend belum menyediakan
`/broadcasting/auth` untuk token device dan nama channel masih memakai ID
numerik, sehingga console memakai polling. Jangan set `ENABLE_WEBSOCKET 1`
sampai kedua hal itu diperbaiki; kalau diaktifkan, `WS_URL` + `REVERB_APP_KEY`
wajib benar dan server Reverb harus berjalan.

## 8. Checklist verifikasi hardware

1. LCD menyala, baris 1 `Kasir Cashless`, lalu `Siap Transaksi`.
2. Panel admin → Devices: console berstatus **online** (heartbeat ≤30 dtk).
3. Kasir membuat tagihan → LCD berubah **Silakan Tap Kartu** ≤2 dtk.
4. Tap kartu terdaftar & saldo cukup → LCD **Transaksi Sukses** + saldo,
   buzzer sukses, POS menampilkan pembeli & saldo.
5. Stok produk berkurang di panel admin.
6. Tap kartu tidak dikenal → LCD **Kartu tdk ada**, POS menampilkan
   "Kartu tidak terdaftar".
7. Batalkan tagihan dari POS → LCD kembali ke **Siap Transaksi** ≤2 dtk.
