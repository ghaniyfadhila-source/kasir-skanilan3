#include <Arduino.h>
#include <MFRC522.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>
#include <WebSocketsClient.h>
#include <WiFiManager.h>

#include "config.h"
#include "state_machine.h"
#include "hardware_pins.h"
#include "network.h"
#include "display.h"

HardwarePins hw;
NetworkManager net;
DisplayManager display(hw.lcd);
StateMachine stateMachine;

String deviceId;
String fwVersion = "1.0.0";

void onWSMessage(const String& event, const JsonDocument& data) {
    if (event == "transaction.created") {
        TransactionData tx;
        tx.id = data["id"].as<String>();
        tx.total = data["total"].as<float>();
        tx.items = data["items"].as<String>();
        tx.expiresAt = data["expires_at"].as<unsigned long>();
        tx.hasPending = true;
        stateMachine.onTransactionReceived(tx);
    } else if (event == "transaction.cancelled") {
        stateMachine.onApiResponse(false, "Transaksi dibatalkan");
    }
}

void setup() {
    Serial.begin(115200);
    delay(100);
    
    // Generate device ID from MAC
    uint8_t mac[6];
    WiFi.macAddress(mac);
    char macStr[18];
    sprintf(macStr, "%02X%02X%02X%02X%02X%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
    deviceId = String(DEVICE_ID) + macStr;
    
    hw.init();
    net.setWSMessageCallback(onWSMessage);
    net.begin();
    display.begin();
    stateMachine.begin();
    
    hw.beepStartup();
    display.showCustom("Kasir Cashless", "v" + fwVersion);
    delay(2000);
}

void loop() {
    net.update();
    stateMachine.update();
    hw.update();  // if needed for non-blocking reads
    delay(10);
}