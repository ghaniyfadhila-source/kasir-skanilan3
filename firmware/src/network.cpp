#include "network.h"
#include <functional>
#include <WiFiManager.h>

NetworkManager::NetworkManager() {}

void NetworkManager::begin() {
    apiBaseUrl = String(API_BASE_URL);
    wsUrl = String(WS_URL);

    if (!parseApiUrl(apiBaseUrl)) {
        Serial.println(F("API_BASE_URL must start with http:// or https://"));
        return;
    }

    if (apiSecure) {
        wifiClient.setInsecure(); // Self-signed certs on the school VPS.
        activeClient = &wifiClient;
    } else {
        activeClient = &plainClient;
    }

    httpClient = new HttpClient(*activeClient, apiHost.c_str(), apiPort);

    connectWiFi();

#if ENABLE_WEBSOCKET
    connectWebSocket();
#endif
}

void NetworkManager::update() {
#if ENABLE_WEBSOCKET
    webSocket.loop();
#endif

    // WiFi reconnect
    if (!isWiFiConnected() && millis() - lastWiFiRetry > WIFI_RETRY_INTERVAL_MS) {
        lastWiFiRetry = millis();
        connectWiFi();
    }

#if ENABLE_WEBSOCKET
    // WebSocket reconnect
    if (isWiFiConnected() && !isWSConnected() && millis() - lastWSReconnect > WS_RECONNECT_INTERVAL_MS) {
        lastWSReconnect = millis();
        connectWebSocket();
    }
#endif

    // Heartbeat does not depend on the WebSocket: the admin panel decides
    // online/offline from last_seen_at, which only this HTTP call updates.
    if (isWiFiConnected() && millis() - lastHeartbeat > HEARTBEAT_INTERVAL_MS) {
        lastHeartbeat = millis();
        sendHeartbeat(WiFi.RSSI(), ESP.getFreeHeap(), millis() / 1000);
    }
}

bool NetworkManager::isWiFiConnected() const {
    return WiFi.status() == WL_CONNECTED;
}

bool NetworkManager::isWSConnected() {
    return webSocket.isConnected();
}

/*
 * Split http(s)://host[:port]/base/path into the pieces ArduinoHttpClient
 * needs. The old code passed the whole URL as the host and hardcoded port 443,
 * so a LAN server (http://192.168.x.x:8000/api) could never be reached.
 */
bool NetworkManager::parseApiUrl(const String& url) {
    String rest = url;
    apiPort = 80;
    apiSecure = false;

    if (rest.startsWith("https://")) {
        apiSecure = true;
        apiPort = 443;
        rest = rest.substring(8);
    } else if (rest.startsWith("http://")) {
        apiSecure = false;
        apiPort = 80;
        rest = rest.substring(7);
    } else {
        return false;
    }

    int slashIndex = rest.indexOf('/');
    apiBasePath = slashIndex >= 0 ? rest.substring(slashIndex) : "";
    String host = slashIndex >= 0 ? rest.substring(0, slashIndex) : rest;

    int colonIndex = host.indexOf(':');
    if (colonIndex > 0) {
        apiPort = (uint16_t) host.substring(colonIndex + 1).toInt();
        host = host.substring(0, colonIndex);
    }

    while (apiBasePath.endsWith("/")) {
        apiBasePath.remove(apiBasePath.length() - 1);
    }

    apiHost = host;
    return apiHost.length() > 0;
}

String NetworkManager::apiPath(const String& path) const {
    return apiBasePath + path;
}

void NetworkManager::connectWiFi() {
    WiFiManager wm;
    wm.setConfigPortalTimeout(120);
    wm.setConnectTimeout(30);

    if (!wm.autoConnect("KasirCashless_Setup")) {
        Serial.println(F("WiFi Config Portal timeout"));
        return;
    }

    Serial.printf("WiFi connected: %s\n", WiFi.localIP().toString().c_str());
}

void NetworkManager::connectWebSocket() {
    if (!isWiFiConnected() || deviceToken.isEmpty()) return;

    // Parse wsUrl to extract host and port
    // wsUrl format: "wss://your-domain.com" or "ws://localhost:8080"
    String url = wsUrl;
    String host = "";
    int port = 443;
    String path = "/app/" + String(REVERB_APP_KEY) + "?protocol=7&client=js&version=8.4.0&flash=false";

    // Remove protocol
    if (url.startsWith("wss://")) {
        url = url.substring(6);
        port = 443;
    } else if (url.startsWith("ws://")) {
        url = url.substring(5);
        port = 80;
    }

    // Extract host and port
    int slashIndex = url.indexOf('/');
    if (slashIndex > 0) {
        host = url.substring(0, slashIndex);
    } else {
        host = url;
    }

    // Check for port in host
    int colonIndex = host.indexOf(':');
    if (colonIndex > 0) {
        port = host.substring(colonIndex + 1).toInt();
        host = host.substring(0, colonIndex);
    }

    // For wss, use SSL
    if (url.startsWith("wss://") || port == 443) {
        webSocket.beginSSL(host.c_str(), port, path.c_str());
    } else {
        webSocket.begin(host.c_str(), port, path.c_str());
    }

    webSocket.onEvent([this](WStype_t type, uint8_t* payload, size_t length) {
        this->handleWebSocketEvent(type, payload, length);
    });
    webSocket.setReconnectInterval(5000);
}

