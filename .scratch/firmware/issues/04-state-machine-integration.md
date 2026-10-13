# 04: State machine 10-state + main loop

**What to build:** Complete state machine driving device behavior, integrated with hardware & network.

**Blocked by:** firmware/02-hardware-abstraction, firmware/03-network-manager, transaction/04-websocket-channels-broadcasting

**Status:** ready-for-agent

- [ ] `StateMachine` enum: BOOT, CONNECTING_WIFI, REGISTERING, IDLE, WAIT_TAP, PROCESSING, SUCCESS, FAILED, ERROR_NET, ERROR_HARDWARE
- [ ] `transitionTo(newState)`: logs transition, records `stateStartTime`
- [ ] `update()`: calls current state handler + `handleTimeout()`
- [ ] State handlers:
  - BOOT: show version, → CONNECTING_WIFI
  - CONNECTING_WIFI: waits for WiFi → REGISTERING
  - REGISTERING: calls registerDevice → IDLE (or ERROR_NET on fail)
  - IDLE: shows "Siap Transaksi", green LED, polls pending tx every 5s
  - WAIT_TAP: shows "Silakan Tap", yellow LED, reads RFID → on UID → PROCESSING
  - PROCESSING: shows "Memproses...", calls sendTap → waits callback
  - SUCCESS: shows "Sukses" + saldo, beepSuccess, green LED, 2s → IDLE
  - FAILED: shows error reason, beepError, red LED, 2s → IDLE
  - ERROR_NET: shows "Error Jaringan", beepWarning, 5s → BOOT
  - ERROR_HARDWARE: shows "Hardware Error", beepError, halt
- [ ] Timeouts: WAIT_TAP 30s (or tx expires_at), CONNECTING_WIFI 30s, REGISTERING 10s, PROCESSING 15s
- [ ] `main.cpp`: creates instances, `setup()` inits all, `loop()` calls `net.update()`, `stateMachine.update()`, delay 10ms
- [ ] Device ID generated from MAC: `ESP8266_` + MAC without colons
- [ ] End-to-end test: boot → register → idle → receive tx → wait tap → tap card → success → idle