# Product
<!-- impeccable:product-schema 1 -->

## Platform
web

## Users
- **Kasir** (4 people, one per canteen counter): works standing at a canteen counter, often under bright daylight or fluorescent light, with a queue waiting. Needs to add items, take payment, and start the next buyer in seconds.
- **Admin** (one school operator): manages kasir accounts, products, RFID cards, console assignment, and transaction history from a desk.
- **Pembeli** (student/teacher/staff): has an RFID card with a stored balance; taps the physical console, never logs in.

## Product Purpose
Kasir Cashless is a school canteen point-of-sale for cashless RFID payments. It exists so a canteen can serve a queue without cash handling, while the school keeps one auditable record of balances and transactions. Success means a cashier can complete a sale without leaving the POS, and a buyer can pay with one tap even when the console was briefly offline.

## Positioning
One Laravel + Filament deployment serves every canteen counter. Each kasir owns exactly one ESP8266 console and only sees their own products, transactions, and console status; an admin sees everything. A pending bill is created before the tap and waits for its console, so a sale started while the console is offline is still payable when it reconnects.

## Operating Context
- Physical console: ESP8266 + RC522 RFID reader + 16x2 I2C LCD + buzzer, mounted at the counter.
- Console talks to the same Laravel app over LAN HTTP (`/api/device/*`), polls for pending bills, and sends a heartbeat every 30 seconds.
- Kasir uses the POS at `/admin/pos` inside the Filament panel; the old `/kasir/pos` URL redirects there.
- Admin works in the same panel at `/admin`, with device, product, RFID card, user, and transaction resources plus dashboard widgets.
- Local development uses SQLite and `php artisan serve`; CI uses PostgreSQL.
- Transactions are created with `expires_at = null`; the 30-second tap window starts when the console first collects the bill.

## Capabilities and Constraints
- Roles: `admin` and `kasir` via spatie/laravel-permission. One console per kasir (`devices.kasir_id` unique).
- POS: product grid, cart, quantity controls, checkout, cancel own pending transaction, live status via polling, success/failure receipt with balance before/after.
- Device registration is public (`POST /api/device/register`) and returns the device token; admin assigns the console to a kasir.
- Transaction history is read-only for kasir; admin can delete history and reset device tokens.
- Firmware only supports 2.4 GHz WiFi. `firmware/include/config.h` holds `WIFI_SSID`, `WIFI_PASSWORD`, and `API_BASE_URL`.
- Existing Cypress hooks must keep working: `.product-grid`, `.product-card`, `.cart-item`, `.cart-qty-inc`, `.cart-qty-dec`, `.qty`, `.cart-total`, `.reset-transaction`, `button[wire\:click="checkout"]`.
- Stack: Laravel, Livewire 3, Filament 3, Tailwind (v4 for hand-written pages, v3 inside Filament), Vite, Cypress.

## Brand Commitments
- Product name: **Kasir Cashless**.
- The user has committed the next visual world to **neobrutalism, "Schoolyard Sticker" register**: primary red/blue/yellow on a light ground, thick black borders, hard offset shadows, sticker-like badges.
- Typography committed: **Archivo Black** for display, **Space Grotesk** for UI, **Space Mono** for numbers/prices.
- Light **and** dark mode are both required.
- Bahasa Indonesia is the product language.

## Evidence on Hand
- Working Laravel app in `backend/`, seeded demo accounts (`admin@skanilan.local`, `kasir@skanilan.local`), products, RFID cards, and a console device row.
- Cypress specs in `cypress/e2e/` prove the admin, kasir, and console flows.
- No real logo, photography, or illustration assets exist. Any decorative asset must be authored and labelled synthetic.

## Product Principles
1. **Queue speed beats decoration.** Every POS action stays reachable in one tap or one keypress.
2. **Status is never ambiguous.** Online/offline, pending, success, and failure are readable at a glance and never rely on color alone.
3. **The console is a first-class actor.** The UI explains what the hardware is doing and never pretends a bill is paid before the tap.
4. **One panel, scoped by role.** Kasir sees only their counter; admin sees the school.
5. **Failure is a normal state.** Offline consoles, expired bills, and rejected taps get plain-language recovery paths.

## Accessibility & Inclusion
- WCAG AA contrast for text and controls in both light and dark mode.
- Keyboard focus must stay visible; the POS must remain usable with a keyboard and a touchscreen.
- Touch targets at least 44x44 px on the POS.
- Status must be conveyed by text and icon as well as color.
