#include "state_machine.h"
#include "hardware_pins.h"
#include "network.h"
#include "display.h"

StateMachine::StateMachine() {}

void StateMachine::begin() {
    transitionTo(DeviceState::BOOT);
}

void StateMachine::update() {
    handleState();
    handleTimeout();
}

void StateMachine::transitionTo(DeviceState newState) {
    DeviceState oldState = currentState;
    previousState = currentState;
    currentState = newState;
    stateStartTime = millis();
    Serial.printf("[STATE] %s -> %s\n", nameOf(oldState), nameOf(newState));
}

const char* StateMachine::nameOf(DeviceState state) {
    switch (state) {
        case DeviceState::BOOT: return "BOOT";
        case DeviceState::CONNECTING_WIFI: return "CONNECTING_WIFI";
        case DeviceState::REGISTERING: return "REGISTERING";
        case DeviceState::IDLE: return "IDLE";
        case DeviceState::WAIT_TAP: return "WAIT_TAP";
        case DeviceState::PROCESSING: return "PROCESSING";
        case DeviceState::SUCCESS: return "SUCCESS";
        case DeviceState::FAILED: return "FAILED";
        case DeviceState::ERROR_NET: return "ERROR_NET";
        case DeviceState::ERROR_HARDWARE: return "ERROR_HARDWARE";
    }
    return "UNKNOWN";
}

const char* StateMachine::getStateName() const {
    return nameOf(currentState);
}

void StateMachine::handleState() {
    switch (currentState) {
        case DeviceState::BOOT: handleBoot(); break;
        case DeviceState::CONNECTING_WIFI: handleConnectingWifi(); break;
        case DeviceState::REGISTERING: handleRegistering(); break;
        case DeviceState::IDLE: handleIdle(); break;
        case DeviceState::WAIT_TAP: handleWaitTap(); break;
        case DeviceState::PROCESSING: handleProcessing(); break;
        case DeviceState::SUCCESS: handleSuccess(); break;
        case DeviceState::FAILED: handleFailed(); break;
        case DeviceState::ERROR_NET: handleErrorNet(); break;
        case DeviceState::ERROR_HARDWARE: handleErrorHardware(); break;
    }
}

void StateMachine::handleTimeout() {
    unsigned long elapsed = millis() - stateStartTime;

    switch (currentState) {
        case DeviceState::WAIT_TAP: {
            // The server tells us how many seconds the bill is still valid, so
            // the deadline is relative to millis() instead of an absolute clock
            // the ESP8266 does not have.
            unsigned long deadline = pendingTransaction.deadlineMs > 0
                ? pendingTransaction.deadlineMs
                : stateStartTime + TRANSACTION_TIMEOUT_MS;
            if (millis() >= deadline) {
                onApiResponse(false, "Waktu habis", "TIMEOUT");
            }
            break;
        }
        case DeviceState::CONNECTING_WIFI:
            if (elapsed > 30000) {
                onNetworkError();
            }
            break;
        case DeviceState::REGISTERING:
            if (elapsed > 10000) {
                onNetworkError();
            }
            break;
        case DeviceState::PROCESSING:
            if (elapsed > 15000) {
                onNetworkError();
            }
            break;
        case DeviceState::SUCCESS:
        case DeviceState::FAILED:
            if (elapsed >= 2000) {
                transitionTo(DeviceState::IDLE);
            }
            break;
        case DeviceState::ERROR_NET:
            if (elapsed >= 5000) {
                transitionTo(DeviceState::BOOT);
            }
            break;
        default:
            break;
    }
}

void StateMachine::handleBoot() {
    display.showCustom("Booting...", "v" + String(fwVersion));
    transitionTo(DeviceState::CONNECTING_WIFI);
}

void StateMachine::handleConnectingWifi() {
    // NetworkManager handles WiFi connection in background
    if (net.isWiFiConnected()) {
        transitionTo(DeviceState::REGISTERING);
    }
}

void StateMachine::handleRegistering() {
    display.showCustom("Register Device", "Please wait...");

    if (net.registerDevice(deviceId, fwVersion)) {
        transitionTo(DeviceState::IDLE);
    }
    // retry handled by timeout
}

void StateMachine::handleIdle() {
    display.showIdle();
    hw.setLED(true, false); // Green solid

    pollPendingTransaction();
}

