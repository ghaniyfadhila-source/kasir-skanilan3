# 01: KasirPos Livewire component: cart & checkout

**What to build:** Kasir POS page with product grid, cart, total, checkout button.

**Blocked by:** product/01-product-resource-scoped, transaction/02-pending-transaction-polling

**Status:** ready-for-agent

- [ ] Livewire component `KasirPos` at route `/kasir/pos`
- [ ] Loads products: `auth()->user()->products()->where('is_active', true)->where('stock', '>', 0)`
- [ ] Product grid: click to add to cart, shows name, category, price (Rp), stock
- [ ] Cart state: array of `{product_id, name, price, qty, subtotal}`
- [ ] Cart UI: qty +/- buttons, remove, shows subtotal per line
- [ ] Total recalculated on every change
- [ ] "Bayar" button: POST `/api/transactions` with `device_id` (from user's device) and `items`
- [ ] On success: stores `transaction_id`, sets status `processing`, starts polling
- [ ] Error handling: toast notification on API error