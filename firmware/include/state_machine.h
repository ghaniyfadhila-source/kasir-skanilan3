#ifndef STATE_MACHINE_H
#define STATE_MACHINE_H

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
    float total;
    String items;  // JSON string
    unsigned long expiresAt;
    bool hasPending = false;
};

class StateMachine {
public:
    StateMachine();
    
    void begin();
    void update();
    
    void onTransactionReceived(const TransactionData& tx);
    void onTapDetected(const String& uid);
    void onApiResponse(bool success, const String& message, float balanceBefore = 0, float balanceAfter = 0);
    void onNetworkError();
    void onHardwareError();
    
    DeviceState getState() const { return currentState; }
    const char* getStateName() const;
    
private:
    DeviceState currentState = DeviceState::BOOT;
    DeviceState previousState = DeviceState::BOOT;
    unsigned long stateStartTime = 0;
    TransactionData pendingTransaction;
    
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
};

#endif