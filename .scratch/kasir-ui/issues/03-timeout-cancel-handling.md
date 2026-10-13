# 03: Timeout & cancel handling

**What to build:** 30-second timeout auto-cancel, manual cancel button.

**Blocked by:** kasir-ui/02-polling-ui-feedback

**Status:** ready-for-agent

- [ ] Transaction created with `expires_at = now()->addSeconds(30)`
- [ ] Backend: scheduled command or model event checks expired pending transactions → marks `cancelled`
- [ ] Broadcasts `TransactionCancelled` event on timeout
- [ ] Device receives cancel event → LCD "Waktu Habis" → back to idle
- [ ] Kasir UI: "Batalkan" button visible during pending/processing → POST `/api/transactions/{id}/cancel`
- [ ] Cancel policy: only kasir who created or admin can cancel
- [ ] On cancel: broadcast cancel, UI shows "Transaksi dibatalkan", reset to idle