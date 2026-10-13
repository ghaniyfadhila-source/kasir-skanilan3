# 02: Top-up & block actions

**What to build:** Row actions for top-up saldo and block/unblock card.

**Blocked by:** rfid/01-rfid-card-resource-crud

**Status:** ready-for-agent

- [ ] Action "Top Up": modal with `amount` field (numeric, prefix Rp, step 1000, min 1000) → calls `$record->addBalance($amount)` → refresh table
- [ ] Action "Blokir": visible when `is_active=true`, confirms → `$record->update(['is_active' => false])`
- [ ] Action "Aktifkan": visible when `is_active=false` → `$record->update(['is_active' => true])`
- [ ] All actions show notification on success
- [ ] Balance updates reflected immediately in table