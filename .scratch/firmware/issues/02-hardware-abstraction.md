# 02: Hardware abstraction (pins, RFID, LCD, Buzzer)

**What to build:** `HardwarePins` class with all hardware interactions, testable on Wokwi.

**Blocked by:** firmware/01-platformio-project, hardware/01-pin-mapping-test

**Status:** ready-for-agent

- [ ] `HardwarePins` constructor: initializes RFID (SPI pins), LCD (I2C), Buzzer (GPIO), LED (built-in)
- [ ] `init()` calls `initRFID()`, `initLCD()`, `initBuzzer()`, `initLEDs()`
- [ ] `readRFID(String& uid)`: non-blocking check for new card, reads UID as uppercase hex, halts PICC
- [ ] `showMessage(line1, line2)`: clears LCD, writes centered text on 2 rows
- [ ] `clearDisplay()`: LCD clear
- [ ] `beepSuccess()`: 1kHz 100ms, `beepError()`: 3x 500Hz 200ms, `beepWarning()`: 2x 1.5kHz 150ms, `beepStartup()`
- [ ] `setLED(green, red)`: uses built-in LED (active LOW) for status indication
- [ ] Unit test on Wokwi: RFID read, LCD display, buzzer beep all work