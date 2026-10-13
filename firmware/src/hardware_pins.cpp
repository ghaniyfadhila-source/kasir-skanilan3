#include "hardware_pins.h"

HardwarePins::HardwarePins()
    : rfid(PIN_RFID_SS, PIN_RFID_RST),
      lcd(LCD_I2C_ADDR, LCD_COLS, LCD_ROWS) {
    for (byte i = 0; i < 6; i++) defaultKey.keyByte[i] = 0xFF;
}

void HardwarePins::init() {
    initRFID();
    initLCD();
    initBuzzer();
    initLEDs();
}

void HardwarePins::initRFID() {
    SPI.begin(PIN_RFID_SCK, PIN_RFID_MISO, PIN_RFID_MOSI, PIN_RFID_SS);
    rfid.PCD_Init();
    rfid.PCD_SetAntennaGain(rfid.RxGain_max);
    Serial.println(F("RFID initialized"));
}

void HardwarePins::initLCD() {
    Wire.begin(PIN_LCD_SDA, PIN_LCD_SCL);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    Serial.println(F("LCD initialized"));
}

void HardwarePins::initBuzzer() {
    pinMode(PIN_BUZZER, OUTPUT);
    digitalWrite(PIN_BUZZER, LOW);
}

void HardwarePins::initLEDs() {
    // Optional: if using external LEDs on different pins
    // pinMode(PIN_LED_GREEN, OUTPUT);
    // pinMode(PIN_LED_RED, OUTPUT);
    // Use built-in LED (D4/GPIO2) as status indicator
    pinMode(LED_BUILTIN, OUTPUT);
    digitalWrite(LED_BUILTIN, HIGH); // Active LOW
}

bool HardwarePins::readRFID(String& uid) {
    if (!rfid.PICC_IsNewCardPresent()) return false;
    if (!rfid.PICC_ReadCardSerial()) return false;

    uid = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10) uid += "0";
        uid += String(rfid.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return true;
}

void HardwarePins::showMessage(const char* line1, const char* line2) {
    lcd.clear();
    writeLCD(0, 0, line1);
    if (line2 && strlen(line2) > 0) writeLCD(0, 1, line2);
}

void HardwarePins::showMessage(const String& line1, const String& line2) {
    showMessage(line1.c_str(), line2.c_str());
}

void HardwarePins::clearDisplay() {
    lcd.clear();
}

void HardwarePins::writeLCD(int col, int row, const char* text) {
    lcd.setCursor(col, row);
    lcd.print(text);
}

void HardwarePins::beepSuccess() {
    tone(PIN_BUZZER, 1000, 100);
    delay(150);
}

void HardwarePins::beepError() {
    for (int i = 0; i < 3; i++) {
        tone(PIN_BUZZER, 500, 200);
        delay(250);
    }
}

void HardwarePins::beepWarning() {
    for (int i = 0; i < 2; i++) {
        tone(PIN_BUZZER, 1500, 150);
        delay(200);
    }
}

void HardwarePins::beepStartup() {
    tone(PIN_BUZZER, 2000, 100);
    delay(150);
    tone(PIN_BUZZER, 2500, 100);
}

void HardwarePins::setLED(bool green, bool red) {
    // Using built-in LED (active LOW) for status
    // Green = ON, Red = BLINK
    if (green && !red) {
        digitalWrite(LED_BUILTIN, LOW);  // Solid ON
    } else if (!green && red) {
        digitalWrite(LED_BUILTIN, HIGH); // OFF (will blink in state machine)
    } else {
        digitalWrite(LED_BUILTIN, HIGH); // OFF
    }
}