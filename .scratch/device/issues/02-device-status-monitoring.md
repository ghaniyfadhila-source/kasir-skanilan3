# 02: Device status monitoring & reset token

**What to build:** Device table shows online/offline status, admin can reset API token.

**Blocked by:** device/01-device-resource-crud

**Status:** ready-for-agent

- [ ] Table column `is_online` (icon): green if `last_seen_at` < 30s, red otherwise
- [ ] Badge column `status` with colors: success=active, warning=maintenance, danger=inactive
- [ ] Filter: status select, online/offline filter
- [ ] Action "Reset Token": confirms, regenerates `api_token`, shows new token in notification
- [ ] Action "View Transactions": links to TransactionResource filtered by device
- [ ] Column `last_seen_at` formatted `d/m/Y H:i`