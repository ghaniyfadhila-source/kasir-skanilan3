# 03: Network manager (WiFi, HTTP, WS)

**What to build:** `NetworkManager` handles WiFi connection, HTTP API calls, WebSocket client.

**Blocked by:** firmware/01-platformio-project

**Status:** ready-for-agent

- [ ] `begin()`: starts WiFiManager captive portal (AP "KasirCashless_Setup"), initializes HTTP client, WebSocket client
- [ ] `connectWiFi()`: uses WiFiManager, blocks until connected or portal timeout
- [ ] `registerDevice(deviceId, fwVersion)`: POST `/api/device/register` → parses `api_token` → stores
- [ ] `checkPendingTransaction()`: GET `/api/device/{id}/pending-transaction` with Bearer token → returns tx data
- [ ] `sendTap(txId, uid)`: POST `/api/device/{id}/tap` with UID → callback with success, message, balances
- [ ] `sendHeartbeat(rssi, heap, uptime)`: POST `/api/device/{id}/heartbeat` every 30s
- [ ] WebSocket: connects to `/ws/device/{id}` with auth, subscribes to `private-device.{deviceId}`
- [ ] WS event handler: parses `transaction.created`, `transaction.cancelled` → calls callback
- [ ] Auto-reconnect WiFi (5s interval) and WS (10s interval)
- [ ] `update()` called in main loop: `webSocket.loop()`, reconnect logic, heartbeat timer