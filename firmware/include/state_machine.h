#ifndef STATE_MACHINE_H
#define STATE_MACHINE_H

#include <Arduino.h>

enum class DeviceState {
    BOOT,
    CONNECTING_WIFI,
    REGISTERING,
    IDLE,
    WAIT_TAP,
    PROCESSING,
    SUCCESS,
    FAILED,
    ERROR_NET,
    ERROR_HARDWARE
};

struct TransactionData {
    String id;
    float total = 0;
    String items;
    // Absolute millis() deadline, derived from the server's remaining_seconds:
    // the console has no RTC/NTP, so it cannot use the ISO expiry timestamp.
    unsigned long deadlineMs = 0;
    bool hasPending = false;
    float balanceBefore = 0;
    float balanceAfter = 0;
};

// Forward declarations
class HardwarePins;
class NetworkManager;
class DisplayManager;

extern HardwarePins hw;
extern NetworkManager net;
extern DisplayManager display;
extern String deviceId;
extern String fwVersion;

class StateMachine {
public:
    StateMachine();

    void begin();
    void update();

    void onTransactionReceived(const TransactionData& tx);
    void onTapDetected(const String& uid);
    void onApiResponse(bool success, const String& message, const String& code = "",
                       float balanceBefore = 0, float balanceAfter = 0);
    void onNetworkError();
    void onHardwareError();

    DeviceState getState() const { return currentState; }
    const char* getStateName() const;

private:
    DeviceState currentState = DeviceState::BOOT;
    DeviceState previousState = DeviceState::BOOT;
    unsigned long stateStartTime = 0;
    TransactionData pendingTransaction;
    String lastError = "";
    String lastErrorCode = "";

    void transitionTo(DeviceState newState);
    void handleState();
    void handleTimeout();

    // State handlers
    void handleBoot();
    void handleConnectingWifi();
    void handleRegistering();
    void handleIdle();
    void handleWaitTap();
    void handleProcessing();
    void handleSuccess();
    void handleFailed();
    void handleErrorNet();
    void handleErrorHardware();

    void pollPendingTransaction();
    const char* lcdMessage(const String& code) const;
    static const char* nameOf(DeviceState state);
};

#endif