void StateMachine::handleWaitTap() {
    display.showWaitTap();
    hw.setLED(false, true); // Red blink (handled in loop)

    // Polling in WAIT_TAP is what detects a cancelled bill: the cashier can
    // cancel at any time and the WebSocket path is disabled.
    pollPendingTransaction();

    String uid;
    if (hw.readRFID(uid)) {
        onTapDetected(uid);
    }
}

void StateMachine::handleProcessing() {
    display.showProcessing();
    hw.setLED(false, false); // Off
    // Wait for API response via callback
}

void StateMachine::handleSuccess() {
    display.showSuccess(pendingTransaction.balanceAfter);
    hw.beepSuccess();
    hw.setLED(true, false);
}

void StateMachine::handleFailed() {
    display.showFailed(lcdMessage(lastErrorCode));
    hw.beepError();
    hw.setLED(false, true);
}

void StateMachine::handleErrorNet() {
    display.showNetError();
    hw.beepWarning();
    hw.setLED(false, true);
}

void StateMachine::handleErrorHardware() {
    display.showCustom("Hardware Error", "Restart Required");
    hw.beepError();
}

void StateMachine::onTransactionReceived(const TransactionData& tx) {
    pendingTransaction = tx;
    pendingTransaction.hasPending = true;
    lastError = "";
    lastErrorCode = "";
    transitionTo(DeviceState::WAIT_TAP);
}

void StateMachine::onTapDetected(const String& uid) {
    if (!pendingTransaction.hasPending) return;

    transitionTo(DeviceState::PROCESSING);
    net.sendTap(pendingTransaction.id, uid,
        [this](bool success, const String& code, const String& msg, float balBefore, float balAfter) {
            this->onApiResponse(success, msg, code, balBefore, balAfter);
        });
}

void StateMachine::onApiResponse(bool success, const String& message, const String& code,
                                 float balanceBefore, float balanceAfter) {
    if (success) {
        pendingTransaction.balanceBefore = balanceBefore;
        pendingTransaction.balanceAfter = balanceAfter;
        pendingTransaction.hasPending = false;
        transitionTo(DeviceState::SUCCESS);
    } else {
        lastError = message;
        lastErrorCode = code;
        pendingTransaction.hasPending = false;
        transitionTo(DeviceState::FAILED);
    }
}

void StateMachine::onNetworkError() {
    transitionTo(DeviceState::ERROR_NET);
}

void StateMachine::onHardwareError() {
    transitionTo(DeviceState::ERROR_HARDWARE);
}

/*
 * Ask the API whether this console still has a pending bill. Called from IDLE
 * (new bill) and WAIT_TAP (cancelled bill / refreshed time budget).
 */
void StateMachine::pollPendingTransaction() {
    static unsigned long lastPoll = 0;
    if (millis() - lastPoll < 2000) return;
    lastPoll = millis();

    String txId, items;
    float total = 0;
    unsigned long remainingSeconds = 0;

    if (net.checkPendingTransaction(txId, total, items, remainingSeconds)) {
        unsigned long remainingMs = (remainingSeconds > 0 ? remainingSeconds : 30) * 1000UL;

        if (currentState == DeviceState::WAIT_TAP && txId == pendingTransaction.id) {
            pendingTransaction.deadlineMs = millis() + remainingMs;
            return;
        }

        TransactionData tx;
        tx.id = txId;
        tx.total = total;
        tx.items = items;
        tx.deadlineMs = millis() + remainingMs;
        tx.hasPending = true;
        onTransactionReceived(tx);
        return;
    }

    // A 200 without has_pending means the bill was completed elsewhere or the
    // cashier cancelled it. Transport errors are ignored so a dropped packet
    // cannot cancel a bill the buyer is still paying.
    if (currentState == DeviceState::WAIT_TAP && net.getLastPendingStatus() == 200) {
        onApiResponse(false, "Transaksi dibatalkan", "CANCELLED");
    }
}

// LCD is 16x2: map API codes to short Indonesian labels that fit.
const char* StateMachine::lcdMessage(const String& code) const {
    if (code == "INSUFFICIENT_BALANCE") return MSG_LOW_BALANCE;
    if (code == "CARD_NOT_FOUND") return "Kartu tdk ada";
    if (code == "CARD_INACTIVE") return "Kartu diblokir";
    if (code == "EXPIRED" || code == "TIMEOUT") return MSG_TIMEOUT;
    if (code == "CANCELLED") return "Dibatalkan";
    return MSG_FAILED;
}
