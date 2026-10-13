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
    previousState = currentState;
    currentState = newState;
    stateStartTime = millis();
    Serial.printf("[STATE] %s -> %s\n", getStateName(), getStateName());
}

const char* StateMachine::getStateName() const {
    switch (currentState) {
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
        case DeviceState::WAIT_TAP:
            if (elapsed >= TRANSACTION_TIMEOUT_MS || (pendingTransaction.hasPending && elapsed >= pendingTransaction.expiresAt)) {
                onApiResponse(false, "Waktu habis");
            }
            break;
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
    } else {
        // retry handled by timeout
    }
}

void StateMachine::handleIdle() {
    display.showIdle();
    hw.setLED(true, false); // Green solid
    
    // Check for pending transaction (polling fallback)
    static unsigned long lastPoll = 0;
    if (millis() - lastPoll > 5000) {
        lastPoll = millis();
        String txId, items;
        float total;
        unsigned long expiresAt;
        if (net.checkPendingTransaction(txId, total, items, expiresAt)) {
            TransactionData tx;
            tx.id = txId;
            tx.total = total;
            tx.items = items;
            tx.expiresAt = expiresAt;
            tx.hasPending = true;
            onTransactionReceived(tx);
        }
    }
}

void StateMachine::handleWaitTap() {
    display.showWaitTap();
    hw.setLED(false, true); // Red blink (handled in loop)
    
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
    display.showFailed("Gagal");
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
    transitionTo(DeviceState::WAIT_TAP);
}

void StateMachine::onTapDetected(const String& uid) {
    if (!pendingTransaction.hasPending) return;
    
    transitionTo(DeviceState::PROCESSING);
    net.sendTap(pendingTransaction.id, uid, 
        [this](bool success, const String& msg, float balBefore, float balAfter) {
            this->onApiResponse(success, msg, balBefore, balAfter);
        });
}

void StateMachine::onApiResponse(bool success, const String& message, float balanceBefore, float balanceAfter) {
    if (success) {
        pendingTransaction.balanceBefore = balanceBefore;
        pendingTransaction.balanceAfter = balanceAfter;
        transitionTo(DeviceState::SUCCESS);
    } else {
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