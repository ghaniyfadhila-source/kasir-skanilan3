# 01: Run migrations & verify schema

**What to build:** All 5 tables created in Supabase with correct FKs, indexes, constraints.

**Blocked by:** setup/02-env-database-connection

**Status:** ready-for-agent

- [ ] `php artisan migrate` runs all 5 migrations successfully
- [ ] Verify tables exist: `users`, `devices`, `products`, `rfid_cards`, `transactions`
- [ ] Check FKs: `users.device_id` → `devices.id`, `devices.kasir_id` → `users.id`, `products.kasir_id` → `users.id`, `rfid_cards.registered_by` → `users.id`, `transactions.device_id` → `devices.id`, `transactions.rfid_card_id` → `rfid_cards.id`
- [ ] Check indexes: `devices.device_id` unique, `rfid_cards.uid` unique, `users.email` unique
- [ ] Check enum constraints: `devices.status`, `transactions.status`, `rfid_cards.is_active`, `products.is_active`
- [ ] `php artisan migrate:status` shows all 5 as "Ran"