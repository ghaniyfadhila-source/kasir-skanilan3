# 03: Tap endpoint & TransactionService

**What to build:** Device sends UID, backend validates card, deducts balance, updates transaction, broadcasts completion.

**Blocked by:** transaction/01-device-register-auth, rfid/01-rfid-card-resource-crud

**Status:** ready-for-agent

- [ ] Route `POST /api/device/{device:device_id}/tap` accepts `uid`
- [ ] `TransactionService::processTap(Transaction $tx, string $uid)`:
  - Validates transaction is pending & not expired
  - Finds `RfidCard` by UID (case-insensitive)
  - Checks card exists, is_active, hasSufficientBalance(total)
  - DB transaction: deduct balance, update transaction (rfid_card_id, status=success, balance_before, balance_after)
  - Broadcasts `TransactionCompleted` event
- [ ] Returns success response with balance_before, balance_after, card info
- [ ] Error responses: NO_PENDING_TRANSACTION, CARD_NOT_FOUND, CARD_INACTIVE, INSUFFICIENT_BALANCE, EXPIRED
- [ ] All error codes match firmware handling