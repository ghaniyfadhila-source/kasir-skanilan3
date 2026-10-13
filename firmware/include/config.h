#ifndef CONFIG_H
#define CONFIG_H

// ===== WiFi & Server Config =====
#define WIFI_SSID "YOUR_WIFI_SSID"
#define WIFI_PASSWORD "YOUR_WIFI_PASSWORD"
#define API_BASE_URL "https://your-domain.com/api"
#define WS_URL "wss://your-domain.com/ws"
#define DEVICE_ID "ESP8266_"  // Will append MAC address

// ===== Pin Mapping (NodeMCU v3) =====
// RFID RC522
#define PIN_RFID_SS    D8  // GPIO15
#define PIN_RFID_RST   D3  // GPIO0 (pull-up 10k needed for boot)
#define PIN_RFID_SCK   D5  // GPIO14
#define PIN_RFID_MOSI  D7  // GPIO13
#define PIN_RFID_MISO  D6  // GPIO12

// LCD I2C (Qapass 16x2 / 20x4)
#define LCD_I2C_ADDR   0x27  // or 0x3F
#define LCD_COLS       16
#define LCD_ROWS       2
#define PIN_LCD_SDA    D2  // GPIO4
#define PIN_LCD_SCL    D1  // GPIO5

// Buzzer
#define PIN_BUZZER     D0  // GPIO16

// LED (optional - can use built-in LED on D4/GPIO2)
// #define PIN_LED_GREEN  D3  // GPIO0 (conflict with RFID RST)
// #define PIN_LED_RED    D4  // GPIO2 (built-in LED, active LOW)

// ===== Timing =====
#define TRANSACTION_TIMEOUT_MS  30000  // 30 seconds
#define HEARTBEAT_INTERVAL_MS   30000  // 30 seconds
#define RFID_READ_INTERVAL_MS   100    // 100ms
#define WIFI_RETRY_INTERVAL_MS  5000   // 5 seconds
#define WS_RECONNECT_INTERVAL_MS 10000 // 10 seconds

// ===== Display Messages =====
#define MSG_IDLE          "Siap Transaksi"
#define MSG_WAIT_TAP      "Silakan Tap Kartu"
#define MSG_PROCESSING    "Memproses..."
#define MSG_SUCCESS       "Transaksi Sukses"
#define MSG_FAILED        "Transaksi Gagal"
#define MSG_NET_ERROR     "Error Jaringan"
#define MSG_LOW_BALANCE   "Saldo Kurang"
#define MSG_CARD_NOT_REG  "Kartu Tdk Terdaftar"
#define MSG_TIMEOUT       "Waktu Habis"

// ===== Buzzer Patterns =====
#define BEEP_SUCCESS      100, 100, 1    // freq, duration, count
#define BEEP_ERROR        200, 200, 3
#define BEEP_WARNING      150, 150, 2
#define BEEP_STARTUP      500, 100, 2

#endif