# 04: WebSocket channels & broadcasting

**What to build:** Private channels for device and admin, events broadcast correctly.

**Blocked by:** transaction/03-tap-endpoint-service, setup/03-reverb-broadcasting

**Status:** ready-for-agent

- [ ] `routes/channels.php`:
  - `private-device.{deviceId}`: allows device (via device_id match) OR admin
  - `private-admin.transactions`: allows admin only
- [ ] Events implement `ShouldBroadcast`:
  - `TransactionCreated` → `private-device.{deviceId}` → event `transaction.created` with id, total, items, expires_at
  - `TransactionCompleted` → `private-device.{deviceId}` + `private-admin.transactions` → event `transaction.completed` with id, status, total, balances, card
  - `TransactionCancelled` → `private-device.{deviceId}` → event `transaction.cancelled` with id, message
- [ ] Events use `broadcastOn()` returning `PrivateChannel` instances
- [ ] `broadcastAs()` returns correct event name
- [ ] Test: create transaction → device receives `transaction.created` via WS