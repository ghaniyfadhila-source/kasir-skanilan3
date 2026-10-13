#include "display.h"
#include "config.h"

DisplayManager::DisplayManager(LiquidCrystal_I2C& lcdRef) : lcd(lcdRef) {}

void DisplayManager::begin() {
    lcd.clear();
    lcd.backlight();
}

void DisplayManager::showIdle() {
    showCustom(MSG_IDLE, "");
}

void DisplayManager::showWaitTap() {
    showCustom(MSG_WAIT_TAP, "");
}

void DisplayManager::showProcessing() {
    showCustom(MSG_PROCESSING, "");
}

void DisplayManager::showSuccess(float balanceAfter) {
    char buf[17];
    sprintf(buf, "Saldo: Rp%.0f", balanceAfter);
    showCustom(MSG_SUCCESS, buf);
}

void DisplayManager::showFailed(const char* reason) {
    showCustom(MSG_FAILED, reason);
}

void DisplayManager::showNetError() {
    showCustom(MSG_NET_ERROR, "Cek Koneksi");
}

void DisplayManager::showTimeout() {
    showCustom(MSG_TIMEOUT, "Silakan Ulangi");
}

void DisplayManager::showCustom(const char* line1, const char* line2) {
    lcd.clear();
    writeCentered(0, line1);
    if (line2 && strlen(line2) > 0) {
        writeCentered(1, line2);
    }
    currentLine2 = line2 ? line2 : "";
}

void DisplayManager::showCustom(const String& line1, const String& line2) {
    showCustom(line1.c_str(), line2.c_str());
}

void DisplayManager::clear() {
    lcd.clear();
    currentLine2 = "";
}

void DisplayManager::writeCentered(int row, const char* text) {
    int len = strlen(text);
    int col = max(0, (LCD_COLS - len) / 2);
    lcd.setCursor(col, row);
    lcd.print(text);
}