void NetworkManager::handleWebSocketEvent(WStype_t type, uint8_t* payload, size_t length) {
    switch (type) {
        case WStype_CONNECTED:
            Serial.println(F("WS Connected"));
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
            Serial.println(F("WS Disconnected"));
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
        default:
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
    String url = apiPath("/device/register");

    httpClient->beginRequest();
    httpClient->post(url.c_str());
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
            lastHeartbeat = 0; // Report online as soon as registration succeeds.
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

bool NetworkManager::checkPendingTransaction(String& txId, float& total, String& items, unsigned long& remainingSeconds) {
    lastPendingStatus = 0;
    if (!httpClient || deviceToken.isEmpty()) return false;

    String url = apiPath("/device/" + deviceId + "/pending-transaction");

    httpClient->beginRequest();
    httpClient->get(url.c_str());
    httpClient->sendHeader("Authorization", "Bearer " + deviceToken);
    httpClient->endRequest();

    int status = httpClient->responseStatusCode();
    String response = httpClient->responseBody();
    lastPendingStatus = status;

    if (status == 200) {
        return parsePendingResponse(response, txId, total, items, remainingSeconds);
    }
    return false;
}

bool NetworkManager::parsePendingResponse(const String& response, String& txId, float& total, String& items, unsigned long& remainingSeconds) {
    JsonDocument doc;
    DeserializationError err = deserializeJson(doc, response);
    if (err) return false;

    if (doc["success"].as<bool>() && doc["has_pending"].as<bool>()) {
        txId = doc["transaction"]["id"].as<String>();
        total = doc["transaction"]["total"].as<float>();
        remainingSeconds = (unsigned long) (doc["transaction"]["remaining_seconds"] | 30);
        items = summarizeItems(doc["transaction"]["items"].as<JsonArray>());
        return true;
    }
    return false;
}

/*
 * The API sends items as an array; the console only needs a short label for
 * the serial log / future display. Named explicitly so the cast cannot fail
 * silently on an array (ArduinoJson used to turn it into an empty string).
 */
String NetworkManager::summarizeItems(const JsonArray& items) const {
    String summary;
    for (JsonVariant item : items) {
        String name = item["name"].as<String>();
        int qty = item["qty"] | 1;
        if (summary.length() > 0) summary += ", ";
        summary += name + " x" + String(qty);
        if (summary.length() > 60) break;
    }
    return summary;
}

bool NetworkManager::sendTap(const String& txId, const String& uid,
                             std::function<void(bool, const String&, const String&, float, float)> callback) {
    (void) txId; // The API resolves the pending transaction from the device.

    if (!httpClient || deviceToken.isEmpty()) return false;

    JsonDocument doc;
    doc["uid"] = uid;

    String body;
    serializeJson(doc, body);
    String url = apiPath("/device/" + deviceId + "/tap");

    httpClient->beginRequest();
    httpClient->post(url.c_str());
    httpClient->sendHeader("Authorization", "Bearer " + deviceToken);
    httpClient->sendHeader("Content-Type", "application/json");
    httpClient->sendHeader("Content-Length", body.length());
    httpClient->beginBody();
    httpClient->print(body);
    httpClient->endRequest();

    int status = httpClient->responseStatusCode();
    String response = httpClient->responseBody();

    bool success = false;
    String code;
    String message;
    float balBefore = 0, balAfter = 0;

    // A rejected tap still carries {success:false, code, message}; reading the
    // body lets the LCD show "Saldo Kurang" instead of "HTTP 400".
    if (!parseTapResponse(response, success, code, message, balBefore, balAfter)) {
        success = false;
        code = "HTTP_ERROR";
        message = "HTTP " + String(status);
    }

    if (callback) callback(success, code, message, balBefore, balAfter);
    return success;
}

bool NetworkManager::parseTapResponse(const String& response, bool& success, String& code, String& message, float& balanceBefore, float& balanceAfter) {
    JsonDocument doc;
    DeserializationError err = deserializeJson(doc, response);
    if (err) return false;

    success = doc["success"].as<bool>();
    code = doc["code"].as<String>();
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
    String url = apiPath("/device/" + deviceId + "/heartbeat");

    httpClient->beginRequest();
    httpClient->post(url.c_str());
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
