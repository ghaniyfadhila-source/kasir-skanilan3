#ifndef NETWORK_H
#define NETWORK_H

#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <WiFiClientSecure.h>
#include <ArduinoHttpClient.h>
#include <WebSocketsClient.h>
#include <ArduinoJson.h>

#include "config.h"

// Forward declaration for deviceId from main.cpp
extern String deviceId;

class NetworkManager {
public:
    NetworkManager();
    
    void begin();
    void update();
    
    bool isWiFiConnected() const;
    bool isWSConnected();
    
    bool registerDevice(const String& deviceId, const String& fwVersion);
    bool checkPendingTransaction(String& txId, float& total, String& items, unsigned long& expiresAt);
    bool sendTap(const String& txId, const String& uid, std::function<void(bool, String, float, float)> callback);
    void sendHeartbeat(int rssi, int freeHeap, int uptime);
    
    void setWSMessageCallback(void (*callback)(const String& event, const JsonDocument& data));
    
    String getDeviceToken() const { return deviceToken; }
    void setDeviceToken(const String& token) { deviceToken = token; }
    
    String getLocalIP() const { return WiFi.localIP().toString(); }
    int getRSSI() const { return WiFi.RSSI(); }
    
private:
    WiFiClientSecure wifiClient;
    HttpClient* httpClient = nullptr;
    WebSocketsClient webSocket;
    
    String deviceToken;
    String apiBaseUrl;
    String wsUrl;
    
    unsigned long lastHeartbeat = 0;
    unsigned long lastWiFiRetry = 0;
    unsigned long lastWSReconnect = 0;
    
    void (*wsMessageCallback)(const String&, const JsonDocument&) = nullptr;
    
    void connectWiFi();
    void connectWebSocket();
    void handleWebSocketEvent(WStype_t type, uint8_t* payload, size_t length);
    String buildAuthHeader() const;
    bool parseRegisterResponse(const String& response, String& token);
    bool parsePendingResponse(const String& response, String& txId, float& total, String& items, unsigned long& expiresAt);
    bool parseTapResponse(const String& response, bool& success, String& message, float& balanceBefore, float& balanceAfter);
};

#endif