#include "network.h"

NetworkManager::NetworkManager() {}

void NetworkManager::begin() {
    apiBaseUrl = String(API_BASE_URL);
    wsUrl = String(WS_URL);
    
    wifiClient.setInsecure(); // For self-signed certs on VPS; remove in production
    httpClient = new HttpClient(wifiClient, apiBaseUrl.c_str(), 443);
    
    connectWiFi();
    connectWebSocket();
}

void NetworkManager::update() {
    webSocket.loop();
    
    // WiFi reconnect
    if (!isWiFiConnected() && millis() - lastWiFiRetry > WIFI_RETRY_INTERVAL_MS) {
        lastWiFiRetry = millis();
        connectWiFi();
    }
    
    // WebSocket reconnect
    if (isWiFiConnected() && !isWSConnected() && millis() - lastWSReconnect > WS_RECONNECT_INTERVAL_MS) {
        lastWSReconnect = millis();
        connectWebSocket();
    }
    
    // Heartbeat
    if (isWSConnected() && millis() - lastHeartbeat > HEARTBEAT_INTERVAL_MS) {
        lastHeartbeat = millis();
        sendHeartbeat(WiFi.RSSI(), ESP.getFreeHeap(), millis() / 1000);
    }
}

bool NetworkManager::isWiFiConnected() const {
    return WiFi.status() == WL_CONNECTED;
}

bool NetworkManager::isWSConnected() const {
    return webSocket.isConnected();
}

void NetworkManager::connectWiFi() {
    WiFiManager wm;
    wm.setConfigPortalTimeout(120);
    wm.setConnectTimeout(30);
    
    if (!wm.autoConnect("KasirCashless_Setup")) {
        Serial.println("WiFi Config Portal timeout");
        return;
    }
    
    Serial.printf("WiFi connected: %s\n", WiFi.localIP().toString().c_str());
}

void NetworkManager::connectWebSocket() {
    if (!isWiFiConnected() || deviceToken.isEmpty()) return;
    
    String wsPath = wsUrl + "/app/" + String(REVERB_APP_KEY) + "?protocol=7&client=js&version=8.4.0&flash=false";
    webSocket.begin(wsPath.c_str());
    webSocket.onEvent([this](WStype_t type, uint8_t* payload, size_t length) {
        this->handleWebSocketEvent(type, payload, length);
    });
    webSocket.setReconnectInterval(5000);
}

void NetworkManager::handleWebSocketEvent(WStype_t type, uint8_t* payload, size_t length) {
    switch (type) {
        case WStype_CONNECTED:
            Serial.println("WS Connected");
            // Subscribe to private channel
            {
                JsonDocument doc;
                doc["event"] = "pusher:subscribe";
                JsonObject data = doc["data"].to<JsonObject>();
                data["channel"] = "private-device." + deviceId; // Need device ID here
                String auth = buildAuthHeader();
                data["auth"] = auth;
                String output;
                serializeJson(doc, output);
                webSocket.sendTXT(output);
            }
            break;
            
        case WStype_DISCONNECTED:
            Serial.println("WS Disconnected");
            break;
            
        case WStype_TEXT:
            {
                JsonDocument doc;
                DeserializationError err = deserializeJson(doc, payload, length);
                if (!err && wsMessageCallback) {
                    String event = doc["event"].as<String>();
                    JsonDocument data = doc["data"];
                    wsMessageCallback(event, data);
                }
            }
            break;
    }
}

String NetworkManager::buildAuthHeader() const {
    // For private channels, need to generate auth signature
    // This is simplified - real implementation needs HMAC-SHA256 with app secret
    return "Bearer " + deviceToken;
}

bool NetworkManager::registerDevice(const String& deviceId, const String& fwVersion) {
    if (!httpClient) return false;
    
    JsonDocument doc;
    doc["device_id"] = deviceId;
    doc["firmware_version"] = fwVersion;
    
    String body;
    serializeJson(doc, body);
    
    httpClient->beginRequest();
    httpClient->post("/device/register");
    httpClient->sendHeader("Content-Type", "application/json");
    httpClient->sendHeader("Content-Length", body.length());
    httpClient->beginBody();
    httpClient->print(body);
    httpClient->endRequest();
    
    int status = httpClient->responseStatusCode();
    String response = httpClient->responseBody();
    
    if (status == 200 || status == 201) {
        String token;
        if (parseRegisterResponse(response, token)) {
            deviceToken = token;
            Serial.printf("Device registered, token: %s\n", token.c_str());
            return true;
        }
    }
    
    Serial.printf("Register failed: %d %s\n", status, response.c_str());
    return false;
}

