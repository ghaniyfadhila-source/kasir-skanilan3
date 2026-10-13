# 02: Polling status & UI feedback

**What to build:** Real-time status updates via polling, visual feedback for each state.

**Blocked by:** kasir-ui/01-kasir-pos-cart-checkout, transaction/04-websocket-channels-broadcasting

**Status:** ready-for-agent

- [ ] JS polling `/api/transactions/{id}/status` every 2s while status=pending/processing
- [ ] Status states UI:
  - `pending`: "Menunggu pembeli tap kartu..." + spinner
  - `processing`: "Memproses pembayaran..." + spinner
  - `success`: green check, shows "Transaksi berhasil!", card info (name, UID), saldo sebelum/sesudah, total
  - `failed`: red X, shows error message, "Transaksi Baru" button
  - `cancelled`: warning, "Transaksi dibatalkan (timeout)"
- [ ] Auto-reset to idle after 3s on success (page reload or state reset)
- [ ] Uses Livewire `dispatch` for JS ↔ PHP communication
- [ ] Toast notifications for success/error