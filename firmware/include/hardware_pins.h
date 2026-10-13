#ifndef HARDWARE_PINS_H
#define HARDWARE_PINS_H

#include <Arduino.h>
#include <MFRC522.h>
#include <LiquidCrystal_I2C.h>

class HardwarePins {
public:
    HardwarePins();
    
    // RFID
    MFRC522 rfid;
    MFRC522::MIFARE_Key defaultKey;
    
    // LCD
    LiquidCrystal_I2C lcd;
    
    void init();
    void initRFID();
    void initLCD();
    void initBuzzer();
    void initLEDs();
    
    bool readRFID(String& uid);
    void showMessage(const char* line1, const char* line2 = "");
    void showMessage(const String& line1, const String& line2 = "");
    void clearDisplay();
    void beepSuccess();
    void beepError();
    void beepWarning();
    void beepStartup();
    void setLED(bool green, bool red);
    
private:
    void writeLCD(int col, int row, const char* text);
};

#endif