# 0002 - Multi-kasir: one panel, one console, one POS per kasir

## Status

Accepted, 2026-10-29.

## Context

The deployment needs four cashiers. Each cashier has one physical console
(ESP8266 + RFID + LCD) and one POS page. A single admin manages all of them.
Users must not see or change another cashier's data, and a console must be
linked to a cashier only by an admin.

## Decisions

- **One Filament panel, scoped by role.** Admin and kasir both log in at
  `/admin`. Admin sees every resource. Kasir sees their own products and
  transactions, a revenue chart scoped to their console, the status of that
  console, and the built-in logout action. User management, devices, and RFID
  cards are admin-only.
- **`devices.kasir_id` is the single source of truth.** It is nullable and
  unique, so an unassigned console can register itself and wait for an admin,
  while a kasir can own at most one console. The legacy `users.device_id`
  column was backfilled into `devices.kasir_id` and then dropped.
- **Kasir transactions are read-only.** A kasir may cancel a pending bill from
  the POS, but editing and deleting transaction records belongs to admin. The
  admin transaction list exposes a bulk "Bersihkan riwayat" action.
- **RFID cards are global.** A card and its balance work at every console; the
  card is not owned by a kasir.
- **A bill waits for its console.** A pending transaction starts without
  `expires_at`. The 30 second tap window starts when the console first collects
  the bill from `pending-transaction`. A console that is offline therefore does
  not burn the window before it can display anything, and at checkout the POS
  simply waits ("Menunggu console online...").
- **No automatic history cleanup.** Expiry only cancels a bill whose tap window
  has already started and elapsed. `transactions:cancel-expired` runs every
  minute for that; it never deletes rows. Pending bills that no console ever
  collected stay pending until an admin deletes them.
- **Checkout is never blocked by console offline state.** The POS only reports
  the console status.

## Consequences

- Kasir dashboards and tables must filter through `transactions.device` ->
  `devices.kasir_id`; there is no second user-level device column to drift.
- The device form rejects a second device for a kasir who already has one.
- Provisioning a new kasir is: admin creates the account, the console registers
  itself, admin assigns the console, then the kasir adds products and opens
  `/kasir/pos`.