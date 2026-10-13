# 01: PlatformIO project + lib deps

**What to build:** PlatformIO project for NodeMCU v3 compiles with all required libraries.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `platformio.ini`: `platform = espressif8266`, `board = nodemcuv3`, `framework = arduino`
- [ ] `lib_deps`: RFID (miguelbalboa/RFID), LiquidCrystal_I2C, ArduinoJson, arduinoWebSockets, ArduinoHttpClient, WiFiManager
- [ ] `build_flags`: debug level, USB CDC on boot
- [ ] `pio run -e nodemcuv3` compiles successfully (no hardware needed for compile check)
- [ ] `.gitignore` excludes `.pio`, `build`