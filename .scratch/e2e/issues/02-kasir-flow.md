# 02: Cypress: kasir flow (POS checkout → device tap → success)

**What to build:** End-to-end test of complete transaction flow from kasir POS to device tap.

**Blocked by:** kasir-ui/03-timeout-cancel-handling, firmware/04-state-machine-integration, hardware/01-pin-mapping-test

**Status:** ready-for-agent

- [ ] Test spec `kasir-flow.cy.js`:
  - Login as kasir (seeded credentials)
  - Navigate to POS page (`/kasir/pos`)
  - Add 2-3 products to cart, verify total
  - Click "Bayar" → verify status "Menunggu pembeli tap kartu..."
  - **Mock device tap**: intercept `/api/device/{id}/tap` or use test RFID card via test backend
  - Verify status transitions: processing → success
  - Verify success UI: card name, UID, saldo sebelum/sesudah, total
  - Verify auto-reset after 3s (or manual "Transaksi Baru")
  - Verify transaction appears in admin transaction feed
  - Verify RFID card balance deducted correctly in database
- [ ] Run: `npx cypress run --spec "cypress/e2e/kasir-flow.cy.js"`
- [ ] Requires: backend running, Reverb running, firmware on device (or mocked)