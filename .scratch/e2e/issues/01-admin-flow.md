# 01: Cypress: admin flow (device, product, RFID, transaction)

**What to build:** E2E test covering admin panel CRUD operations.

**Blocked by:** admin-dashboard/02-live-feed-chart

**Status:** ready-for-agent

- [ ] Cypress config: baseUrl `http://localhost:8000`, viewport 1280x720
- [ ] Test spec `admin-flow.cy.js`:
  - Login as admin (seeded credentials)
  - Navigate to Devices: create device, verify in table, edit name, reset token, delete
  - Navigate to Products: create product (kasir scoped), verify price/stock/category, edit, delete
  - Navigate to RFID Cards: create card with UID, top-up 10000, block, unblock, delete
  - Navigate to Transactions: verify feed shows transactions, filter by device/status/date
  - Navigate to Dashboard: verify device status grid, live feed updates, chart renders
- [ ] Run: `npx cypress run --spec "cypress/e2e/admin-flow.cy.js"`
- [ ] All assertions pass