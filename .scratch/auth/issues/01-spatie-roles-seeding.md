# 01: Spatie roles & permissions seeding

**What to build:** Database seeder creates `admin` and `kasir` roles with correct permissions.

**Blocked by:** database/02-model-relationships

**Status:** ready-for-agent

- [ ] `DatabaseSeeder` creates permissions: `manage devices`, `manage products`, `manage rfid cards`, `view transactions`, `view reports`, `manage users`
- [ ] Role `admin` gets all permissions
- [ ] Role `kasir` gets: `manage products`, `view transactions`
- [ ] `php artisan db:seed` runs without error
- [ ] Verify in DB: `roles` table has 2 rows, `permissions` has 6, `role_has_permissions` correct mapping
- [ ] Test: `User::find(1)->assignRole('admin')` → `hasRole('admin')` returns true