bool NetworkManager::parseRegisterResponse(const String& response, String& token) {
    JsonDocument doc;
    DeserializationError err = deserializeJson(doc, response);
    if (err) return false;
    
    if (doc["success"].as<bool>()) {
        token = doc["device"]["api_token"].as<String>();
        return true;
    }
    return false;
}

bool NetworkManager::checkPendingTransaction(String& txId, float& total, String& items, unsigned long& expiresAt) {
    if (!httpClient || deviceToken.isEmpty()) return false;
    
    httpClient->beginRequest();
    httpClient->get("/device/" + deviceId + "/pending-transaction");
    httpClient->sendHeader("Authorization", "Bearer " + deviceToken);
    httpClient->endRequest();
    
    int status = httpClient->responseStatusCode();
    String response = httpClient->responseBody();
    
    if (status == 200) {
        return parsePendingResponse(response, txId, total, items, expiresAt);
    }
    return false;
}

bool NetworkManager::parsePendingResponse(const String& response, String& txId, float& total, String& items, unsigned long& expiresAt) {
    JsonDocument doc;
    DeserializationError err = deserializeJson(doc, response);
    if (err) return false;
    
    if (doc["success"].as<bool>() && doc["has_pending"].as<bool>()) {
        txId = doc["transaction"]["id"].as<String>();
        total = doc["transaction"]["total"].as<float>();
        items = doc["transaction"]["items"].as<String>();
        expiresAt = doc["transaction"]["expires_at"].as<unsigned long>();
        return true;
    }
    return false;
}

bool NetworkManager::sendTap(const String& txId, const String& uid, 
                            std::function<void(bool, String, float, float)> callback) {
    if (!httpClient || deviceToken.isEmpty()) return false;
    
    JsonDocument doc;
    doc["uid"] = uid;
    
    String body;
    serializeJson(doc, body);
    
    httpClient->beginRequest();
    httpClient->post("/device/" + deviceId + "/tap");
    httpClient->sendHeader("Authorization", "Bearer " + deviceToken);
    httpClient->sendHeader("Content-Type", "application/json");
    httpClient->sendHeader("Content-Length", body.length());
    httpClient->beginBody();
    httpClient->print(body);
    httpClient->endRequest();
    
    int status = httpClient->responseStatusCode();
    String response = httpClient->responseBody();
    
    bool success = false;
    String message;
    float balBefore = 0, balAfter = 0;
    
    if (status == 200) {
        parseTapResponse(response, success, message, balBefore, balAfter);
    } else {
        success = false;
        message = "HTTP " + String(status);
    }
    
    if (callback) callback(success, message, balBefore, balAfter);
    return success;
}

bool NetworkManager::parseTapResponse(const String& response, bool& success, String& message, float& balanceBefore, float& balanceAfter) {
    JsonDocument doc;
    DeserializationError err = deserializeJson(doc, response);
    if (err) return false;
    
    success = doc["success"].as<bool>();
    message = doc["message"].as<String>();
    
    if (success && doc["transaction"].is<JsonObject>()) {
        balanceBefore = doc["transaction"]["balance_before"].as<float>();
        balanceAfter = doc["transaction"]["balance_after"].as<float>();
    }
    return true;
}

void NetworkManager::sendHeartbeat(int rssi, int freeHeap, int uptime) {
    if (!httpClient || deviceToken.isEmpty()) return;
    
    JsonDocument doc;
    doc["rssi"] = rssi;
    doc["free_heap"] = freeHeap;
    doc["uptime"] = uptime;
    
    String body;
    serializeJson(doc, body);
    
    httpClient->beginRequest();
    httpClient->post("/device/" + deviceId + "/heartbeat");
    httpClient->sendHeader("Authorization", "Bearer " + deviceToken);
    httpClient->sendHeader("Content-Type", "application/json");
    httpClient->sendHeader("Content-Length", body.length());
    httpClient->beginBody();
    httpClient->print(body);
    httpClient->endRequest();
}

void NetworkManager::setWSMessageCallback(void (*callback)(const String&, const JsonDocument&)) {
    wsMessageCallback = callback;
}