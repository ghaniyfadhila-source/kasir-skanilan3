#ifndef DISPLAY_H
#define DISPLAY_H

#include <Arduino.h>
#include <LiquidCrystal_I2C.h>
#include "config.h"

class DisplayManager {
public:
    DisplayManager(LiquidCrystal_I2C& lcd);
    
    void begin();
    void showIdle();
    void showWaitTap();
    void showProcessing();
    void showSuccess(float balanceAfter = 0);
    void showFailed(const char* reason);
    void showNetError();
    void showTimeout();
    void showCustom(const char* line1, const char* line2 = "");
    void showCustom(const String& line1, const String& line2 = "");
    void clear();
    
private:
    LiquidCrystal_I2C& lcd;
    unsigned long lastScroll = 0;
    int scrollPos = 0;
    String currentLine2 = "";
    
    void writeCentered(int row, const char* text);
    void scrollText(int row, const String& text);
};

#endif