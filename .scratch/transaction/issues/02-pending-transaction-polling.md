# 02: Pending transaction check (polling fallback)

**What to build:** Device can poll for pending transaction assigned to it.

**Blocked by:** transaction/01-device-register-auth

**Status:** ready-for-agent

- [ ] Route `GET /api/device/{device:device_id}/pending-transaction` (device guard)
- [ ] Returns latest transaction with `status=pending` AND (`expires_at` null OR > now)
- [ ] Response: `{success: true, has_pending: true, transaction: {id, total, items, expires_at}}`
- [ ] If none: `{success: true, has_pending: false}`
- [ ] Device polls every 5s (firmware side)
- [ ] Policy: device can only access its own pending transactions